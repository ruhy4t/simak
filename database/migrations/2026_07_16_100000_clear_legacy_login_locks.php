<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')
            && Schema::hasColumn('users', 'locked_until')
            && Schema::hasColumn('users', 'failed_login_attempts')) {
            DB::table('users')->update([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ]);
        }
    }

    public function down(): void
    {
        // Legacy account locks are intentionally not restored.
    }
};
