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

    public function test_warm_settings_use_one_database_cache_read_per_request(): void
    {
        (require database_path('migrations/2026_07_14_000000_create_cache_tables_if_missing.php'))->up();
        config()->set('cache.default', 'database');
        Cache::put('settings.all', collect([
            'working_hours_start' => (object) ['value' => '08:00', 'type' => 'string'],
            'working_hours_end' => (object) ['value' => '16:00', 'type' => 'string'],
        ]), 3600);
        DB::flushQueryLog();
        DB::enableQueryLog();
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('08:00', Setting::get('working_hours_start'));
            $this->assertSame('16:00', Setting::get('working_hours_end'));
        }
        $this->assertCount(1, DB::getQueryLog());

        // A new request must observe settings changed by another request.
        Cache::put('settings.all', collect([
            'working_hours_start' => (object) ['value' => '07:30', 'type' => 'string'],
        ]), 3600);
        $this->app->forgetScopedInstances();
        $this->assertSame('07:30', Setting::get('working_hours_start'));
    }

    public function test_non_holidays_are_cached_and_invalidated_when_a_holiday_is_added(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('description');
            $table->timestamps();
        });
        DB::flushQueryLog();
        DB::enableQueryLog();
        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse(Holiday::isHoliday('2026-09-07'));
            $this->assertNull(Holiday::getDescription('2026-09-07'));
        }
        $this->assertCount(1, DB::getQueryLog());
        // SQLite does not truncate datetime strings in a DATE column as MySQL does.
        $holiday = new Holiday;
        $holiday->setDateFormat('Y-m-d');
        $holiday->fill(['date' => '2026-09-07', 'description' => 'Libur tambahan'])->save();
        $this->assertTrue(Holiday::isHoliday('2026-09-07'));
        $this->assertSame('Libur tambahan', Holiday::getDescription('2026-09-07'));
    }

    public function test_mutation_month_scope_uses_an_index_friendly_date_range(): void
    {
        $query = MutasiKeluar::query()->byMonth('2026-07');

        $this->assertSame(['2026-07-01', '2026-08-01'], $query->getBindings());
        $this->assertStringNotContainsString('month(', strtolower($query->toSql()));
        $this->assertStringNotContainsString('year(', strtolower($query->toSql()));
    }

    public function test_mutation_list_ordering_indexes_are_idempotent(): void
    {
        Schema::create('mutasi_masuk', function (Blueprint $table): void {
            $table->string('id_mutasi')->primary();
            $table->string('npsn_sekolah_tujuan');
            $table->string('pengusul');
            $table->timestamp('last_submitted_at')->nullable();
        });
        Schema::create('mutasi_keluar', function (Blueprint $table): void {
            $table->string('id_mutasi')->primary();
            $table->string('npsn_sekolah_asal');
            $table->string('pengusul');
            $table->timestamp('last_submitted_at')->nullable();
        });

        $migration = require database_path('migrations/2026_11_13_160000_add_mutation_list_ordering_indexes.php');
        $migration->up();
        $migration->up();

        foreach ([
            'mutasi_masuk' => ['idx_mm_school_list', 'idx_mm_operator_list'],
            'mutasi_keluar' => ['idx_mk_school_list', 'idx_mk_operator_list'],
        ] as $tableName => $indexNames) {
            foreach ($indexNames as $indexName) {
                $this->assertTrue(Schema::hasIndex($tableName, $indexName));
            }
        }
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
