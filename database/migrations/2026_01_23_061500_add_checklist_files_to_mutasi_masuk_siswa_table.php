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
        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->boolean('has_surat_pindah')->default(false)->after('file_surat_penyaluran');
            $table->boolean('has_surat_dapodik')->default(false)->after('has_surat_pindah');
            $table->boolean('has_rekomendasi')->default(false)->after('has_surat_dapodik');
            $table->boolean('has_ijazah_sd')->default(false)->after('has_rekomendasi');
            $table->boolean('has_rapor')->default(false)->after('has_ijazah_sd');
            $table->boolean('has_surat_penyaluran')->default(false)->after('has_rapor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->dropColumn([
                'has_surat_pindah',
                'has_surat_dapodik',
                'has_rekomendasi',
                'has_ijazah_sd',
                'has_rapor',
                'has_surat_penyaluran'
            ]);
        });
    }
};
