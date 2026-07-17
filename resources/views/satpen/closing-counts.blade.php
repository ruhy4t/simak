@extends('layouts.app')

@section('title', 'Data Akhir Aktual')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600">Rekonsiliasi Kohort</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Data Akhir Aktual</h1>
                <p class="mt-1 text-sm text-slate-500">Isi jumlah siswa aktual per kelas dan jenis kelamin tanpa data nama siswa.</p>
            </div>
            <a href="{{ route('sekolah.profile') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-blue-700">
                <i class="bi bi-arrow-left"></i> Kembali ke Profil
            </a>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
            <div class="flex gap-3">
                <i class="bi bi-shield-lock-fill mt-0.5 text-xl"></i>
                <div>
                    <p class="font-bold">Data akan dikunci setelah disimpan.</p>
                    <p class="mt-1 text-sm leading-6">Periksa seluruh angka dengan teliti. Tidak diperlukan dokumen atau identitas siswa.</p>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('sekolah.closing-counts') }}" class="rounded-2xl border border-slate-200 bg-white p-5">
            <label for="academic-year" class="mb-2 block text-sm font-bold text-slate-700">Tahun Pelajaran</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <select id="academic-year" name="academic_year" class="min-w-0 flex-1 rounded-xl border-slate-300 font-semibold focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach($academicYears as $year)
                        <option value="{{ $year->name }}" @selected($selectedAcademicYear?->id === $year->id)>
                            {{ $year->name }} {{ $year->is_active ? '(Aktif)' : '(Ditutup)' }}
                        </option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-950">Tampilkan</button>
            </div>
        </form>

        @if($selectedAcademicYear)
            <form method="POST" action="{{ route('sekolah.closing-counts.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYear->id }}">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-slate-900">{{ $satpen->nama }}</h2>
                            <p class="text-xs text-slate-500">Tahun Pelajaran {{ $selectedAcademicYear->name }}</p>
                        </div>
                        @if($isLocked)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800"><i class="bi bi-lock-fill"></i> Terkunci</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800"><i class="bi bi-pencil-fill"></i> Belum dikunci</span>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[42rem] text-sm">
                        <thead class="bg-white text-left text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Kelas</th>
                                <th class="px-6 py-4 text-center">Data Awal L</th>
                                <th class="px-6 py-4 text-center">Data Akhir L</th>
                                <th class="px-6 py-4 text-center">Data Awal P</th>
                                <th class="px-6 py-4 text-center">Data Akhir P</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach([7 => 'VII', 8 => 'VIII', 9 => 'IX'] as $grade => $label)
                                @php($row = $countRows->get($grade))
                                <tr>
                                    <td class="px-6 py-4 text-lg font-black text-slate-800">{{ $label }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-500">{{ number_format($row?->male_count ?? 0) }}</td>
                                    <td class="px-6 py-4">
                                        <input type="number" min="0" max="99999" required name="closing_{{ $grade }}_male"
                                            value="{{ old("closing_{$grade}_male", $row?->closing_male_count) }}" @readonly($isLocked)
                                            class="w-full rounded-xl border-2 border-blue-200 px-3 py-2.5 text-center text-lg font-bold focus:border-blue-500 focus:ring-blue-500">
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-500">{{ number_format($row?->female_count ?? 0) }}</td>
                                    <td class="px-6 py-4">
                                        <input type="number" min="0" max="99999" required name="closing_{{ $grade }}_female"
                                            value="{{ old("closing_{$grade}_female", $row?->closing_female_count) }}" @readonly($isLocked)
                                            class="w-full rounded-xl border-2 border-pink-200 px-3 py-2.5 text-center text-lg font-bold focus:border-pink-500 focus:ring-pink-500">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @unless($isLocked)
                    <div class="space-y-4 border-t border-slate-200 bg-slate-50 px-5 py-5 sm:px-6">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4">
                            <input type="checkbox" name="confirmation" value="1" required class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm leading-6 text-slate-700">Saya memastikan seluruh data sudah benar dan memahami bahwa data akan dikunci setelah disimpan.</span>
                        </label>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3.5 font-bold text-white hover:bg-emerald-700">
                            <i class="bi bi-lock-fill"></i> Simpan dan Kunci Data Akhir
                        </button>
                    </div>
                @endunless
            </form>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Tahun pelajaran belum tersedia.</div>
        @endif
    </div>
@endsection
