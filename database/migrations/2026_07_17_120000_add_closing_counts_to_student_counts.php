<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('satuan_pendidikan_student_counts', function (Blueprint $table) {
            $table->unsignedInteger('closing_male_count')->nullable()->after('confirmed_at');
            $table->unsignedInteger('closing_female_count')->nullable()->after('closing_male_count');
            $table->timestamp('closing_confirmed_at')->nullable()->after('closing_female_count');
        });
    }

    public function down(): void
    {
        Schema::table('satuan_pendidikan_student_counts', function (Blueprint $table) {
            $table->dropColumn([
                'closing_male_count',
                'closing_female_count',
                'closing_confirmed_at',
            ]);
        });
    }
};
