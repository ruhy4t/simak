<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CohortReportService
{
    public function __construct(private readonly ReportingSchema $reportingSchema)
    {
    }

    public function available(): bool
    {
        return $this->reportingSchema->cohortAvailable();
    }

    /**
     * Build an aggregate cohort report without exposing student identities.
     */
    public function report(
        AcademicYear $academicYear,
        ?int $schoolId = null,
        ?string $schoolStatus = null,
    ): array {
        $isProvisional = $academicYear->status === AcademicYear::STATUS_ACTIVE;

        if (! $this->available()) {
            return array_merge($this->emptyReport(), ['is_provisional' => $isProvisional]);
        }

        // The reverse opening-count calculation is a one-year compatibility
        // aid for legacy 2025/2026 data. Later years use the school's official
        // opening counts populated through the normal intake/rollover flow.
        $usesCalculatedOpening = $academicYear->name === '2025/2026';

        $countQuery = DB::table('satuan_pendidikan_student_counts as counts')
            ->join('satuan_pendidikan as schools', 'schools.id', '=', 'counts.satuan_pendidikan_id')
            ->where('counts.academic_year_id', $academicYear->id)
            ->whereIn('counts.grade', [7, 8, 9])
            ->where('schools.is_local', true)
            ->whereRaw('UPPER(TRIM(schools.kota)) = ?', ['KOTA BOGOR'])
            ->whereRaw('UPPER(TRIM(schools.bentuk_pendidikan)) IN (?, ?)', ['SMP', 'SPK SMP'])
            ->whereIn('schools.status_sp', ['Negeri', 'Swasta'])
            ->select([
                'counts.satuan_pendidikan_id',
                'schools.npsn',
                'counts.grade',
                'counts.male_count',
                'counts.female_count',
                'counts.closing_male_count',
                'counts.closing_female_count',
                'counts.closing_confirmed_at',
            ]);

        if ($schoolId) {
            $countQuery->where('counts.satuan_pendidikan_id', $schoolId);
        } elseif ($schoolStatus) {
            $countQuery->where('schools.status_sp', $schoolStatus);
        }

        $countRows = $countQuery->get();
        $npsns = $countRows->pluck('npsn')->filter()->unique()->values();
        $incoming = $this->incomingTotals($academicYear, $npsns);
        $outgoing = $this->outgoingTotals($academicYear, $npsns);
        $nonMutationOutgoing = $this->nonMutationOutgoingTotals($academicYear, $npsns);

        $rows = collect();
        foreach ([7, 8, 9] as $grade) {
            foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $gender => $genderLabel) {
                $rows->push([
                    'grade' => $grade,
                    'gender' => $gender,
                    'gender_label' => $genderLabel,
                    'opening' => 0,
                    'incoming' => 0,
                    'outgoing' => 0,
                    'non_mutation_outgoing' => 0,
                    'expected' => 0,
                    'actual' => 0,
                    'calculated_opening' => 0,
                    'opening_variance' => 0,
                    'unexplained_exit' => 0,
                    'other_addition' => 0,
                    'reported_units' => 0,
                    'missing_units' => 0,
                    'reported_opening' => 0,
                    'reported_incoming' => 0,
                    'reported_outgoing' => 0,
                    'reported_non_mutation_outgoing' => 0,
                    'reported_expected' => 0,
                ]);
            }
        }
        $rows = $rows->keyBy(fn (array $row) => $this->rowKey($row['grade'], $row['gender']));

        foreach ($countRows as $count) {
            foreach (['L' => ['male_count', 'closing_male_count'], 'P' => ['female_count', 'closing_female_count']] as $gender => [$openingColumn, $closingColumn]) {
                $rowKey = $this->rowKey((int) $count->grade, $gender);
                $row = $rows[$rowKey];
                $mutationKey = $this->mutationKey((string) $count->npsn, (int) $count->grade, $gender);
                $opening = (int) $count->{$openingColumn};
                $incomingCount = (int) ($incoming[$mutationKey] ?? 0);
                $outgoingCount = (int) ($outgoing[$mutationKey] ?? 0);
                $nonMutationOutgoingCount = (int) ($nonMutationOutgoing[$mutationKey] ?? 0);
                $expected = $opening + $incomingCount - $outgoingCount - $nonMutationOutgoingCount;
                $isReported = $count->closing_confirmed_at !== null && $count->{$closingColumn} !== null;

                $row['opening'] += $opening;
                $row['incoming'] += $incomingCount;
                $row['outgoing'] += $outgoingCount;
                $row['non_mutation_outgoing'] += $nonMutationOutgoingCount;
                $row['expected'] += $expected;

                if ($isReported) {
                    $actual = (int) $count->{$closingColumn};
                    $calculatedOpening = $usesCalculatedOpening
                        ? $actual + $outgoingCount + $nonMutationOutgoingCount - $incomingCount
                        : $opening;
                    $row['actual'] += $actual;
                    $row['calculated_opening'] += $calculatedOpening;
                    $row['opening_variance'] += $opening - $calculatedOpening;
                    $row['reported_opening'] += $opening;
                    $row['reported_incoming'] += $incomingCount;
                    $row['reported_outgoing'] += $outgoingCount;
                    $row['reported_non_mutation_outgoing'] += $nonMutationOutgoingCount;
                    $row['reported_expected'] += $expected;
                    $row['unexplained_exit'] += max(0, $expected - $actual);
                    $row['other_addition'] += max(0, $actual - $expected);
                    $row['reported_units']++;
                } else {
                    $row['missing_units']++;
                }

                $rows[$rowKey] = $row;
            }
        }

        $rows = $rows->values();
        $gradeSummary = collect([7, 8, 9])->map(function (int $grade) use ($rows): array {
            $gradeRows = $rows->where('grade', $grade);

            return [
                'grade' => $grade,
                'grade_label' => [7 => 'VII', 8 => 'VIII', 9 => 'IX'][$grade],
                'opening' => (int) $gradeRows->sum('reported_opening'),
                'incoming' => (int) $gradeRows->sum('reported_incoming'),
                'outgoing' => (int) $gradeRows->sum('reported_outgoing'),
                'non_mutation_outgoing' => (int) $gradeRows->sum('reported_non_mutation_outgoing'),
                'expected' => (int) $gradeRows->sum('reported_expected'),
                'actual' => (int) $gradeRows->sum('actual'),
                'unexplained_exit' => (int) $gradeRows->sum('unexplained_exit'),
                'other_addition' => (int) $gradeRows->sum('other_addition'),
            ];
        });
        $provisionalGradeSummary = collect([7, 8, 9])->map(function (int $grade) use ($rows): array {
            $gradeRows = $rows->where('grade', $grade);

            return [
                'grade' => $grade,
                'grade_label' => [7 => 'VII', 8 => 'VIII', 9 => 'IX'][$grade],
                'opening' => (int) $gradeRows->sum('opening'),
                'incoming' => (int) $gradeRows->sum('incoming'),
                'outgoing' => (int) $gradeRows->sum('outgoing'),
                'non_mutation_outgoing' => (int) $gradeRows->sum('non_mutation_outgoing'),
                'expected' => (int) $gradeRows->sum('expected'),
                'actual' => (int) $gradeRows->sum('actual'),
                'has_actual' => (int) $gradeRows->sum('reported_units') > 0,
            ];
        });
        $summary = [];
        foreach (['opening', 'incoming', 'outgoing', 'non_mutation_outgoing', 'expected', 'actual', 'unexplained_exit', 'other_addition'] as $field) {
            $summary[$field] = (int) $rows->sum($field);
        }
        $reportedSummary = [];
        foreach (['opening', 'incoming', 'outgoing', 'non_mutation_outgoing', 'expected'] as $field) {
            $reportedSummary[$field] = (int) $rows->sum("reported_{$field}");
        }
        $reportedSummary['actual'] = $summary['actual'];
        $reportedSummary['calculated_opening'] = $usesCalculatedOpening
            ? $reportedSummary['actual']
                + $reportedSummary['outgoing']
                + $reportedSummary['non_mutation_outgoing']
                - $reportedSummary['incoming']
            : $reportedSummary['opening'];
        $reportedSummary['opening_variance'] = $reportedSummary['opening']
            - $reportedSummary['calculated_opening'];
        $reportedSummary['unexplained_exit'] = $summary['unexplained_exit'];
        $reportedSummary['other_addition'] = $summary['other_addition'];

        $schoolCount = $countRows->pluck('satuan_pendidikan_id')->unique()->count();
        $reportedSchoolCount = $countRows
            ->groupBy('satuan_pendidikan_id')
            ->filter(fn (Collection $schoolRows) => $schoolRows->count() === 3
                && $schoolRows->every(fn (object $row) => $row->closing_confirmed_at !== null))
            ->count();

        return [
            'rows' => $rows->all(),
            'grade_summary' => $gradeSummary->all(),
            'provisional_grade_summary' => $provisionalGradeSummary->all(),
            'summary' => $summary,
            'reported_summary' => $reportedSummary,
            'uses_calculated_opening' => $usesCalculatedOpening,
            'is_provisional' => $isProvisional,
            'school_count' => $schoolCount,
            'reported_school_count' => $reportedSchoolCount,
            'missing_school_count' => max(0, $schoolCount - $reportedSchoolCount),
            'is_complete' => $schoolCount > 0 && $reportedSchoolCount === $schoolCount,
        ];
    }

    private function incomingTotals(AcademicYear $academicYear, Collection $npsns): Collection
    {
        if ($npsns->isEmpty() || ! $this->reportingSchema->longitudinalAvailable()) {
            return collect();
        }

        $query = DB::table('mutasi_masuk')
            ->join('mutasi_masuk_siswa', 'mutasi_masuk_siswa.id_mutasi', '=', 'mutasi_masuk.id_mutasi')
            ->select(
                'mutasi_masuk.npsn_sekolah_tujuan as npsn',
                'mutasi_masuk_siswa.kelas',
                'mutasi_masuk_siswa.jenis_kelamin',
                DB::raw('COUNT(*) as total')
            )
            ->where('mutasi_masuk.status', 'Selesai')
            ->whereNull('mutasi_masuk.deleted_at')
            ->whereIn('mutasi_masuk.npsn_sekolah_tujuan', $npsns)
            ->whereIn('mutasi_masuk_siswa.kelas', ['7', 'VII', '8', 'VIII', '9', 'IX']);

        $this->constrainToYear($query, 'mutasi_masuk.tanggal_pengajuan', $academicYear);

        return $this->indexMutationTotals($query
            ->groupBy('mutasi_masuk.npsn_sekolah_tujuan', 'mutasi_masuk_siswa.kelas', 'mutasi_masuk_siswa.jenis_kelamin')
            ->get());
    }

    private function outgoingTotals(AcademicYear $academicYear, Collection $npsns): Collection
    {
        if ($npsns->isEmpty() || ! $this->reportingSchema->longitudinalAvailable()) {
            return collect();
        }

        $query = DB::table('mutasi_keluar')
            ->select('npsn_sekolah_asal as npsn', 'kelas', 'jenis_kelamin', DB::raw('COUNT(*) as total'))
            ->where('status', 'Selesai')
            ->whereNull('deleted_at')
            ->whereIn('npsn_sekolah_asal', $npsns)
            ->whereIn('kelas', ['7', 'VII', '8', 'VIII', '9', 'IX']);

        $this->constrainToYear($query, 'tanggal_pengajuan', $academicYear);

        return $this->indexMutationTotals($query
            ->groupBy('npsn_sekolah_asal', 'kelas', 'jenis_kelamin')
            ->get());
    }

    private function nonMutationOutgoingTotals(AcademicYear $academicYear, Collection $npsns): Collection
    {
        if ($npsns->isEmpty() || ! $this->reportingSchema->hasNonMutationExits()) {
            return collect();
        }

        return $this->indexMutationTotals(DB::table('non_mutation_student_exits as exits')
            ->join('satuan_pendidikan as schools', 'schools.id', '=', 'exits.satuan_pendidikan_id')
            ->select(
                'schools.npsn',
                'exits.grade as kelas',
                'exits.gender as jenis_kelamin',
                DB::raw('COUNT(*) as total')
            )
            ->where('exits.academic_year_id', $academicYear->id)
            ->whereIn('schools.npsn', $npsns)
            ->groupBy('schools.npsn', 'exits.grade', 'exits.gender')
            ->get());
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

    private function indexMutationTotals(Collection $rows): Collection
    {
        return $rows->reduce(function (Collection $totals, object $row) {
            $key = $this->mutationKey(
                (string) $row->npsn,
                $this->gradeNumber((string) $row->kelas),
                (string) $row->jenis_kelamin
            );
            $totals[$key] = (int) ($totals[$key] ?? 0) + (int) $row->total;

            return $totals;
        }, collect());
    }

    private function gradeNumber(string $grade): int
    {
        return match (strtoupper($grade)) {
            '8', 'VIII' => 8,
            '9', 'IX' => 9,
            default => 7,
        };
    }

    private function mutationKey(string $npsn, int $grade, string $gender): string
    {
        return "{$npsn}|{$grade}|{$gender}";
    }

    private function rowKey(int $grade, string $gender): string
    {
        return "{$grade}|{$gender}";
    }

    private function emptyReport(): array
    {
        return [
            'rows' => [],
            'grade_summary' => [],
            'provisional_grade_summary' => [],
            'summary' => array_fill_keys([
                'opening', 'incoming', 'outgoing', 'non_mutation_outgoing', 'expected', 'actual', 'unexplained_exit', 'other_addition',
            ], 0),
            'reported_summary' => array_fill_keys([
                'opening', 'incoming', 'outgoing', 'non_mutation_outgoing', 'expected', 'actual',
                'calculated_opening', 'opening_variance', 'unexplained_exit', 'other_addition',
            ], 0),
            'uses_calculated_opening' => false,
            'is_provisional' => false,
            'school_count' => 0,
            'reported_school_count' => 0,
            'missing_school_count' => 0,
            'is_complete' => false,
        ];
    }
}
