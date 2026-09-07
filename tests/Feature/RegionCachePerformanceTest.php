<?php

namespace Tests\Feature;

use App\Services\RegionCacheService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegionCachePerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('cache.default', 'array');
        DB::purge('sqlite');
        Cache::flush();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_warm_region_cache_skips_schema_queries_and_keeps_provinces_separate(): void
    {
        Schema::create('ref_provinsi', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nama');
        });
        Schema::create('ref_kabupaten', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('province_id');
            $table->string('nama');
        });
        DB::table('ref_provinsi')->insert(['id' => '32', 'nama' => 'Jawa Barat']);
        DB::table('ref_kabupaten')->insert([
            ['id' => '3271', 'province_id' => '32', 'nama' => 'Kota Bogor'],
            ['id' => '3374', 'province_id' => '33', 'nama' => 'Kota Semarang'],
        ]);
        $service = app(RegionCacheService::class);
        $service->getProvinces();
        $service->getRegencies('32');
        $service->getRegencies('33');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->assertSame('Jawa Barat', $service->getProvinces()['data']->first()->name);
        $this->assertSame('Kota Bogor', $service->getRegencies('32')['data']->first()->name);
        $this->assertSame('Kota Semarang', $service->getRegencies('33')['data']->first()->name);
        $this->assertCount(0, DB::getQueryLog());
        DB::table('ref_kabupaten')->where('id', '3271')->update(['nama' => 'Bogor diperbarui']);
        $service->clearCacheFor('kabupaten', '32');
        $this->assertSame('Bogor diperbarui', $service->getRegencies('32')['data']->first()->name);
        Http::assertNothingSent();
    }

    public function test_missing_local_tables_still_use_external_reference_data(): void
    {
        Http::fake(['*' => Http::response([['id' => '32', 'name' => 'Referensi eksternal']])]);
        $service = app(RegionCacheService::class);
        $this->assertSame('external', $service->getProvinces()['source']);
        $this->assertSame('external', $service->getRegencies('32')['source']);
        $service->getProvinces();
        $service->getRegencies('32');
        Http::assertSentCount(2);
    }
}
