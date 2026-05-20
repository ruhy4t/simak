@extends('layouts.app')

@section('title', 'Grafik Tren Mutasi')

@section('content')
    @section('content')
        <div class="space-y-6">
            <!-- Header & Filter -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Grafik Tren Mutasi</h1>
                    <p class="text-gray-500 mt-1">Statistik mutasi siswa
                        {{ $month ? 'harian bulan ' . \Carbon\Carbon::createFromDate(null, $month)->translatedFormat('F') : 'per bulan' }}
                        tahun {{ $year }}.</p>
                </div>

                <form action="{{ route('laporan.tren') }}" method="GET" class="flex items-center gap-2">

                    {{-- Month Filter --}}
                    <label for="month" class="text-sm font-medium text-gray-700">Bulan:</label>
                    <select name="month" id="month" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Semua Bulan</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>

                    <label for="year" class="text-sm font-medium text-gray-700 ml-2">Tahun:</label>
                    <select name="year" id="year" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </form>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <!-- Tren Mutasi Masuk -->
                <div class="bg-white rounded-xl shadow-xs border p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Mutasi Masuk</h3>
                    </div>
                    <div class="relative h-72 w-full">
                        <canvas id="chartMasuk"></canvas>
                    </div>
                </div>

                <!-- Tren Mutasi Keluar -->
                <div class="bg-white rounded-xl shadow-xs border p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Mutasi Keluar</h3>
                    </div>
                    <div class="relative h-72 w-full">
                        <canvas id="chartKeluar"></canvas>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('laporan.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                    <i class="bi bi-arrow-left mr-2"></i> Kembali ke Menu Laporan
                </a>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const labels = @json($chartData['labels']);

                // --- DATA SETUP ---
                const dataMasuk = {
                    dalam: @json($chartData['masuk']['dalam']),
                    luarKota: @json($chartData['masuk']['luar_kota']),
                    luarNegeri: @json($chartData['masuk']['luar_negeri'])
                };

                const dataKeluar = {
                    dalam: @json($chartData['keluar']['dalam']),
                    luarKota: @json($chartData['keluar']['luar_kota']),
                    luarNegeri: @json($chartData['keluar']['luar_negeri'])
                };

                // --- CONFIG COMMON ---
                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { borderDash: [2, 4], color: '#f3f4f6' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                    elements: {
                        line: { tension: 0.4 }, // Smooth curves
                        point: { radius: 0, hitRadius: 10, hoverRadius: 5 }
                    }
                };

                // --- COLORS & GRADIENTS ---
                // 1. Dalam Kota (Green - Local/Stable)
                function createGradientGreen(ctx) {
                    const grad = ctx.createLinearGradient(0, 0, 0, 300);
                    grad.addColorStop(0, 'rgba(16, 185, 129, 0.2)'); // Emerald 500
                    grad.addColorStop(1, 'rgba(16, 185, 129, 0)');
                    return grad;
                }

                // 2. Luar Kota (Blue - Regional) - Changed to Blue for high contrast with Green
                function createGradientBlue(ctx) {
                    const grad = ctx.createLinearGradient(0, 0, 0, 300);
                    grad.addColorStop(0, 'rgba(59, 130, 246, 0.2)'); // Blue 500
                    grad.addColorStop(1, 'rgba(59, 130, 246, 0)');
                    return grad;
                }

                // 3. Luar Negeri (Purple - Distant/Special)
                function createGradientPurple(ctx) {
                    const grad = ctx.createLinearGradient(0, 0, 0, 300);
                    grad.addColorStop(0, 'rgba(139, 92, 246, 0.2)'); // Violet 500
                    grad.addColorStop(1, 'rgba(139, 92, 246, 0)');
                    return grad;
                }

                // --- CHART MASUK ---
                const ctxMasuk = document.getElementById('chartMasuk').getContext('2d');

                new Chart(ctxMasuk, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Dalam Kota (Bogor)',
                                data: dataMasuk.dalam,
                                borderColor: '#10b981', // Emerald 500
                                backgroundColor: createGradientGreen(ctxMasuk),
                                fill: true,
                                pointBackgroundColor: '#10b981'
                            },
                            {
                                label: 'Luar Kota',
                                data: dataMasuk.luarKota,
                                borderColor: '#3b82f6', // Blue 500
                                backgroundColor: createGradientBlue(ctxMasuk),
                                fill: true,
                                pointBackgroundColor: '#3b82f6'
                            },
                            {
                                label: 'Luar Negeri',
                                data: dataMasuk.luarNegeri,
                                borderColor: '#8b5cf6', // Violet 500
                                backgroundColor: createGradientPurple(ctxMasuk),
                                borderDash: [5, 5],
                                fill: true,
                                pointBackgroundColor: '#8b5cf6'
                            }
                        ]
                    },
                    options: commonOptions
                });

                // --- CHART KELUAR ---
                const ctxKeluar = document.getElementById('chartKeluar').getContext('2d');

                new Chart(ctxKeluar, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Dalam Kota (Bogor)',
                                data: dataKeluar.dalam,
                                borderColor: '#10b981', // Emerald 500
                                backgroundColor: createGradientGreen(ctxKeluar),
                                fill: true,
                                pointBackgroundColor: '#10b981'
                            },
                            {
                                label: 'Luar Kota',
                                data: dataKeluar.luarKota,
                                borderColor: '#3b82f6', // Blue 500
                                backgroundColor: createGradientBlue(ctxKeluar),
                                fill: true,
                                pointBackgroundColor: '#3b82f6'
                            },
                            {
                                label: 'Luar Negeri',
                                data: dataKeluar.luarNegeri,
                                borderColor: '#8b5cf6', // Violet 500
                                backgroundColor: createGradientPurple(ctxKeluar),
                                borderDash: [5, 5],
                                fill: true,
                                pointBackgroundColor: '#8b5cf6'
                            }
                        ]
                    },
                    options: commonOptions
                });
            });
        </script>
    @endpush
@endsection