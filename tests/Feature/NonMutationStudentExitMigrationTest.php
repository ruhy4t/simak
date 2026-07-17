<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NonMutationStudentExitMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('satuan_pendidikan', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_individual_non_mutation_exit_table_can_be_created_and_removed(): void
    {
        $migration = require database_path('migrations/2026_07_17_130000_create_non_mutation_student_exits_table.php');

        $migration->up();
        $this->assertTrue(Schema::hasColumns('non_mutation_student_exits', [
            'satuan_pendidikan_id',
            'academic_year_id',
            'student_name',
            'nisn',
            'grade',
            'gender',
            'exit_date',
            'reason',
            'reported_by',
        ]));

        $migration->down();
        $this->assertFalse(Schema::hasTable('non_mutation_student_exits'));
    }
}
