<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('satuan_pendidikan_student_counts', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('female_count');
        });
    }

    public function down(): void
    {
        Schema::table('satuan_pendidikan_student_counts', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};
