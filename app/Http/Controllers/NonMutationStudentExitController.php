<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\NonMutationStudentExit;
use App\Models\SatuanPendidikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NonMutationStudentExitController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $academicYears = AcademicYear::query()->orderByDesc('name')->get();
        $selectedYear = $academicYears->firstWhere('id', $request->integer('academic_year'))
            ?? $academicYears->firstWhere('is_active', true)
            ?? $academicYears->first();

        $query = NonMutationStudentExit::query()
            ->with(['school:id,npsn,nama', 'academicYear:id,name'])
            ->latest('exit_date')
            ->latest('id');

        if ($selectedYear) {
            $query->where('academic_year_id', $selectedYear->id);
        }
        if (in_array($user->role, ['Sekolah', 'Operator'], true)) {
            abort_unless($user->sekolah, 404, 'Data sekolah tidak ditemukan.');
            $query->where('satuan_pendidikan_id', $user->sekolah->id);
        } elseif ($request->filled('school')) {
            $query->where('satuan_pendidikan_id', $request->integer('school'));
        }
        $total = (clone $query)->count();
        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(fn ($item) => $item
                ->where('student_name', 'like', "%{$search}%")
                ->orWhere('nisn', 'like', "%{$search}%"));
        }

        $records = $query->paginate(20)->withQueryString();
        $schools = in_array($user->role, ['Dinas', 'SuperAdmin'], true)
            ? SatuanPendidikan::query()->local()->orderBy('nama')->get(['id', 'npsn', 'nama'])
            : collect();

        return view('non-mutation-exits.index', compact(
            'records', 'academicYears', 'selectedYear', 'schools', 'total'
        ));
    }

    public function create()
    {
        $school = $this->schoolForEntry();
        $academicYears = AcademicYear::query()
            ->whereIn('status', [AcademicYear::STATUS_ACTIVE, AcademicYear::STATUS_CLOSED])
            ->orderByDesc('name')
            ->get();

        return view('non-mutation-exits.form', [
            'record' => new NonMutationStudentExit,
            'school' => $school,
            'academicYears' => $academicYears,
        ]);
    }

    public function store(Request $request)
    {
        $school = $this->schoolForEntry();
        $validated = $this->validateRecord($request, $school);

        NonMutationStudentExit::create($validated + [
            'satuan_pendidikan_id' => $school->id,
            'reported_by' => Auth::id(),
        ]);

        $this->forgetCohortCache((int) $validated['academic_year_id']);

        return redirect()->route('non-mutation-exits.index', ['academic_year' => $validated['academic_year_id']])
            ->with('success', 'Data siswa keluar non-mutasi berhasil disimpan.');
    }

    public function edit(NonMutationStudentExit $nonMutationExit)
    {
        $this->authorizeEditable($nonMutationExit);

        return view('non-mutation-exits.form', [
            'record' => $nonMutationExit,
            'school' => $nonMutationExit->school,
            'academicYears' => AcademicYear::query()
                ->whereIn('status', [AcademicYear::STATUS_ACTIVE, AcademicYear::STATUS_CLOSED])
                ->orderByDesc('name')
                ->get(),
        ]);
    }

    public function update(Request $request, NonMutationStudentExit $nonMutationExit)
    {
        $this->authorizeEditable($nonMutationExit);
        $validated = $this->validateRecord($request, $nonMutationExit->school, $nonMutationExit);
        $oldYearId = $nonMutationExit->academic_year_id;

        $nonMutationExit->update($validated);

        $this->forgetCohortCache((int) $oldYearId);
        $this->forgetCohortCache((int) $validated['academic_year_id']);

        return redirect()->route('non-mutation-exits.index', ['academic_year' => $validated['academic_year_id']])
            ->with('success', 'Data siswa keluar non-mutasi berhasil diperbarui.');
    }

    public function destroy(NonMutationStudentExit $nonMutationExit)
    {
        $this->authorizeEditable($nonMutationExit);
        $yearId = $nonMutationExit->academic_year_id;
        $nonMutationExit->delete();
        $this->forgetCohortCache((int) $yearId);

        return back()->with('success', 'Data siswa keluar non-mutasi berhasil dihapus.');
    }

    private function validateRecord(
        Request $request,
        SatuanPendidikan $school,
        ?NonMutationStudentExit $record = null
    ): array {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'student_name' => ['required', 'string', 'max:150'],
            'nisn' => [
                'required',
                'digits:10',
                Rule::unique('non_mutation_student_exits', 'nisn')
                    ->where(fn ($query) => $query
                        ->where('satuan_pendidikan_id', $school->id)
                        ->where('academic_year_id', $request->integer('academic_year_id')))
                    ->ignore($record?->id),
            ],
            'grade' => ['required', 'integer', Rule::in([7, 8, 9])],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'exit_date' => ['required', 'date'],
            'reason' => ['required', Rule::in(NonMutationStudentExit::REASONS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
        if (($academicYear->starts_on && $validated['exit_date'] < $academicYear->starts_on->toDateString())
            || ($academicYear->ends_on && $validated['exit_date'] > $academicYear->ends_on->toDateString())) {
            throw ValidationException::withMessages([
                'exit_date' => "Tanggal keluar harus berada dalam periode tahun pelajaran {$academicYear->name}.",
            ]);
        }

        return $validated;
    }

    private function schoolForEntry(): SatuanPendidikan
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Sekolah', 'Operator'], true), 403);
        abort_unless($user->sekolah, 404, 'Data sekolah tidak ditemukan.');

        return $user->sekolah;
    }

    private function authorizeEditable(NonMutationStudentExit $record): void
    {
        $school = $this->schoolForEntry();
        abort_unless($record->satuan_pendidikan_id === $school->id, 403);
    }

    private function forgetCohortCache(int $academicYearId): void
    {
        Cache::forget("public.statistics.cohort.v1.{$academicYearId}");
    }
}
