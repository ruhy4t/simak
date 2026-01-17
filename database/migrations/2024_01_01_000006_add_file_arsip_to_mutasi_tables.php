<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            if (!Schema::hasColumn('mutasi_keluar', 'file_arsip_scan')) {
                $table->string('file_arsip_scan')->nullable()->after('catatan_berkas');
            }
        });

        Schema::table('mutasi_masuk', function (Blueprint $table) {
            if (!Schema::hasColumn('mutasi_masuk', 'file_arsip_scan')) {
                $table->string('file_arsip_scan')->nullable()->after('catatan_berkas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            if (Schema::hasColumn('mutasi_keluar', 'file_arsip_scan')) {
                $table->dropColumn('file_arsip_scan');
            }
        });

        Schema::table('mutasi_masuk', function (Blueprint $table) {
            if (Schema::hasColumn('mutasi_masuk', 'file_arsip_scan')) {
                $table->dropColumn('file_arsip_scan');
            }
        });
    }
};
