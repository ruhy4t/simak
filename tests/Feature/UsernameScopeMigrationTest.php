<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsernameScopeMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_it_removes_legacy_global_username_index_and_keeps_npsn_scope(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username');
            $table->string('npsn', 8)->nullable();
            $table->unique('username', 'username');
            $table->unique(['username', 'npsn'], 'users_username_npsn_unique');
        });

        $migration = require database_path('migrations/2026_07_22_000000_fix_users_username_indexes.php');
        $migration->up();

        $uniqueIndexes = collect(Schema::getIndexes('users'))->filter(
            fn (array $index): bool => $index['unique'] && !$index['primary'],
        );

        $this->assertFalse($uniqueIndexes->contains(
            fn (array $index): bool => $index['columns'] === ['username'],
        ));
        $this->assertTrue($uniqueIndexes->contains(
            fn (array $index): bool => $index['columns'] === ['username', 'npsn'],
        ));

        DB::table('users')->insert([
            ['username' => 'operator', 'npsn' => '20200001'],
            ['username' => 'operator', 'npsn' => '20200002'],
        ]);

        $this->assertDatabaseCount('users', 2);
    }
}
