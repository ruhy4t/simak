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

        $academicYear = $this->activeAcademicYear();
        $school = $user->sekolah;
        $isCompleted = $academicYear && $school
            && SatuanPendidikanStudentCount::query()
                ->where('satuan_pendidikan_id', $school->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('grade', 7)
                ->whereNotNull('confirmed_at')
                ->exists();

        if ($isCompleted) {
            return $next($request);
        }

        $message = $academicYear
            ? "Lengkapi dan simpan Data Siswa Awal tahun pelajaran {$academicYear->name} pada Profil Sekolah sebelum mengajukan mutasi."
            : 'Tahun pelajaran aktif belum tersedia. Hubungi Dinas sebelum mengajukan mutasi.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => route('sekolah.profile'),
            ], 422);
        }

        return redirect()->route('sekolah.profile')->with('error', $message);
    }

    private function activeAcademicYear(): ?AcademicYear
    {
        if (! Schema::hasTable('academic_years')
            || ! Schema::hasTable('satuan_pendidikan_student_counts')
            || ! Schema::hasColumn('satuan_pendidikan_student_counts', 'confirmed_at')) {
            return null;
        }

        return AcademicYear::query()->where('is_active', true)->first();
    }
}
