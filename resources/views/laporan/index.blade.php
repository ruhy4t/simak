<<<<<<< HEAD
@extends('layouts.app')

@section('title', 'Laporan Mutasi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Mutasi</h1>
            <p class="text-gray-500 mt-1">Pilih jenis laporan yang ingin Anda cetak atau lihat.</p>
        </div>

        <!-- NEW: Comprehensive Print Section -->
        <div
            class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl shadow-lg p-6 text-white flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-2">
                    <div class="p-3 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="bi bi-printer-fill text-2xl text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold">Cetak Laporan Lengkap (Komprehensif)</h2>
                </div>
                <p class="text-emerald-50 text-sm md:w-3/4 leading-relaxed">
                    Unduh satu dokumen PDF lengkap yang berisi Rekapitulasi, Grafik Tren, Distribusi Wilayah, Demografi
                    Siswa, hingga Daftar Nominatif secara detail. Sesuai standar untuk arsip Dinas/Sekolah.
                </p>
            </div>

            <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20 w-full md:w-auto min-w-[300px]">
                <form action="{{ route('laporan.cetak-komprehensif') }}" method="GET" target="_blank"
                    class="flex flex-col gap-3">
                    <label class="text-xs font-bold uppercase tracking-wider text-emerald-100">Pilih Periode Laporan</label>
                    <div class="flex gap-2">
                        <select name="month"
                            class="bg-white text-gray-800 text-sm rounded-lg flex-1 border-0 focus:ring-2 focus:ring-emerald-300">
                            <option value="">Setahun Penuh</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate(null, $m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                        <select name="year"
                            class="bg-white text-gray-800 text-sm rounded-lg w-24 border-0 focus:ring-2 focus:ring-emerald-300">
                            @for($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit"
                        class="w-full py-2.5 bg-white text-emerald-700 font-bold rounded-lg shadow hover:bg-emerald-50 transition flex items-center justify-center gap-2">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Cetak PDF Sekarang
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card Laporan Rekapitulasi -->
            <a href="{{ route('laporan.rekap') }}"
                class="group relative bg-white rounded-xl shadow-sm border p-6 overflow-hidden transition hover:shadow-md hover:border-blue-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                    <i class="bi bi-bar-chart-fill text-9xl text-blue-600"></i>
                </div>

                <div class="relative z-10 flex flex-col h-full">
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4 text-blue-600 group-hover:scale-110 transition">
                        <i class="bi bi-bar-chart-line text-2xl"></i>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">Laporan Rekapitulasi</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">
                        Ringkasan statistik jumlah mutasi masuk dan keluar.
                        @if(Auth::user()->role === 'Dinas' || Auth::user()->role === 'SuperAdmin')
                            Dilengkapi dengan data per sekolah.
                        @else
                            Dilengkapi dengan data per bulan.
                        @endif
                    </p>

                    <div class="flex items-center text-blue-600 font-medium text-sm group-hover:translate-x-1 transition">
                        Buka Laporan <i class="bi bi-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>

            <!-- Card Grafik Tren Mutasi -->
            <a href="{{ route('laporan.tren') }}"
                class="group relative bg-white rounded-xl shadow-sm border p-6 overflow-hidden transition hover:shadow-md hover:border-indigo-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                    <i class="bi bi-graph-up-arrow text-9xl text-indigo-600"></i>
                </div>

                <div class="relative z-10 flex flex-col h-full">
                    <div
                        class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4 text-indigo-600 group-hover:scale-110 transition">
                        <i class="bi bi-pie-chart text-2xl"></i>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">Grafik Tren Mutasi</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">
                        Visualisasi tren perpindahan masuk dan keluar (Dalam Kota, Luar Kota, Luar Negeri).
                    </p>

                    <div class="flex items-center text-indigo-600 font-medium text-sm group-hover:translate-x-1 transition">
                        Lihat Grafik <i class="bi bi-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>

            <!-- Card Laporan Detail (Daftar Nominatif) -->
            <a href="{{ route('laporan.nominatif') }}"
                class="group relative bg-white rounded-xl shadow-sm border p-6 overflow-hidden transition hover:shadow-md hover:border-orange-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                    <i class="bi bi-card-list text-9xl text-orange-600"></i>
                </div>

                <div class="relative z-10 flex flex-col h-full">
                    <div
                        class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4 text-orange-600 group-hover:scale-110 transition">
                        <i class="bi bi-table text-2xl"></i>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">Laporan Detail (Daftar Nominatif)</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">
                        Daftar lengkap siswa yang melakukan mutasi dengan detail NISN, Nama, dan Sekolah Tujuan/Asal.
                    </p>

                    <div class="flex items-center text-orange-600 font-medium text-sm group-hover:translate-x-1 transition">
                        Buka Laporan <i class="bi bi-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
=======
@extends('layouts.app')

@section('title', 'Laporan Mutasi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Mutasi</h1>
            <p class="text-gray-500 mt-1">Pilih jenis laporan yang ingin Anda cetak atau lihat.</p>
        </div>

        <!-- NEW: Comprehensive Print Section -->
        <div
            class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl shadow-lg p-6 text-white flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-2">
                    <div class="p-3 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="bi bi-printer-fill text-2xl text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold">Cetak Laporan Lengkap (Komprehensif)</h2>
                </div>
                <p class="text-emerald-50 text-sm md:w-3/4 leading-relaxed">
                    Unduh satu dokumen PDF lengkap yang berisi Rekapitulasi, Grafik Tren, Distribusi Wilayah, Demografi
                    Siswa, hingga Daftar Nominatif secara detail. Sesuai standar untuk arsip Dinas/Sekolah.
                </p>
            </div>

            <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20 w-full md:w-auto min-w-[300px]">
                <form action="{{ route('laporan.cetak-komprehensif') }}" method="GET" target="_blank"
                    class="flex flex-col gap-3">
                    <label class="text-xs font-bold uppercase tracking-wider text-emerald-100">Pilih Periode Laporan</label>
                    <div class="flex gap-2">
                        <select name="month"
                            class="bg-white text-gray-800 text-sm rounded-lg flex-1 border-0 focus:ring-2 focus:ring-emerald-300">
                            <option value="">Setahun Penuh</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate(null, $m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                        <select name="year"
                            class="bg-white text-gray-800 text-sm rounded-lg w-24 border-0 focus:ring-2 focus:ring-emerald-300">
                            @for($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit"
                        class="w-full py-2.5 bg-white text-emerald-700 font-bold rounded-lg shadow hover:bg-emerald-50 transition flex items-center justify-center gap-2">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Cetak PDF Sekarang
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card Laporan Rekapitulasi -->
            <a href="{{ route('laporan.rekap') }}"
                class="group relative bg-white rounded-xl shadow-sm border p-6 overflow-hidden transition hover:shadow-md hover:border-blue-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                    <i class="bi bi-bar-chart-fill text-9xl text-blue-600"></i>
                </div>

                <div class="relative z-10 flex flex-col h-full">
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4 text-blue-600 group-hover:scale-110 transition">
                        <i class="bi bi-bar-chart-line text-2xl"></i>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">Laporan Rekapitulasi</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">
                        Ringkasan statistik jumlah mutasi masuk dan keluar.
                        @if(Auth::user()->role === 'Dinas' || Auth::user()->role === 'SuperAdmin')
                            Dilengkapi dengan data per sekolah.
                        @else
                            Dilengkapi dengan data per bulan.
                        @endif
                    </p>

                    <div class="flex items-center text-blue-600 font-medium text-sm group-hover:translate-x-1 transition">
                        Buka Laporan <i class="bi bi-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>

            <!-- Card Grafik Tren Mutasi -->
            <a href="{{ route('laporan.tren') }}"
                class="group relative bg-white rounded-xl shadow-sm border p-6 overflow-hidden transition hover:shadow-md hover:border-indigo-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                    <i class="bi bi-graph-up-arrow text-9xl text-indigo-600"></i>
                </div>

                <div class="relative z-10 flex flex-col h-full">
                    <div
                        class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4 text-indigo-600 group-hover:scale-110 transition">
                        <i class="bi bi-pie-chart text-2xl"></i>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">Grafik Tren Mutasi</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">
                        Visualisasi tren perpindahan masuk dan keluar (Dalam Kota, Luar Kota, Luar Negeri).
                    </p>

                    <div class="flex items-center text-indigo-600 font-medium text-sm group-hover:translate-x-1 transition">
                        Lihat Grafik <i class="bi bi-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>

            <!-- Card Laporan Detail (Daftar Nominatif) -->
            <a href="{{ route('laporan.nominatif') }}"
                class="group relative bg-white rounded-xl shadow-sm border p-6 overflow-hidden transition hover:shadow-md hover:border-orange-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                    <i class="bi bi-card-list text-9xl text-orange-600"></i>
                </div>

                <div class="relative z-10 flex flex-col h-full">
                    <div
                        class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4 text-orange-600 group-hover:scale-110 transition">
                        <i class="bi bi-table text-2xl"></i>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">Laporan Detail (Daftar Nominatif)</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">
                        Daftar lengkap siswa yang melakukan mutasi dengan detail NISN, Nama, dan Sekolah Tujuan/Asal.
                    </p>

                    <div class="flex items-center text-orange-600 font-medium text-sm group-hover:translate-x-1 transition">
                        Buka Laporan <i class="bi bi-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
>>>>>>> 9466a07 (Upload project pertama kali)
@endsection