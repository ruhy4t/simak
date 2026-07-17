@extends('layouts.app')

@section('title', 'Grafik Tren Mutasi')

@section('content')
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

    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Grafik Tren Mutasi</h1>
                <p class="mt-1 text-gray-500">
                    Statistik mutasi siswa
                    {{ $month ? 'harian bulan '.\Carbon\Carbon::createFromDate(null, $month)->translatedFormat('F') : 'per bulan' }}
                    tahun {{ $year }}.
                </p>
            </div>

            <form action="{{ route('laporan.tren') }}" method="GET"
                class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-3 sm:flex-row sm:items-end">
                <div>
                    <label for="month" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Bulan</label>
                    <select name="month" id="month"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Bulan</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected((int) request('month') === $m)>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="year" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tahun</label>
                    <select name="year" id="year"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" @selected((int) $year === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Terapkan
                </button>
            </form>
        </div>

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

                <section class="rounded-xl border bg-white p-4 shadow-xs sm:p-6" aria-labelledby="chart-{{ $type }}-title">
                    <h2 id="chart-{{ $type }}-title" class="mb-6 text-lg font-bold text-gray-900">{{ $title }}</h2>
                    <div class="relative h-72 w-full sm:h-80">
                        <canvas data-report-trend-chart
                            data-chart="{{ json_encode($chartPayload) }}"
                            aria-label="Grafik {{ strtolower($title) }} berdasarkan wilayah"></canvas>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="pt-1 text-center">
            <a href="{{ route('laporan.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                <i class="bi bi-arrow-left mr-2"></i> Kembali ke Menu Laporan
            </a>
        </div>
    </div>
@endsection
