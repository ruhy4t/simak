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
        // Mutasi Keluar - Add Missing Indexes
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            // Index for sorting and date filtering (Critical for Dashboard & Index)
            $table->index('tanggal_pengajuan');

            // Index for Status Filtering
            $table->index('status');

            // Index for Soft Deletes (deleted_at is checked in every query)
            $table->index('deleted_at');

            // Index for Role-based Filtering (Sekolah & Operator)
            $table->index('npsn_sekolah_asal');
            $table->index('pengusul');
        });

        // Mutasi Masuk - Add Missing Indexes
        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->index('tanggal_pengajuan');
            $table->index('status');
            $table->index('deleted_at');

            // Index for Role-based Filtering
            $table->index('npsn_sekolah_tujuan');
            $table->index('pengusul');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->dropIndex(['tanggal_pengajuan']);
            $table->dropIndex(['status']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['npsn_sekolah_asal']);
            $table->dropIndex(['pengusul']);
        });

        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->dropIndex(['tanggal_pengajuan']);
            $table->dropIndex(['status']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['npsn_sekolah_tujuan']);
            $table->dropIndex(['pengusul']);
        });
    }
};
