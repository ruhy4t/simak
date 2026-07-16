<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mutasi_keluar', 'mutasi_masuk'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'last_submitted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->timestamp('last_submitted_at')->nullable();
                });
            }

            if (! Schema::hasColumn($tableName, 'revision_count')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->unsignedInteger('revision_count')->default(0);
                });
            }

            $indexName = $tableName === 'mutasi_keluar'
                ? 'idx_keluar_last_submitted'
                : 'idx_masuk_last_submitted';

            if (! Schema::hasIndex($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                    $table->index('last_submitted_at', $indexName);
                });
            }

            DB::table($tableName)
                ->whereNull('last_submitted_at')
                ->update([
                    'last_submitted_at' => DB::raw('COALESCE(created_at, tanggal_pengajuan)'),
                ]);
        }
    }

    public function down(): void
    {
        foreach (['mutasi_keluar', 'mutasi_masuk'] as $tableName) {
            $indexName = $tableName === 'mutasi_keluar'
                ? 'idx_keluar_last_submitted'
                : 'idx_masuk_last_submitted';

            if (Schema::hasIndex($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                    $table->dropIndex($indexName);
                });
            }

            if (Schema::hasColumn($tableName, 'last_submitted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('last_submitted_at');
                });
            }

            if (Schema::hasColumn($tableName, 'revision_count')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('revision_count');
                });
            }
        }
    }
};
