<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\StudentCountRolloverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AcademicYearController extends Controller
{
    public function __construct(private readonly StudentCountRolloverService $rolloverService) {}

    public function store(Request $request)
    {
        $this->ensureTablesAreReady();

        $validated = $request->validate([
            'name' => ['required', 'regex:/^\d{4}\/\d{4}$/', 'unique:academic_years,name'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ], [
            'name.regex' => 'Format tahun pelajaran harus seperti 2026/2027.',
            'name.unique' => 'Tahun pelajaran tersebut sudah tersedia.',
            'ends_on.after' => 'Tanggal selesai harus setelah tanggal mulai.',
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
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
        ]);

        return back()->with('success', 'Tahun pelajaran berhasil ditambahkan sebagai tahun mendatang.');
    }

    public function updateSchedule(Request $request, int $academicYearId)
    {
        $this->ensureTablesAreReady();

        $academicYear = AcademicYear::findOrFail($academicYearId);
        $validated = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ], [
            'ends_on.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        $academicYear->update($validated);

        return back()->with('success', "Periode tahun pelajaran {$academicYear->name} berhasil diperbarui.");
    }

    public function activate(int $academicYearId)
    {
        $this->ensureTablesAreReady();

        $academicYear = AcademicYear::findOrFail($academicYearId);

        DB::transaction(function () use ($academicYear) {
            $previousAcademicYear = AcademicYear::query()
                ->where('is_active', true)
                ->where('id', '!=', $academicYear->id)
                ->first();

            $isNewerYear = $previousAcademicYear?->starts_on
                && $academicYear->starts_on
                && $academicYear->starts_on->gt($previousAcademicYear->starts_on);

            if ($isNewerYear && ! $academicYear->student_counts_rolled_over_at) {
                $this->rolloverService->rollover($previousAcademicYear, $academicYear);
            }

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
        $academicYearId = (int) $academicYear->id;
        $values = ['updated_at' => now()];

        foreach ([7, 8, 9] as $grade) {
            $baseQuery = 'SELECT %s FROM satuan_pendidikan_student_counts '
                .'WHERE satuan_pendidikan_id = satuan_pendidikan.id '
                ."AND academic_year_id = {$academicYearId} AND grade = {$grade} LIMIT 1";

            $values["jumlah_siswa_{$grade}_l"] = DB::raw(
                'COALESCE(('.sprintf($baseQuery, 'male_count').'), 0)'
            );
            $values["jumlah_siswa_{$grade}_p"] = DB::raw(
                'COALESCE(('.sprintf($baseQuery, 'female_count').'), 0)'
            );
            $values["jumlah_siswa_awal_{$grade}"] = DB::raw(
                'COALESCE(('.sprintf($baseQuery, 'male_count + female_count').'), 0)'
            );
        }

        DB::table('satuan_pendidikan')->update($values);
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
