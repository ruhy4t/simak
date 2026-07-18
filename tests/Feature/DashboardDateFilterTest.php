<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardDateFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Cache::flush();

        Schema::create('satuan_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->string('npsn')->unique();
            $table->string('nama');
            $table->string('bentuk_pendidikan');
            $table->string('status_sp');
            $table->string('kota');
            $table->boolean('is_local')->default(true);
        });

        Schema::create('mutasi_keluar', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_pengajuan')->index();
            $table->string('nama_sekolah_asal');
            $table->string('npsn_sekolah_asal');
            $table->string('status');
            $table->string('pengusul');
            $table->softDeletes();
        });

        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->id();
            $table->string('id_mutasi')->unique();
            $table->date('tanggal_pengajuan')->index();
            $table->unsignedInteger('jumlah_siswa')->default(1);
            $table->string('nama_sekolah_tujuan');
            $table->string('npsn_sekolah_tujuan');
            $table->string('status');
            $table->string('pengusul');
            $table->softDeletes();
        });

        Schema::create('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->id();
            $table->string('id_mutasi');
        });

        DB::table('satuan_pendidikan')->insert([
            'npsn' => '12345678',
            'nama' => 'SMP Test',
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Negeri',
            'kota' => 'KOTA BOGOR',
            'is_local' => true,
        ]);

        $user = new User([
            'username' => 'operator-test',
            'role' => 'Operator',
            'npsn' => '12345678',
            'status' => 'Aktif',
        ]);
        $user->id = 1;
        Auth::setUser($user);

        $this->insertMutationPair('2026-06-30', 'Selesai', 2);
        $this->insertMutationPair('2026-07-05', 'Menunggu Verifikasi Dinas', 3);
        $this->insertMutationPair('2026-07-20', 'Selesai', 4);
    }

    protected function tearDown(): void
    {
        Auth::forgetUser();
        Cache::flush();
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_month_filter_applies_to_summary_cards_and_school_statistics(): void
    {
        $view = app(DashboardController::class)->index(Request::create('/dashboard', 'GET', [
            'period' => 'month',
            'month' => 7,
            'year' => 2026,
        ]));
        $data = $view->getData();

        $this->assertSame(2, $data['statsKeluar']['total']);
        $this->assertSame(2, $data['statsMasuk']['total']);
        $this->assertSame(7, $data['statsMasuk']['total_siswa']);
        $this->assertSame(4, $data['statsMasuk']['selesai_siswa']);
        $this->assertSame(2, (int) $data['schoolStatsKeluar'][0]->jumlah);
        $this->assertSame(7, (int) $data['schoolStatsMasuk'][0]->jumlah);
        $this->assertSame('Juli 2026', $data['dateFilter']['label']);
        $this->assertCount(31, $data['trendChart']['labels']);
        $this->assertSame(2, array_sum($data['trendChart']['masuk']));
        $this->assertSame(2, array_sum($data['trendChart']['keluar']));
        $this->assertSame([2, 0, 0, 0, 2], $data['statusChart']['values']);
    }

    public function test_custom_date_range_is_inclusive_on_both_dates(): void
    {
        $view = app(DashboardController::class)->index(Request::create('/dashboard', 'GET', [
            'period' => 'custom',
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-20',
        ]));
        $data = $view->getData();

        $this->assertSame(2, $data['statsKeluar']['total']);
        $this->assertSame(1, $data['statsKeluar']['menunggu']);
        $this->assertSame(1, $data['statsKeluar']['selesai']);
        $this->assertSame(7, $data['statsMasuk']['total_siswa']);
        $this->assertSame(4, $data['statsMasuk']['selesai_siswa']);
        $this->assertSame('5 Jul 2026 – 20 Jul 2026', $data['dateFilter']['label']);
        $this->assertCount(16, $data['trendChart']['labels']);
        $this->assertSame(1, $data['trendChart']['masuk'][0]);
        $this->assertSame(1, $data['trendChart']['masuk'][15]);
    }

    public function test_repeated_dashboard_request_reuses_the_aggregate_snapshot(): void
    {
        $request = Request::create('/dashboard', 'GET', [
            'period' => 'month',
            'month' => 7,
            'year' => 2026,
        ]);
        $controller = app(DashboardController::class);

        $controller->index($request);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $secondView = $controller->index($request);

        $mutationQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'mutasi_masuk')
                || str_contains($query['query'], 'mutasi_keluar'));

        $this->assertCount(0, $mutationQueries);
        $this->assertSame(2, $secondView->getData()['statsMasuk']['total']);
    }

    public function test_dinas_dashboard_keeps_historical_mutations_even_when_school_reference_is_missing(): void
    {
        $dinas = new User([
            'username' => 'dinas-test',
            'role' => 'Dinas',
            'status' => 'Aktif',
        ]);
        $dinas->id = 2;
        Auth::setUser($dinas);

        DB::table('mutasi_keluar')->insert([
            'tanggal_pengajuan' => '2026-07-25',
            'nama_sekolah_asal' => 'Sekolah Luar Cakupan',
            'npsn_sekolah_asal' => '99999999',
            'status' => 'Selesai',
            'pengusul' => 'operator-luar',
        ]);
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'MM-OUTSIDE',
            'tanggal_pengajuan' => '2026-07-25',
            'jumlah_siswa' => 5,
            'nama_sekolah_tujuan' => 'Sekolah Luar Cakupan',
            'npsn_sekolah_tujuan' => '99999999',
            'status' => 'Selesai',
            'pengusul' => 'operator-luar',
        ]);
        DB::table('mutasi_masuk_siswa')->insert(
            array_fill(0, 5, ['id_mutasi' => 'MM-OUTSIDE'])
        );

        $data = app(DashboardController::class)->index(Request::create('/dashboard', 'GET', [
            'period' => 'month',
            'month' => 7,
            'year' => 2026,
        ]))->getData();

        $this->assertSame(3, $data['statsKeluar']['total']);
        $this->assertSame(3, $data['statsMasuk']['total']);
        $this->assertSame(12, $data['statsMasuk']['total_siswa']);
        $this->assertSame(9, $data['statsMasuk']['selesai_siswa']);
    }

    public function test_incoming_student_total_falls_back_to_header_for_legacy_records_without_details(): void
    {
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'MM-LEGACY',
            'tanggal_pengajuan' => '2026-07-10',
            'jumlah_siswa' => 6,
            'nama_sekolah_tujuan' => 'SMP Test',
            'npsn_sekolah_tujuan' => '12345678',
            'status' => 'Selesai',
            'pengusul' => 'operator-test',
        ]);

        $data = app(DashboardController::class)->index(Request::create('/dashboard', 'GET', [
            'period' => 'month',
            'month' => 7,
            'year' => 2026,
        ]))->getData();

        $this->assertSame(3, $data['statsMasuk']['total']);
        $this->assertSame(13, $data['statsMasuk']['total_siswa']);
        $this->assertSame(10, $data['statsMasuk']['selesai_siswa']);
        $this->assertSame(13, (int) $data['schoolStatsMasuk'][0]->jumlah);
    }

    public function test_all_period_counts_from_the_beginning_through_today_and_excludes_drafts(): void
    {
        CarbonImmutable::setTestNow('2026-07-18 12:00:00');

        try {
            DB::table('mutasi_keluar')->insert([
                'tanggal_pengajuan' => '2026-07-10',
                'nama_sekolah_asal' => 'SMP Test',
                'npsn_sekolah_asal' => '12345678',
                'status' => 'Draft',
                'pengusul' => 'operator-test',
            ]);
            DB::table('mutasi_masuk')->insert([
                'id_mutasi' => 'MM-DRAFT',
                'tanggal_pengajuan' => '2026-07-10',
                'jumlah_siswa' => 8,
                'nama_sekolah_tujuan' => 'SMP Test',
                'npsn_sekolah_tujuan' => '12345678',
                'status' => 'Draft',
                'pengusul' => 'operator-test',
            ]);

            $data = app(DashboardController::class)
                ->index(Request::create('/dashboard', 'GET'))
                ->getData();

            $this->assertSame(2, $data['statsKeluar']['total']);
            $this->assertSame(2, $data['statsMasuk']['total']);
            $this->assertSame(5, $data['statsMasuk']['total_siswa']);
            $this->assertSame('Awal data s.d. 18 Juli 2026', $data['dateFilter']['label']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function insertMutationPair(string $date, string $status, int $studentCount): void
    {
        DB::table('mutasi_keluar')->insert([
            'tanggal_pengajuan' => $date,
            'nama_sekolah_asal' => 'SMP Test',
            'npsn_sekolah_asal' => '12345678',
            'status' => $status,
            'pengusul' => 'operator-test',
        ]);

        $incomingId = 'MM-'.str_replace('-', '', $date).'-'.$studentCount;
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => $incomingId,
            'tanggal_pengajuan' => $date,
            'jumlah_siswa' => $studentCount,
            'nama_sekolah_tujuan' => 'SMP Test',
            'npsn_sekolah_tujuan' => '12345678',
            'status' => $status,
            'pengusul' => 'operator-test',
        ]);

        DB::table('mutasi_masuk_siswa')->insert(
            array_fill(0, $studentCount, ['id_mutasi' => $incomingId])
        );
    }
}
