<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ReportingSchema
{
    private const CACHE_KEY = 'reporting.schema.capabilities.v2';

    private ?array $capabilities = null;

    public function cohortAvailable(): bool
    {
        return $this->capabilities()['cohort'];
    }

    public function longitudinalAvailable(): bool
    {
        return $this->capabilities()['longitudinal'];
    }

    public function hasNonMutationExits(): bool
    {
        return $this->capabilities()['non_mutation_exits'];
    }

    private function capabilities(): array
    {
        if ($this->capabilities !== null) {
            return $this->capabilities;
        }

        try {
            return $this->capabilities = Cache::remember(
                self::CACHE_KEY,
                now()->addHours(6),
                fn (): array => $this->inspect()
            );
        } catch (\Throwable) {
            return $this->capabilities = $this->inspect();
        }
    }

    private function inspect(): array
    {
        $hasAcademicYears = Schema::hasTable('academic_years');
        $hasStudentCounts = Schema::hasTable('satuan_pendidikan_student_counts');
        $hasClosingCounts = $hasStudentCounts && Schema::hasColumns(
            'satuan_pendidikan_student_counts',
            ['closing_male_count', 'closing_female_count', 'closing_confirmed_at', 'closing_locked_early']
        );
        $hasIncoming = Schema::hasTable('mutasi_masuk');
        $hasIncomingStudents = Schema::hasTable('mutasi_masuk_siswa');
        $hasOutgoing = Schema::hasTable('mutasi_keluar');

        return [
            'cohort' => $hasAcademicYears && $hasStudentCounts && $hasClosingCounts,
            'longitudinal' => $hasAcademicYears
                && $hasStudentCounts
                && $hasClosingCounts
                && $hasIncoming
                && $hasIncomingStudents
                && $hasOutgoing,
            'non_mutation_exits' => Schema::hasTable('non_mutation_student_exits'),
        ];
    }
}
