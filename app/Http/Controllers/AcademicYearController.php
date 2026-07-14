<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AcademicYearController extends Controller
{
    public function store(Request $request)
    {
        $this->ensureTablesAreReady();

        $validated = $request->validate([
            'name' => ['required', 'regex:/^\d{4}\/\d{4}$/', 'unique:academic_years,name'],
        ], [
            'name.regex' => 'Format tahun pelajaran harus seperti 2026/2027.',
            'name.unique' => 'Tahun pelajaran tersebut sudah tersedia.',
        ]);

        [$startYear, $endYear] = array_map('intval', explode('/', $validated['name']));

        if ($endYear !== $startYear + 1) {
            throw ValidationException::withMessages([
                'name' => 'Rentang tahun pelajaran harus berurutan, misalnya 2026/2027.',
            ]);
        }

        AcademicYear::create([
            'name' => $validated['name'],
            'status' => AcademicYear::STATUS_UPCOMING,
            'is_active' => false,
            'starts_on' => "{$startYear}-07-01",
            'ends_on' => "{$endYear}-06-30",
        ]);

        return back()->with('success', 'Tahun pelajaran berhasil ditambahkan sebagai tahun mendatang.');
    }

    public function activate(int $academicYearId)
    {
        $this->ensureTablesAreReady();

        $academicYear = AcademicYear::findOrFail($academicYearId);

        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()
                ->where('is_active', true)
                ->where('id', '!=', $academicYear->id)
                ->update([
                    'is_active' => false,
                    'status' => AcademicYear::STATUS_CLOSED,
                    'updated_at' => now(),
                ]);

            $academicYear->update([
                'is_active' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);

            $this->syncLegacyStudentCounts($academicYear);
        });

        return back()->with('success', "Tahun pelajaran {$academicYear->name} sekarang aktif.");
    }

    private function syncLegacyStudentCounts(AcademicYear $academicYear): void
    {
        DB::table('satuan_pendidikan')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($schools) use ($academicYear) {
                $schoolIds = $schools->pluck('id');
                $counts = DB::table('satuan_pendidikan_student_counts')
                    ->where('academic_year_id', $academicYear->id)
                    ->whereIn('satuan_pendidikan_id', $schoolIds)
                    ->get()
                    ->groupBy('satuan_pendidikan_id');

                foreach ($schoolIds as $schoolId) {
                    $schoolCounts = $counts->get($schoolId, collect())->keyBy('grade');
                    $values = ['updated_at' => now()];

                    foreach ([7, 8, 9] as $grade) {
                        $male = (int) ($schoolCounts->get($grade)->male_count ?? 0);
                        $female = (int) ($schoolCounts->get($grade)->female_count ?? 0);

                        $values["jumlah_siswa_{$grade}_l"] = $male;
                        $values["jumlah_siswa_{$grade}_p"] = $female;
                        $values["jumlah_siswa_awal_{$grade}"] = $male + $female;
                    }

                    DB::table('satuan_pendidikan')->where('id', $schoolId)->update($values);
                }
            });
    }

    private function ensureTablesAreReady(): void
    {
        abort_unless(
            Schema::hasTable('academic_years') && Schema::hasTable('satuan_pendidikan_student_counts'),
            503,
            'Migrasi tahun pelajaran belum dijalankan.'
        );
    }
}
