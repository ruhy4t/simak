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
        Schema::table('satuan_pendidikan', function (Blueprint $table) {
            $table->integer('jumlah_siswa_awal_7')->default(0)->after('kepala_sekolah_nip');
            $table->integer('jumlah_siswa_awal_8')->default(0)->after('jumlah_siswa_awal_7');
            $table->integer('jumlah_siswa_awal_9')->default(0)->after('jumlah_siswa_awal_8');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satuan_pendidikan', function (Blueprint $table) {
            $table->dropColumn(['jumlah_siswa_awal_7', 'jumlah_siswa_awal_8', 'jumlah_siswa_awal_9']);
        });
    }
};
