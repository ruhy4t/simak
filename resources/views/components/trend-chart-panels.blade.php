@props(['chartData'])

@php
    $chartSeries = [
        [
            'label' => 'Dalam Kota (Bogor)',
            'color' => '#10b981',
            'masuk' => $chartData['masuk']['dalam'],
            'keluar' => $chartData['keluar']['dalam'],
        ],
        [
            'label' => 'Luar Kota',
            'color' => '#3b82f6',
            'masuk' => $chartData['masuk']['luar_kota'],
            'keluar' => $chartData['keluar']['luar_kota'],
        ],
        [
            'label' => 'Luar Negeri',
            'color' => '#8b5cf6',
            'dashed' => true,
            'masuk' => $chartData['masuk']['luar_negeri'],
            'keluar' => $chartData['keluar']['luar_negeri'],
        ],
    ];
@endphp

<div class="grid grid-cols-1 gap-6">
    @foreach(['masuk' => 'Mutasi Masuk', 'keluar' => 'Mutasi Keluar'] as $type => $title)
        @php
            $chartPayload = [
                'labels' => $chartData['labels'],
                'datasets' => collect($chartSeries)->map(fn ($series) => [
                    'label' => $series['label'],
                    'values' => $series[$type],
                    'color' => $series['color'],
                    'dashed' => $series['dashed'] ?? false,
                ])->values()->all(),
            ];
        @endphp

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-6"
            aria-labelledby="chart-{{ $type }}-title">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Tren Periode</p>
                    <h2 id="chart-{{ $type }}-title" class="mt-1 text-lg font-bold text-slate-900">{{ $title }}</h2>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $type === 'masuk' ? 'bg-sky-50 text-sky-600' : 'bg-orange-50 text-orange-600' }}">
                    <i class="bi {{ $type === 'masuk' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right' }}"></i>
                </span>
            </div>
            <div class="relative h-72 w-full sm:h-80">
                <canvas data-report-trend-chart
                    data-chart="{{ json_encode($chartPayload) }}"
                    role="img"
                    aria-label="Grafik {{ strtolower($title) }} berdasarkan wilayah"></canvas>
            </div>
        </section>
    @endforeach
</div>
