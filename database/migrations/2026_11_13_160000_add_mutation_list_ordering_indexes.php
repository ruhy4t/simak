<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'mutasi_masuk' => [
                ['npsn_sekolah_tujuan', 'idx_mm_school_list'],
                ['pengusul', 'idx_mm_operator_list'],
            ],
            'mutasi_keluar' => [
                ['npsn_sekolah_asal', 'idx_mk_school_list'],
                ['pengusul', 'idx_mk_operator_list'],
            ],
        ];

        foreach ($indexes as $tableName => $tableIndexes) {
            foreach ($tableIndexes as [$filterColumn, $indexName]) {
                if (! Schema::hasIndex($tableName, $indexName)) {
                    Schema::table($tableName, function (Blueprint $table) use ($filterColumn, $indexName): void {
                        $table->index([$filterColumn, 'last_submitted_at', 'id_mutasi'], $indexName);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'mutasi_masuk' => ['idx_mm_school_list', 'idx_mm_operator_list'],
            'mutasi_keluar' => ['idx_mk_school_list', 'idx_mk_operator_list'],
        ] as $tableName => $indexNames) {
            foreach ($indexNames as $indexName) {
                if (Schema::hasIndex($tableName, $indexName)) {
                    Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }
    }
};
