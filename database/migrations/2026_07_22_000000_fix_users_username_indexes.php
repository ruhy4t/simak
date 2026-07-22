<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $indexes = Schema::getIndexes('users');

        // Some deployed databases kept a legacy global index named `username`
        // even after the scoped username + NPSN migration was applied.
        foreach ($indexes as $index) {
            $columns = array_map('strtolower', $index['columns']);

            if ($index['unique'] && !$index['primary'] && $columns === ['username']) {
                Schema::table('users', function (Blueprint $table) use ($index): void {
                    $table->dropUnique($index['name']);
                });
            }
        }

        $hasScopedIndex = collect(Schema::getIndexes('users'))->contains(function (array $index): bool {
            return $index['unique']
                && array_map('strtolower', $index['columns']) === ['username', 'npsn'];
        });

        if (!$hasScopedIndex) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique(['username', 'npsn'], 'users_username_npsn_unique');
            });
        }
    }

    public function down(): void
    {
        $hasScopedIndex = collect(Schema::getIndexes('users'))->contains(function (array $index): bool {
            return $index['unique']
                && array_map('strtolower', $index['columns']) === ['username', 'npsn'];
        });

        if ($hasScopedIndex) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_username_npsn_unique');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username', 'users_username_unique');
        });
    }
};
