<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_mutation_student_exits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satuan_pendidikan_id')->constrained('satuan_pendidikan')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('student_name', 150);
            $table->string('nisn', 10);
            $table->unsignedTinyInteger('grade');
            $table->char('gender', 1);
            $table->date('exit_date');
            $table->string('reason', 50)->default('Tanpa keterangan');
            $table->text('notes')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['satuan_pendidikan_id', 'academic_year_id', 'nisn'],
                'non_mutation_exit_school_year_nisn_unique'
            );
            $table->index(['academic_year_id', 'grade'], 'non_mutation_exit_year_grade_index');
            $table->index('satuan_pendidikan_id', 'non_mutation_exit_school_index');
            $table->index('exit_date', 'non_mutation_exit_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_mutation_student_exits');
    }
};
