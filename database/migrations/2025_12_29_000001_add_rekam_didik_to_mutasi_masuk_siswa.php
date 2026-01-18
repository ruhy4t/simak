<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->string('file_rekam_didik')->nullable()->after('file_rekomendasi');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->dropColumn('file_rekam_didik');
        });
    }
};