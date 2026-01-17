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
        if (Schema::hasTable('penandatangan')) {
            Schema::table('penandatangan', function (Blueprint $table) {
                if (!Schema::hasColumn('penandatangan', 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable()->after('updated_at');
                }
                if (!Schema::hasColumn('penandatangan', 'alasan_penghapusan')) {
                    $table->text('alasan_penghapusan')->nullable()->after('deleted_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('penandatangan')) {
            Schema::table('penandatangan', function (Blueprint $table) {
                $table->dropColumn(['deleted_at', 'alasan_penghapusan']);
            });
        }
    }
};