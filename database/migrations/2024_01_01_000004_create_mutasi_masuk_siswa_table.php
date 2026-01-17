<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->id();
            $table->string('id_mutasi', 20);

            // Data Siswa
            $table->string('nama_murid', 100);
            $table->string('nisn', 10)->index();
            $table->string('nis', 20)->nullable();
            $table->string('tempat_lahir', 50);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('kelas', 10);
            $table->date('tanggal_diterima'); // Tanggal diterima per siswa

            // Sekolah Asal (per siswa, bisa beda-beda)
            $table->string('npsn_sekolah_asal', 8)->nullable();
            $table->string('nama_sekolah_asal', 150);
            $table->text('alamat_sekolah_asal');
            $table->string('provinsi_asal', 100);
            $table->string('kabupaten_kota_asal', 100);
            $table->boolean('npsn_ditemukan')->default(false); // Untuk logika Rekomendasi
            $table->boolean('asal_luar_negeri')->default(false); // Untuk logika Surat Penyaluran

            // Dokumen (per siswa)
            $table->string('file_surat_pindah')->nullable(); // Wajib
            $table->string('file_surat_dapodik')->nullable(); // Wajib
            $table->string('file_rekomendasi')->nullable(); // Wajib jika NPSN tidak ditemukan
            $table->string('file_ijazah_sd')->nullable(); // Wajib
            $table->string('file_rapor')->nullable(); // Wajib
            $table->string('file_surat_penyaluran')->nullable(); // Wajib jika luar negeri

            $table->timestamps();

            // Foreign key
            $table->foreign('id_mutasi')
                ->references('id_mutasi')
                ->on('mutasi_masuk')
                ->onDelete('cascade');

            $table->index(['id_mutasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_masuk_siswa');
    }
};