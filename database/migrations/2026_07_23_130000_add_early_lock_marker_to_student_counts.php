<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('satuan_pendidikan_student_counts', 'closing_locked_early')) {
            Schema::table('satuan_pendidikan_student_counts', function (Blueprint $table): void {
                $table->boolean('closing_locked_early')->default(false)->after('closing_confirmed_at');
            });
        }

        DB::table('academic_years')
            ->where('is_active', true)
            ->whereNotNull('ends_on')
            ->orderBy('id')
            ->each(function (object $year): void {
                DB::table('satuan_pendidikan_student_counts')
                    ->where('academic_year_id', $year->id)
                    ->whereNotNull('closing_confirmed_at')
                    ->where('closing_confirmed_at', '<', $year->ends_on)
                    ->update(['closing_locked_early' => true]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('satuan_pendidikan_student_counts', 'closing_locked_early')) {
            Schema::table('satuan_pendidikan_student_counts', function (Blueprint $table): void {
                $table->dropColumn('closing_locked_early');
            });
        }
    }
};
