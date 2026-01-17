<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add verification status columns for each dokumen
 * This enables smart filtering of which documents need revision
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            // Verification status for each document (null = not verified yet, 1 = OK, 0 = needs revision)
            $table->tinyInteger('verif_surat_pindah')->nullable()->after('file_rekam_didik');
            $table->tinyInteger('verif_surat_dapodik')->nullable()->after('verif_surat_pindah');
            $table->tinyInteger('verif_rekomendasi')->nullable()->after('verif_surat_dapodik');
            $table->tinyInteger('verif_ijazah_sd')->nullable()->after('verif_rekomendasi');
            $table->tinyInteger('verif_rapor')->nullable()->after('verif_ijazah_sd');
            $table->tinyInteger('verif_surat_penyaluran')->nullable()->after('verif_rapor');
            $table->tinyInteger('verif_rekam_didik')->nullable()->after('verif_surat_penyaluran');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->dropColumn([
                'verif_surat_pindah',
                'verif_surat_dapodik',
                'verif_rekomendasi',
                'verif_ijazah_sd',
                'verif_rapor',
                'verif_surat_penyaluran',
                'verif_rekam_didik',
            ]);
        });
    }
};
