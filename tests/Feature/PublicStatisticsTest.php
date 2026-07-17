<?php

namespace Tests\Feature;

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

        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_tujuan')->nullable();
            $table->string('status');
        });

        Schema::create('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->id();
            $table->string('id_mutasi');
            $table->boolean('asal_luar_negeri')->default(false);
            $table->string('kabupaten_kota_asal')->nullable();
        });

        Schema::create('mutasi_keluar', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_asal')->nullable();
            $table->string('provinsi_tujuan')->nullable();
            $table->string('kabupaten_kota_tujuan')->nullable();
            $table->string('status');
        });

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
            ->assertSee('Statistik Publik Mutasi Siswa')
            ->assertSee('Ringkasan agregat mutasi selesai dan siswa keluar non-mutasi yang telah tercatat')
            ->assertDontSee('Nama Siswa')
            ->assertDontSee('NISN')
            ->assertHeader('Cache-Control', 'max-age=300, public, s-maxage=1800')
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

    public function test_public_statistics_are_cached(): void
    {
        $first = $this->get('/statistik-mutasi?year=2026&month=7')->viewData('chartData');

        $this->insertIncoming('M-CACHED', '2026-07-15', 'Selesai', false, 'KOTA BOGOR');

        $second = $this->get('/statistik-mutasi?year=2026&month=7')->viewData('chartData');

        $this->assertSame(3, $this->total($first['masuk']));
        $this->assertSame($first, $second);
    }

    private function seedStatistics(): void
    {
        $this->insertIncoming('M-1', '2026-07-01', 'Selesai', false, 'KOTA BOGOR');
        $this->insertIncoming('M-2', '2026-07-02', 'Selesai', false, 'KOTA BANDUNG');
        $this->insertIncoming('M-3', '2026-07-03', 'Selesai', true, null);
        $this->insertIncoming('M-4', '2026-07-04', 'Menunggu Verifikasi Dinas', false, 'KOTA BOGOR');

        $this->insertOutgoing('K-1', '2026-07-05', 'Selesai', 'JAWA BARAT', 'KOTA BOGOR');
        $this->insertOutgoing('K-2', '2026-07-06', 'Selesai', 'JAWA BARAT', 'KOTA BANDUNG');
        $this->insertOutgoing('K-3', '2026-07-07', 'Selesai', 'LUAR NEGERI', null);
        $this->insertOutgoing('K-4', '2026-07-08', 'Draft', 'JAWA BARAT', 'KOTA BOGOR');
    }

    private function insertIncoming(string $id, string $date, string $status, bool $foreign, ?string $city): void
    {
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => $id,
            'tanggal_pengajuan' => $date,
            'npsn_sekolah_tujuan' => '12345678',
            'status' => $status,
        ]);

        DB::table('mutasi_masuk_siswa')->insert([
            'id_mutasi' => $id,
            'asal_luar_negeri' => $foreign,
            'kabupaten_kota_asal' => $city,
        ]);
    }

    private function insertOutgoing(string $id, string $date, string $status, ?string $province, ?string $city): void
    {
        DB::table('mutasi_keluar')->insert([
            'id_mutasi' => $id,
            'tanggal_pengajuan' => $date,
            'npsn_sekolah_asal' => '12345678',
            'provinsi_tujuan' => $province,
            'kabupaten_kota_tujuan' => $city,
            'status' => $status,
        ]);
    }

    private function total(array $groups): int
    {
        return collect($groups)->flatten()->sum();
    }
}
