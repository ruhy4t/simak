@props(['report', 'title' => null, 'compact' => false])

@php
    $summary = $report['summary'];
    $formatNullable = fn ($value) => $value === null ? '—' : number_format($value);
@endphp

<section class="overflow-hidden rounded-2xl border border-indigo-100 bg-white shadow-xs">
    <div class="border-b border-indigo-100 bg-indigo-50/70 p-5 sm:p-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600">Kohort Angkatan</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">
                    {{ $title ?? 'Angkatan Masuk '.$report['entry_year_name'] }}
                </h2>
                <details class="mt-2 text-sm text-slate-600">
                    <summary class="w-fit cursor-pointer font-semibold text-indigo-700">Cara membaca</summary>
                    <p class="mt-2 max-w-3xl leading-6">
                        Angkatan yang sama diikuti dari kelas VII pada tahun masuk, kelas VIII pada tahun berikutnya,
                        hingga kelas IX pada tahun ketiga.
                    </p>
                </details>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100">
                <i class="bi bi-signpost-split"></i>
                {{ $report['available_stage_count'] }}/3 tahap tersedia
            </span>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold text-slate-500">Awal Kelas VII</p>
                <p class="mt-1 text-2xl font-black text-slate-950">{{ number_format($summary['initial']) }}</p>
            </article>
            <article class="rounded-xl border border-sky-100 bg-sky-50 p-4">
                <p class="text-xs font-semibold text-sky-700">Total Masuk</p>
                <p class="mt-1 text-2xl font-black text-sky-950">{{ number_format($summary['incoming']) }}</p>
            </article>
            <article class="rounded-xl border border-orange-100 bg-orange-50 p-4">
                <p class="text-xs font-semibold text-orange-700">Total Keluar</p>
                <p class="mt-1 text-2xl font-black text-orange-950">{{ number_format($summary['outgoing']) }}</p>
            </article>
            <article class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                <p class="text-xs font-semibold text-rose-700">Keluar Non-Mutasi</p>
                <p class="mt-1 text-2xl font-black text-rose-950">{{ number_format($summary['non_mutation_outgoing']) }}</p>
            </article>
            <article class="col-span-2 rounded-xl border border-emerald-100 bg-emerald-50 p-4 lg:col-span-1">
                <p class="text-xs font-semibold text-emerald-700">Akhir Kelas IX</p>
                <p class="mt-1 text-2xl font-black text-emerald-950">{{ $formatNullable($summary['graduated']) }}</p>
            </article>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[54rem] text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Tahap</th>
                    <th class="px-4 py-3 text-left">Tahun Pelajaran</th>
                    <th class="px-4 py-3 text-right">Data Awal</th>
                    <th class="px-4 py-3 text-right">Masuk</th>
                    <th class="px-4 py-3 text-right">Keluar</th>
                    <th class="px-4 py-3 text-right">Non-Mutasi</th>
                    <th class="px-4 py-3 text-right">Perkiraan</th>
                    <th class="px-4 py-3 text-right">Aktual</th>
                    <th class="px-4 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($report['stages'] as $stage)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-black text-slate-900">Kelas {{ $stage['grade_label'] }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-600">{{ $stage['academic_year_name'] ?? '—' }}</td>
                        @foreach(['opening', 'incoming', 'outgoing', 'non_mutation_outgoing', 'expected'] as $field)
                            <td class="px-4 py-3 text-right font-semibold text-slate-700">
                                {{ $stage['available'] ? number_format($stage[$field]) : '—' }}
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right font-black text-slate-900">{{ $stage['available'] ? $formatNullable($stage['actual']) : '—' }}</td>
                        <td class="px-4 py-3">
                            @if(!$stage['available'])
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">Belum tersedia</span>
                            @elseif($stage['is_complete'])
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">Selesai</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">
                                    Berjalan {{ $stage['reported_school_count'] }}/{{ $stage['school_count'] }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
