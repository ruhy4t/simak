<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('mutasi_masuk', ['status', 'updated_at'])) {
            Schema::table('mutasi_masuk', function (Blueprint $table): void {
                $table->index(['status', 'updated_at'], 'idx_masuk_status_updated');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('mutasi_masuk', 'idx_masuk_status_updated')) {
            Schema::table('mutasi_masuk', function (Blueprint $table): void {
                $table->dropIndex('idx_masuk_status_updated');
            });
        }
    }
};
