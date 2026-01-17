<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tabel referensi Satuan Pendidikan SMP Kota Bogor
     * Digunakan untuk pencarian sekolah asal/tujuan mutasi
     */
    public function up(): void
    {
        Schema::create('satuan_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 8)->unique()->index();
            $table->string('nama', 150);
            $table->string('bentuk_pendidikan', 20)->default('SMP'); // SMP, SPK SMP, dll
            $table->enum('status_sp', ['Negeri', 'Swasta'])->default('Swasta');
            $table->text('alamat_jalan')->nullable();
            $table->string('kelurahan', 50)->nullable();
            $table->string('kecamatan', 50)->nullable();
            $table->string('kota', 50)->default('Kota Bogor');
            $table->string('provinsi', 50)->default('Jawa Barat');
            $table->text('alamat_lengkap')->nullable(); // Gabungan alamat dengan Kel./Kec.
            $table->boolean('is_local')->default(true); // true = SMP Kota Bogor, false = luar kota
            $table->timestamps();

            // Indexes for search
            $table->index('nama');
            $table->index(['kota', 'provinsi']);
            $table->index('is_local');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satuan_pendidikan');
    }
};