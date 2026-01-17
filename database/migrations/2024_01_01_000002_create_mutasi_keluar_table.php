<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mutasi_keluar', function (Blueprint $table) {
            $table->string('id_mutasi', 20)->primary();
            $table->date('tanggal_pengajuan')->index();
            $table->string('nama_murid', 100);
            $table->string('nisn', 10)->index();
            $table->string('nis', 20)->nullable();
            $table->string('tempat_lahir', 50);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('kelas', 10);
            $table->string('nama_sekolah_asal', 150);
            $table->string('npsn_sekolah_asal', 8)->index();
            $table->text('alamat_sekolah_asal');
            $table->string('npsn_sekolah_tujuan', 8);
            $table->string('nama_sekolah_tujuan', 150);
            $table->text('alamat_sekolah_tujuan')->nullable();
            $table->string('kabupaten_kota_tujuan', 100);
            $table->string('provinsi_tujuan', 100);
            $table->text('alasan_pindah');
            $table->string('nomor_surat_sekolah', 50)->nullable();
            $table->date('tanggal_surat_sekolah')->nullable();
            $table->string('status', 50)->default('Menunggu Verifikasi Dinas')->index();
            $table->string('pengusul', 50);

            // File links (stored in storage)
            $table->string('file_suket_keluar')->nullable();
            $table->string('file_suket_dapo')->nullable();
            $table->string('file_suket_diterima')->nullable();
            $table->string('file_rapor')->nullable();

            // Verification checkboxes
            $table->boolean('ceklis_surat_sekolah')->default(false);
            $table->boolean('ceklis_berkas_dapodik')->default(false);
            $table->boolean('ceklis_sekolah_tujuan')->default(false);
            $table->boolean('ceklis_rapor')->default(false);
            $table->text('catatan_berkas')->nullable();

            $table->timestamps();

            // Composite indexes for filtering
            $table->index(['npsn_sekolah_asal', 'status']);
            $table->index(['pengusul', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_keluar');
    }
};