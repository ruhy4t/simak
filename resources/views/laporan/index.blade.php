@extends('layouts.app')

@section('title', 'Laporan Mutasi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Mutasi</h1>
            <p class="text-gray-500 mt-1">Pilih jenis laporan yang ingin Anda cetak atau lihat.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch">
            <!-- LEFT COLUMN: Features List -->
            <div class="flex flex-col justify-between h-full gap-6">

                <!-- Card Laporan Rekapitulasi (Blue Theme) -->
                <a href="{{ route('laporan.rekap') }}"
                    class="group flex flex-1 items-center bg-blue-50/80 rounded-2xl border-2 border-blue-100 p-8 transition hover:bg-blue-100/80 hover:border-blue-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="flex items-center gap-6">
                        <div
                            class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-blue-600 shadow-xs group-hover:scale-110 transition-transform">
                            <i class="bi bi-bar-chart-line text-3xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-extrabold text-blue-900">Rekapitulasi</h3>
                            <p class="text-blue-700/80 text-sm mt-1 font-medium">Ringkasan statistik mutasi masuk & keluar.
                            </p>
                        </div>
                    </div>
                </a>

                <!-- Card Grafik Tren Mutasi (Indigo Theme) -->
                <a href="{{ route('laporan.tren') }}"
                    class="group flex flex-1 items-center bg-indigo-50/80 rounded-2xl border-2 border-indigo-100 p-8 transition hover:bg-indigo-100/80 hover:border-indigo-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="flex items-center gap-6">
                        <div
                            class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-indigo-600 shadow-xs group-hover:scale-110 transition-transform">
                            <i class="bi bi-graph-up text-3xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-extrabold text-indigo-900">Tren Mutasi</h3>
                            <p class="text-indigo-700/80 text-sm mt-1 font-medium">Visualisasi tren data harian & bulanan.
                            </p>
                        </div>
                    </div>
                </a>

                <!-- Card Laporan Detail (Orange Theme) -->
                <a href="{{ route('laporan.kohort') }}"
                    class="group flex flex-1 items-center rounded-2xl border-2 border-emerald-100 bg-emerald-50/80 p-8 transition hover:-translate-y-1 hover:border-emerald-300 hover:bg-emerald-100/80 hover:shadow-lg">
                    <div class="flex items-center gap-6">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-xs transition-transform group-hover:scale-110">
                            <i class="bi bi-bar-chart-steps text-3xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-extrabold text-emerald-900">Kohort Awal–Akhir</h3>
                            <p class="mt-1 text-sm font-medium text-emerald-700/80">Rekonsiliasi data awal, mutasi, dan data akhir aktual.</p>
                        </div>
                    </div>
                </a>

                <!-- Card Laporan Detail (Orange Theme) -->
                <a href="{{ route('laporan.nominatif') }}"
                    class="group flex flex-1 items-center bg-orange-50/80 rounded-2xl border-2 border-orange-100 p-8 transition hover:bg-orange-100/80 hover:border-orange-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="flex items-center gap-6">
                        <div
                            class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-orange-600 shadow-xs group-hover:scale-110 transition-transform">
                            <i class="bi bi-table text-3xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-extrabold text-orange-900">Daftar Nominatif</h3>
                            <p class="text-orange-800/80 text-sm mt-1 font-medium">Detail data siswa by NISN.</p>
                        </div>
                    </div>
                </a>

            </div>

            <!-- RIGHT COLUMN: Cetak Komprehensif (Emerald Light Theme) -->
            <div class="h-full">
                <div
                    class="bg-emerald-50 rounded-3xl border-2 border-emerald-100 shadow-xl p-8 text-emerald-900 h-full relative overflow-hidden flex flex-col justify-center">

                    <div class="relative z-10 space-y-8">
                        <div class="flex items-start gap-5">
                            <div class="p-4 bg-white rounded-2xl shadow-xs border border-emerald-100/50">
                                <i class="bi bi-printer-fill text-4xl text-emerald-600"></i>
                            </div>
                            <div>
                                <h2 class="text-3xl font-black text-emerald-950 tracking-tight">Cetak Laporan Lengkap</h2>
                                <p class="text-emerald-800 text-base mt-2 leading-relaxed font-medium">Download arsip
                                    lengkap PDF (Rekap, Grafik, Nominatif) dalam satu dokumen.</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-8 border border-emerald-100 shadow-xs">
                            <form action="{{ route('laporan.cetak-komprehensif') }}" method="GET" target="_blank"
                                class="space-y-6">

                                <div>
                                    <label
                                        class="block text-sm font-bold uppercase tracking-wider text-emerald-900 mb-3">Pilih
                                        Periode Laporan</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <select name="month"
                                            class="sm:col-span-2 bg-slate-50 text-slate-900 font-bold text-lg rounded-xl border border-slate-200 focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 py-3.5 px-4 cursor-pointer hover:bg-white transition">
                                            <option value="">Setahun Penuh</option>
                                            @for($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                                    {{ \Carbon\Carbon::createFromDate(null, $m)->translatedFormat('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                        <select name="year"
                                            class="bg-slate-50 text-slate-900 font-bold text-lg rounded-xl border border-slate-200 focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 py-3.5 px-4 cursor-pointer hover:bg-white transition">
                                            @for($y = date('Y'); $y >= 2020; $y--)
                                                <option value="{{ $y }}">{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                                <button type="submit"
                                    class="w-full py-4 bg-emerald-600 text-white font-extrabold text-xl rounded-xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3 group">
                                    <i
                                        class="bi bi-printer group-hover:rotate-12 transition-transform duration-300 text-2xl"></i>
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
