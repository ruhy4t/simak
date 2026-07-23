<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInitialStudentCountsCompleted;
use App\Models\AcademicYear;
use App\Models\SatuanPendidikan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InitialStudentCountsMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('satuan_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->string('npsn')->unique();
            $table->string('nama');
            $table->string('kepala_sekolah_nama')->nullable();
            $table->string('kepala_sekolah_nip')->nullable();
            $table->string('kop_surat_path')->nullable();
            $table->timestamps();
        });
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('status');
            $table->boolean('is_active')->default(false);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });
        Schema::create('satuan_pendidikan_student_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satuan_pendidikan_id');
            $table->foreignId('academic_year_id');
            $table->unsignedTinyInteger('grade');
            $table->unsignedInteger('male_count')->default(0);
            $table->unsignedInteger('female_count')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedInteger('closing_male_count')->nullable();
            $table->unsignedInteger('closing_female_count')->nullable();
            $table->timestamp('closing_confirmed_at')->nullable();
            $table->timestamps();
        });

        AcademicYear::create([
            'name' => '2025/2026',
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
            'starts_on' => '2025-07-15',
            'ends_on' => '2026-07-14',
        ]);
        AcademicYear::create([
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_ACTIVE,
            'is_active' => true,
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-07-14',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_school_is_redirected_until_initial_counts_are_confirmed(): void
    {
        [$school, $user] = $this->schoolUser();

        $response = $this->runMiddleware($user);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('sekolah.profile'), $response->headers->get('Location'));
        $this->assertStringContainsString('2026/2027', session('error'));
    }

    public function test_school_can_submit_mutations_without_active_year_final_data(): void
    {
        [$school, $user] = $this->schoolUser();
        $this->insertCounts($school, '2025/2026', withClosing: true);
        $this->insertCounts($school, '2026/2027', withClosing: false);

        $response = $this->runMiddleware($user);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    public function test_complete_profile_initial_and_final_zero_counts_allow_mutations(): void
    {
        [$school, $user] = $this->schoolUser();
        $this->insertCounts($school, '2025/2026', withClosing: true);
        $this->insertCounts($school, '2026/2027', withClosing: true);

        $response = $this->runMiddleware($user);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    public function test_incomplete_profile_blocks_submission_even_when_counts_are_complete(): void
    {
        [$school, $user] = $this->schoolUser();
        $this->insertCounts($school, '2025/2026', withClosing: true);
        $this->insertCounts($school, '2026/2027', withClosing: true);
        DB::table('satuan_pendidikan')->where('id', $school->id)->update(['kop_surat_path' => null]);

        $response = $this->runMiddleware($user);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('sekolah.profile'), $response->headers->get('Location'));
        $this->assertStringContainsString('Profil Sekolah', session('error'));
    }

    public function test_previous_year_final_data_is_required_instead_of_active_year_final_data(): void
    {
        [$school, $user] = $this->schoolUser();
        $this->insertCounts($school, '2025/2026', withClosing: false);
        $this->insertCounts($school, '2026/2027', withClosing: true);

        $response = $this->runMiddleware($user);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            route('sekolah.closing-counts', ['academic_year' => '2025/2026']),
            $response->headers->get('Location')
        );
        $this->assertStringContainsString('Data Akhir kelas VII, VIII, dan IX tahun pelajaran 2025/2026', session('error'));
        $this->assertStringNotContainsString('Data Akhir kelas VII, VIII, dan IX tahun pelajaran 2026/2027', session('error'));
    }

    public function test_ajax_submission_receives_validation_response_and_profile_redirect(): void
    {
        [, $user] = $this->schoolUser();
        $request = Request::create('/mutasi-masuk/draft', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        $response = app(EnsureInitialStudentCountsCompleted::class)
            ->handle($request, fn () => response('allowed'));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(route('sekolah.profile'), $response->getData(true)['redirect']);
    }

    public function test_mutation_revision_routes_also_require_complete_school_data(): void
    {
        foreach ([
            'mutasi.keluar.revision',
            'mutasi.keluar.revision.submit',
            'mutasi.masuk.revision',
            'mutasi.masuk.revision.submit',
        ] as $routeName) {
            $route = collect(app('router')->getRoutes()->getRoutes())
                ->first(fn ($route) => $route->getName() === $routeName);

            $this->assertNotNull($route, $routeName);
            $this->assertContains('student-counts.complete', $route->gatherMiddleware(), $routeName);
        }
    }

    public function test_dinas_is_not_blocked(): void
    {
        $user = new User(['role' => 'Dinas']);

        $response = $this->runMiddleware($user);

        $this->assertSame(200, $response->getStatusCode());
    }

    private function schoolUser(): array
    {
        $schoolId = DB::table('satuan_pendidikan')->insertGetId([
            'npsn' => '12345678',
            'nama' => 'SMP Pengujian',
            'kepala_sekolah_nama' => 'Kepala Sekolah, M.Pd.',
            'kepala_sekolah_nip' => '-',
            'kop_surat_path' => 'uploads/kop_surat/contoh.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $school = SatuanPendidikan::findOrFail($schoolId);
        $user = new User([
            'username' => 'operator-sekolah',
            'role' => 'Sekolah',
            'npsn' => $school->npsn,
        ]);

        return [$school, $user];
    }

    private function insertCounts(SatuanPendidikan $school, string $academicYearName, bool $withClosing): void
    {
        $academicYear = AcademicYear::query()->where('name', $academicYearName)->firstOrFail();
        $now = now();

        foreach ([7, 8, 9] as $grade) {
            DB::table('satuan_pendidikan_student_counts')->insert([
                'satuan_pendidikan_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'grade' => $grade,
                'male_count' => 0,
                'female_count' => 0,
                'confirmed_at' => $grade === 7 ? $now : null,
                'closing_male_count' => $withClosing ? 0 : null,
                'closing_female_count' => $withClosing ? 0 : null,
                'closing_confirmed_at' => $withClosing ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function runMiddleware(User $user)
    {
        $request = Request::create('/mutasi-keluar/create', 'GET');
        $request->setUserResolver(fn () => $user);

        return app(EnsureInitialStudentCountsCompleted::class)
            ->handle($request, fn () => response('allowed'));
    }
}
