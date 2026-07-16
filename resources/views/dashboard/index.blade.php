@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-7">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-600">Pusat Kendali</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Ringkasan Dashboard</h1>
                <p class="mt-1.5 text-sm text-slate-500">Selamat datang, <span class="font-semibold text-slate-700">{{ $user->nama_pengguna ?? $user->username }}</span>. Berikut kondisi layanan terbaru.</p>
            </div>
            <div class="inline-flex items-center self-start px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-500">
                <i class="bi bi-calendar3 mr-2 text-sky-500"></i>
                {{ now()->translatedFormat('l, j F Y') }}
            </div>
        </div>

        <!-- Global Date Filter -->
        <section x-data="{ showAdvanced: {{ in_array($dateFilter['period'], ['month', 'custom']) ? 'true' : 'false' }} }"
            class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5">
            <div class="flex flex-col xl:flex-row xl:items-center gap-4">
                <div class="flex items-center gap-3 min-w-fit">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <i class="bi bi-funnel"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Periode Statistik</p>
                        <p class="font-bold text-slate-800">{{ $dateFilter['label'] }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 xl:ml-auto">
                    @foreach([
                        ['label' => 'Semua', 'period' => 'all'],
                        ['label' => 'Hari Ini', 'period' => 'today'],
                        ['label' => 'Bulan Ini', 'period' => 'this_month'],
                        ['label' => 'Tahun Ini', 'period' => 'this_year'],
                    ] as $preset)
                        <a href="{{ $preset['period'] === 'all' ? route('dashboard') : route('dashboard', ['period' => $preset['period']]) }}"
                            class="px-3 py-2 rounded-xl text-sm font-semibold border transition-all {{ $dateFilter['period'] === $preset['period'] ? 'bg-sky-500 border-sky-500 text-white shadow-sm shadow-sky-200' : 'bg-white border-slate-200 text-slate-600 hover:border-sky-300 hover:text-sky-600 hover:bg-sky-50' }}">
                            {{ $preset['label'] }}
                        </a>
                    @endforeach

                    <button type="button" @click="showAdvanced = !showAdvanced"
                        :aria-expanded="showAdvanced"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold border transition-all {{ in_array($dateFilter['period'], ['month', 'custom']) ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-white border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600' }}">
                        <i class="bi bi-calendar-range"></i>
                        Pilih Periode
                        <i class="bi bi-chevron-down text-xs transition-transform" :class="showAdvanced && 'rotate-180'"></i>
                    </button>
                </div>
            </div>

            <div x-show="showAdvanced" x-collapse x-cloak class="mt-5 pt-5 border-t border-slate-100">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <form method="GET" action="{{ route('dashboard') }}"
                        class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                        <input type="hidden" name="period" value="month">
                        <p class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                            <i class="bi bi-calendar-month text-sky-500"></i> Bulan dan Tahun
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2">
                            <select name="month" required
                                class="rounded-xl border-slate-200 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-200">
                                @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $idx => $monthName)
                                    <option value="{{ $idx + 1 }}" @selected(($filterMonth ?? now()->month) == $idx + 1)>{{ $monthName }}</option>
                                @endforeach
                            </select>
                            <select name="year" required
                                class="rounded-xl border-slate-200 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-200">
                                @foreach($availableYears as $availableYear)
                                    <option value="{{ $availableYear }}" @selected(($filterYear ?? now()->year) == $availableYear)>{{ $availableYear }}</option>
                                @endforeach
                            </select>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-bold transition shadow-sm shadow-sky-200">
                                Terapkan
                            </button>
                        </div>
                    </form>

                    <form method="GET" action="{{ route('dashboard') }}"
                        class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                        <input type="hidden" name="period" value="custom">
                        <p class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                            <i class="bi bi-calendar2-range text-indigo-500"></i> Rentang Tanggal
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr_auto] gap-2 items-center">
                            <input type="date" name="start_date" required
                                value="{{ $dateFilter['period'] === 'custom' ? $dateFilter['start'] : now()->startOfMonth()->format('Y-m-d') }}"
                                class="rounded-xl border-slate-200 text-sm text-slate-700 focus:border-indigo-400 focus:ring-indigo-200">
                            <span class="hidden sm:block text-slate-400 text-sm">s.d.</span>
                            <input type="date" name="end_date" required
                                value="{{ $dateFilter['period'] === 'custom' ? $dateFilter['end'] : now()->format('Y-m-d') }}"
                                class="rounded-xl border-slate-200 text-sm text-slate-700 focus:border-indigo-400 focus:ring-indigo-200">
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-bold transition shadow-sm shadow-indigo-200">
                                Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @error('end_date')
                <p class="mt-3 text-sm text-rose-600"><i class="bi bi-exclamation-circle mr-1"></i>{{ $message }}</p>
            @enderror
        </section>

        @php
            $overviewStats = [
                [
                    'label' => 'Total Pengajuan',
                    'value' => ($statsMasuk['total'] ?? 0) + ($statsKeluar['total'] ?? 0),
                    'hint' => 'Masuk dan keluar',
                    'icon' => 'bi-files',
                    'iconClass' => 'bg-slate-100 text-slate-600',
                ],
                [
                    'label' => 'Menunggu Verifikasi',
                    'value' => ($statsMasuk['menunggu'] ?? 0) + ($statsKeluar['menunggu'] ?? 0),
                    'hint' => 'Perlu ditindaklanjuti',
                    'icon' => 'bi-hourglass-split',
                    'iconClass' => 'bg-amber-50 text-amber-600',
                ],
                [
                    'label' => 'Perlu Perbaikan',
                    'value' => ($statsMasuk['perbaikan'] ?? 0) + ($statsKeluar['perbaikan'] ?? 0),
                    'hint' => 'Menunggu pembaruan',
                    'icon' => 'bi-exclamation-triangle',
                    'iconClass' => 'bg-rose-50 text-rose-600',
                ],
                [
                    'label' => 'Selesai',
                    'value' => ($statsMasuk['selesai'] ?? 0) + ($statsKeluar['selesai'] ?? 0),
                    'hint' => 'Dokumen diterbitkan',
                    'icon' => 'bi-check2-circle',
                    'iconClass' => 'bg-emerald-50 text-emerald-600',
                ],
            ];

            $mutationSummaries = [
                [
                    'label' => 'Mutasi Masuk',
                    'description' => number_format($statsMasuk['total_siswa'] ?? 0).' siswa tercatat',
                    'total' => $statsMasuk['total'] ?? 0,
                    'icon' => 'bi-box-arrow-in-right',
                    'iconClass' => 'bg-sky-50 text-sky-600',
                    'statuses' => [
                        ['label' => 'Menunggu', 'value' => $statsMasuk['menunggu'] ?? 0, 'dot' => 'bg-amber-500'],
                        ['label' => 'Perbaikan', 'value' => $statsMasuk['perbaikan'] ?? 0, 'dot' => 'bg-rose-500'],
                        ['label' => 'Disetujui', 'value' => $statsMasuk['disetujui'] ?? 0, 'dot' => 'bg-sky-500'],
                        ['label' => 'Proses TTD', 'value' => $statsMasuk['proses_ttd'] ?? 0, 'dot' => 'bg-violet-500'],
                        ['label' => 'Selesai', 'value' => $statsMasuk['selesai'] ?? 0, 'dot' => 'bg-emerald-500'],
                    ],
                ],
                [
                    'label' => 'Mutasi Keluar',
                    'description' => 'Pengajuan perpindahan keluar',
                    'total' => $statsKeluar['total'] ?? 0,
                    'icon' => 'bi-box-arrow-right',
                    'iconClass' => 'bg-indigo-50 text-indigo-600',
                    'statuses' => [
                        ['label' => 'Menunggu', 'value' => $statsKeluar['menunggu'] ?? 0, 'dot' => 'bg-amber-500'],
                        ['label' => 'Perbaikan', 'value' => $statsKeluar['perbaikan'] ?? 0, 'dot' => 'bg-rose-500'],
                        ['label' => 'Disetujui', 'value' => $statsKeluar['disetujui'] ?? 0, 'dot' => 'bg-sky-500'],
                        ['label' => 'Proses TTD', 'value' => $statsKeluar['proses_ttd'] ?? 0, 'dot' => 'bg-violet-500'],
                        ['label' => 'Selesai', 'value' => $statsKeluar['selesai'] ?? 0, 'dot' => 'bg-emerald-500'],
                    ],
                ],
            ];
        @endphp

        <section aria-labelledby="overview-heading" class="space-y-4">
            <div class="flex items-end justify-between gap-4 px-0.5">
                <div>
                    <h2 id="overview-heading" class="text-base font-bold text-slate-900">Ikhtisar Layanan</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Angka utama pada periode {{ strtolower($dateFilter['label']) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($overviewStats as $stat)
                    <article class="rounded-xl border border-slate-200/80 bg-white p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($stat['value']) }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $stat['hint'] }}</p>
                            </div>
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $stat['iconClass'] }}">
                                <i class="bi {{ $stat['icon'] }} text-lg"></i>
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2" aria-label="Rincian mutasi">
            @foreach($mutationSummaries as $summary)
                <article class="rounded-xl border border-slate-200/80 bg-white p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg {{ $summary['iconClass'] }}">
                                <i class="bi {{ $summary['icon'] }} text-lg"></i>
                            </span>
                            <div>
                                <h2 class="font-bold text-slate-900">{{ $summary['label'] }}</h2>
                                <p class="text-xs text-slate-500">{{ $summary['description'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold tracking-tight text-slate-950">{{ number_format($summary['total']) }}</p>
                            <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Total</p>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-x-5 gap-y-3 border-t border-slate-100 pt-4 sm:grid-cols-5">
                        @foreach($summary['statuses'] as $status)
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                                    <p class="text-xs text-slate-500">{{ $status['label'] }}</p>
                                </div>
                                <p class="mt-1 pl-3 text-base font-bold text-slate-800">{{ number_format($status['value']) }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </section>

        <!-- Interactive Charts -->
        <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-xs p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-graph-up-arrow text-sky-500"></i>
                            Tren Mutasi
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Perbandingan jumlah pengajuan masuk dan keluar</p>
                    </div>
                    <span class="inline-flex items-center self-start px-3 py-1.5 rounded-full bg-sky-50 text-sky-700 text-xs font-bold border border-sky-100">
                        {{ $dateFilter['label'] }} · {{ $trendChart['granularity'] === 'day' ? 'Harian' : 'Bulanan' }}
                    </span>
                </div>

                @if(count($trendChart['labels']) > 0)
                    <div class="relative h-72 sm:h-80">
                        <canvas data-dashboard-trend-chart
                            data-chart="{{ json_encode($trendChart) }}"
                            aria-label="Grafik tren mutasi masuk dan keluar"></canvas>
                    </div>
                @else
                    <div class="h-72 flex flex-col items-center justify-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center">
                        <i class="bi bi-bar-chart text-4xl text-slate-300"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-500">Belum ada data pada periode ini</p>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-pie-chart text-violet-500"></i>
                        Distribusi Status
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Gabungan mutasi masuk dan keluar</p>
                </div>

                @if(array_sum($statusChart['values']) > 0)
                    <div class="relative h-72 sm:h-80">
                        <canvas data-dashboard-status-chart
                            data-chart="{{ json_encode($statusChart) }}"
                            aria-label="Grafik distribusi status mutasi"></canvas>
                    </div>
                @else
                    <div class="h-72 flex flex-col items-center justify-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center">
                        <i class="bi bi-pie-chart text-4xl text-slate-300"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-500">Belum ada status pada periode ini</p>
                    </div>
                @endif
            </div>
        </section>

        <!-- User Stats (Sekolah / Dinas) -->
        @if($statsUsers)
            <div class="space-y-4">
                 <h2 class="text-lg font-semibold text-slate-800 flex items-center gap-2 px-1">
                    <i class="bi bi-people text-purple-500"></i>
                    Statistik Pengguna
                </h2>
                
                @if($user->role === 'Sekolah')
                    <!-- Sekolah: Stats Operator saja -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach([
                            ['label' => 'Total Operator', 'value' => $statsUsers['total'], 'color' => 'purple', 'bg' => 'purple'],
                            ['label' => 'Aktif', 'value' => $statsUsers['active'], 'color' => 'emerald', 'bg' => 'emerald'],
                            ['label' => 'Non-Aktif', 'value' => $statsUsers['inactive'], 'color' => 'rose', 'bg' => 'rose']
                        ] as $uStat)
                         <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs flex items-center justify-between group hover:shadow-md transition">
                            <div>
                                <p class="text-sm text-slate-500 font-medium">{{ $uStat['label'] }}</p>
                                <p class="text-3xl font-bold text-slate-800 mt-2">{{ number_format($uStat['value']) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-{{ $uStat['bg'] }}-50 text-{{ $uStat['color'] }}-600 flex items-center justify-center text-xl">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex justify-end">
                        <a href="{{ route('users.manage') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-600 hover:text-sky-600 hover:border-sky-200 hover:bg-sky-50 transition-colors shadow-xs">
                            Kelola Pengguna <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @else
                    <!-- Dinas: Stats Sekolah & Operator -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Role Sekolah -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <span class="font-bold text-lg text-slate-800">Sekolah</span>
                                </div>
                                <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-bold">{{ number_format($statsUsers['sekolah_total'] ?? 0) }} Total</span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm items-center">
                                    <span class="text-slate-500">Aktif</span>
                                    <span class="font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-sm">{{ $statsUsers['sekolah_active'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between text-sm items-center">
                                     <span class="text-slate-500">Non-Aktif</span>
                                    <span class="font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-sm">{{ $statsUsers['sekolah_inactive'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                         <!-- Role Operator -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xs hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <span class="font-bold text-lg text-slate-800">Operator</span>
                                </div>
                                <span class="bg-orange-50 text-orange-700 px-3 py-1 rounded-full text-xs font-bold">{{ number_format($statsUsers['operator_total'] ?? 0) }} Total</span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm items-center">
                                    <span class="text-slate-500">Aktif</span>
                                    <span class="font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-sm">{{ $statsUsers['operator_active'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between text-sm items-center">
                                     <span class="text-slate-500">Non-Aktif</span>
                                    <span class="font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-sm">{{ $statsUsers['operator_inactive'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <a href="{{ route('dinas.users') }}"
                             class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-600 hover:text-sky-600 hover:border-sky-200 hover:bg-sky-50 transition-colors shadow-xs">
                            Kelola Semua Pengguna <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Statistik Mutasi per Sekolah -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 overflow-hidden relative">
            <div class="absolute top-0 left-0 w-full h-1 bg-linear-to-r from-sky-400 to-blue-500"></div>
            
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                <div>
                     <h2 class="text-lg font-bold text-slate-800">Statistik Mutasi Per Sekolah</h2>
                     <p class="text-sm text-slate-500 mt-1">Data perpindahan siswa berdasarkan sekolah · {{ $dateFilter['label'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                <!-- Tabel Mutasi Masuk (KIRI) -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                         <span class="flex items-center gap-2"><i class="bi bi-box-arrow-in-right text-sky-500"></i> Mutasi Masuk</span>
                         <span class="text-xs font-normal text-slate-400">Top 10</span>
                    </h3>
                    @if(count($schoolStatsMasuk) > 0)
                        <div class="overflow-hidden rounded-xl border border-slate-100 shadow-xs">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-12">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Sekolah Tujuan</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider w-24">Siswa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($schoolStatsMasuk as $idx => $item)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $item->nama_sekolah ?? '-' }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sky-50 text-sky-600 font-bold text-xs ring-1 ring-sky-100">
                                                    {{ $item->jumlah }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <i class="bi bi-inbox text-slate-300 text-4xl"></i>
                            <p class="mt-2 text-sm text-slate-500">Belum ada data</p>
                        </div>
                    @endif
                    <div class="mt-4 text-center">
                        <a href="{{ route('mutasi.masuk.index') }}"
                            class="text-sm text-slate-500 hover:text-sky-600 font-medium transition inline-flex items-center gap-1 group">
                             Lihat Selengkapnya <i class="bi bi-chevron-right text-xs group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <!-- Tabel Mutasi Keluar (KANAN) -->
                <div>
                     <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                         <span class="flex items-center gap-2"><i class="bi bi-box-arrow-right text-orange-500"></i> Mutasi Keluar</span>
                         <span class="text-xs font-normal text-slate-400">Top 10</span>
                    </h3>
                    @if(count($schoolStatsKeluar) > 0)
                        <div class="overflow-hidden rounded-xl border border-slate-100 shadow-xs">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-12">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Sekolah Asal</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider w-24">Siswa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($schoolStatsKeluar as $idx => $item)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $item->nama_sekolah ?? '-' }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-50 text-orange-600 font-bold text-xs ring-1 ring-orange-100">
                                                    {{ $item->jumlah }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <i class="bi bi-inbox text-slate-300 text-4xl"></i>
                            <p class="mt-2 text-sm text-slate-500">Belum ada data</p>
                        </div>
                    @endif
                     <div class="mt-4 text-center">
                        <a href="{{ route('mutasi.keluar.index') }}"
                            class="text-sm text-slate-500 hover:text-sky-600 font-medium transition inline-flex items-center gap-1 group">
                             Lihat Selengkapnya <i class="bi bi-chevron-right text-xs group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        @if(in_array($user->role, ['Dinas', 'SuperAdmin']))
             <!-- Verifikasi -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 @php
                    $verifyStats = [
                        [
                            'title' => 'Mutasi Masuk', 
                            'count' => $statsMasuk['menunggu'], 
                            'route' => 'mutasi.masuk.index', 
                            'icon' => 'bi-box-arrow-in-right',
                            'color' => 'sky'
                        ],
                        [
                            'title' => 'Mutasi Keluar', 
                            'count' => $statsKeluar['menunggu'], 
                            'route' => 'mutasi.keluar.index', 
                            'icon' => 'bi-box-arrow-right',
                            'color' => 'orange'
                        ]
                    ];
                @endphp

                @foreach($verifyStats as $vStat)
                    <a href="{{ route($vStat['route'], ['status' => 'Menunggu Verifikasi Dinas']) }}"
                        class="group relative overflow-hidden bg-white rounded-2xl p-6 border border-slate-100 shadow-xs hover:shadow-lg transition-all duration-300">
                        <div class="absolute right-0 top-0 w-32 h-32 bg-linear-to-br from-{{ $vStat['color'] }}-100/50 to-transparent rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-150"></div>
                        
                        <div class="relative flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-{{ $vStat['color'] }}-50 text-{{ $vStat['color'] }}-500 flex items-center justify-center shadow-inner group-hover:rotate-12 transition-transform">
                                <i class="bi {{ $vStat['icon'] }} text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 font-medium uppercase tracking-wide">Menunggu Verifikasi</p>
                                <h3 class="text-xl font-bold text-slate-800">{{ $vStat['title'] }}</h3>
                            </div>
                            <div class="ml-auto">
                                <span class="text-3xl font-bold text-{{ $vStat['color'] }}-600">{{ $vStat['count'] }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Footer Info -->
        <div class="bg-linear-to-r from-slate-900 to-slate-800 rounded-2xl p-8 text-white relative overflow-hidden shadow-xl">
             <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-center gap-6 text-center md:text-left">
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-xs shadow-inner border border-white/10">
                    <i class="bi bi-info-circle text-3xl text-sky-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white mb-2">Pusat Informasi SIMAK</h3>
                    <p class="text-slate-300 leading-relaxed max-w-2xl">
                        Pastikan seluruh dokumen persyaratan mutasi telah diunggah dengan format yang benar (PDF/JPG) agar proses verifikasi dapat berjalan lancar tanpa hambatan.
                    </p>
                </div>
                {{-- Optional Action Button --}}
            </div>
        </div>
    </div>

    {{-- Telegram Chat ID Reminder Popup --}}
    @if(empty($user->telegram_chat_id) && in_array($user->role, ['Operator', 'Sekolah', 'Dinas', 'SuperAdmin']))
        <div x-data="{ showTelegramPopup: true }" x-show="showTelegramPopup" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden scale-100"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">

                {{-- Header --}}
                <div class="bg-slate-900 px-6 py-6 text-center relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-full bg-linear-to-br from-blue-600/20 to-purple-600/20"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-white/10 mx-auto rounded-2xl flex items-center justify-center backdrop-blur-md mb-4 border border-white/10">
                            <i class="bi bi-telegram text-sky-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Aktifkan Notifikasi</h3>
                        <p class="text-slate-400 text-sm mt-1">Dapatkan update status realtime via Telegram</p>
                    </div>
                </div>

                {{-- Content --}}
                <div class="px-8 py-6 space-y-6">
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center mt-0.5">1</span>
                            <div class="text-sm text-slate-600">
                                Cari bot <span class="font-mono font-bold text-slate-800 bg-slate-100 px-1 rounded-sm">@userinfobot</span> di Telegram
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center mt-0.5">2</span>
                            <div class="text-sm text-slate-600">
                                Start bot untuk mendapatkan <strong>ID Telegram</strong> Anda.
                            </div>
                        </div>
                         <div class="flex gap-4">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center mt-0.5">3</span>
                            <div class="text-sm text-slate-600">
                                Masukkan ID di Profil SIMAK, lalu chat <span class="font-mono font-bold text-sky-600">@simak_smp_bot</span>.
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button @click="showTelegramPopup = false"
                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition">
                            Nanti Saja
                        </button>
                        <a href="https://t.me/simak_smp_bot" target="_blank"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-medium text-center shadow-lg shadow-sky-500/30 transition hover:-translate-y-0.5">
                            Buka Telegram
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <style>
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>@endsection
