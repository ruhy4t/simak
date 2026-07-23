@props(['cohort', 'title' => 'Rekonsiliasi Tahunan', 'description' => null, 'compact' => false])

@php
    $isProvisional = $cohort['is_provisional'] ?? false;
    $summary = $isProvisional ? $cohort['summary'] : $cohort['reported_summary'];
    $hasActual = !$isProvisional || $cohort['reported_school_count'] > 0;
    $summary['actual'] = $hasActual ? $summary['actual'] : null;
    $usesCalculatedOpening = !$isProvisional && ($cohort['uses_calculated_opening'] ?? false);
    $gradeSummary = $isProvisional
        ? ($cohort['provisional_grade_summary'] ?? [])
        : ($cohort['grade_summary'] ?? []);
    $expectedLabel = $isProvisional ? 'Perkiraan Saat Ini' : 'Perkiraan Akhir';
    $actualLabel = $isProvisional ? 'Data Akhir Terkunci' : 'Akhir Aktual';
    $chartPayload = [
        'labels' => array_values(array_filter([
            'Data Awal Tercatat',
            $usesCalculatedOpening ? 'Data Awal Hasil Hitung' : null,
            'Mutasi Masuk', 'Mutasi Keluar', 'Keluar Non-Mutasi', $expectedLabel,
            $hasActual ? $actualLabel : null,
        ])),
        'values' => array_values(array_filter([
            $summary['opening'],
            $usesCalculatedOpening ? $summary['calculated_opening'] : null,
            $summary['incoming'],
            -$summary['outgoing'],
            -$summary['non_mutation_outgoing'],
            $summary['expected'],
            $summary['actual'],
        ], fn ($value) => $value !== null)),
    ];
    $summaryCards = [
        ['Data Awal Tercatat', $summary['opening'], 'border-slate-200 bg-slate-50', 'text-slate-600', 'text-slate-950'],
    ];
    if ($usesCalculatedOpening) {
        $summaryCards[] = ['Data Awal Hasil Hitung', $summary['calculated_opening'], 'border-blue-100 bg-blue-50', 'text-blue-700', 'text-blue-950'];
        $summaryCards[] = ['Selisih Data Awal', $summary['opening_variance'], 'border-violet-100 bg-violet-50', 'text-violet-700', 'text-violet-950'];
    }
    $summaryCards = array_merge($summaryCards, [
        ['Mutasi Masuk', $summary['incoming'], 'border-sky-100 bg-sky-50', 'text-sky-700', 'text-sky-950'],
        ['Mutasi Keluar', $summary['outgoing'], 'border-orange-100 bg-orange-50', 'text-orange-700', 'text-orange-950'],
        ['Keluar Non-Mutasi', $summary['non_mutation_outgoing'], 'border-rose-100 bg-rose-50', 'text-rose-700', 'text-rose-950'],
        [$expectedLabel, $summary['expected'], 'border-indigo-100 bg-indigo-50', 'text-indigo-700', 'text-indigo-950'],
        [$actualLabel, $summary['actual'], 'border-emerald-100 bg-emerald-50', 'text-emerald-700', 'text-emerald-950'],
    ]);
    $hasDisplayData = $isProvisional
        ? $cohort['school_count'] > 0
        : $cohort['reported_school_count'] > 0;
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600">Rekonsiliasi Tahunan</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $title }}</h2>
            @if($description)
                @if($compact)
                    <details class="mt-2 max-w-3xl text-sm text-slate-500">
                        <summary class="w-fit cursor-pointer font-semibold text-slate-600">Keterangan</summary>
                        <p class="mt-2 leading-6">{{ $description }}</p>
                    </details>
                @else
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ $description }}</p>
                @endif
            @endif
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">
            <i class="bi bi-building-check"></i>
            {{ number_format($cohort['reported_school_count']) }}/{{ number_format($cohort['school_count']) }}
            {{ $isProvisional ? 'data akhir terkunci' : 'sekolah melapor' }}
        </span>
    </div>

    @if($hasDisplayData)
        @if($isProvisional)
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-bold">Data sementara tahun berjalan, bukan angka final.</p>
                <p class="mt-1 leading-5">Perkiraan saat ini dihitung dari data awal, mutasi masuk, mutasi keluar, dan keluar non-mutasi yang telah tercatat.</p>
            </div>
        @endif
        @if(($cohort['early_locked_school_count'] ?? 0) > 0)
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                <p class="font-bold">{{ number_format($cohort['early_locked_school_count']) }} sekolah mengunci data sebelum periode berakhir.</p>
                <p class="mt-1 leading-5">Data tersebut ditandai untuk pemeriksaan dan tidak dihitung sebagai laporan final sampai dikunci ulang pada periode yang sah.</p>
            </div>
        @endif

        @if($usesCalculatedOpening)
            <details class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900" @if(!$compact) open @endif>
                @if($compact)<summary class="cursor-pointer font-bold">Cara menghitung data awal</summary>@endif
                @unless($compact)<p class="font-bold">Cara menghitung Data Awal Hasil Hitung · Khusus 2025/2026</p>@endunless
                <p class="mt-1 leading-6">Data Akhir Aktual + Mutasi Keluar + Keluar Non-Mutasi − Mutasi Masuk. Angka ini merupakan pembanding dan tidak menimpa Data Awal resmi sekolah. Selisih dihitung dari Data Awal Tercatat − Data Awal Hasil Hitung; nilai negatif berarti data tercatat lebih rendah.</p>
            </details>
        @endif

        <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach($summaryCards as [$label, $value, $cardClass, $labelClass, $valueClass])
                <article class="rounded-xl border p-4 {{ $cardClass }}">
                    <p class="text-xs font-semibold {{ $labelClass }}">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-black {{ $valueClass }}">{{ $value === null ? '—' : number_format($value) }}</p>
                </article>
            @endforeach
        </div>

        @if(!empty($gradeSummary))
            <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h3 class="font-bold text-slate-900">Jumlah per Kelas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[48rem] text-sm">
                        <thead class="bg-white text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Kelas</th>
                                <th class="px-4 py-3 text-right">Data Awal</th>
                                <th class="px-4 py-3 text-right">Masuk</th>
                                <th class="px-4 py-3 text-right">Keluar</th>
                                <th class="px-4 py-3 text-right">Non-Mutasi</th>
                                <th class="px-4 py-3 text-right">{{ $expectedLabel }}</th>
                                <th class="px-4 py-3 text-right">{{ $actualLabel }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($gradeSummary as $grade)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-black text-slate-900">{{ $grade['grade_label'] }}</td>
                                    @foreach(['opening', 'incoming', 'outgoing', 'non_mutation_outgoing', 'expected', 'actual'] as $field)
                                        <td class="px-4 py-3 text-right font-semibold text-slate-700">
                                            {{ $isProvisional && $field === 'actual' && !($grade['has_actual'] ?? false) ? '—' : number_format($grade[$field]) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="relative h-72 min-w-0 sm:h-80">
                <canvas data-cohort-chart data-chart="{{ json_encode($chartPayload) }}"
                    role="img" aria-label="Grafik perubahan jumlah murid dari data awal hingga data akhir aktual"></canvas>
            </div>
            <div class="grid content-start gap-3">
                <article class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                    <p class="text-sm font-bold text-rose-800">Keluar non-mutasi terdata</p>
                    <p class="mt-1 text-3xl font-black text-rose-950">{{ number_format($summary['non_mutation_outgoing']) }}</p>
                    @unless($compact)<p class="mt-1 text-xs leading-5 text-rose-700">Data individual yang telah dicatat oleh sekolah.</p>@endunless
                </article>
                @unless($isProvisional)
                    <article class="rounded-xl border border-violet-100 bg-violet-50 p-4">
                        <p class="text-sm font-bold text-violet-800">Selisih keluar belum terdata</p>
                        <p class="mt-1 text-3xl font-black text-violet-950">{{ number_format($summary['unexplained_exit']) }}</p>
                        @unless($compact)<p class="mt-1 text-xs leading-5 text-violet-700">Kekurangan data akhir setelah seluruh laporan terverifikasi dihitung.</p>@endunless
                    </article>
                    <article class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                        <p class="text-sm font-bold text-amber-800">Penambahan belum tercatat</p>
                        <p class="mt-1 text-3xl font-black text-amber-950">{{ number_format($summary['other_addition']) }}</p>
                        @unless($compact)<p class="mt-1 text-xs leading-5 text-amber-700">Selisih saat data aktual lebih besar dari perkiraan akhir.</p>@endunless
                    </article>
                @endunless
                @if($compact && !$isProvisional)
                    <details class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                        <summary class="cursor-pointer font-bold text-slate-700">Penjelasan indikator</summary>
                        <div class="mt-2 space-y-2 leading-5">
                            <p><strong>Keluar non-mutasi:</strong> data individual yang dicatat sekolah.</p>
                            <p><strong>Selisih keluar:</strong> kekurangan data akhir setelah laporan dihitung.</p>
                            <p><strong>Penambahan:</strong> selisih saat data aktual melebihi perkiraan.</p>
                        </div>
                    </details>
                @endif
            </div>
        </div>
    @else
        <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
            <i class="bi bi-clipboard-data text-3xl text-slate-400"></i>
            <p class="mt-3 font-bold text-slate-700">{{ $isProvisional ? 'Data awal tahun aktif belum tersedia.' : 'Data akhir aktual belum tersedia.' }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $isProvisional ? 'Ringkasan akan tampil setelah data awal sekolah tersedia.' : 'Grafik akan tampil setelah sekolah pertama mengunci data akhir.' }}</p>
        </div>
    @endif
</section>
