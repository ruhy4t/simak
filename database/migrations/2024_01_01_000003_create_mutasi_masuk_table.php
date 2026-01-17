<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop old table if exists
        Schema::dropIfExists('mutasi_masuk');

        // Create new header table
        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->string('id_mutasi', 20)->primary();
            $table->date('tanggal_pengajuan')->index();
            $table->integer('jumlah_siswa')->default(1);

            // Surat Permohonan
            $table->string('nomor_surat', 50);
            $table->date('tanggal_surat');
            $table->string('file_surat_permohonan')->nullable();

            // Sekolah Tujuan (dari user login)
            $table->string('npsn_sekolah_tujuan', 8)->index();
            $table->string('nama_sekolah_tujuan', 150);
            $table->text('alamat_sekolah_tujuan');

            // Status
            $table->string('status', 50)->default('Menunggu Verifikasi Dinas')->index();
            $table->string('pengusul', 50);

            // Verifikasi
            $table->text('catatan_berkas')->nullable();

            $table->timestamps();

            $table->index(['pengusul', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_masuk');
    }
};
