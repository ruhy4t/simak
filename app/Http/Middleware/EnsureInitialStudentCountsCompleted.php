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
        $missing = [];
        $redirectRoute = 'sekolah.profile';

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
            $missing[] = 'Data Murid Awal kelas VII, VIII, dan IX';
        }

        $closingCountsComplete = $counts->count() === 3
            && $counts->every(fn (SatuanPendidikanStudentCount $count) => $count->closing_confirmed_at !== null
                && $count->closing_male_count !== null
                && $count->closing_female_count !== null
            );
        if (! $closingCountsComplete) {
            $missing[] = 'Data Akhir kelas VII, VIII, dan IX';
            if ($initialCountsComplete && $school && $this->profileIsComplete($school)) {
                $redirectRoute = 'sekolah.closing-counts';
            }
        }

        if ($academicYear && $missing === []) {
            return $next($request);
        }

        $message = $academicYear
            ? 'Lengkapi '.implode(', ', $missing)." tahun pelajaran {$academicYear->name} sebelum mengajukan mutasi."
            : 'Tahun pelajaran aktif belum tersedia. Hubungi Dinas sebelum mengajukan mutasi.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => route($redirectRoute),
            ], 422);
        }

        return redirect()->route($redirectRoute)->with('error', $message);
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
                'closing_male_count',
                'closing_female_count',
                'closing_confirmed_at',
            ])) {
            return null;
        }

        return AcademicYear::query()->where('is_active', true)->first();
    }
}
