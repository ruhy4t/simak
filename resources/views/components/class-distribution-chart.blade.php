@props(['chartData'])

@php
    $classData = $chartData['kelas'] ?? [
        'labels' => ['VII', 'VIII', 'IX'],
        'masuk' => [0, 0, 0],
        'keluar' => [0, 0, 0],
    ];
    $chartPayload = [
        'labels' => $classData['labels'],
        'datasets' => [
            [
                'label' => 'Mutasi Masuk',
                'values' => $classData['masuk'],
                'color' => '#0284c7',
            ],
            [
                'label' => 'Mutasi Keluar',
                'values' => $classData['keluar'],
                'color' => '#f97316',
            ],
        ],
    ];
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-6"
    aria-labelledby="class-distribution-title">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Distribusi</p>
            <h2 id="class-distribution-title" class="mt-1 text-lg font-bold text-slate-900">Mutasi per Kelas</h2>
        </div>
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
            <i class="bi bi-bar-chart-fill"></i>
        </span>
    </div>

    <div class="relative h-72 w-full sm:h-80">
        <canvas data-class-distribution-chart
            data-chart="{{ json_encode($chartPayload) }}"
            role="img"
            aria-label="Grafik mutasi masuk dan keluar untuk kelas VII, VIII, dan IX"></canvas>
    </div>
</section>
