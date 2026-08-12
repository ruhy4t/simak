<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use App\Models\SatuanPendidikanStudentCount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureInitialStudentCountsCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['Sekolah', 'Operator'], true)) {
            return $next($request);
        }

        $school = $user->sekolah;
        $academicYear = $this->activeAcademicYear();
        $previousAcademicYear = $academicYear
            ? $this->previousAcademicYear($academicYear)
            : null;
        $missing = [];
        $redirectRoute = 'sekolah.profile';
        $redirectParameters = [];

        if (! $school || ! $this->profileIsComplete($school)) {
            $missing[] = 'Profil Sekolah (nama dan NIP kepala sekolah serta kop surat)';
        }

        $counts = collect();
        if ($academicYear && $school) {
            $counts = SatuanPendidikanStudentCount::query()
                ->where('satuan_pendidikan_id', $school->id)
                ->where('academic_year_id', $academicYear->id)
                ->whereIn('grade', [7, 8, 9])
                ->get()
                ->keyBy('grade');
        }

        $initialCountsComplete = $counts->count() === 3
            && $counts->has(7)
            && $counts[7]->confirmed_at !== null;
        if (! $initialCountsComplete) {
            $missing[] = "Data Murid Awal kelas VII, VIII, dan IX tahun pelajaran {$academicYear?->name}";
        }

        if ($previousAcademicYear && $school) {
            $previousCounts = SatuanPendidikanStudentCount::query()
                ->where('satuan_pendidikan_id', $school->id)
                ->where('academic_year_id', $previousAcademicYear->id)
                ->whereIn('grade', [7, 8, 9])
                ->get()
                ->keyBy('grade');

            $closingCountsComplete = $previousCounts->count() === 3
                && collect([7, 8, 9])->every(
                    fn (int $grade) => $previousCounts->has($grade)
                        && $previousCounts[$grade]->closing_confirmed_at !== null
                );

            if (! $closingCountsComplete) {
                $missing[] = "Data Akhir kelas VII, VIII, dan IX tahun pelajaran {$previousAcademicYear->name}";

                if (count($missing) === 1) {
                    $redirectRoute = 'sekolah.closing-counts';
                    $redirectParameters = ['academic_year' => $previousAcademicYear->name];
                }
            }
        }

        if ($academicYear && $missing === []) {
            return $next($request);
        }

        $message = $academicYear
            ? 'Lengkapi '.implode(' serta ', $missing).' sebelum mengajukan mutasi.'
            : 'Tahun pelajaran aktif belum tersedia. Hubungi Dinas sebelum mengajukan mutasi.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => route($redirectRoute, $redirectParameters),
            ], 422);
        }

        // Remember the mutation form that triggered the prerequisite check so
        // the school can continue there immediately after completing its data.
        if ($request->hasSession()) {
            $request->session()->put('mutation.intended_url', $request->fullUrl());
        }

        return redirect()->route($redirectRoute, $redirectParameters)->with('error', $message);
    }

    private function profileIsComplete(object $school): bool
    {
        return filled($school->kepala_sekolah_nama)
            && filled($school->kepala_sekolah_nip)
            && filled($school->kop_surat_path);
    }

    private function activeAcademicYear(): ?AcademicYear
    {
        if (! Schema::hasTable('academic_years')
            || ! Schema::hasTable('satuan_pendidikan_student_counts')
            || ! Schema::hasColumns('satuan_pendidikan_student_counts', [
                'confirmed_at',
            ])) {
            return null;
        }

        return AcademicYear::query()->where('is_active', true)->first();
    }

    private function previousAcademicYear(AcademicYear $academicYear): ?AcademicYear
    {
        if (preg_match('/^(\d{4})\/(\d{4})$/', $academicYear->name, $matches)) {
            $previousName = ((int) $matches[1] - 1).'/'.$matches[1];
            $previousAcademicYear = AcademicYear::query()
                ->where('name', $previousName)
                ->first();

            if ($previousAcademicYear) {
                return $previousAcademicYear;
            }
        }

        return AcademicYear::query()
            ->where('id', '!=', $academicYear->id)
            ->where('status', AcademicYear::STATUS_CLOSED)
            ->when(
                $academicYear->starts_on,
                fn ($query) => $query->where('starts_on', '<', $academicYear->starts_on)
            )
            ->orderByDesc('starts_on')
            ->orderByDesc('name')
            ->first();
    }
}
