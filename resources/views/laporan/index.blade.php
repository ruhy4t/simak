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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Card Laporan Rekapitulasi -->
            <a href="{{ route('laporan.rekap') }}"
                class="group relative bg-white rounded-lg shadow-sm border border-slate-200 p-4 transition hover:shadow-md hover:border-blue-400">
                <div class="flex items-start gap-4">
                    <div
                        class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="bi bi-bar-chart-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm mb-1 group-hover:text-blue-700">Rekapitulasi</h3>
                        <p class="text-slate-500 text-xs leading-relaxed">
                            Ringkasan statistik mutasi masuk & keluar.
                        </p>
                    </div>
                </div>
            </a>

            <!-- Card Grafik Tren Mutasi -->
            <a href="{{ route('laporan.tren') }}"
                class="group relative bg-white rounded-lg shadow-sm border border-slate-200 p-4 transition hover:shadow-md hover:border-indigo-400">
                <div class="flex items-start gap-4">
                    <div
                        class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                        <i class="bi bi-graph-up text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm mb-1 group-hover:text-indigo-700">Tren Mutasi</h3>
                        <p class="text-slate-500 text-xs leading-relaxed">
                            Visualisasi tren harian/bulanan.
                        </p>
                    </div>
                </div>
            </a>

            <!-- Card Laporan Detail (Daftar Nominatif) -->
            <a href="{{ route('laporan.nominatif') }}"
                class="group relative bg-white rounded-lg shadow-sm border border-slate-200 p-4 transition hover:shadow-md hover:border-orange-400">
                <div class="flex items-start gap-4">
                    <div
                        class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition">
                        <i class="bi bi-table text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm mb-1 group-hover:text-orange-700">Daftar Nominatif</h3>
                        <p class="text-slate-500 text-xs leading-relaxed">
                            Detail siswa mutasi (NISN, Nama, Asal/Tujuan).
                        </p>
                    </div>
                </div>
            </a>
        </div>
</div>@endsection