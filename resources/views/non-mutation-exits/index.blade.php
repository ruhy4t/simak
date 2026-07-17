@extends('layouts.app')

@section('title', 'Siswa Keluar Non-Mutasi')

@section('content')
    @php($canEnter = in_array(Auth::user()->role, ['Sekolah', 'Operator'], true))
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-600">Pendataan Per Siswa</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Siswa Keluar Non-Mutasi</h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">Catat siswa yang keluar tetapi tidak memiliki pengajuan mutasi resmi. Data langsung masuk laporan tanpa persetujuan Dinas.</p>
            </div>
            @if($canEnter)
                <a href="{{ route('non-mutation-exits.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-3 font-bold text-white hover:bg-rose-700">
                    <i class="bi bi-person-plus-fill"></i> Tambah Data Siswa
                </a>
            @endif
        </div>

        <section class="rounded-2xl border border-rose-100 bg-rose-50 p-5 text-rose-900 sm:max-w-sm">
            <p class="text-sm font-semibold text-rose-700">Total Siswa Tercatat</p>
            <p class="mt-1 text-3xl font-black">{{ number_format($total) }}</p>
            <p class="mt-1 text-xs text-rose-600">Sesuai tahun pelajaran dan sekolah pada filter.</p>
        </section>

        <form method="GET" action="{{ route('non-mutation-exits.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4 md:items-end">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500" for="exit-year">Tahun Pelajaran</label>
                <select id="exit-year" name="academic_year" class="w-full rounded-xl border-slate-300 text-sm">
                    @foreach($academicYears as $year)<option value="{{ $year->id }}" @selected($selectedYear?->id === $year->id)>{{ $year->name }}</option>@endforeach
                </select>
            </div>
            @if($schools->isNotEmpty())
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500" for="exit-school">Sekolah</label>
                    <select id="exit-school" name="school" class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="">Semua sekolah</option>
                        @foreach($schools as $school)<option value="{{ $school->id }}" @selected(request('school') == $school->id)>{{ $school->nama }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500" for="exit-search">Nama/NISN</label>
                <input id="exit-search" name="search" value="{{ request('search') }}" class="w-full rounded-xl border-slate-300 text-sm" placeholder="Cari data...">
            </div>
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700"><i class="bi bi-funnel"></i> Terapkan</button>
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[72rem] text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Siswa</th><th class="px-4 py-3 text-left">Sekolah</th>
                            <th class="px-4 py-3 text-center">Kelas/JK</th><th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Alasan</th>
                            <th class="px-4 py-3 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $record)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-4 py-4"><p class="font-bold text-slate-900">{{ $record->student_name }}</p><p class="mt-0.5 text-xs text-slate-500">NISN: {{ $record->nisn ?: 'Tidak diketahui' }}</p></td>
                                <td class="px-4 py-4"><p class="font-semibold text-slate-700">{{ $record->school->nama }}</p><p class="text-xs text-slate-500">{{ $record->academicYear->name }}</p></td>
                                <td class="px-4 py-4 text-center font-bold text-slate-700">{{ [7 => 'VII', 8 => 'VIII', 9 => 'IX'][$record->grade] }} / {{ $record->gender }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $record->exit_date->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-4"><p class="font-semibold text-slate-700">{{ $record->reason }}</p>@if($record->notes)<p class="mt-1 max-w-xs text-xs leading-5 text-slate-500">{{ $record->notes }}</p>@endif</td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        @if($canEnter)
                                            <a href="{{ route('non-mutation-exits.edit', $record) }}" class="rounded-lg bg-blue-50 px-3 py-2 font-bold text-blue-700 hover:bg-blue-100">Edit</a>
                                            <form method="POST" action="{{ route('non-mutation-exits.destroy', $record) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="rounded-lg bg-rose-50 px-3 py-2 font-bold text-rose-700 hover:bg-rose-100">Hapus</button></form>
                                        @endif
                                    </div>
                                    @unless($canEnter)<p class="text-right text-xs text-slate-400">Hanya lihat</p>@endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-16 text-center text-slate-500"><i class="bi bi-person-check text-3xl"></i><p class="mt-3 font-semibold">Belum ada data siswa keluar non-mutasi pada filter ini.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($records->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $records->links() }}</div>@endif
        </section>
    </div>
@endsection
