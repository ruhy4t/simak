<?php

namespace Tests\Feature;

use App\Http\Controllers\LaporanController;
use App\Models\SatuanPendidikan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class LaporanRekapPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('mutasi_masuk', function (Blueprint $table): void {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_tujuan');
            $table->string('nama_sekolah_tujuan')->nullable();
            $table->string('status');
        });
        Schema::create('mutasi_masuk_siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('id_mutasi');
            $table->string('nama_murid')->nullable();
            $table->string('nisn')->nullable();
            $table->string('kelas');
            $table->string('jenis_kelamin');
            $table->string('nama_sekolah_asal')->nullable();
            $table->string('npsn_sekolah_asal')->nullable();
        });
        Schema::create('mutasi_keluar', function (Blueprint $table): void {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_asal');
            $table->string('npsn_sekolah_tujuan')->nullable();
            $table->string('nama_murid')->nullable();
            $table->string('nisn')->nullable();
            $table->string('nama_sekolah_asal')->nullable();
            $table->string('nama_sekolah_tujuan')->nullable();
            $table->string('kelas');
            $table->string('jenis_kelamin');
            $table->string('status');
            $table->softDeletes();
        });
        Schema::create('satuan_pendidikan', function (Blueprint $table): void {
            $table->id();
            $table->string('npsn')->unique();
            $table->string('nama');
            $table->boolean('is_local')->default(true);
            $table->string('kota');
            $table->string('bentuk_pendidikan');
            $table->string('status_sp');
            $table->unsignedInteger('jumlah_siswa_7_l')->default(0);
            $table->unsignedInteger('jumlah_siswa_7_p')->default(0);
            $table->unsignedInteger('jumlah_siswa_8_l')->default(0);
            $table->unsignedInteger('jumlah_siswa_8_p')->default(0);
            $table->unsignedInteger('jumlah_siswa_9_l')->default(0);
            $table->unsignedInteger('jumlah_siswa_9_p')->default(0);
        });

        DB::table('mutasi_masuk')->insert([
            ['id_mutasi' => 'M-JAN', 'tanggal_pengajuan' => '2026-01-20', 'npsn_sekolah_tujuan' => '12345678', 'status' => 'Selesai'],
            ['id_mutasi' => 'M-MAR', 'tanggal_pengajuan' => '2026-03-05', 'npsn_sekolah_tujuan' => '12345678', 'status' => 'Selesai'],
            ['id_mutasi' => 'M-PENDING', 'tanggal_pengajuan' => '2026-03-06', 'npsn_sekolah_tujuan' => '12345678', 'status' => 'Menunggu Verifikasi Dinas'],
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            ['id_mutasi' => 'M-JAN', 'kelas' => 'VII', 'jenis_kelamin' => 'L'],
            ['id_mutasi' => 'M-MAR', 'kelas' => 'IX', 'jenis_kelamin' => 'P'],
            ['id_mutasi' => 'M-PENDING', 'kelas' => 'VIII', 'jenis_kelamin' => 'L'],
        ]);
        DB::table('mutasi_keluar')->insert([
            [
                'id_mutasi' => 'K-FEB',
                'tanggal_pengajuan' => '2026-02-10',
                'npsn_sekolah_asal' => '12345678',
                'kelas' => 'VIII',
                'jenis_kelamin' => 'P',
                'status' => 'Selesai',
            ],
            [
                'id_mutasi' => 'K-PENDING',
                'tanggal_pengajuan' => '2026-02-11',
                'npsn_sekolah_asal' => '12345678',
                'kelas' => 'VII',
                'jenis_kelamin' => 'L',
                'status' => 'Proses TTD',
            ],
        ]);
        DB::table('satuan_pendidikan')->insert([
            'npsn' => '12345678',
            'nama' => 'SMP Uji Bogor',
            'is_local' => true,
            'kota' => 'KOTA BOGOR',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Negeri',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_school_rekap_aggregates_the_complete_range_in_two_queries(): void
    {
        $school = new SatuanPendidikan([
            'npsn' => '12345678',
            'jumlah_siswa_7_l' => 10,
            'jumlah_siswa_7_p' => 11,
            'jumlah_siswa_8_l' => 12,
            'jumlah_siswa_8_p' => 13,
            'jumlah_siswa_9_l' => 14,
            'jumlah_siswa_9_p' => 15,
        ]);
        $user = new User(['role' => 'Operator', 'npsn' => '12345678']);
        $user->setRelation('sekolah', $school);

        DB::enableQueryLog();
        $method = new ReflectionMethod(LaporanController::class, 'getRekapData');
        $report = $method->invoke(app(LaporanController::class), '2026-01-15', '2026-03-10', $user);

        $aggregateQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'mutasi_masuk')
                || str_contains($query['query'], 'mutasi_keluar'));

        $this->assertCount(2, $aggregateQueries);
        $this->assertCount(3, $report);
        $this->assertSame(1, $report[0]['masuk']);
        $this->assertSame(1, $report[1]['keluar']);
        $this->assertSame(1, $report[2]['masuk']);
    }

    public function test_school_monthly_opening_balance_matches_previous_month_closing_balance(): void
    {
        $school = new SatuanPendidikan([
            'npsn' => '12345678',
            'jumlah_siswa_7_l' => 10,
            'jumlah_siswa_7_p' => 11,
            'jumlah_siswa_8_l' => 12,
            'jumlah_siswa_8_p' => 13,
            'jumlah_siswa_9_l' => 14,
            'jumlah_siswa_9_p' => 15,
        ]);
        $user = new User(['role' => 'Operator', 'npsn' => '12345678']);
        $user->setRelation('sekolah', $school);

        $method = new ReflectionMethod(LaporanController::class, 'getRekapData');
        $report = $method->invoke(app(LaporanController::class), '2026-01-01', '2026-03-31', $user);

        $this->assertSame($report[0]['akhir'], $report[1]['awal']);
        $this->assertSame($report[1]['akhir'], $report[2]['awal']);

        foreach (['vii', 'viii', 'ix'] as $grade) {
            $this->assertSame($report[0]["akhir_{$grade}"], $report[1]["awal_{$grade}"]);
            $this->assertSame($report[1]["akhir_{$grade}"], $report[2]["awal_{$grade}"]);
        }

        $this->assertSame(76, $report[0]['akhir']);
        $this->assertSame(76, $report[1]['awal']);
        $this->assertSame(75, $report[1]['akhir']);
        $this->assertSame(75, $report[2]['awal']);
        $this->assertSame(76, $report[2]['akhir']);
    }

    public function test_single_month_report_includes_completed_mutations_from_previous_months(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
        });
        Schema::create('satuan_pendidikan_student_counts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('satuan_pendidikan_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedTinyInteger('grade');
            $table->unsignedInteger('male_count');
            $table->unsignedInteger('female_count');
        });

        $academicYearId = DB::table('academic_years')->insertGetId([
            'name' => '2025/2026',
            'starts_on' => '2025-07-01',
            'ends_on' => '2026-06-30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('satuan_pendidikan_student_counts')->insert([
            ['satuan_pendidikan_id' => 1, 'academic_year_id' => $academicYearId, 'grade' => 7, 'male_count' => 10, 'female_count' => 11],
            ['satuan_pendidikan_id' => 1, 'academic_year_id' => $academicYearId, 'grade' => 8, 'male_count' => 12, 'female_count' => 13],
            ['satuan_pendidikan_id' => 1, 'academic_year_id' => $academicYearId, 'grade' => 9, 'male_count' => 14, 'female_count' => 15],
        ]);

        $school = SatuanPendidikan::findOrFail(1);
        $user = new User(['role' => 'Operator', 'npsn' => '12345678']);
        $user->setRelation('sekolah', $school);

        $method = new ReflectionMethod(LaporanController::class, 'getRekapData');
        $february = $method->invoke(app(LaporanController::class), '2026-02-01', '2026-02-28', $user);
        $january = $method->invoke(app(LaporanController::class), '2026-01-01', '2026-01-31', $user);

        $this->assertCount(1, $february);
        $this->assertSame($january[0]['akhir'], $february[0]['awal']);
        $this->assertSame($january[0]['akhir_vii'], $february[0]['awal_vii']);
        $this->assertSame(76, $february[0]['awal']);
        $this->assertSame(75, $february[0]['akhir']);
    }

    public function test_eligible_npsns_are_materialized_once_for_cross_collation_safe_queries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $controller = app(LaporanController::class);
        $method = new ReflectionMethod(LaporanController::class, 'eligibleBogorJuniorHighNpsns');

        $this->assertSame(['12345678'], $method->invoke($controller));
        $this->assertSame(['12345678'], $method->invoke($controller));

        $eligibleSchoolQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'satuan_pendidikan'));

        $this->assertCount(1, $eligibleSchoolQueries);
    }

    public function test_citywide_rekap_excludes_school_without_completed_mutation(): void
    {
        DB::table('satuan_pendidikan')->insert([
            'npsn' => '87654321',
            'nama' => 'SMP Tanpa Mutasi Selesai',
            'is_local' => true,
            'kota' => 'KOTA BOGOR',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Swasta',
            'jumlah_siswa_7_l' => 100,
        ]);
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'M-UNFINISHED-SCHOOL',
            'tanggal_pengajuan' => '2026-02-15',
            'npsn_sekolah_tujuan' => '87654321',
            'status' => 'Proses TTD',
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            'id_mutasi' => 'M-UNFINISHED-SCHOOL',
            'kelas' => 'VII',
            'jenis_kelamin' => 'L',
        ]);

        $user = new User(['role' => 'Dinas']);
        $method = new ReflectionMethod(LaporanController::class, 'getRekapData');
        $report = $method->invoke(app(LaporanController::class), '2026-01-01', '2026-12-31', $user);

        $this->assertSame(['SMP Uji Bogor'], array_column($report, 'nama_sekolah'));
    }

    public function test_school_nominative_queries_use_materialized_eligible_npsns(): void
    {
        $school = new SatuanPendidikan(['npsn' => '12345678']);
        $user = new User(['role' => 'Operator', 'npsn' => '12345678']);
        $user->setRelation('sekolah', $school);

        $controller = app(LaporanController::class);
        $method = new ReflectionMethod(LaporanController::class, 'getNominatifData');

        $incoming = $method->invoke(
            $controller,
            '2026-01-01',
            '2026-03-31',
            'masuk',
            $user,
        );
        $outgoing = $method->invoke(
            $controller,
            '2026-01-01',
            '2026-03-31',
            'keluar',
            $user,
        );

        $this->assertCount(2, $incoming);
        $this->assertCount(1, $outgoing);
        $this->assertTrue($incoming->every(fn (object $item): bool => $item->jenis === 'masuk'));
        $this->assertTrue($outgoing->every(fn (object $item): bool => $item->jenis === 'keluar'));
        $this->assertTrue($incoming->every(fn (object $item): bool => $item->status === 'Selesai'));
        $this->assertTrue($outgoing->every(fn (object $item): bool => $item->status === 'Selesai'));
    }

    public function test_all_report_roles_support_filled_and_manual_supervisor_signatures(): void
    {
        $user = new User([
            'role' => 'Dinas',
            'username' => 'petugas-laporan',
            'nama_pengguna' => 'Siti Aminah',
        ]);
        $method = new ReflectionMethod(LaporanController::class, 'printIdentity');

        $filled = $method->invoke(
            app(LaporanController::class),
            Request::create('/laporan/rekap/print', 'POST', [
                'atasan_jabatan' => 'Kepala Bidang SMP',
                'atasan_nama' => 'Ahmad Hidayat',
                'atasan_nip' => '197001011999031001',
            ]),
            $user,
        );

        $this->assertSame('Siti Aminah', $filled['printer']['nama']);
        $this->assertSame('Kepala Bidang SMP', $filled['supervisor']['jabatan']);
        $this->assertSame('Ahmad Hidayat', $filled['supervisor']['nama']);
        $this->assertSame('197001011999031001', $filled['supervisor']['nip']);

        $manual = $method->invoke(
            app(LaporanController::class),
            Request::create('/laporan/rekap/print', 'POST'),
            $user,
        );
        $rendered = view('laporan.partials.print-signatures', ['printIdentity' => $manual])->render();

        $this->assertNull($manual['supervisor']['nama']);
        $this->assertStringContainsString('Jabatan: ______________________________', $rendered);
        $this->assertStringContainsString('Nama: _________________________________', $rendered);
        $this->assertStringContainsString('Siti Aminah', $rendered);

        $operator = new User([
            'role' => 'Operator',
            'username' => 'operator-sekolah',
            'nama_pengguna' => 'Rina Kartika',
        ]);
        $schoolPrint = $method->invoke(
            app(LaporanController::class),
            Request::create('/laporan/rekap/print', 'POST', [
                'atasan_jabatan' => 'Kepala Sekolah',
                'atasan_nama' => 'Budi Santoso',
                'atasan_nip' => '196801011994031002',
            ]),
            $operator,
        );
        $schoolRendered = view('laporan.partials.print-signatures', ['printIdentity' => $schoolPrint])->render();

        $this->assertTrue($schoolPrint['is_school']);
        $this->assertSame('Budi Santoso', $schoolPrint['supervisor']['nama']);
        $this->assertStringContainsString('Kepala Sekolah', $schoolRendered);
        $this->assertStringContainsString('Rina Kartika', $schoolRendered);
    }
}
