<?php

namespace Tests\Feature;

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\SatuanPendidikanController;
use App\Models\AcademicYear;
use App\Models\SatuanPendidikan;
use App\Models\User;
use App\Services\StudentCountRolloverService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicYearTest extends TestCase
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
            $table->string('bentuk_pendidikan')->default('SMP');
            $table->string('status_sp')->default('Negeri');
            $table->text('alamat_jalan')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->default('Kota Bogor');
            $table->string('provinsi')->default('Jawa Barat');
            $table->text('alamat_lengkap')->nullable();
            $table->boolean('is_local')->default(true);
            $table->string('kepala_sekolah_nama')->nullable();
            $table->string('kepala_sekolah_nip')->nullable();
            $table->string('kop_surat_path')->nullable();
            foreach ([7, 8, 9] as $grade) {
                $table->unsignedInteger("jumlah_siswa_awal_{$grade}")->default(0);
                $table->unsignedInteger("jumlah_siswa_{$grade}_l")->default(0);
                $table->unsignedInteger("jumlah_siswa_{$grade}_p")->default(0);
            }
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->string('npsn')->nullable();
            $table->string('nama_sekolah')->nullable();
            $table->text('alamat')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Auth::forgetUser();
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_migration_backfills_legacy_student_counts_into_2025_2026(): void
    {
        $schoolId = $this->insertSchool();

        $migration = require database_path('migrations/2026_07_14_010000_create_academic_year_student_counts.php');
        $migration->up();
        $migration->up();
        $this->runRolloverTrackingMigration();

        $academicYear = DB::table('academic_years')->where('name', '2025/2026')->first();

        $this->assertNotNull($academicYear);
        $this->assertTrue((bool) $academicYear->is_active);
        $this->assertSame('2025-07-15', $academicYear->starts_on);
        $this->assertSame('2026-07-14', $academicYear->ends_on);
        $this->assertDatabaseCount('satuan_pendidikan_student_counts', 3);
        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $academicYear->id,
            'grade' => 7,
            'male_count' => 10,
            'female_count' => 12,
        ]);
    }

    public function test_profile_reads_and_updates_counts_for_the_selected_academic_year(): void
    {
        $schoolId = $this->insertSchool();
        $this->runAcademicYearMigrations();

        $academicYear = AcademicYear::where('name', '2025/2026')->firstOrFail();
        $school = SatuanPendidikan::findOrFail($schoolId);
        $user = new User([
            'username' => 'sekolah-test',
            'role' => 'Sekolah',
            'npsn' => $school->npsn,
            'status' => 'Aktif',
        ]);
        $user->id = 99;
        $user->setRelation('sekolah', $school);
        Auth::setUser($user);

        $controller = app(SatuanPendidikanController::class);
        $view = $controller->editProfile(Request::create(
            '/sekolah/profil',
            'GET',
            ['academic_year' => '2025/2026']
        ));

        $this->assertSame(10, $view->getData()['studentCounts'][7]['male']);
        $this->assertSame(12, $view->getData()['studentCounts'][7]['female']);

        $controller->updateProfile(Request::create('/sekolah/profil', 'PUT', [
            'academic_year_id' => $academicYear->id,
            'jumlah_siswa_7_l' => 20,
            'jumlah_siswa_7_p' => 21,
            'jumlah_siswa_8_l' => 22,
            'jumlah_siswa_8_p' => 23,
            'jumlah_siswa_9_l' => 24,
            'jumlah_siswa_9_p' => 25,
        ]));

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $academicYear->id,
            'grade' => 7,
            'male_count' => 20,
            'female_count' => 21,
        ]);
        $this->assertNotNull(DB::table('satuan_pendidikan_student_counts')
            ->where('satuan_pendidikan_id', $schoolId)
            ->where('academic_year_id', $academicYear->id)
            ->where('grade', 7)
            ->value('confirmed_at'));
        $this->assertDatabaseHas('satuan_pendidikan', [
            'id' => $schoolId,
            'jumlah_siswa_awal_7' => 41,
            'jumlah_siswa_7_l' => 20,
            'jumlah_siswa_7_p' => 21,
        ]);
        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $academicYear->id,
            'grade' => 8,
            'male_count' => 13,
            'female_count' => 13,
        ]);
    }

    public function test_activating_a_year_closes_the_previous_year_and_syncs_legacy_totals(): void
    {
        $schoolId = $this->insertSchool();
        $this->runAcademicYearMigrations();

        $nextYear = AcademicYear::create([
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_UPCOMING,
            'is_active' => false,
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-07-14',
        ]);

        foreach ([7 => [30, 31], 8 => [32, 33], 9 => [34, 35]] as $grade => [$male, $female]) {
            DB::table('satuan_pendidikan_student_counts')->insert([
                'satuan_pendidikan_id' => $schoolId,
                'academic_year_id' => $nextYear->id,
                'grade' => $grade,
                'male_count' => $male,
                'female_count' => $female,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(AcademicYearController::class)->activate($nextYear->id);

        $this->assertDatabaseHas('academic_years', [
            'name' => '2025/2026',
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('academic_years', [
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('satuan_pendidikan', [
            'id' => $schoolId,
            'jumlah_siswa_awal_7' => 61,
            'jumlah_siswa_7_l' => 30,
            'jumlah_siswa_7_p' => 31,
        ]);
    }

    public function test_activation_promotes_final_balances_after_completed_mutations_once(): void
    {
        $schoolId = $this->insertSchool();
        $this->runAcademicYearMigrations();
        $this->createMutationTables();

        $previousYear = AcademicYear::where('name', '2025/2026')->firstOrFail();
        $nextYear = AcademicYear::create([
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_UPCOMING,
            'is_active' => false,
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-07-14',
        ]);
        DB::table('satuan_pendidikan_student_counts')->insert([
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $nextYear->id,
            'grade' => 7,
            'male_count' => 30,
            'female_count' => 31,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('mutasi_keluar')->insert([
            [
                'id_mutasi' => 'MK1', 'tanggal_pengajuan' => '2026-05-01', 'tanggal_selesai' => '2026-06-01',
                'npsn_sekolah_asal' => '12345678', 'kelas' => 'VII', 'jenis_kelamin' => 'L', 'status' => 'Selesai',
            ],
            [
                'id_mutasi' => 'MK2', 'tanggal_pengajuan' => '2026-05-01', 'tanggal_selesai' => '2026-06-01',
                'npsn_sekolah_asal' => '12345678', 'kelas' => 'VII', 'jenis_kelamin' => 'P', 'status' => 'Proses TTD',
            ],
            [
                'id_mutasi' => 'MK3', 'tanggal_pengajuan' => '2027-01-01', 'tanggal_selesai' => '2027-01-02',
                'npsn_sekolah_asal' => '12345678', 'kelas' => 'VII', 'jenis_kelamin' => 'P', 'status' => 'Selesai',
            ],
        ]);
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'MM1',
            'tanggal_pengajuan' => '2026-05-01',
            'tanggal_selesai' => '2026-06-02',
            'npsn_sekolah_tujuan' => '12345678',
            'status' => 'Selesai',
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            ['id_mutasi' => 'MM1', 'kelas' => 'VII', 'jenis_kelamin' => 'P'],
            ['id_mutasi' => 'MM1', 'kelas' => '8', 'jenis_kelamin' => 'L'],
        ]);

        app(AcademicYearController::class)->activate($nextYear->id);

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $nextYear->id,
            'grade' => 7,
            'male_count' => 30,
            'female_count' => 31,
        ]);
        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $nextYear->id,
            'grade' => 8,
            'male_count' => 9,
            'female_count' => 13,
        ]);
        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $nextYear->id,
            'grade' => 9,
            'male_count' => 14,
            'female_count' => 13,
        ]);
        $this->assertDatabaseHas('academic_years', [
            'id' => $nextYear->id,
            'student_counts_rolled_over_from_id' => $previousYear->id,
        ]);

        DB::table('satuan_pendidikan_student_counts')
            ->where('academic_year_id', $nextYear->id)
            ->where('grade', 8)
            ->update(['male_count' => 99]);
        app(StudentCountRolloverService::class)->rollover($previousYear, $nextYear->fresh());
        $this->assertSame(99, (int) DB::table('satuan_pendidikan_student_counts')
            ->where('academic_year_id', $nextYear->id)
            ->where('grade', 8)
            ->value('male_count'));
    }

    public function test_dinas_can_correct_all_active_year_grades(): void
    {
        $schoolId = $this->insertSchool();
        $this->runAcademicYearMigrations();
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        app(SatuanPendidikanController::class)->update(Request::create('/satuan-pendidikan/'.$schoolId, 'PUT', [
            'npsn' => '12345678',
            'nama' => 'SMP Test',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Negeri',
            'kota' => 'Kota Bogor',
            'provinsi' => 'Jawa Barat',
            'academic_year_id' => $activeYear->id,
            'jumlah_siswa_7_l' => 20,
            'jumlah_siswa_7_p' => 21,
            'jumlah_siswa_8_l' => 22,
            'jumlah_siswa_8_p' => 23,
            'jumlah_siswa_9_l' => 24,
            'jumlah_siswa_9_p' => 25,
        ]), $schoolId);

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $activeYear->id,
            'grade' => 8,
            'male_count' => 22,
            'female_count' => 23,
        ]);
        $this->assertDatabaseHas('satuan_pendidikan', [
            'id' => $schoolId,
            'jumlah_siswa_awal_9' => 49,
        ]);
    }

    public function test_activating_a_year_bulk_updates_all_schools_in_one_query(): void
    {
        for ($schoolNumber = 1; $schoolNumber <= 25; $schoolNumber++) {
            DB::table('satuan_pendidikan')->insert([
                'npsn' => str_pad((string) $schoolNumber, 8, '0', STR_PAD_LEFT),
                'nama' => "SMP Test {$schoolNumber}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->runAcademicYearMigrations();

        $nextYear = AcademicYear::create([
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_UPCOMING,
            'is_active' => false,
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-07-14',
        ]);

        $schoolId = DB::table('satuan_pendidikan')->where('npsn', '00000001')->value('id');
        DB::table('satuan_pendidikan_student_counts')->insert([
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $nextYear->id,
            'grade' => 7,
            'male_count' => 40,
            'female_count' => 41,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $schoolUpdateQueries = 0;
        DB::listen(function (QueryExecuted $query) use (&$schoolUpdateQueries) {
            if (str_starts_with(strtolower($query->sql), 'update "satuan_pendidikan"')) {
                $schoolUpdateQueries++;
            }
        });

        app(AcademicYearController::class)->activate($nextYear->id);

        $this->assertSame(1, $schoolUpdateQueries);
        $this->assertDatabaseHas('satuan_pendidikan', [
            'id' => $schoolId,
            'jumlah_siswa_awal_7' => 81,
            'jumlah_siswa_7_l' => 40,
            'jumlah_siswa_7_p' => 41,
        ]);
        $this->assertSame(0, DB::table('satuan_pendidikan')->where('npsn', '00000002')->value('jumlah_siswa_awal_7'));
    }

    public function test_dinas_can_define_and_update_the_academic_year_period(): void
    {
        $this->insertSchool();
        $this->runAcademicYearMigrations();

        $controller = app(AcademicYearController::class);
        $controller->store(Request::create('/pengaturan/tahun-pelajaran', 'POST', [
            'name' => '2026/2027',
            'starts_on' => '2026-07-20',
            'ends_on' => '2027-07-19',
        ]));

        $academicYear = AcademicYear::where('name', '2026/2027')->firstOrFail();
        $this->assertSame('2026-07-20', $academicYear->starts_on->format('Y-m-d'));
        $this->assertSame('2027-07-19', $academicYear->ends_on->format('Y-m-d'));

        $controller->updateSchedule(Request::create('/periode', 'PATCH', [
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-07-14',
        ]), $academicYear->id);

        $academicYear->refresh();
        $this->assertSame('2026-07-15', $academicYear->starts_on->format('Y-m-d'));
        $this->assertSame('2027-07-14', $academicYear->ends_on->format('Y-m-d'));
    }

    private function insertSchool(): int
    {
        return DB::table('satuan_pendidikan')->insertGetId([
            'npsn' => '12345678',
            'nama' => 'SMP Test',
            'jumlah_siswa_awal_7' => 22,
            'jumlah_siswa_awal_8' => 26,
            'jumlah_siswa_awal_9' => 30,
            'jumlah_siswa_7_l' => 10,
            'jumlah_siswa_7_p' => 12,
            'jumlah_siswa_8_l' => 13,
            'jumlah_siswa_8_p' => 13,
            'jumlah_siswa_9_l' => 14,
            'jumlah_siswa_9_p' => 16,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function runAcademicYearMigrations(): void
    {
        $migration = require database_path('migrations/2026_07_14_010000_create_academic_year_student_counts.php');
        $migration->up();
        $this->runRolloverTrackingMigration();
        $confirmationMigration = require database_path('migrations/2026_07_17_000000_add_confirmed_at_to_student_counts.php');
        $confirmationMigration->up();
    }

    private function runRolloverTrackingMigration(): void
    {
        $migration = require database_path('migrations/2026_07_15_000000_add_student_count_rollover_tracking_to_academic_years.php');
        $migration->up();
    }

    private function createMutationTables(): void
    {
        Schema::create('mutasi_keluar', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->date('tanggal_selesai')->nullable();
            $table->string('npsn_sekolah_asal');
            $table->string('kelas');
            $table->string('jenis_kelamin');
            $table->string('status');
        });
        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->date('tanggal_selesai')->nullable();
            $table->string('npsn_sekolah_tujuan');
            $table->string('status');
        });
        Schema::create('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->id();
            $table->string('id_mutasi');
            $table->string('kelas');
            $table->string('jenis_kelamin');
        });
    }
}
