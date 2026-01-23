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
        Schema::table('mutasi_masuk', function (Blueprint $table) {
            // Index for Search
            $table->index('nomor_surat', 'idx_mm_nomor_surat');
            $table->index('nama_sekolah_tujuan', 'idx_mm_nama_sekolah_tujuan');
            $table->index('npsn_sekolah_tujuan', 'idx_mm_npsn_tujuan');

            // Index for Filtering
            $table->index('status', 'idx_mm_status');
            $table->index('pengusul', 'idx_mm_pengusul'); // Operator username

            // Index for Sorting
            $table->index('tanggal_pengajuan', 'idx_mm_tanggal_pengajuan');
        });

        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            // Index for Recursive Search (Search logic uses whereHas)
            $table->index('nama_murid', 'idx_mms_nama_murid');
            $table->index('nisn', 'idx_mms_nisn');

            // Index for Display in Table
            $table->index('nama_sekolah_asal', 'idx_mms_nama_sekolah_asal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->dropIndex('idx_mm_nomor_surat');
            $table->dropIndex('idx_mm_nama_sekolah_tujuan');
            $table->dropIndex('idx_mm_npsn_tujuan');
            $table->dropIndex('idx_mm_status');
            $table->dropIndex('idx_mm_pengusul');
            $table->dropIndex('idx_mm_tanggal_pengajuan');
        });

        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->dropIndex('idx_mms_nama_murid');
            $table->dropIndex('idx_mms_nisn');
            $table->dropIndex('idx_mms_nama_sekolah_asal');
        });
    }
};
