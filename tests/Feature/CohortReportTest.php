<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\CohortReportService;
use App\Services\LongitudinalCohortService;
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
        $year->update(['name' => '2025/2026']);

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
        $this->assertTrue($report['uses_calculated_opening']);
        $this->assertSame(1, $report['reported_summary']['unexplained_exit']);
        $this->assertSame(2, $report['reported_summary']['other_addition']);
        $this->assertSame(1, $report['reported_school_count']);
        $this->assertTrue($report['is_complete']);
        $this->assertSame(18, $report['grade_summary'][0]['opening']);
        $this->assertSame(1, $report['grade_summary'][0]['incoming']);
        $this->assertSame(3, $report['grade_summary'][0]['outgoing']);
        $this->assertSame(16, $report['grade_summary'][0]['expected']);
        $this->assertSame(17, $report['grade_summary'][0]['actual']);
    }

    public function test_reverse_opening_calculation_is_not_used_after_2025_2026(): void
    {
        [$year] = $this->seedSchoolAndYear(true);

        $report = app(CohortReportService::class)->report($year);

        $this->assertFalse($report['uses_calculated_opening']);
        $this->assertSame($report['reported_summary']['opening'], $report['reported_summary']['calculated_opening']);
        $this->assertSame(0, $report['reported_summary']['opening_variance']);
    }

    public function test_active_year_displays_provisional_totals_without_locked_closing_counts(): void
    {
        [$year] = $this->seedSchoolAndYear(false);

        $report = app(CohortReportService::class)->report($year);

        $this->assertTrue($report['is_provisional']);
        $this->assertSame(0, $report['reported_school_count']);
        $this->assertSame(18, $report['summary']['opening']);
        $this->assertSame(18, $report['summary']['expected']);
        $this->assertSame(18, $report['provisional_grade_summary'][0]['opening']);
        $this->assertFalse($report['provisional_grade_summary'][0]['has_actual']);

        $this->get('/statistik-mutasi?year=2026&academic_year='.$year->id)
            ->assertOk()
            ->assertSee('Data sementara tahun berjalan, bukan angka final.')
            ->assertSee('Perkiraan Saat Ini')
            ->assertSee('Data Akhir Terkunci')
            ->assertSee('—')
            ->assertDontSee('Data akhir aktual belum tersedia.');
    }

    public function test_early_locked_counts_are_flagged_and_excluded_from_final_totals(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(true);
        $year->update(['ends_on' => now()->addYear()->toDateString()]);
        DB::table('satuan_pendidikan_student_counts')
            ->where('satuan_pendidikan_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->update([
                'closing_confirmed_at' => now(),
                'closing_locked_early' => true,
            ]);

        $report = app(CohortReportService::class)->report($year, $schoolId);

        $this->assertSame(1, $report['early_locked_school_count']);
        $this->assertSame(0, $report['reported_school_count']);
        $this->assertSame(0, $report['summary']['actual']);
        $this->assertFalse($report['is_complete']);
    }

    public function test_school_can_submit_closing_counts_once_and_values_are_locked(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(false);
        $year->update([
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
        ]);
        $userId = DB::table('users')->insertGetId([
            'username' => 'operator-test',
            'password' => Hash::make('secret-value'),
            'role' => 'Operator',
            'status' => 'Aktif',
            'npsn' => '12345678',
            'telegram_chat_id' => '123456789',
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

    public function test_missing_opening_rows_are_repaired_when_closing_counts_are_submitted(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(false);
        $year->update([
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
        ]);
        DB::table('satuan_pendidikan')->where('id', $schoolId)->update([
            'jumlah_siswa_8_l' => 12,
            'jumlah_siswa_8_p' => 13,
            'jumlah_siswa_9_l' => 14,
            'jumlah_siswa_9_p' => 15,
        ]);
        DB::table('satuan_pendidikan_student_counts')
            ->where('satuan_pendidikan_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->whereIn('grade', [8, 9])
            ->delete();
        $user = $this->createUser('operator-incomplete-opening', 'Operator', '12345678');

        $intendedUrl = route('mutasi.keluar.create');

        $this->actingAs($user)
            ->withSession(['mutation.intended_url' => $intendedUrl])
            ->post(route('sekolah.closing-counts.store'), [
                'academic_year_id' => $year->id,
                'confirmation' => '1',
                'closing_7_male' => 9,
                'closing_7_female' => 8,
                'closing_8_male' => 11,
                'closing_8_female' => 10,
                'closing_9_male' => 7,
                'closing_9_female' => 6,
            ])
            ->assertRedirect($intendedUrl)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $year->id,
            'grade' => 8,
            'male_count' => 12,
            'female_count' => 13,
            'closing_male_count' => 11,
            'closing_female_count' => 10,
        ]);
        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $year->id,
            'grade' => 9,
            'male_count' => 14,
            'female_count' => 15,
            'closing_male_count' => 7,
            'closing_female_count' => 6,
        ]);
    }

    public function test_active_year_cannot_be_locked_before_its_end_date(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(false);
        $year->update(['ends_on' => now()->addYear()->toDateString()]);
        $user = $this->createUser('operator-active-year', 'Operator', '12345678');

        $response = $this->actingAs($user)->post(route('sekolah.closing-counts.store'), [
            'academic_year_id' => $year->id,
            'confirmation' => '1',
            'closing_7_male' => 9,
            'closing_7_female' => 8,
            'closing_8_male' => 11,
            'closing_8_female' => 10,
            'closing_9_male' => 7,
            'closing_9_female' => 6,
        ]);

        $response
            ->assertRedirect(route('sekolah.closing-counts', ['academic_year' => $year->name]))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $year->id,
            'grade' => 7,
            'closing_confirmed_at' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'closing_counts_blocked',
            'username' => $user->username,
        ]);

        $this->actingAs($user)
            ->get(route('sekolah.closing-counts'))
            ->assertOk()
            ->assertSee('Pengisian data akhir belum dibuka.')
            ->assertDontSee('Simpan dan Kunci Data Akhir');
    }

    public function test_active_year_can_be_locked_when_its_end_date_is_reached(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(false);
        $year->update(['ends_on' => now()->toDateString()]);
        $user = $this->createUser('operator-closing-day', 'Operator', '12345678');

        $this->actingAs($user)->post(route('sekolah.closing-counts.store'), [
            'academic_year_id' => $year->id,
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
            'academic_year_id' => $year->id,
            'grade' => 7,
            'closing_male_count' => 9,
        ]);
    }

    public function test_only_dinas_can_reopen_locked_closing_counts_with_a_reason(): void
    {
        [$year, $schoolId] = $this->seedSchoolAndYear(true);
        $schoolUser = $this->createUser('operator-reopen-denied', 'Operator', '12345678');
        $dinasUser = $this->createUser('dinas-reopen', 'Dinas');
        $route = route('closing-counts.reopen', [$schoolId, $year->id]);

        $this->actingAs($schoolUser)
            ->patch($route, ['reason' => 'Data perlu diperiksa ulang.'])
            ->assertForbidden();

        $this->actingAs($dinasUser)
            ->patch($route, ['reason' => 'Data dikunci sebelum rekonsiliasi selesai.'])
            ->assertRedirect(route('laporan.kohort', [
                'academic_year' => $year->id,
                'school' => $schoolId,
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'satuan_pendidikan_id' => $schoolId,
            'academic_year_id' => $year->id,
            'grade' => 7,
            'closing_male_count' => null,
            'closing_female_count' => null,
            'closing_confirmed_at' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'closing_counts_reopened',
            'username' => $dinasUser->username,
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
        $upcomingYear = AcademicYear::create([
            'name' => '2027/2028',
            'status' => AcademicYear::STATUS_UPCOMING,
            'is_active' => false,
            'starts_on' => '2027-07-15',
            'ends_on' => '2028-07-14',
        ]);

        $this->get('/statistik-mutasi?year=2026&academic_year='.$year->id)
            ->assertOk()
            ->assertSee('Rekonsiliasi Seluruh SMP/SPK SMP Kota Bogor')
            ->assertSee('2026/2027 (Aktif)')
            ->assertSee('Tahun pelajaran masih aktif')
            ->assertViewHas('cohortYears', fn ($years) => ! $years->contains('id', $upcomingYear->id))
            ->assertSee('Kohort Angkatan')
            ->assertSee('id="kohort-angkatan"', false)
            ->assertSee('action="'.route('public.statistics').'#kohort-angkatan"', false);

        $year->update([
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
        ]);

        $this->get('/statistik-mutasi?year=2026&academic_year='.$year->id)
            ->assertOk()
            ->assertSee('Rekonsiliasi Seluruh SMP/SPK SMP Kota Bogor')
            ->assertSee('id="rekonsiliasi-tahunan"', false)
            ->assertSee('action="'.route('public.statistics').'#rekonsiliasi-tahunan"', false)
            ->assertSee('Jumlah per Kelas')
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
        $includedSpkSchool = $this->insertSchoolWithCounts($year, [
            'npsn' => '55555555',
            'nama' => 'SPK SMP Kota Bogor',
            'bentuk_pendidikan' => 'SPK SMP',
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
        $publicReport = app(CohortReportService::class)->report($year, null, 'Negeri');
        $privateReport = app(CohortReportService::class)->report($year, null, 'Swasta');

        $this->assertSame(3, $report['school_count']);
        $this->assertSame(3, $report['reported_school_count']);
        $this->assertSame(30, $report['reported_summary']['opening']);
        $this->assertSame(29, $report['reported_summary']['actual']);
        $this->assertSame(1, $publicReport['school_count']);
        $this->assertSame(2, $privateReport['school_count']);
        $this->assertContains($includedPrivateSchool, DB::table('satuan_pendidikan')->pluck('id')->all());
        $this->assertContains($includedSpkSchool, DB::table('satuan_pendidikan')->pluck('id')->all());
    }

    public function test_npsn_filter_scopes_reconciliation_and_cohort_and_displays_school_name(): void
    {
        [$year] = $this->seedSchoolAndYear(false);
        $this->insertSchoolWithCounts($year, [
            'npsn' => '22222222',
            'nama' => 'SMP Pembanding',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Swasta',
            'kota' => 'Kota Bogor',
            'is_local' => true,
        ], 100);

        $this->get('/statistik-mutasi?year=2026&npsn=12345678&academic_year='.$year->id.'&cohort_entry_year='.$year->id)
            ->assertOk()
            ->assertViewHas('selectedSchool', fn ($school) => $school?->nama === 'SMP Uji Kohort')
            ->assertViewHas('cohort', fn (array $report) => $report['school_count'] === 1
                && $report['summary']['opening'] === 18)
            ->assertViewHas('longitudinalCohort', fn (array $report) => $report['summary']['initial'] === 18
                && $report['stages'][0]['school_count'] === 1)
            ->assertSee('Sekolah Terpilih')
            ->assertSee('SMP Uji Kohort')
            ->assertSee('NPSN 12345678')
            ->assertSee('Rekonsiliasi SMP Uji Kohort')
            ->assertSee('Angkatan 2026/2027 · SMP Uji Kohort');
    }

    public function test_internal_annual_reconciliation_is_available_for_active_and_closed_years(): void
    {
        [$year] = $this->seedSchoolAndYear(true);
        $operator = $this->createUser('operator-cohort', 'Operator', '12345678');

        $this->actingAs($operator)
            ->get(route('laporan.kohort'))
            ->assertOk()
            ->assertViewHas('academicYear', fn (AcademicYear $selectedYear) => $selectedYear->is($year))
            ->assertSee('Kohort Angkatan')
            ->assertSee('Rekonsiliasi SMP Uji Kohort')
            ->assertSee('2026/2027 (Aktif)')
            ->assertSee('Ringkasan sementara tahun berjalan');

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

    public function test_dinas_school_filter_only_lists_bogor_smp_and_spk_smp_with_search_and_reset(): void
    {
        [$year] = $this->seedSchoolAndYear(true);
        $this->insertSchoolWithCounts($year, [
            'npsn' => '22222222',
            'nama' => 'SPK SMP Bogor Raya',
            'bentuk_pendidikan' => 'SPK SMP',
            'status_sp' => 'Swasta',
            'kota' => 'Kota Bogor',
            'is_local' => true,
        ]);
        $this->insertSchoolWithCounts($year, [
            'npsn' => '33333333',
            'nama' => 'SMP Bandung Tidak Tampil',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Negeri',
            'kota' => 'Kota Bandung',
            'is_local' => false,
        ]);
        $this->insertSchoolWithCounts($year, [
            'npsn' => '44444444',
            'nama' => 'SD Bogor Tidak Tampil',
            'bentuk_pendidikan' => 'SD',
            'status_sp' => 'Negeri',
            'kota' => 'Kota Bogor',
            'is_local' => true,
        ]);
        $dinas = $this->createUser('dinas-cohort-filter', 'Dinas');

        $this->actingAs($dinas)
            ->get(route('laporan.kohort'))
            ->assertOk()
            ->assertViewHas('schools', fn ($schools) => $schools->pluck('nama')->all() === [
                'SMP Uji Kohort',
                'SPK SMP Bogor Raya',
            ])
            ->assertSee('Cari nama sekolah atau NPSN')
            ->assertSee('Seluruh SMP/SPK SMP Kota Bogor')
            ->assertSee('Reset Filter')
            ->assertSee('x-data="cohortSchoolFilter"', false)
            ->assertSee('SPK SMP Bogor Raya')
            ->assertDontSee('SMP Bandung Tidak Tampil')
            ->assertDontSee('SD Bogor Tidak Tampil');
    }

    public function test_longitudinal_cohort_follows_one_intake_from_grade_seven_to_nine(): void
    {
        [$entryYear, $schoolId] = $this->seedSchoolAndYear(true);
        $entryYear->update([
            'name' => '2025/2026',
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
            'starts_on' => '2025-07-15',
            'ends_on' => '2026-07-14',
        ]);
        DB::table('satuan_pendidikan_student_counts')
            ->where('academic_year_id', $entryYear->id)
            ->where('grade', 7)
            ->update([
                'male_count' => 50,
                'female_count' => 50,
                'closing_male_count' => 49,
                'closing_female_count' => 49,
            ]);

        $gradeEightYear = AcademicYear::create([
            'name' => '2026/2027',
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-07-14',
        ]);
        $gradeNineYear = AcademicYear::create([
            'name' => '2027/2028',
            'status' => AcademicYear::STATUS_CLOSED,
            'is_active' => false,
            'starts_on' => '2027-07-15',
            'ends_on' => '2028-07-14',
        ]);

        foreach ([[$gradeEightYear, 8, 98, 97], [$gradeNineYear, 9, 97, 96]] as [$year, $grade, $opening, $closing]) {
            DB::table('satuan_pendidikan_student_counts')->insert([
                'satuan_pendidikan_id' => $schoolId,
                'academic_year_id' => $year->id,
                'grade' => $grade,
                'male_count' => intdiv($opening, 2),
                'female_count' => $opening - intdiv($opening, 2),
                'confirmed_at' => now(),
                'closing_male_count' => intdiv($closing, 2),
                'closing_female_count' => $closing - intdiv($closing, 2),
                'closing_confirmed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'M-LONG-1',
            'tanggal_pengajuan' => '2026-08-01',
            'jumlah_siswa' => 3,
            'npsn_sekolah_tujuan' => '12345678',
            'status' => 'Selesai',
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            ['id_mutasi' => 'M-LONG-1', 'kelas' => 'VIII', 'jenis_kelamin' => 'L'],
            ['id_mutasi' => 'M-LONG-1', 'kelas' => '8', 'jenis_kelamin' => 'P'],
            ['id_mutasi' => 'M-LONG-1', 'kelas' => 'VIII', 'jenis_kelamin' => 'P'],
        ]);
        DB::table('mutasi_keluar')->insert([
            ['id_mutasi' => 'K-LONG-1', 'tanggal_pengajuan' => '2026-09-01', 'npsn_sekolah_asal' => '12345678', 'kelas' => 'VIII', 'jenis_kelamin' => 'L', 'status' => 'Selesai'],
            ['id_mutasi' => 'K-LONG-2', 'tanggal_pengajuan' => '2027-09-01', 'npsn_sekolah_asal' => '12345678', 'kelas' => 'IX', 'jenis_kelamin' => 'L', 'status' => 'Selesai'],
            ['id_mutasi' => 'K-LONG-3', 'tanggal_pengajuan' => '2027-10-01', 'npsn_sekolah_asal' => '12345678', 'kelas' => '9', 'jenis_kelamin' => 'P', 'status' => 'Selesai'],
        ]);
        DB::table('non_mutation_student_exits')->insert([
            ['satuan_pendidikan_id' => $schoolId, 'academic_year_id' => $gradeEightYear->id, 'student_name' => 'Murid Satu', 'nisn' => '1000000001', 'grade' => 8, 'gender' => 'L', 'exit_date' => '2026-10-01', 'reason' => 'Lainnya', 'created_at' => now(), 'updated_at' => now()],
            ['satuan_pendidikan_id' => $schoolId, 'academic_year_id' => $gradeEightYear->id, 'student_name' => 'Murid Dua', 'nisn' => '1000000002', 'grade' => 8, 'gender' => 'P', 'exit_date' => '2026-10-02', 'reason' => 'Lainnya', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $report = app(LongitudinalCohortService::class)->report($entryYear, $schoolId);

        $this->assertSame(3, $report['available_stage_count']);
        $this->assertTrue($report['is_complete']);
        $this->assertSame(100, $report['summary']['initial']);
        $this->assertSame(3, $report['summary']['incoming']);
        $this->assertSame(3, $report['summary']['outgoing']);
        $this->assertSame(2, $report['summary']['non_mutation_outgoing']);
        $this->assertSame(96, $report['summary']['graduated']);
        $this->assertSame(['VII', 'VIII', 'IX'], array_column($report['stages'], 'grade_label'));
        $this->assertSame([100, 98, 97], array_column($report['stages'], 'opening'));
        $this->assertSame([98, 97, 96], array_column($report['stages'], 'actual'));
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
            'student_name' => 'Murid Uji Rahasia',
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
            'student_name' => 'Murid Uji Rahasia',
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
            ->assertDontSee('Murid Uji Rahasia')
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
                'closing_confirmed_at' => $withClosing ? $year->ends_on : null,
                'closing_locked_early' => false,
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
                'closing_confirmed_at' => $year->ends_on ?? now(),
                'closing_locked_early' => false,
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
            'telegram_chat_id' => '123456789',
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
            $table->string('telegram_chat_id')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->string('username', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
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
            $table->boolean('closing_locked_early')->default(false);
            $table->timestamps();
            $table->unique(['satuan_pendidikan_id', 'academic_year_id', 'grade']);
        });
        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->unsignedInteger('jumlah_siswa')->default(1);
            $table->string('npsn_sekolah_tujuan');
            $table->string('status');
            $table->softDeletes();
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
            $table->softDeletes();
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
