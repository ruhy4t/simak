<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('name', 9)->unique();
                $table->string('status', 20)->default('upcoming')->index();
                $table->boolean('is_active')->default(false)->index();
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('satuan_pendidikan_student_counts')) {
            Schema::create('satuan_pendidikan_student_counts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('satuan_pendidikan_id')->constrained('satuan_pendidikan')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('grade');
                $table->unsignedInteger('male_count')->default(0);
                $table->unsignedInteger('female_count')->default(0);
                $table->timestamps();

                $table->unique(
                    ['satuan_pendidikan_id', 'academic_year_id', 'grade'],
                    'school_year_grade_unique'
                );
                $table->index(['academic_year_id', 'grade']);
            });
        }

        $legacyYearId = DB::table('academic_years')->where('name', '2025/2026')->value('id');

        if (!$legacyYearId) {
            $hasActiveYear = DB::table('academic_years')->where('is_active', true)->exists();
            $legacyYearId = DB::table('academic_years')->insertGetId([
                'name' => '2025/2026',
                'status' => $hasActiveYear ? 'closed' : 'active',
                'is_active' => !$hasActiveYear,
                'starts_on' => '2025-07-01',
                'ends_on' => '2026-06-30',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasColumns('satuan_pendidikan', [
            'jumlah_siswa_7_l',
            'jumlah_siswa_7_p',
            'jumlah_siswa_8_l',
            'jumlah_siswa_8_p',
            'jumlah_siswa_9_l',
            'jumlah_siswa_9_p',
        ])) {
            DB::table('satuan_pendidikan')
                ->select([
                    'id',
                    'jumlah_siswa_7_l',
                    'jumlah_siswa_7_p',
                    'jumlah_siswa_8_l',
                    'jumlah_siswa_8_p',
                    'jumlah_siswa_9_l',
                    'jumlah_siswa_9_p',
                ])
                ->orderBy('id')
                ->chunkById(200, function ($schools) use ($legacyYearId) {
                    $now = now();
                    $rows = [];

                    foreach ($schools as $school) {
                        foreach ([7, 8, 9] as $grade) {
                            $rows[] = [
                                'satuan_pendidikan_id' => $school->id,
                                'academic_year_id' => $legacyYearId,
                                'grade' => $grade,
                                'male_count' => $school->{"jumlah_siswa_{$grade}_l"} ?? 0,
                                'female_count' => $school->{"jumlah_siswa_{$grade}_p"} ?? 0,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    DB::table('satuan_pendidikan_student_counts')->insertOrIgnore($rows);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('satuan_pendidikan_student_counts');
        Schema::dropIfExists('academic_years');
    }
};
