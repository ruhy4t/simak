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
        // Mutasi Keluar Indexes
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            // Index searchable columns for faster retrieval
            $table->index('nama_murid');
            $table->index('nama_sekolah_asal');
            $table->index('nomor_surat_sekolah');
        });

        // Mutasi Masuk Indexes
        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->index('nomor_surat');
            $table->index('nama_sekolah_tujuan');
        });

        // Mutasi Masuk Murid Indexes (Searching students within Mutasi Masuk)
        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->index('nama_murid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->dropIndex(['nama_murid']);
            $table->dropIndex(['nama_sekolah_asal']);
            $table->dropIndex(['nomor_surat_sekolah']);
        });

        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->dropIndex(['nomor_surat']);
            $table->dropIndex(['nama_sekolah_tujuan']);
        });

        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->dropIndex(['nama_murid']);
            $table->dropIndex(['nisn']);
        });
    }
};
