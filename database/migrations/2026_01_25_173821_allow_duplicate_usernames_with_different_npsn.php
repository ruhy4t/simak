<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop global unique constraint on username
            $table->dropUnique('users_username_unique');

            // Add scoped unique constraint (username + npsn)
            // Note: Since npsn is nullable, this allows non-NPSN users (Dinas) to have duplicates of 'username' 
            // if the DB treats NULL != NULL. However, we will enforce uniqueness in application logic for Dinas.
            // For Schools/Operators (who have NPSN), this works perfectly.
            $table->unique(['username', 'npsn'], 'users_username_npsn_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_npsn_unique');
            $table->unique('username', 'users_username_unique');
        });
    }
};
