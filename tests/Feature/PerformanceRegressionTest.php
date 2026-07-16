<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Holiday;
use App\Models\MutasiKeluar;
use App\Models\Setting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('cache.default', 'array');

        DB::purge('sqlite');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_settings_are_loaded_once_and_invalidated_after_an_update(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'allowed_days', 'value' => '1,2,3,4,5', 'type' => 'json'],
            ['key' => 'working_hours_start', 'value' => '08:00', 'type' => 'string'],
            ['key' => 'working_hours_end', 'value' => '16:00', 'type' => 'string'],
        ]);

        DB::enableQueryLog();

        $this->assertSame([1, 2, 3, 4, 5], Setting::getAllowedDays());
        $this->assertSame('08:00', Setting::get('working_hours_start'));
        $this->assertSame('16:00', Setting::get('working_hours_end'));

        $selects = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'select'));

        $this->assertCount(1, $selects);

        Setting::set('working_hours_start', '07:30');

        $this->assertSame('07:30', Setting::get('working_hours_start'));
    }

    public function test_holiday_status_and_description_share_one_lookup_and_updates_invalidate_it(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('description');
            $table->timestamps();
        });

        $holidayId = DB::table('holidays')->insertGetId([
            'date' => '2026-08-17',
            'description' => 'Hari Kemerdekaan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $holiday = Holiday::findOrFail($holidayId);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertTrue(Holiday::isHoliday('2026-08-17'));
        $this->assertSame('Hari Kemerdekaan', Holiday::getDescription('2026-08-17'));

        $selects = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'select'));

        $this->assertCount(1, $selects);

        $holiday->update(['description' => 'HUT Republik Indonesia']);

        $this->assertSame('HUT Republik Indonesia', Holiday::getDescription('2026-08-17'));

        $holiday->update(['date' => '2026-08-18']);

        $this->assertFalse(Holiday::isHoliday('2026-08-17'));
    }

    public function test_mutation_month_scope_uses_an_index_friendly_date_range(): void
    {
        $query = MutasiKeluar::query()->byMonth('2026-07');

        $this->assertSame(['2026-07-01', '2026-08-01'], $query->getBindings());
        $this->assertStringNotContainsString('month(', strtolower($query->toSql()));
        $this->assertStringNotContainsString('year(', strtolower($query->toSql()));
    }

    public function test_shared_cache_tables_can_be_created_without_affecting_existing_tables(): void
    {
        $migration = require database_path('migrations/2026_07_14_000000_create_cache_tables_if_missing.php');

        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('cache_locks'));
    }

    public function test_submission_timeline_migration_backfills_existing_mutations(): void
    {
        foreach (['mutasi_keluar', 'mutasi_masuk'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->string('id_mutasi')->primary();
                $table->date('tanggal_pengajuan');
                $table->timestamps();
            });

            DB::table($tableName)->insert([
                'id_mutasi' => $tableName === 'mutasi_keluar' ? 'MK-001' : 'MM-001',
                'tanggal_pengajuan' => '2026-07-01',
                'created_at' => '2026-07-01 08:30:00',
                'updated_at' => '2026-07-01 08:30:00',
            ]);
        }

        $migration = require database_path('migrations/2026_07_16_120000_add_submission_timeline_to_mutasi_tables.php');
        $migration->up();
        $migration->up();

        foreach (['mutasi_keluar', 'mutasi_masuk'] as $tableName) {
            $mutation = DB::table($tableName)->first();

            $this->assertTrue(Schema::hasColumn($tableName, 'last_submitted_at'));
            $this->assertTrue(Schema::hasColumn($tableName, 'revision_count'));
            $this->assertSame('2026-07-01 08:30:00', $mutation->last_submitted_at);
            $this->assertSame(0, $mutation->revision_count);
        }
    }

    public function test_dashboard_available_years_are_reused_from_cache(): void
    {
        Schema::create('mutasi_keluar', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_pengajuan')->nullable();
        });
        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_pengajuan')->nullable();
        });

        DB::table('mutasi_keluar')->insert(['tanggal_pengajuan' => '2024-07-01']);
        DB::table('mutasi_masuk')->insert(['tanggal_pengajuan' => '2025-07-01']);

        $method = new \ReflectionMethod(DashboardController::class, 'getAvailableYears');
        $controller = app(DashboardController::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $first = $method->invoke($controller);
        $second = $method->invoke($controller);

        $selects = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'select'));

        $this->assertContains(2024, $first);
        $this->assertContains(2025, $first);
        $this->assertSame($first, $second);
        $this->assertCount(1, $selects);
    }
}
