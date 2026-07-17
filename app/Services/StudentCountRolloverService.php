<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentCountRolloverService
{
    /**
     * Promote the prior year's final grade 7/8 balances into grade 8/9.
     * All mutation totals are aggregated in a fixed number of queries.
     */
    public function rollover(AcademicYear $fromYear, AcademicYear $toYear): void
    {
        if ($toYear->student_counts_rolled_over_at) {
            return;
        }

        $schools = DB::table('satuan_pendidikan')->select('id', 'npsn')->get();
        $previousCounts = DB::table('satuan_pendidikan_student_counts')
            ->where('academic_year_id', $fromYear->id)
            ->whereIn('grade', [7, 8])
            ->get()
            ->keyBy(fn (object $row) => $this->countKey(
                (int) $row->satuan_pendidikan_id,
                (int) $row->grade
            ));
        $hasClosingCounts = Schema::hasColumns('satuan_pendidikan_student_counts', [
            'closing_male_count',
            'closing_female_count',
            'closing_confirmed_at',
        ]);

        $incoming = $this->incomingTotals($fromYear);
        $outgoing = $this->outgoingTotals($fromYear);
        $nonMutationOutgoing = $this->nonMutationOutgoingTotals($fromYear);
        $now = now();
        $rows = [];

        foreach ($schools as $school) {
            foreach ([7 => 8, 8 => 9] as $sourceGrade => $targetGrade) {
                $countRow = $previousCounts->get($this->countKey((int) $school->id, $sourceGrade));

                foreach (['L' => 'male_count', 'P' => 'female_count'] as $gender => $column) {
                    $mutationKey = $this->mutationKey((string) $school->npsn, $sourceGrade, $gender);
                    $opening = (int) ($countRow->{$column} ?? 0);
                    $closingColumn = $gender === 'L' ? 'closing_male_count' : 'closing_female_count';
                    $hasConfirmedClosing = $hasClosingCounts
                        && $countRow?->closing_confirmed_at !== null
                        && $countRow?->{$closingColumn} !== null;
                    $final = $hasConfirmedClosing
                        ? (int) $countRow->{$closingColumn}
                        : max(
                            0,
                            $opening
                                + (int) ($incoming[$mutationKey] ?? 0)
                                - (int) ($outgoing[$mutationKey] ?? 0)
                                - (int) ($nonMutationOutgoing[$mutationKey] ?? 0)
                        );

                    $rowKey = $this->countKey((int) $school->id, $targetGrade);
                    if (! isset($rows[$rowKey])) {
                        $rows[$rowKey] = [
                            'satuan_pendidikan_id' => (int) $school->id,
                            'academic_year_id' => (int) $toYear->id,
                            'grade' => $targetGrade,
                            'male_count' => 0,
                            'female_count' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    $rows[$rowKey][$column] = $final;
                }
            }
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('satuan_pendidikan_student_counts')->upsert(
                $chunk,
                ['satuan_pendidikan_id', 'academic_year_id', 'grade'],
                ['male_count', 'female_count', 'updated_at']
            );
        }

        $toYear->forceFill([
            'student_counts_rolled_over_from_id' => $fromYear->id,
            'student_counts_rolled_over_at' => $now,
        ])->save();
    }

    private function outgoingTotals(AcademicYear $academicYear): Collection
    {
        if (! Schema::hasTable('mutasi_keluar')) {
            return collect();
        }

        $query = DB::table('mutasi_keluar')
            ->select('npsn_sekolah_asal as npsn', 'kelas', 'jenis_kelamin', DB::raw('COUNT(*) as total'))
            ->where('status', 'Selesai')
            ->whereIn('kelas', ['7', 'VII', '8', 'VIII']);
        $this->constrainToYear($query, 'tanggal_pengajuan', $academicYear);

        return $this->indexMutationTotals($query
            ->groupBy('npsn_sekolah_asal', 'kelas', 'jenis_kelamin')
            ->get());
    }

    private function incomingTotals(AcademicYear $academicYear): Collection
    {
        if (! Schema::hasTable('mutasi_masuk') || ! Schema::hasTable('mutasi_masuk_siswa')) {
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
            ->whereIn('mutasi_masuk_siswa.kelas', ['7', 'VII', '8', 'VIII']);
        $this->constrainToYear($query, 'mutasi_masuk.tanggal_pengajuan', $academicYear);

        return $this->indexMutationTotals($query
            ->groupBy(
                'mutasi_masuk.npsn_sekolah_tujuan',
                'mutasi_masuk_siswa.kelas',
                'mutasi_masuk_siswa.jenis_kelamin'
            )
            ->get());
    }

    private function nonMutationOutgoingTotals(AcademicYear $academicYear): Collection
    {
        if (! Schema::hasTable('non_mutation_student_exits')) {
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
            ->whereIn('exits.grade', [7, 8])
            ->groupBy('schools.npsn', 'exits.grade', 'exits.gender')
            ->get());
    }

    private function constrainToYear($query, $column, AcademicYear $academicYear): void
    {
        if ($academicYear->starts_on) {
            $query->whereDate($column, '>=', $academicYear->starts_on->format('Y-m-d'));
        }
        if ($academicYear->ends_on) {
            $query->whereDate($column, '<=', $academicYear->ends_on->format('Y-m-d'));
        }
    }

    private function gradeNumber(string $grade): int
    {
        return in_array($grade, ['8', 'VIII'], true) ? 8 : 7;
    }

    private function indexMutationTotals(Collection $rows): Collection
    {
        $totals = collect();

        foreach ($rows as $row) {
            $key = $this->mutationKey(
                (string) $row->npsn,
                $this->gradeNumber((string) $row->kelas),
                (string) $row->jenis_kelamin
            );
            $totals[$key] = (int) ($totals[$key] ?? 0) + (int) $row->total;
        }

        return $totals;
    }

    private function mutationKey(string $npsn, int $grade, string $gender): string
    {
        return "{$npsn}|{$grade}|{$gender}";
    }

    private function countKey(int $schoolId, int $grade): string
    {
        return "{$schoolId}|{$grade}";
    }
}
