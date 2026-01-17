<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add verification status columns for each file document in mutasi_keluar
 * This enables smart filtering of which documents need revision
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            // Verification status for each file (null = not verified yet, 1 = OK, 0 = needs revision)
            $table->tinyInteger('verif_suket_keluar')->nullable()->after('file_rapor');
            $table->tinyInteger('verif_suket_dapo')->nullable()->after('verif_suket_keluar');
            $table->tinyInteger('verif_suket_diterima')->nullable()->after('verif_suket_dapo');
            $table->tinyInteger('verif_rapor')->nullable()->after('verif_suket_diterima');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->dropColumn([
                'verif_suket_keluar',
                'verif_suket_dapo',
                'verif_suket_diterima',
                'verif_rapor',
            ]);
        });
    }
};
