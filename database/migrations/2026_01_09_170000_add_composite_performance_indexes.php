<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Optimization: Add composite indexes for common query patterns.
 * These indexes significantly improve query performance for:
 * - Status + date filtering (reports, listings)
 * - NPSN + status filtering (role-based queries)
 * - Holiday date lookups (workday middleware)
 */
return new class extends Migration {
    public function up(): void
    {
        // Composite indexes for mutasi_masuk
        Schema::table('mutasi_masuk', function (Blueprint $table) {
            // For reports: WHERE status = ? AND tanggal_pengajuan BETWEEN ? AND ?
            $table->index(['status', 'tanggal_pengajuan'], 'idx_masuk_status_tanggal');

            // For role-based queries: WHERE npsn_sekolah_tujuan = ? AND status = ?
            $table->index(['npsn_sekolah_tujuan', 'status'], 'idx_masuk_npsn_status');
        });

        // Composite indexes for mutasi_keluar
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            // For reports: WHERE status = ? AND tanggal_pengajuan BETWEEN ? AND ?
            $table->index(['status', 'tanggal_pengajuan'], 'idx_keluar_status_tanggal');

            // For role-based queries: WHERE npsn_sekolah_asal = ? AND status = ?
            $table->index(['npsn_sekolah_asal', 'status'], 'idx_keluar_npsn_status');

            // For DailyTasks: WHERE status = 'Perlu Perbaikan' AND updated_at < ?
            $table->index(['status', 'updated_at'], 'idx_keluar_status_updated');
        });

        // Index for holidays table (workday middleware)
        Schema::table('holidays', function (Blueprint $table) {
            // Primary lookup: WHERE date = ?
            $table->index('date', 'idx_holidays_date');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->dropIndex('idx_masuk_status_tanggal');
            $table->dropIndex('idx_masuk_npsn_status');
        });

        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->dropIndex('idx_keluar_status_tanggal');
            $table->dropIndex('idx_keluar_npsn_status');
            $table->dropIndex('idx_keluar_status_updated');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropIndex('idx_holidays_date');
        });
    }
};