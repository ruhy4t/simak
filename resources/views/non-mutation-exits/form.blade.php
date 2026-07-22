@extends('layouts.app')

@section('title', $record->exists ? 'Edit Murid Keluar Non-Mutasi' : 'Tambah Murid Keluar Non-Mutasi')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('non-mutation-exits.index') }}" class="text-sm font-bold text-slate-500 hover:text-rose-700"><i class="bi bi-arrow-left"></i> Kembali</a>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ $record->exists ? 'Perbaiki Data Murid' : 'Tambah Murid Keluar Non-Mutasi' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Pendataan individual tanpa dokumen pendukung dan tanpa proses persetujuan Dinas.</p>
        </div>

        <form method="POST" action="{{ $record->exists ? route('non-mutation-exits.update', $record) : route('non-mutation-exits.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            @csrf
            @if($record->exists) @method('PUT') @endif
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4"><p class="font-bold text-slate-900">{{ $school->nama }}</p><p class="text-xs text-slate-500">NPSN {{ $school->npsn }}</p></div>
            <div class="grid gap-5 p-6 sm:grid-cols-2">
                <div>
                    <label for="academic-year" class="mb-2 block text-sm font-bold text-slate-700">Tahun Pelajaran <span class="text-rose-600">*</span></label>
                    <select id="academic-year" name="academic_year_id" required class="w-full rounded-xl border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id', $record->academic_year_id ?: $academicYears->firstWhere('is_active', true)?->id) == $year->id)>{{ $year->name }}{{ $year->is_active ? ' (Aktif)' : '' }}</option>@endforeach
                    </select>
                    @error('academic_year_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="student-name" class="mb-2 block text-sm font-bold text-slate-700">Nama Murid <span class="text-rose-600">*</span></label>
                    <input id="student-name" name="student_name" required maxlength="150" value="{{ old('student_name', $record->student_name) }}" class="w-full rounded-xl border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    @error('student_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nisn" class="mb-2 block text-sm font-bold text-slate-700">NISN <span class="text-rose-600">*</span></label>
                    <input id="nisn" name="nisn" required inputmode="numeric" minlength="10" maxlength="10" value="{{ old('nisn', $record->nisn) }}" class="w-full rounded-xl border-slate-300 font-mono focus:border-rose-500 focus:ring-rose-500" placeholder="Wajib 10 digit">
                    @error('nisn')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label for="grade" class="mb-2 block text-sm font-bold text-slate-700">Kelas <span class="text-rose-600">*</span></label><select id="grade" name="grade" required class="w-full rounded-xl border-slate-300">@foreach([7 => 'VII', 8 => 'VIII', 9 => 'IX'] as $value => $label)<option value="{{ $value }}" @selected(old('grade', $record->grade) == $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><label for="gender" class="mb-2 block text-sm font-bold text-slate-700">Jenis Kelamin <span class="text-rose-600">*</span></label><select id="gender" name="gender" required class="w-full rounded-xl border-slate-300"><option value="L" @selected(old('gender', $record->gender) === 'L')>Laki-laki</option><option value="P" @selected(old('gender', $record->gender) === 'P')>Perempuan</option></select></div>
                </div>
                <div>
                    <label for="exit-date" class="mb-2 block text-sm font-bold text-slate-700">Tanggal Keluar/Diketahui <span class="text-rose-600">*</span></label>
                    <input id="exit-date" type="date" name="exit_date" required value="{{ old('exit_date', $record->exit_date?->format('Y-m-d')) }}" class="w-full rounded-xl border-slate-300">
                    @error('exit_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="reason" class="mb-2 block text-sm font-bold text-slate-700">Keterangan/Alasan <span class="text-rose-600">*</span></label>
                    <select id="reason" name="reason" required class="w-full rounded-xl border-slate-300">@foreach(\App\Models\NonMutationStudentExit::REASONS as $reason)<option value="{{ $reason }}" @selected(old('reason', $record->reason ?: 'Tanpa keterangan') === $reason)>{{ $reason }}</option>@endforeach</select>
                </div>
                <div class="sm:col-span-2">
                    <label for="notes" class="mb-2 block text-sm font-bold text-slate-700">Catatan Tambahan <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea id="notes" name="notes" maxlength="2000" rows="4" class="w-full rounded-xl border-slate-300" placeholder="Informasi tambahan yang diketahui sekolah...">{{ old('notes', $record->notes) }}</textarea>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:justify-end">
                <a href="{{ route('non-mutation-exits.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-center font-bold text-slate-700 hover:bg-white">Batal</a>
                <button class="rounded-xl bg-rose-600 px-6 py-3 font-bold text-white hover:bg-rose-700"><i class="bi bi-check-circle-fill"></i> {{ $record->exists ? 'Simpan Perubahan' : 'Simpan Data' }}</button>
            </div>
        </form>
    </div>
@endsection
