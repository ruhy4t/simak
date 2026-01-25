<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'created_at')) {
                // Add timestampts (created_at & updated_at)
                // Use nullable first to fill data
                $table->timestamps();

                // Add index for fast pruning
                $table->index('created_at', 'idx_audit_created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_created_at');
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
