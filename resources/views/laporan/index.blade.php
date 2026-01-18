@extends('layouts.app')

@section('title', 'Laporan Mutasi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Mutasi</h1>
            <p class="text-gray-500 mt-1">Pilih jenis laporan yang ingin Anda cetak atau lihat.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- LEFT COLUMN: Features List -->
            <div class="lg:col-span-1 space-y-4">

                <!-- Card Laporan Rekapitulasi -->
                <a href="{{ route('laporan.rekap') }}"
                    class="group block bg-white rounded-xl shadow-sm border border-slate-200 p-5 transition hover:shadow-md hover:border-blue-400">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                            <i class="bi bi-bar-chart-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 group-hover:text-blue-700">Rekapitulasi</h3>
                            <p class="text-slate-500 text-xs mt-0.5">Ringkasan statistik mutasi.</p>
                        </div>
                    </div>
                </a>

                <!-- Card Grafik Tren Mutasi -->
                <a href="{{ route('laporan.tren') }}"
                    class="group block bg-white rounded-xl shadow-sm border border-slate-200 p-5 transition hover:shadow-md hover:border-indigo-400">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                            <i class="bi bi-graph-up text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 group-hover:text-indigo-700">Tren Mutasi</h3>
                            <p class="text-slate-500 text-xs mt-0.5">Visualisasi tren harian/bulanan.</p>
                        </div>
                    </div>
                </a>

                <!-- Card Laporan Detail (Daftar Nominatif) -->
                <a href="{{ route('laporan.nominatif') }}"
                    class="group block bg-white rounded-xl shadow-sm border border-slate-200 p-5 transition hover:shadow-md hover:border-orange-400">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition">
                            <i class="bi bi-table text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 group-hover:text-orange-700">Daftar Nominatif</h3>
                            <p class="text-slate-500 text-xs mt-0.5">Detail siswa by NISN.</p>
                        </div>
                    </div>
                </a>

            </div>

            <!-- RIGHT COLUMN: Cetak Komprehensif -->
            <div class="lg:col-span-2">
                <div
                    class="bg-gradient-to-br from-emerald-500 to-teal-700 rounded-2xl shadow-lg p-8 text-white h-full relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <i class="bi bi-printer-fill text-9xl"></i>
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="p-3 bg-white/20 rounded-xl backdrop-blur-md shadow-inner">
                                <i class="bi bi-printer-fill text-3xl text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold">Cetak Laporan Lengkap</h2>
                                <p class="text-emerald-100 text-sm">Download arsip lengkap PDF (Rekap, Grafik, Nominatif)
                                    dalam satu dokumen.</p>
                            </div>
                        </div>

                        <div class="bg-white/10 rounded-2xl p-6 backdrop-blur-md border border-white/20">
                            <form action="{{ route('laporan.cetak-komprehensif') }}" method="GET" target="_blank"
                                class="space-y-5">

                                <div>
                                    <label
                                        class="block text-xs font-bold uppercase tracking-wider text-emerald-100 mb-2">Pilih
                                        Periode Laporan</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <select name="month"
                                            class="sm:col-span-2 bg-white text-slate-800 font-medium rounded-xl border-0 focus:ring-4 focus:ring-emerald-500/30 py-3">
                                            <option value="">Setahun Penuh (Januari - Desember)</option>
                                            @for($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                                    {{ \Carbon\Carbon::createFromDate(null, $m)->translatedFormat('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                        <select name="year"
                                            class="bg-white text-slate-800 font-medium rounded-xl border-0 focus:ring-4 focus:ring-emerald-500/30 py-3">
                                            @for($y = date('Y'); $y >= 2020; $y--)
                                                <option value="{{ $y }}">{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                                <button type="submit"
                                    class="w-full py-3.5 bg-white text-emerald-700 font-bold text-lg rounded-xl shadow-lg shadow-black/10 hover:bg-emerald-50 hover:scale-[1.01] active:scale-[0.98] transition-all flex items-center justify-center gap-2 group">
                                    <i class="bi bi-printer group-hover:rotate-12 transition-transform duration-300"></i>
                                    <span>Cetak Laporan Sekarang</span>
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection