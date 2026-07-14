<?php

namespace Tests\Feature;

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\SatuanPendidikanController;
use App\Models\AcademicYear;
use App\Models\SatuanPendidikan;
use App\Models\User;
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

        $academicYear = DB::table('academic_years')->where('name', '2025/2026')->first();

        $this->assertNotNull($academicYear);
        $this->assertTrue((bool) $academicYear->is_active);
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
        $migration = require database_path('migrations/2026_07_14_010000_create_academic_year_student_counts.php');
        $migration->up();

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
        $this->assertDatabaseHas('satuan_pendidikan', [
            'id' => $schoolId,
            'jumlah_siswa_awal_7' => 41,
            'jumlah_siswa_7_l' => 20,
            'jumlah_siswa_7_p' => 21,
        ]);
    }

    public function test_activating_a_year_closes_the_previous_year_and_syncs_legacy_totals(): void
    {
        $schoolId = $this->insertSchool();
        $migration = require database_path('migrations/2026_07_14_010000_create_academic_year_student_counts.php');
        $migration->up();

        $nextYear = AcademicYear::create([
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_UPCOMING,
            'is_active' => false,
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
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
}
