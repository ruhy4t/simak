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
        // Mutasi Keluar Indexes
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            // Check if indexes exist before adding to avoid errors on re-run
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('mutasi_keluar');

            if (!array_key_exists('idx_mk_status', $indexes)) {
                $table->index('status', 'idx_mk_status');
            }
            if (!array_key_exists('idx_mk_npsn_asal', $indexes)) {
                $table->index('npsn_sekolah_asal', 'idx_mk_npsn_asal');
            }
            if (!array_key_exists('idx_mk_pengusul', $indexes)) {
                $table->index('pengusul', 'idx_mk_pengusul');
            }
            if (!array_key_exists('idx_mk_tanggal', $indexes)) {
                $table->index('tanggal_pengajuan', 'idx_mk_tanggal');
            }
        });

        // Satuan Pendidikan Indexes
        Schema::table('satuan_pendidikan', function (Blueprint $table) {
            $table->index('npsn', 'idx_sp_npsn');
            $table->index('nama', 'idx_sp_nama'); // For LIKE search
            $table->index('is_local', 'idx_sp_is_local');
            $table->index('status_sp', 'idx_sp_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->dropIndex('idx_mk_status');
            $table->dropIndex('idx_mk_npsn_asal');
            $table->dropIndex('idx_mk_pengusul');
            $table->dropIndex('idx_mk_tanggal');
        });

        Schema::table('satuan_pendidikan', function (Blueprint $table) {
            $table->dropIndex('idx_sp_npsn');
            $table->dropIndex('idx_sp_nama');
            $table->dropIndex('idx_sp_is_local');
            $table->dropIndex('idx_sp_status');
        });
    }
};
