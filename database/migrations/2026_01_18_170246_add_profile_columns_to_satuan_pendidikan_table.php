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
            $table->string('kop_surat_path')->nullable()->after('provinsi');
            $table->string('kepala_sekolah_nama')->nullable()->after('kop_surat_path');
            $table->string('kepala_sekolah_nip')->nullable()->after('kepala_sekolah_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satuan_pendidikan', function (Blueprint $table) {
            $table->dropColumn(['kop_surat_path', 'kepala_sekolah_nama', 'kepala_sekolah_nip']);
        });
    }
};
