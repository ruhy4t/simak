<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LongitudinalCohortService
{
    public function available(): bool
    {
        return Schema::hasTable('academic_years')
            && Schema::hasTable('satuan_pendidikan_student_counts')
            && Schema::hasTable('mutasi_masuk')
            && Schema::hasTable('mutasi_masuk_siswa')
            && Schema::hasTable('mutasi_keluar');
    }

    /**
     * Follow one intake cohort from grade VII through grade IX.
     */
    public function report(
        AcademicYear $entryYear,
        ?int $schoolId = null,
        ?string $schoolStatus = null,
    ): array {
        if (! $this->available()) {
            return $this->emptyReport($entryYear);
        }

        $years = $this->resolveStageYears($entryYear);
        $stages = collect([
            $this->buildStage($years[0] ?? null, 7, $schoolId, $schoolStatus),
            $this->buildStage($years[1] ?? null, 8, $schoolId, $schoolStatus),
            $this->buildStage($years[2] ?? null, 9, $schoolId, $schoolStatus),
        ]);

        $previousBalance = null;
        $stages = $stages->map(function (array $stage) use (&$previousBalance): array {
            if (! $stage['available']) {
                return $stage;
            }

            $stage['transition_adjustment'] = $previousBalance === null
                ? 0
                : $stage['opening'] - $previousBalance;
            $previousBalance = $stage['actual'] ?? $stage['expected'];

            return $stage;
        });

        $finalStage = $stages->last();
        $latestStage = $stages->filter(fn (array $stage) => $stage['available'])->last();

        return [
            'entry_year_id' => $entryYear->id,
            'entry_year_name' => $entryYear->name,
            'stages' => $stages->all(),
            'available_stage_count' => $stages->where('available', true)->count(),
            'is_complete' => (bool) ($finalStage['is_complete'] ?? false),
            'summary' => [
                'initial' => (int) ($stages->first()['opening'] ?? 0),
                'incoming' => (int) $stages->sum('incoming'),
                'outgoing' => (int) $stages->sum('outgoing'),
                'non_mutation_outgoing' => (int) $stages->sum('non_mutation_outgoing'),
                'transition_adjustment' => (int) $stages->sum('transition_adjustment'),
                'current' => $latestStage
                    ? (int) ($latestStage['actual'] ?? $latestStage['expected'])
                    : null,
                'graduated' => ($finalStage['available'] ?? false) && $finalStage['actual'] !== null
                    ? (int) $finalStage['actual']
                    : null,
            ],
        ];
    }

    /** @return array<int, AcademicYear|null> */
    private function resolveStageYears(AcademicYear $entryYear): array
    {
        if (preg_match('/^(\d{4})\/(\d{4})$/', $entryYear->name, $matches) === 1) {
            $start = (int) $matches[1];
            $names = [
                $entryYear->name,
                ($start + 1).'/'.($start + 2),
                ($start + 2).'/'.($start + 3),
            ];
            $years = AcademicYear::query()->whereIn('name', $names)->get()->keyBy('name');

            return array_map(fn (string $name) => $years->get($name), $names);
        }

        $years = AcademicYear::query()
            ->when($entryYear->starts_on, fn ($query) => $query->where('starts_on', '>=', $entryYear->starts_on))
            ->orderByRaw('starts_on IS NULL')
            ->orderBy('starts_on')
            ->orderBy('name')
            ->limit(3)
            ->get();

        return [
            $years->get(0),
            $years->get(1),
            $years->get(2),
        ];
    }

    private function buildStage(
        ?AcademicYear $academicYear,
        int $grade,
        ?int $schoolId,
        ?string $schoolStatus,
    ): array {
        $base = [
            'grade' => $grade,
            'grade_label' => $this->gradeLabel($grade),
            'academic_year_id' => $academicYear?->id,
            'academic_year_name' => $academicYear?->name,
            'available' => false,
            'state' => 'unavailable',
            'school_count' => 0,
            'reported_school_count' => 0,
            'opening' => 0,
            'incoming' => 0,
            'outgoing' => 0,
            'non_mutation_outgoing' => 0,
            'expected' => 0,
            'actual' => null,
            'transition_adjustment' => 0,
            'is_complete' => false,
        ];

        if (! $academicYear) {
            return $base;
        }

        $countRows = $this->countRows($academicYear, $grade, $schoolId, $schoolStatus);
        if ($countRows->isEmpty()) {
            return $base;
        }

        $schoolIds = $countRows->pluck('satuan_pendidikan_id')->unique()->values();
        $npsns = $countRows->pluck('npsn')->filter()->unique()->values();
        $opening = (int) $countRows->sum(fn (object $row) => (int) $row->male_count + (int) $row->female_count);
        $reportedRows = $countRows->filter(fn (object $row) => $row->closing_confirmed_at !== null
            && $row->closing_male_count !== null
            && $row->closing_female_count !== null);
        $reportedSchoolCount = $reportedRows->pluck('satuan_pendidikan_id')->unique()->count();
        $schoolCount = $schoolIds->count();
        $incoming = $this->incomingTotal($academicYear, $grade, $npsns);
        $outgoing = $this->outgoingTotal($academicYear, $grade, $npsns);
        $nonMutationOutgoing = $this->nonMutationOutgoingTotal($academicYear, $grade, $schoolIds);
        $actual = $reportedSchoolCount > 0
            ? (int) $reportedRows->sum(fn (object $row) => (int) $row->closing_male_count + (int) $row->closing_female_count)
            : null;
        $isComplete = $schoolCount > 0 && $reportedSchoolCount === $schoolCount;

        return array_merge($base, [
            'available' => true,
            'state' => $isComplete ? 'complete' : 'in_progress',
            'school_count' => $schoolCount,
            'reported_school_count' => $reportedSchoolCount,
            'opening' => $opening,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'non_mutation_outgoing' => $nonMutationOutgoing,
            'expected' => $opening + $incoming - $outgoing - $nonMutationOutgoing,
            'actual' => $actual,
            'is_complete' => $isComplete,
        ]);
    }

    private function countRows(
        AcademicYear $academicYear,
        int $grade,
        ?int $schoolId,
        ?string $schoolStatus,
    ): Collection {
        return DB::table('satuan_pendidikan_student_counts as counts')
            ->join('satuan_pendidikan as schools', 'schools.id', '=', 'counts.satuan_pendidikan_id')
            ->where('counts.academic_year_id', $academicYear->id)
            ->where('counts.grade', $grade)
            ->where('schools.is_local', true)
            ->whereRaw('UPPER(TRIM(schools.kota)) = ?', ['KOTA BOGOR'])
            ->whereRaw('UPPER(TRIM(schools.bentuk_pendidikan)) = ?', ['SMP'])
            ->whereIn('schools.status_sp', ['Negeri', 'Swasta'])
            ->when($schoolId, fn ($query) => $query->where('counts.satuan_pendidikan_id', $schoolId))
            ->when(! $schoolId && $schoolStatus, fn ($query) => $query->where('schools.status_sp', $schoolStatus))
            ->get([
                'counts.satuan_pendidikan_id',
                'schools.npsn',
                'counts.male_count',
                'counts.female_count',
                'counts.closing_male_count',
                'counts.closing_female_count',
                'counts.closing_confirmed_at',
            ]);
    }

    private function incomingTotal(AcademicYear $academicYear, int $grade, Collection $npsns): int
    {
        if ($npsns->isEmpty()) {
            return 0;
        }

        $query = DB::table('mutasi_masuk')
            ->join('mutasi_masuk_siswa', 'mutasi_masuk_siswa.id_mutasi', '=', 'mutasi_masuk.id_mutasi')
            ->where('mutasi_masuk.status', 'Selesai')
            ->whereNull('mutasi_masuk.deleted_at')
            ->whereIn('mutasi_masuk.npsn_sekolah_tujuan', $npsns)
            ->whereIn('mutasi_masuk_siswa.kelas', $this->gradeValues($grade));
        $this->constrainToYear($query, 'mutasi_masuk.tanggal_pengajuan', $academicYear);

        return $query->count();
    }

    private function outgoingTotal(AcademicYear $academicYear, int $grade, Collection $npsns): int
    {
        if ($npsns->isEmpty()) {
            return 0;
        }

        $query = DB::table('mutasi_keluar')
            ->where('status', 'Selesai')
            ->whereNull('deleted_at')
            ->whereIn('npsn_sekolah_asal', $npsns)
            ->whereIn('kelas', $this->gradeValues($grade));
        $this->constrainToYear($query, 'tanggal_pengajuan', $academicYear);

        return $query->count();
    }

    private function nonMutationOutgoingTotal(AcademicYear $academicYear, int $grade, Collection $schoolIds): int
    {
        if ($schoolIds->isEmpty() || ! Schema::hasTable('non_mutation_student_exits')) {
            return 0;
        }

        return DB::table('non_mutation_student_exits')
            ->where('academic_year_id', $academicYear->id)
            ->where('grade', $grade)
            ->whereIn('satuan_pendidikan_id', $schoolIds)
            ->count();
    }

    private function constrainToYear($query, string $column, AcademicYear $academicYear): void
    {
        if ($academicYear->starts_on) {
            $query->where($column, '>=', $academicYear->starts_on->toDateString());
        }
        if ($academicYear->ends_on) {
            $query->where($column, '<=', $academicYear->ends_on->toDateString());
        }
    }

    private function gradeValues(int $grade): array
    {
        return match ($grade) {
            8 => ['8', 'VIII'],
            9 => ['9', 'IX'],
            default => ['7', 'VII'],
        };
    }

    private function gradeLabel(int $grade): string
    {
        return match ($grade) {
            8 => 'VIII',
            9 => 'IX',
            default => 'VII',
        };
    }

    private function emptyReport(AcademicYear $entryYear): array
    {
        return [
            'entry_year_id' => $entryYear->id,
            'entry_year_name' => $entryYear->name,
            'stages' => [],
            'available_stage_count' => 0,
            'is_complete' => false,
            'summary' => [
                'initial' => 0,
                'incoming' => 0,
                'outgoing' => 0,
                'non_mutation_outgoing' => 0,
                'transition_adjustment' => 0,
                'current' => null,
                'graduated' => null,
            ],
        ];
    }
}
