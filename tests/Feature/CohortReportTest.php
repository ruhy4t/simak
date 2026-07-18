<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\CohortReportService;
use App\Services\StudentCountRolloverService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CohortReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_cohort_report_reconciles_only_completed_mutations_and_actual_counts(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(true);

        DB::table('mutasi_masuk')->insert([
            ['id_mutasi' => 'M-1', 'tanggal_pengajuan' => '2026-08-01', 'npsn_sekolah_tujuan' => '12345678', 'status' => 'Selesai'],
            ['id_mutasi' => 'M-2', 'tanggal_pengajuan' => '2026-08-02', 'npsn_sekolah_tujuan' => '12345678', 'status' => 'Draft'],
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            ['id_mutasi' => 'M-1', 'kelas' => 'VII', 'jenis_kelamin' => 'L'],
            ['id_mutasi' => 'M-2', 'kelas' => 'VII', 'jenis_kelamin' => 'L'],
        ]);
        DB::table('mutasi_keluar')->insert([
            ['id_mutasi' => 'K-1', 'tanggal_pengajuan' => '2026-09-01', 'npsn_sekolah_asal' => '12345678', 'kelas' => '7', 'jenis_kelamin' => 'L', 'status' => 'Selesai'],
            ['id_mutasi' => 'K-2', 'tanggal_pengajuan' => '2026-09-02', 'npsn_sekolah_asal' => '12345678', 'kelas' => 'VII', 'jenis_kelamin' => 'L', 'status' => 'Selesai'],
            ['id_mutasi' => 'K-3', 'tanggal_pengajuan' => '2026-09-03', 'npsn_sekolah_asal' => '12345678', 'kelas' => '7', 'jenis_kelamin' => 'P', 'status' => 'Selesai'],
        ]);

        $report = app(CohortReportService::class)->report($year, $schoolId);

        $this->assertSame(18, $report['reported_summary']['opening']);
        $this->assertSame(1, $report['reported_summary']['incoming']);
        $this->assertSame(3, $report['reported_summary']['outgoing']);
        $this->assertSame(16, $report['reported_summary']['expected']);
        $this->assertSame(17, $report['reported_summary']['actual']);
        $this->assertSame(19, $report['reported_summary']['calculated_opening']);
        $this->assertSame(-1, $report['reported_summary']['opening_variance']);
        $this->assertSame(1, $report['reported_summary']['unexplained_exit']);
        $this->assertSame(2, $report['reported_summary']['other_addition']);
        $this->assertSame(1, $report['reported_school_count']);
        $this->assertTrue($report['is_complete']);
    }

    public function test_school_can_submit_closing_counts_once_and_values_are_locked(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(false);
        $userId = DB::table('users')->insertGetId([
            'username' => 'operator-test',
            'password' => Hash::make('secret-value'),
            'role' => 'Operator',
            'status' => 'Aktif',
            'npsn' => '12345678',
            'must_change_password' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::findOrFail($userId);

        $payload = [
            'academic_year_id' => $year->id,
            'confirmation' => '1',
            'closing_7_male' => 9,
            'closing_7_female' => 8,
            'closing_8_male' => 11,
            'closing_8_female' => 10,
            'closing_9_male' => 7,
            'closing_9_female' => 6,
        ];

        $this->actingAs($user)
            ->post(route('sekolah.closing-counts.store'), $payload)
            ->assertRedirect(route('sekolah.closing-counts', ['academic_year' => $year->name]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $year->id,
            'grade' => 7,
            'closing_male_count' => 9,
            'closing_female_count' => 8,
        ]);

        $payload['closing_7_male'] = 99;
        $this->actingAs($user)
            ->post(route('sekolah.closing-counts.store'), $payload)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'grade' => 7,
            'closing_male_count' => 9,
        ]);
    }

    public function test_closing_counts_default_to_previous_year_and_refresh_active_year_opening_counts(): void
    {
        [$previousYear, $schoolId] = $this->seedSchoolAndYear(false);
        $previousYear->update([
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
        ]);

        $activeYear = AcademicYear::create([
            'name' => '2027/2028',
            'status' => AcademicYear::STATUS_ACTIVE,
            'is_active' => true,
            'starts_on' => '2027-07-15',
            'ends_on' => '2028-07-14',
            'student_counts_rolled_over_from_id' => $previousYear->id,
            'student_counts_rolled_over_at' => now(),
        ]);

        foreach ([7 => [20, 21], 8 => [1, 1], 9 => [2, 2]] as $grade => [$male, $female]) {
            DB::table('satuan_pendidikan_student_counts')->insert([
                'satuan_pendidikan_id' => $schoolId,
                'academic_year_id' => $activeYear->id,
                'grade' => $grade,
                'male_count' => $male,
                'female_count' => $female,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $user = $this->createUser('operator-rollover-refresh', 'Operator', '12345678');

        $this->actingAs($user)
            ->get(route('sekolah.closing-counts'))
            ->assertOk()
            ->assertViewHas('selectedAcademicYear', fn (AcademicYear $year) => $year->is($previousYear));

        $this->actingAs($user)->post(route('sekolah.closing-counts.store'), [
            'academic_year_id' => $previousYear->id,
            'confirmation' => '1',
            'closing_7_male' => 9,
            'closing_7_female' => 8,
            'closing_8_male' => 11,
            'closing_8_female' => 10,
            'closing_9_male' => 7,
            'closing_9_female' => 6,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $activeYear->id,
            'grade' => 8,
            'male_count' => 9,
            'female_count' => 8,
        ]);
        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $activeYear->id,
            'grade' => 9,
            'male_count' => 11,
            'female_count' => 10,
        ]);
        $this->assertDatabaseHas('satuan_pendidikan', [
            'id' => $schoolId,
            'jumlah_siswa_8_l' => 9,
            'jumlah_siswa_8_p' => 8,
            'jumlah_siswa_9_l' => 11,
            'jumlah_siswa_9_p' => 10,
        ]);
    }

    public function test_public_statistics_expose_only_citywide_cohort_aggregates(): void
    {
        [$year] = $this->seedSchoolAndYear(true);

        $this->get('/statistik-mutasi?year=2026&academic_year='.$year->id)
            ->assertOk()
            ->assertDontSee('Kohort Siswa Kota Bogor');

        $year->update([
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
        ]);

        $this->get('/statistik-mutasi?year=2026&academic_year='.$year->id)
            ->assertOk()
            ->assertSee('Kohort Siswa Kota Bogor')
            ->assertSee('Keluar non-mutasi terdata')
            ->assertDontSee('SMP Uji Kohort')
            ->assertDontSee('12345678');
    }

    public function test_citywide_cohort_only_counts_public_and_private_junior_high_schools_from_bogor_city(): void
    {
        [$year] = $this->seedSchoolAndYear(true);

        $includedPrivateSchool = $this->insertSchoolWithCounts($year, [
            'npsn' => '22222222',
            'nama' => 'SMP Swasta Kota Bogor',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Swasta',
            'kota' => 'Kota Bogor',
            'is_local' => true,
        ]);

        $this->insertSchoolWithCounts($year, [
            'npsn' => '33333333',
            'nama' => 'SMP Luar Kota',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Negeri',
            'kota' => 'Kota Bandung',
            'is_local' => false,
        ], 100);
        $this->insertSchoolWithCounts($year, [
            'npsn' => '44444444',
            'nama' => 'SD Kota Bogor',
            'bentuk_pendidikan' => 'SD',
            'status_sp' => 'Swasta',
            'kota' => 'Kota Bogor',
            'is_local' => true,
        ], 100);

        $report = app(CohortReportService::class)->report($year);

        $this->assertSame(2, $report['school_count']);
        $this->assertSame(2, $report['reported_school_count']);
        $this->assertSame(24, $report['reported_summary']['opening']);
        $this->assertSame(23, $report['reported_summary']['actual']);
        $this->assertContains($includedPrivateSchool, DB::table('satuan_pendidikan')->pluck('id')->all());
    }

    public function test_internal_cohort_is_available_only_for_closed_academic_years(): void
    {
        [$year] = $this->seedSchoolAndYear(true);
        $operator = $this->createUser('operator-cohort', 'Operator', '12345678');

        $this->actingAs($operator)
            ->get(route('laporan.kohort'))
            ->assertNotFound();

        $year->update([
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
        ]);

        $this->actingAs($operator)
            ->get(route('laporan.kohort', ['academic_year' => $year->id]))
            ->assertOk()
            ->assertViewHas('academicYear', fn (AcademicYear $selectedYear) => $selectedYear->is($year))
            ->assertSee('2026/2027');
    }

    public function test_rollover_uses_locked_actual_closing_counts(): void
    {
        [$fromYear, $schoolId] = $this->seedSchoolAndYear(true);
        $toYear = AcademicYear::create([
            'name' => '2027/2028',
            'status' => AcademicYear::STATUS_UPCOMING,
            'is_active' => false,
            'starts_on' => '2027-07-15',
            'ends_on' => '2028-07-14',
        ]);

        app(StudentCountRolloverService::class)->rollover($fromYear, $toYear);

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $toYear->id,
            'grade' => 8,
            'male_count' => 8,
            'female_count' => 9,
        ]);
    }

    public function test_school_record_immediately_updates_aggregate_without_dinas_approval(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(true);
        $operator = $this->createUser('operator-entry', 'Operator', '12345678');

        $payload = [
            'academic_year_id' => $year->id,
            'student_name' => 'Siswa Uji Rahasia',
            'grade' => 7,
            'gender' => 'L',
            'exit_date' => '2026-10-10',
            'reason' => 'Tanpa keterangan',
            'notes' => 'Tidak lagi hadir di sekolah.',
        ];

        $this->actingAs($operator)
            ->post(route('non-mutation-exits.store'), $payload)
            ->assertSessionHasErrors('nisn');

        $payload['nisn'] = '0012345678';
        $this->actingAs($operator)
            ->post(route('non-mutation-exits.store'), $payload)
            ->assertRedirect();

        $payload['student_name'] = 'Nama Duplikat';
        $this->actingAs($operator)
            ->post(route('non-mutation-exits.store'), $payload)
            ->assertSessionHasErrors('nisn');

        $this->assertDatabaseHas('non_mutation_student_exits', [
            'student_name' => 'Siswa Uji Rahasia',
            'nisn' => '0012345678',
        ]);

        $report = app(CohortReportService::class)->report($year, $schoolId);
        $this->assertSame(1, $report['reported_summary']['non_mutation_outgoing']);
        $this->assertSame(17, $report['reported_summary']['expected']);

        $year->update([
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
        ]);

        $this->get('/statistik-mutasi?year=2026&academic_year='.$year->id)
            ->assertOk()
            ->assertSee('Keluar non-mutasi terdata')
            ->assertDontSee('Siswa Uji Rahasia')
            ->assertDontSee('0012345678');
    }

    private function seedSchoolAndYear(bool $withClosing): array
    {
        $schoolId = DB::table('satuan_pendidikan')->insertGetId([
            'npsn' => '12345678',
            'nama' => 'SMP Uji Kohort',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Negeri',
            'kota' => 'Kota Bogor',
            'is_local' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_ACTIVE,
            'is_active' => true,
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-07-14',
        ]);

        foreach ([7 => [10, 8], 8 => [0, 0], 9 => [0, 0]] as $grade => [$male, $female]) {
            DB::table('satuan_pendidikan_student_counts')->insert([
                'satuan_pendidikan_id' => $schoolId,
                'academic_year_id' => $year->id,
                'grade' => $grade,
                'male_count' => $male,
                'female_count' => $female,
                'confirmed_at' => now(),
                'closing_male_count' => $withClosing ? ($grade === 7 ? 8 : 0) : null,
                'closing_female_count' => $withClosing ? ($grade === 7 ? 9 : 0) : null,
                'closing_confirmed_at' => $withClosing ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$year, $schoolId];
    }

    private function insertSchoolWithCounts(AcademicYear $year, array $school, int $openingPerGrade = 1): int
    {
        $schoolId = DB::table('satuan_pendidikan')->insertGetId(array_merge($school, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        foreach ([7, 8, 9] as $grade) {
            DB::table('satuan_pendidikan_student_counts')->insert([
                'satuan_pendidikan_id' => $schoolId,
                'academic_year_id' => $year->id,
                'grade' => $grade,
                'male_count' => $openingPerGrade,
                'female_count' => $openingPerGrade,
                'confirmed_at' => now(),
                'closing_male_count' => $openingPerGrade,
                'closing_female_count' => $openingPerGrade,
                'closing_confirmed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $schoolId;
    }

    private function createUser(string $username, string $role, ?string $npsn = null): User
    {
        $id = DB::table('users')->insertGetId([
            'username' => $username,
            'password' => Hash::make('secret-value'),
            'role' => $role,
            'status' => 'Aktif',
            'npsn' => $npsn,
            'must_change_password' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('password');
            $table->string('role');
            $table->string('status')->default('Aktif');
            $table->string('npsn')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('satuan_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->string('npsn')->unique();
            $table->string('nama');
            $table->string('bentuk_pendidikan')->default('SMP');
            $table->string('status_sp')->default('Swasta');
            $table->string('kota')->default('Kota Bogor');
            $table->boolean('is_local')->default(true);
            $table->unsignedInteger('jumlah_siswa_awal_8')->default(0);
            $table->unsignedInteger('jumlah_siswa_awal_9')->default(0);
            $table->unsignedInteger('jumlah_siswa_8_l')->default(0);
            $table->unsignedInteger('jumlah_siswa_8_p')->default(0);
            $table->unsignedInteger('jumlah_siswa_9_l')->default(0);
            $table->unsignedInteger('jumlah_siswa_9_p')->default(0);
            $table->timestamps();
        });
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('status');
            $table->boolean('is_active')->default(false);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignId('student_counts_rolled_over_from_id')->nullable();
            $table->timestamp('student_counts_rolled_over_at')->nullable();
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
            $table->unique(['satuan_pendidikan_id', 'academic_year_id', 'grade']);
        });
        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_tujuan');
            $table->string('status');
        });
        Schema::create('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->id();
            $table->string('id_mutasi');
            $table->string('kelas');
            $table->string('jenis_kelamin');
            $table->boolean('asal_luar_negeri')->default(false);
            $table->string('kabupaten_kota_asal')->nullable();
        });
        Schema::create('mutasi_keluar', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_asal');
            $table->string('kelas');
            $table->string('jenis_kelamin');
            $table->string('status');
            $table->string('provinsi_tujuan')->nullable();
            $table->string('kabupaten_kota_tujuan')->nullable();
        });
        Schema::create('non_mutation_student_exits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satuan_pendidikan_id');
            $table->foreignId('academic_year_id');
            $table->string('student_name');
            $table->string('nisn');
            $table->unsignedTinyInteger('grade');
            $table->char('gender');
            $table->date('exit_date');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('reported_by')->nullable();
            $table->timestamps();
            $table->unique(['satuan_pendidikan_id', 'academic_year_id', 'nisn']);
        });
    }
}
