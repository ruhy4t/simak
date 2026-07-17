@props(['cohort', 'title' => 'Grafik Kohort Siswa', 'description' => null])

@php
    $summary = $cohort['reported_summary'];
    $chartPayload = [
        'labels' => ['Data Awal', 'Mutasi Masuk', 'Mutasi Keluar', 'Keluar Non-Mutasi', 'Perkiraan Akhir', 'Data Akhir Aktual'],
        'values' => [
            $summary['opening'],
            $summary['incoming'],
            -$summary['outgoing'],
            -$summary['non_mutation_outgoing'],
            $summary['expected'],
            $summary['actual'],
        ],
    ];
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600">Rekonsiliasi Agregat</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $title }}</h2>
            @if($description)
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ $description }}</p>
            @endif
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">
            <i class="bi bi-building-check"></i>
            {{ number_format($cohort['reported_school_count']) }}/{{ number_format($cohort['school_count']) }} sekolah melapor
        </span>
    </div>

    @if($cohort['reported_school_count'] > 0)
        <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-6">
            @foreach([
                ['Data Awal', $summary['opening'], 'border-slate-200 bg-slate-50', 'text-slate-600', 'text-slate-950'],
                ['Mutasi Masuk', $summary['incoming'], 'border-sky-100 bg-sky-50', 'text-sky-700', 'text-sky-950'],
                ['Mutasi Keluar', $summary['outgoing'], 'border-orange-100 bg-orange-50', 'text-orange-700', 'text-orange-950'],
                ['Keluar Non-Mutasi', $summary['non_mutation_outgoing'], 'border-rose-100 bg-rose-50', 'text-rose-700', 'text-rose-950'],
                ['Perkiraan Akhir', $summary['expected'], 'border-indigo-100 bg-indigo-50', 'text-indigo-700', 'text-indigo-950'],
                ['Akhir Aktual', $summary['actual'], 'border-emerald-100 bg-emerald-50', 'text-emerald-700', 'text-emerald-950'],
            ] as [$label, $value, $cardClass, $labelClass, $valueClass])
                <article class="rounded-xl border p-4 {{ $cardClass }}">
                    <p class="text-xs font-semibold {{ $labelClass }}">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-black {{ $valueClass }}">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="relative h-72 min-w-0 sm:h-80">
                <canvas data-cohort-chart data-chart="{{ json_encode($chartPayload) }}"
                    role="img" aria-label="Grafik perubahan jumlah siswa dari data awal hingga data akhir aktual"></canvas>
            </div>
            <div class="grid content-start gap-3">
                <article class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                    <p class="text-sm font-bold text-rose-800">Keluar non-mutasi terdata</p>
                    <p class="mt-1 text-3xl font-black text-rose-950">{{ number_format($summary['non_mutation_outgoing']) }}</p>
                    <p class="mt-1 text-xs leading-5 text-rose-700">Data individual yang telah dicatat oleh sekolah.</p>
                </article>
                <article class="rounded-xl border border-violet-100 bg-violet-50 p-4">
                    <p class="text-sm font-bold text-violet-800">Selisih keluar belum terdata</p>
                    <p class="mt-1 text-3xl font-black text-violet-950">{{ number_format($summary['unexplained_exit']) }}</p>
                    <p class="mt-1 text-xs leading-5 text-violet-700">Kekurangan data akhir setelah seluruh laporan terverifikasi dihitung.</p>
                </article>
                <article class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <p class="text-sm font-bold text-amber-800">Penambahan belum tercatat</p>
                    <p class="mt-1 text-3xl font-black text-amber-950">{{ number_format($summary['other_addition']) }}</p>
                    <p class="mt-1 text-xs leading-5 text-amber-700">Selisih saat data aktual lebih besar dari perkiraan akhir.</p>
                </article>
            </div>
        </div>
    @else
        <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
            <i class="bi bi-clipboard-data text-3xl text-slate-400"></i>
            <p class="mt-3 font-bold text-slate-700">Data akhir aktual belum tersedia.</p>
            <p class="mt-1 text-sm text-slate-500">Grafik akan tampil setelah sekolah pertama mengunci data akhir.</p>
        </div>
    @endif
</section>
