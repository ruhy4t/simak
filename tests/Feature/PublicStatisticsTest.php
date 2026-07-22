<?php

namespace Tests\Feature;

use App\Services\MutationStatisticsCache;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicStatisticsTest extends TestCase
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
            $table->string('kota')->nullable();
            $table->boolean('is_local')->default(true);
        });

        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->unsignedInteger('jumlah_siswa')->default(1);
            $table->string('npsn_sekolah_tujuan')->nullable();
            $table->string('status');
            $table->softDeletes();
        });

        Schema::create('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->id();
            $table->string('id_mutasi');
            $table->boolean('asal_luar_negeri')->default(false);
            $table->string('kabupaten_kota_asal')->nullable();
            $table->string('kelas')->nullable();
        });

        Schema::create('mutasi_keluar', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_asal')->nullable();
            $table->string('provinsi_tujuan')->nullable();
            $table->string('kabupaten_kota_tujuan')->nullable();
            $table->string('kelas')->nullable();
            $table->string('status');
            $table->softDeletes();
        });

        $this->insertSchool('12345678', 'SMP Negeri Contoh', 'Negeri');
        $this->seedStatistics();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_public_statistics_are_available_to_guests_with_aggregate_data_only(): void
    {
        $response = $this->get('/statistik-mutasi?year=2026&month=7');

        $response
            ->assertOk()
            ->assertViewIs('public.statistics')
            ->assertSee('Statistik Publik Mutasi Murid')
            ->assertSee('Tentang data')
            ->assertSee('Mutasi per Kelas')
            ->assertSee('Kelas VII')
            ->assertSee('Masuk 1')
            ->assertSee('Keluar 1')
            ->assertDontSee('Tidak terklasifikasi')
            ->assertDontSee('Nama Murid')
            ->assertDontSee('NISN')
            ->assertHeader('Cache-Control', 'max-age=60, public, s-maxage=300, stale-while-revalidate=600')
            ->assertViewHas('chartData', function (array $data) {
                return count($data['labels']) === 31
                    && array_sum($data['masuk']['dalam']) === 1
                    && array_sum($data['masuk']['luar_kota']) === 1
                    && array_sum($data['masuk']['luar_negeri']) === 1
                    && array_sum($data['keluar']['dalam']) === 1
                    && array_sum($data['keluar']['luar_kota']) === 1
                    && array_sum($data['keluar']['luar_negeri']) === 1;
            });

        $this->assertSame(2, substr_count($response->getContent(), 'data-report-trend-chart'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-class-distribution-chart'));
        $this->assertSame([1, 1, 1], $response->viewData('chartData')['kelas']['masuk']);
        $this->assertSame([1, 1, 1], $response->viewData('chartData')['kelas']['keluar']);
    }

    public function test_public_statistics_validate_filters_and_use_rate_limiting(): void
    {
        $this->from('/statistik-mutasi')
            ->get('/statistik-mutasi?year=2019&month=13')
            ->assertRedirect('/statistik-mutasi')
            ->assertSessionHasErrors(['year', 'month']);

        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->getName() === 'public.statistics');

        $this->assertNotNull($route);
        $this->assertContains('throttle:public-statistics', $route->gatherMiddleware());
        $this->assertNotContains('auth', $route->gatherMiddleware());
    }

    public function test_status_filter_is_used_only_when_npsn_is_empty(): void
    {
        $this->insertSchool('87654321', 'SMP Swasta Contoh', 'Swasta');
        $this->insertIncoming('M-SWASTA', '2026-07-09', 'Selesai', false, 'KOTA BOGOR', '87654321');
        $this->insertOutgoing('K-SWASTA', '2026-07-10', 'Selesai', 'JAWA BARAT', 'KOTA BOGOR', '87654321');

        $statusData = $this->get('/statistik-mutasi?year=2026&month=7&school_status=Swasta')
            ->assertOk()
            ->assertViewHas('schoolStatus', 'Swasta')
            ->viewData('chartData');

        $npsnResponse = $this->get('/statistik-mutasi?year=2026&month=7&school_status=Negeri&npsn=87654321');
        $npsnData = $npsnResponse
            ->assertOk()
            ->assertViewHas('schoolStatus', null)
            ->assertViewHas('selectedSchool', fn ($school) => $school?->npsn === '87654321')
            ->assertSee('Diabaikan karena NPSN digunakan.')
            ->viewData('chartData');

        $this->assertSame(1, $this->total($statusData['masuk']));
        $this->assertSame(1, $this->total($statusData['keluar']));
        $this->assertSame($statusData, $npsnData);
    }

    public function test_npsn_must_be_exact_and_belong_to_an_eligible_bogor_city_junior_high_school(): void
    {
        $this->from('/statistik-mutasi')
            ->get('/statistik-mutasi?npsn=123')
            ->assertRedirect('/statistik-mutasi')
            ->assertSessionHasErrors('npsn');

        $this->from('/statistik-mutasi')
            ->get('/statistik-mutasi?npsn=99999999')
            ->assertRedirect('/statistik-mutasi')
            ->assertSessionHasErrors('npsn');
    }

    public function test_public_statistics_are_cached(): void
    {
        $first = $this->get('/statistik-mutasi?year=2026&month=7')->viewData('chartData');

        $this->insertIncoming('M-CACHED', '2026-07-15', 'Selesai', false, 'KOTA BOGOR');

        $second = $this->get('/statistik-mutasi?year=2026&month=7')->viewData('chartData');

        $this->assertSame(3, $this->total($first['masuk']));
        $this->assertSame($first, $second);
    }

    public function test_completed_mutation_invalidates_public_statistics_snapshot(): void
    {
        $first = $this->get('/statistik-mutasi?year=2026&month=7')->viewData('chartData');
        $this->assertSame(3, $this->total($first['masuk']));

        $this->insertIncoming('M-REFRESHED', '2026-07-15', 'Selesai', false, 'KOTA BOGOR');
        MutationStatisticsCache::invalidate();

        $refreshed = $this->get('/statistik-mutasi?year=2026&month=7')->viewData('chartData');
        $this->assertSame(4, $this->total($refreshed['masuk']));
    }

    public function test_every_completed_outgoing_mutation_is_included_when_legacy_destination_is_empty(): void
    {
        $this->insertOutgoing('K-LEGACY-EMPTY', '2026-07-10', 'Selesai', null, null);
        $this->insertOutgoing('K-LEGACY-CASE', '2026-07-11', 'Selesai', ' luar negeri ', null);

        $chartData = $this->get('/statistik-mutasi?year=2026&month=7')
            ->assertOk()
            ->viewData('chartData');

        $dashboardStyleTotal = DB::table('mutasi_keluar')
            ->where('status', 'Selesai')
            ->where('npsn_sekolah_asal', '12345678')
            ->count();

        $this->assertSame($dashboardStyleTotal, $this->total($chartData['keluar']));
        $this->assertSame(2, array_sum($chartData['keluar']['luar_kota']));
        $this->assertSame(2, array_sum($chartData['keluar']['luar_negeri']));
    }

    public function test_unfiltered_totals_match_dashboard_scope_and_keep_legacy_incoming_students(): void
    {
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'M-LEGACY-NO-DETAIL',
            'tanggal_pengajuan' => '2026-07-12',
            'jumlah_siswa' => 4,
            'npsn_sekolah_tujuan' => '99999999',
            'status' => 'Selesai',
        ]);
        $this->insertOutgoing('K-MISSING-REFERENCE', '2026-07-13', 'Selesai', 'JAWA BARAT', 'KOTA BANDUNG', '99999999');
        $this->insertIncoming('M-DELETED', '2026-07-14', 'Selesai', false, 'KOTA BOGOR');
        DB::table('mutasi_masuk')->where('id_mutasi', 'M-DELETED')->update(['deleted_at' => now()]);
        $this->insertOutgoing('K-DELETED', '2026-07-14', 'Selesai', 'JAWA BARAT', 'KOTA BOGOR');
        DB::table('mutasi_keluar')->where('id_mutasi', 'K-DELETED')->update(['deleted_at' => now()]);

        $unfiltered = $this->get('/statistik-mutasi?year=2026&month=7')
            ->assertOk()
            ->viewData('chartData');
        $filtered = $this->get('/statistik-mutasi?year=2026&month=7&school_status=Negeri')
            ->assertOk()
            ->viewData('chartData');

        $this->assertSame(7, $unfiltered['totals']['masuk']);
        $this->assertSame(4, $unfiltered['totals']['keluar']);
        $this->assertSame(3, $filtered['totals']['masuk']);
        $this->assertSame(3, $filtered['totals']['keluar']);
    }

    private function seedStatistics(): void
    {
        $this->insertIncoming('M-1', '2026-07-01', 'Selesai', false, 'KOTA BOGOR', '12345678', 'VII');
        $this->insertIncoming('M-2', '2026-07-02', 'Selesai', false, 'KOTA BANDUNG', '12345678', 'VIII');
        $this->insertIncoming('M-3', '2026-07-03', 'Selesai', true, null, '12345678', 'IX');
        $this->insertIncoming('M-4', '2026-07-04', 'Menunggu Verifikasi Dinas', false, 'KOTA BOGOR', '12345678', 'VII');

        $this->insertOutgoing('K-1', '2026-07-05', 'Selesai', 'JAWA BARAT', 'KOTA BOGOR', '12345678', 'VII');
        $this->insertOutgoing('K-2', '2026-07-06', 'Selesai', 'JAWA BARAT', 'KOTA BANDUNG', '12345678', 'VIII');
        $this->insertOutgoing('K-3', '2026-07-07', 'Selesai', 'LUAR NEGERI', null, '12345678', 'IX');
        $this->insertOutgoing('K-4', '2026-07-08', 'Draft', 'JAWA BARAT', 'KOTA BOGOR', '12345678', 'VII');
    }

    private function insertSchool(string $npsn, string $name, string $status): void
    {
        DB::table('satuan_pendidikan')->insert([
            'npsn' => $npsn,
            'nama' => $name,
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => $status,
            'kota' => 'KOTA BOGOR',
            'is_local' => true,
        ]);
    }

    private function insertIncoming(
        string $id,
        string $date,
        string $status,
        bool $foreign,
        ?string $city,
        string $npsn = '12345678',
        ?string $class = 'VII'
    ): void {
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => $id,
            'tanggal_pengajuan' => $date,
            'npsn_sekolah_tujuan' => $npsn,
            'status' => $status,
        ]);

        DB::table('mutasi_masuk_siswa')->insert([
            'id_mutasi' => $id,
            'asal_luar_negeri' => $foreign,
            'kabupaten_kota_asal' => $city,
            'kelas' => $class,
        ]);
    }

    private function insertOutgoing(
        string $id,
        string $date,
        string $status,
        ?string $province,
        ?string $city,
        string $npsn = '12345678',
        ?string $class = 'VII'
    ): void {
        DB::table('mutasi_keluar')->insert([
            'id_mutasi' => $id,
            'tanggal_pengajuan' => $date,
            'npsn_sekolah_asal' => $npsn,
            'provinsi_tujuan' => $province,
            'kabupaten_kota_tujuan' => $city,
            'kelas' => $class,
            'status' => $status,
        ]);
    }

    private function total(array $groups): int
    {
        return collect($groups)->flatten()->sum();
    }
}
