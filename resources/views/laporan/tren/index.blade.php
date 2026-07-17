@extends('layouts.app')

@section('title', 'Grafik Tren Mutasi')

@section('content')
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

        <x-trend-chart-panels :chart-data="$chartData" />

        <div class="pt-1 text-center">
            <a href="{{ route('laporan.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                <i class="bi bi-arrow-left mr-2"></i> Kembali ke Menu Laporan
            </a>
        </div>
    </div>
@endsection
