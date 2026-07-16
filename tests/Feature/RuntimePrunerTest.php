<?php

namespace Tests\Feature;

use App\Services\RuntimePruner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RuntimePrunerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Storage::fake('local');

        foreach (['cache', 'cache_locks'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->string('key')->primary();
                if ($tableName === 'cache') {
                    $table->mediumText('value');
                }
                $table->integer('expiration');
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_expired_cache_and_abandoned_uploads_are_pruned(): void
    {
        DB::table('cache')->insert([
            ['key' => 'expired', 'value' => 'x', 'expiration' => now()->subMinute()->timestamp],
            ['key' => 'active', 'value' => 'x', 'expiration' => now()->addHour()->timestamp],
        ]);
        DB::table('cache_locks')->insert([
            ['key' => 'expired-lock', 'expiration' => now()->subMinute()->timestamp],
            ['key' => 'active-lock', 'expiration' => now()->addHour()->timestamp],
        ]);

        $oldPath = 'temp_uploads/42/temp_old_document.pdf';
        $newPath = 'temp_uploads/42/temp_new_document.pdf';
        Storage::disk('local')->put($oldPath, "%PDF-1.4\n%%EOF");
        Storage::disk('local')->put($newPath, "%PDF-1.4\n%%EOF");
        touch(Storage::disk('local')->path($oldPath), now()->subDays(8)->timestamp);

        $result = app(RuntimePruner::class)->prune();

        $this->assertSame(['cache' => 1, 'cache_locks' => 1, 'temporary_uploads' => 1], $result);
        $this->assertDatabaseMissing('cache', ['key' => 'expired']);
        $this->assertDatabaseHas('cache', ['key' => 'active']);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);
    }
}
