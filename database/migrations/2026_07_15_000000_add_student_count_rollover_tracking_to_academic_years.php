<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years')) {
            return;
        }

        if (! Schema::hasColumn('academic_years', 'student_counts_rolled_over_from_id')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->unsignedBigInteger('student_counts_rolled_over_from_id')->nullable()->index();
            });
        }

        if (! Schema::hasColumn('academic_years', 'student_counts_rolled_over_at')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->timestamp('student_counts_rolled_over_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('academic_years')) {
            return;
        }

        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropIndex(['student_counts_rolled_over_from_id']);
            $table->dropColumn([
                'student_counts_rolled_over_from_id',
                'student_counts_rolled_over_at',
            ]);
        });
    }
};
