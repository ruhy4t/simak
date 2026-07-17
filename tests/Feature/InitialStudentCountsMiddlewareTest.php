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
            $table->timestamps();
        });

        AcademicYear::create([
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_ACTIVE,
            'is_active' => true,
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

    public function test_confirmed_zero_counts_allow_school_to_submit_mutations(): void
    {
        [$school, $user] = $this->schoolUser();
        $academicYear = AcademicYear::query()->where('is_active', true)->firstOrFail();

        DB::table('satuan_pendidikan_student_counts')->insert([
            'satuan_pendidikan_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'grade' => 7,
            'male_count' => 0,
            'female_count' => 0,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->runMiddleware($user);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
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

    private function runMiddleware(User $user)
    {
        $request = Request::create('/mutasi-keluar/create', 'GET');
        $request->setUserResolver(fn () => $user);

        return app(EnsureInitialStudentCountsCompleted::class)
            ->handle($request, fn () => response('allowed'));
    }
}
