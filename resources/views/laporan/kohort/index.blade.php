@extends('layouts.app')

@section('title', 'Rekonsiliasi Tahunan dan Kohort Angkatan')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600">Laporan Agregat</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Rekonsiliasi Tahunan & Kohort Angkatan</h1>
            <p class="mt-1 text-sm text-slate-500">Bandingkan data awal dan akhir per tahun, lalu ikuti perkembangan satu angkatan dari kelas VII sampai IX.</p>
        </div>

        <form method="GET" action="{{ route('laporan.kohort') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
            @if($academicYears->isNotEmpty())
                <div>
                    <label for="reconciliation-year" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Rekonsiliasi Tahun Pelajaran</label>
                    <select id="reconciliation-year" name="academic_year" class="w-full rounded-xl border-slate-300 font-semibold">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected($academicYear?->id === $year->id)>{{ $year->name }}{{ $year->is_active ? ' (Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($cohortEntryYears->isNotEmpty())
                <div>
                    <label for="cohort-entry-year" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Angkatan Masuk Kelas VII</label>
                    <select id="cohort-entry-year" name="cohort_entry_year" class="w-full rounded-xl border-slate-300 font-semibold">
                        @foreach($cohortEntryYears as $year)
                            <option value="{{ $year->id }}" @selected($cohortEntryYear?->id === $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($schools->isNotEmpty())
                <div>
                    <label for="cohort-school" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Satuan Pendidikan</label>
                    <select id="cohort-school" name="school" class="w-full rounded-xl border-slate-300 font-semibold">
                        <option value="">Seluruh sekolah</option>
                        @foreach($schools as $schoolOption)
                            <option value="{{ $schoolOption->id }}" @selected($school?->id === $schoolOption->id)>{{ $schoolOption->nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button class="rounded-xl bg-slate-900 px-5 py-2.5 font-bold text-white hover:bg-slate-700"><i class="bi bi-funnel"></i> Terapkan</button>
        </form>

        @if($academicYear && $cohort)
            <x-cohort-summary :cohort="$cohort" title="Rekonsiliasi {{ $school?->nama ?? 'Kota Bogor' }} · {{ $academicYear->name }}"
                :description="$academicYear->is_active
                    ? 'Rekonsiliasi sementara tahun berjalan berdasarkan data akhir yang sudah dikunci sekolah.'
                    : 'Perbandingan data awal, perubahan siswa, dan data akhir aktual pada satu tahun pelajaran.'" />

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-bold text-slate-900">Rincian per Kelas dan Jenis Kelamin</h2>
                    <p class="mt-1 text-xs text-slate-500">Kolom perkiraan mencakup seluruh sekolah pada filter; selisih hanya dihitung untuk data yang sudah dilaporkan.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[64rem] text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Kelas</th><th class="px-4 py-3 text-left">JK</th>
                                <th class="px-4 py-3 text-right">Awal Pelapor</th>
                                @if($cohort['uses_calculated_opening'] ?? false)
                                    <th class="px-4 py-3 text-right">Awal Hitung</th><th class="px-4 py-3 text-right">Selisih Awal</th>
                                @endif
                                <th class="px-4 py-3 text-right">Masuk</th>
                                <th class="px-4 py-3 text-right">Keluar Mutasi</th><th class="px-4 py-3 text-right">Keluar Non-mutasi</th><th class="px-4 py-3 text-right">Perkiraan</th>
                                <th class="px-4 py-3 text-right">Aktual</th><th class="px-4 py-3 text-right">Selisih Belum Terdata</th>
                                <th class="px-4 py-3 text-right">Belum Lapor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($cohort['rows'] as $row)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-black text-slate-800">{{ [7 => 'VII', 8 => 'VIII', 9 => 'IX'][$row['grade']] }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row['gender_label'] }}</td>
                                    @foreach(array_merge(
                                        ['reported_opening'],
                                        ($cohort['uses_calculated_opening'] ?? false) ? ['calculated_opening', 'opening_variance'] : [],
                                        ['incoming', 'outgoing', 'non_mutation_outgoing', 'expected', 'actual', 'unexplained_exit']
                                    ) as $field)
                                        <td class="px-4 py-3 text-right font-semibold {{ in_array($field, ['opening_variance', 'unexplained_exit'], true) && $row[$field] != 0 ? 'text-rose-700' : 'text-slate-700' }}">{{ number_format($row[$field]) }}</td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $row['missing_units'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ number_format($row['missing_units']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if($cohortEntryYear && $longitudinalCohort)
            <x-longitudinal-cohort :report="$longitudinalCohort"
                title="Kohort Angkatan {{ $cohortEntryYear->name }} · {{ $school?->nama ?? 'Kota Bogor' }}" />
        @endif
    </div>
@endsection
