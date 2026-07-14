<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_years')) {
            return;
        }

        DB::table('academic_years')
            ->where('name', '2025/2026')
            ->where('starts_on', '2025-07-01')
            ->where('ends_on', '2026-06-30')
            ->update([
                'starts_on' => '2025-07-15',
                'ends_on' => '2026-07-14',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Dates may have been customized by Dinas, so rollback must not
        // overwrite the current operational schedule.
    }
};
