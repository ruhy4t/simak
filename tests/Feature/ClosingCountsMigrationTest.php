<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClosingCountsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->date('ends_on')->nullable();
        });
        Schema::create('satuan_pendidikan_student_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable();
            $table->unsignedInteger('female_count')->default(0);
            $table->timestamp('confirmed_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_closing_count_columns_can_be_added_and_removed(): void
    {
        $migration = require database_path('migrations/2026_07_17_120000_add_closing_counts_to_student_counts.php');

        $migration->up();
        $this->assertTrue(Schema::hasColumns('satuan_pendidikan_student_counts', [
            'closing_male_count',
            'closing_female_count',
            'closing_confirmed_at',
        ]));

        $migration->down();
        $this->assertFalse(Schema::hasColumn('satuan_pendidikan_student_counts', 'closing_male_count'));
    }

    public function test_active_year_closing_counts_locked_before_the_end_are_marked_early(): void
    {
        $closingMigration = require database_path('migrations/2026_07_17_120000_add_closing_counts_to_student_counts.php');
        $closingMigration->up();

        $yearId = DB::table('academic_years')->insertGetId([
            'is_active' => true,
            'ends_on' => now()->addMonth()->toDateString(),
        ]);
        DB::table('satuan_pendidikan_student_counts')->insert([
            'academic_year_id' => $yearId,
            'closing_confirmed_at' => now(),
        ]);

        $markerMigration = require database_path('migrations/2026_07_23_130000_add_early_lock_marker_to_student_counts.php');
        $markerMigration->up();
        $markerMigration->up();

        $this->assertTrue(Schema::hasColumn(
            'satuan_pendidikan_student_counts',
            'closing_locked_early'
        ));
        $this->assertDatabaseHas('satuan_pendidikan_student_counts', [
            'academic_year_id' => $yearId,
            'closing_locked_early' => true,
        ]);

        $markerMigration->down();
        $this->assertFalse(Schema::hasColumn(
            'satuan_pendidikan_student_counts',
            'closing_locked_early'
        ));
    }
}
