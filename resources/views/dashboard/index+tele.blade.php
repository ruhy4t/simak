<<<<<<< HEAD
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-500 mt-1">Selamat datang, {{ $user->nama_pengguna ?? $user->username }}!</p>
            </div>
            <div class="text-sm text-gray-500">
                <i class="bi bi-calendar3 mr-1"></i>
                {{ now()->translatedFormat('l, j F Y') }}
            </div>
        </div>

        <!-- Mutasi Keluar Stats -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-box-arrow-right text-orange-600"></i>
                Statistik Mutasi Keluar
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Total Keluar -->
                <div class="bg-orange-50 rounded-xl p-4 border border-orange-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-archive text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($statsKeluar['total']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Menunggu -->
                <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Menunggu</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ number_format($statsKeluar['menunggu']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Proses -->
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-gear text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Proses</p>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($statsKeluar['proses']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Disetujui -->
                <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Disetujui</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($statsKeluar['disetujui']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Selesai -->
                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-all text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Selesai</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ number_format($statsKeluar['selesai']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mutasi Masuk Stats -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-box-arrow-in-right text-blue-600"></i>
                Statistik Mutasi Masuk
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Total Masuk -->
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-archive text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($statsMasuk['total']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Total Siswa -->
                <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-people text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Siswa</p>
                            <p class="text-2xl font-bold text-indigo-600">{{ number_format($statsMasuk['total_siswa']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Menunggu -->
                <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Menunggu</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ number_format($statsMasuk['menunggu']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Disetujui -->
                <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Disetujui</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($statsMasuk['disetujui']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Perbaikan -->
                <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-exclamation-triangle text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Perbaikan</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($statsMasuk['perbaikan']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Selesai -->
                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-all text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Selesai</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ number_format($statsMasuk['selesai']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Stats (Sekolah / Dinas) -->
        @if($statsUsers)
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-people text-purple-600"></i>
                    Statistik Pengguna
                </h2>

                @if($user->role === 'Sekolah')
                    <!-- Sekolah: Stats Operator saja -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                            <p class="text-xs text-gray-500">Total Operator</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($statsUsers['total']) }}</p>
                        </div>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                            <p class="text-xs text-gray-500">Aktif</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($statsUsers['active']) }}</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                            <p class="text-xs text-gray-500">Non-Aktif</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($statsUsers['inactive']) }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('users.manage') }}"
                            class="inline-flex items-center text-sm text-purple-600 hover:text-purple-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Kelola Pengguna
                        </a>
                    </div>
                @else
                    <!-- Dinas: Stats Sekolah & Operator -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Role Sekolah -->
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-blue-800">Role Sekolah</span>
                                <span
                                    class="text-xl font-bold text-blue-900">{{ number_format($statsUsers['sekolah_total'] ?? 0) }}</span>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>Aktif:
                                    {{ $statsUsers['sekolah_active'] ?? 0 }}</span>
                                <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>Non-Aktif:
                                    {{ $statsUsers['sekolah_inactive'] ?? 0 }}</span>
                            </div>
                        </div>
                        <!-- Role Operator -->
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-green-800">Role Operator</span>
                                <span
                                    class="text-xl font-bold text-green-900">{{ number_format($statsUsers['operator_total'] ?? 0) }}</span>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>Aktif:
                                    {{ $statsUsers['operator_active'] ?? 0 }}</span>
                                <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>Non-Aktif:
                                    {{ $statsUsers['operator_inactive'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('dinas.users') }}"
                            class="inline-flex items-center text-sm text-purple-600 hover:text-purple-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Kelola Pengguna
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Statistik Mutasi per Sekolah -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="bi bi-building text-primary-600"></i>
                    Statistik Mutasi Per Sekolah
                </h2>

                <!-- Filter Form -->
                <form method="GET" action="{{ route('dashboard') }}" class="flex gap-2 items-center flex-wrap">
                    <select name="month"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary-100 focus:border-primary-500">
                        <option value="">Semua Bulan</option>
                        @php
                            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        @endphp
                        @foreach($months as $idx => $m)
                            <option value="{{ $idx + 1 }}" {{ $filterMonth == ($idx + 1) ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                    <select name="year"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary-100 focus:border-primary-500">
                        <option value="">Semua Tahun</option>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="text-sm bg-primary-600 text-white px-4 py-1.5 rounded-lg hover:bg-primary-700 transition">
                        <i class="bi bi-funnel mr-1"></i>Filter
                    </button>
                    @if($filterMonth || $filterYear)
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    @endif
                </form>
            </div>

            @if($filterMonth || $filterYear)
                <div class="mb-4 text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
                    <i class="bi bi-info-circle mr-1"></i>
                    Menampilkan data:
                    @if($filterMonth)
                        {{ $months[$filterMonth - 1] }}
                    @endif
                    @if($filterYear)
                        {{ $filterYear }}
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tabel Mutasi Keluar -->
                <div>
                    <h3 class="text-sm font-semibold text-orange-600 mb-3 flex items-center gap-2">
                        <i class="bi bi-box-arrow-right"></i>
                        Mutasi Keluar (Top 10)
                    </h3>
                    @if(count($schoolStatsKeluar) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-orange-50 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Sekolah Asal</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-600">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($schoolStatsKeluar as $idx => $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                                            <td class="px-3 py-2 text-gray-900">{{ $item->nama_sekolah ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    {{ $item->jumlah }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-400">
                            <i class="bi bi-inbox text-3xl"></i>
                            <p class="mt-2 text-sm">Belum ada data</p>
                        </div>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('mutasi.keluar.index') }}"
                            class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Lihat Semua Mutasi Keluar
                        </a>
                    </div>
                </div>

                <!-- Tabel Mutasi Masuk -->
                <div>
                    <h3 class="text-sm font-semibold text-blue-600 mb-3 flex items-center gap-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Mutasi Masuk (Top 10)
                    </h3>
                    @if(count($schoolStatsMasuk) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-blue-50 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Sekolah Tujuan</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-600">Siswa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($schoolStatsMasuk as $idx => $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                                            <td class="px-3 py-2 text-gray-900">{{ $item->nama_sekolah ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $item->jumlah }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-400">
                            <i class="bi bi-inbox text-3xl"></i>
                            <p class="mt-2 text-sm">Belum ada data</p>
                        </div>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('mutasi.masuk.index') }}"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Lihat Semua Mutasi Masuk
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(in_array($user->role, ['Dinas', 'SuperAdmin']))
            <!-- Verifikasi - Only for Dinas/SuperAdmin -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="bi bi-clipboard-check mr-2 text-yellow-600"></i>
                    Verifikasi Berkas
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('mutasi.keluar.index', ['status' => 'Menunggu Verifikasi Dinas']) }}"
                        class="flex items-center gap-4 p-4 bg-yellow-50 border-2 border-yellow-200 hover:border-yellow-400 rounded-xl transition">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-2xl text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Mutasi Keluar Menunggu</p>
                            <p class="text-sm text-gray-500">{{ $statsKeluar['menunggu'] }} perlu verifikasi</p>
                        </div>
                    </a>
                    <a href="{{ route('mutasi.masuk.index', ['status' => 'Menunggu Verifikasi Dinas']) }}"
                        class="flex items-center gap-4 p-4 bg-yellow-50 border-2 border-yellow-200 hover:border-yellow-400 rounded-xl transition">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-2xl text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Mutasi Masuk Menunggu</p>
                            <p class="text-sm text-gray-500">{{ $statsMasuk['menunggu'] }} perlu verifikasi</p>
                        </div>
                    </a>
                </div>
            </div>
        @endif

        <!-- Info Card -->
        <div class="bg-gradient-to-r from-primary-50 to-blue-50 rounded-xl border border-primary-100 p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-info-circle text-primary-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Informasi</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        SIMAK adalah Sistem Informasi Mutasi untuk mengelola perpindahan siswa antar sekolah di Kota Bogor.
                        Pastikan semua dokumen yang diupload lengkap dan jelas untuk mempercepat proses verifikasi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Telegram Chat ID Reminder Popup --}}
    @if(empty($user->telegram_chat_id) && in_array($user->role, ['Operator', 'Sekolah', 'Dinas', 'SuperAdmin']))
        <div x-data="{ showTelegramPopup: true }" x-show="showTelegramPopup" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(0,0,0,0.5);">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">

                {{-- Header --}}
                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="bi bi-telegram text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Aktifkan Notifikasi Telegram</h3>
                            <p class="text-sm text-blue-100">Terima update status mutasi langsung di HP Anda!</p>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="px-6 py-5">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-blue-600">
                                1</div>
                            <div>
                                <p class="text-sm text-gray-700">Buka Telegram, cari bot:</p>
                                <code
                                    class="block mt-1 px-3 py-1.5 bg-gray-100 rounded text-sm font-mono text-gray-800">@simak_smp_bot</code>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-blue-600">
                                2</div>
                            <div>
                                <p class="text-sm text-gray-700">Klik <strong>/start</strong> dan bot akan memberikan
                                    <strong>Chat ID</strong> Anda</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-blue-600">
                                3</div>
                            <div>
                                <p class="text-sm text-gray-700">Copy Chat ID, lalu masukkan di <strong>Menu Profil</strong>
                                    aplikasi SIMAK</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3 bg-green-50 rounded-lg border border-green-200">
                        <div class="flex items-center gap-2 text-green-700 text-sm">
                            <i class="bi bi-check-circle"></i>
                            <span>Notifikasi: Status Disetujui, Perlu Perbaikan, Proses TTD, Selesai</span>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 flex gap-3">
                    <button @click="showTelegramPopup = false"
                        class="flex-1 px-4 py-2.5 text-gray-600 hover:text-gray-800 text-sm font-medium transition">
                        Nanti Saja
                    </button>
                    <a href="https://t.me/simak_smp_bot" target="_blank"
                        class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium text-center transition">
                        <i class="bi bi-telegram mr-1"></i> Buka Telegram
                    </a>
                </div>
            </div>
        </div>
    @endif
=======
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-500 mt-1">Selamat datang, {{ $user->nama_pengguna ?? $user->username }}!</p>
            </div>
            <div class="text-sm text-gray-500">
                <i class="bi bi-calendar3 mr-1"></i>
                {{ now()->translatedFormat('l, j F Y') }}
            </div>
        </div>

        <!-- Mutasi Keluar Stats -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-box-arrow-right text-orange-600"></i>
                Statistik Mutasi Keluar
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Total Keluar -->
                <div class="bg-orange-50 rounded-xl p-4 border border-orange-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-archive text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($statsKeluar['total']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Menunggu -->
                <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Menunggu</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ number_format($statsKeluar['menunggu']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Proses -->
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-gear text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Proses</p>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($statsKeluar['proses']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Disetujui -->
                <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Disetujui</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($statsKeluar['disetujui']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Selesai -->
                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-all text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Selesai</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ number_format($statsKeluar['selesai']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mutasi Masuk Stats -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-box-arrow-in-right text-blue-600"></i>
                Statistik Mutasi Masuk
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Total Masuk -->
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-archive text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($statsMasuk['total']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Total Siswa -->
                <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-people text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Siswa</p>
                            <p class="text-2xl font-bold text-indigo-600">{{ number_format($statsMasuk['total_siswa']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Menunggu -->
                <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Menunggu</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ number_format($statsMasuk['menunggu']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Disetujui -->
                <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Disetujui</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($statsMasuk['disetujui']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Perbaikan -->
                <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-exclamation-triangle text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Perbaikan</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($statsMasuk['perbaikan']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Selesai -->
                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-all text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Selesai</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ number_format($statsMasuk['selesai']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Stats (Sekolah / Dinas) -->
        @if($statsUsers)
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-people text-purple-600"></i>
                    Statistik Pengguna
                </h2>

                @if($user->role === 'Sekolah')
                    <!-- Sekolah: Stats Operator saja -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                            <p class="text-xs text-gray-500">Total Operator</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($statsUsers['total']) }}</p>
                        </div>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                            <p class="text-xs text-gray-500">Aktif</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($statsUsers['active']) }}</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                            <p class="text-xs text-gray-500">Non-Aktif</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($statsUsers['inactive']) }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('users.manage') }}"
                            class="inline-flex items-center text-sm text-purple-600 hover:text-purple-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Kelola Pengguna
                        </a>
                    </div>
                @else
                    <!-- Dinas: Stats Sekolah & Operator -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Role Sekolah -->
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-blue-800">Role Sekolah</span>
                                <span
                                    class="text-xl font-bold text-blue-900">{{ number_format($statsUsers['sekolah_total'] ?? 0) }}</span>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>Aktif:
                                    {{ $statsUsers['sekolah_active'] ?? 0 }}</span>
                                <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>Non-Aktif:
                                    {{ $statsUsers['sekolah_inactive'] ?? 0 }}</span>
                            </div>
                        </div>
                        <!-- Role Operator -->
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-green-800">Role Operator</span>
                                <span
                                    class="text-xl font-bold text-green-900">{{ number_format($statsUsers['operator_total'] ?? 0) }}</span>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>Aktif:
                                    {{ $statsUsers['operator_active'] ?? 0 }}</span>
                                <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>Non-Aktif:
                                    {{ $statsUsers['operator_inactive'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('dinas.users') }}"
                            class="inline-flex items-center text-sm text-purple-600 hover:text-purple-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Kelola Pengguna
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Statistik Mutasi per Sekolah -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="bi bi-building text-primary-600"></i>
                    Statistik Mutasi Per Sekolah
                </h2>

                <!-- Filter Form -->
                <form method="GET" action="{{ route('dashboard') }}" class="flex gap-2 items-center flex-wrap">
                    <select name="month"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary-100 focus:border-primary-500">
                        <option value="">Semua Bulan</option>
                        @php
                            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        @endphp
                        @foreach($months as $idx => $m)
                            <option value="{{ $idx + 1 }}" {{ $filterMonth == ($idx + 1) ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                    <select name="year"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary-100 focus:border-primary-500">
                        <option value="">Semua Tahun</option>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="text-sm bg-primary-600 text-white px-4 py-1.5 rounded-lg hover:bg-primary-700 transition">
                        <i class="bi bi-funnel mr-1"></i>Filter
                    </button>
                    @if($filterMonth || $filterYear)
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    @endif
                </form>
            </div>

            @if($filterMonth || $filterYear)
                <div class="mb-4 text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
                    <i class="bi bi-info-circle mr-1"></i>
                    Menampilkan data:
                    @if($filterMonth)
                        {{ $months[$filterMonth - 1] }}
                    @endif
                    @if($filterYear)
                        {{ $filterYear }}
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tabel Mutasi Keluar -->
                <div>
                    <h3 class="text-sm font-semibold text-orange-600 mb-3 flex items-center gap-2">
                        <i class="bi bi-box-arrow-right"></i>
                        Mutasi Keluar (Top 10)
                    </h3>
                    @if(count($schoolStatsKeluar) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-orange-50 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Sekolah Asal</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-600">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($schoolStatsKeluar as $idx => $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                                            <td class="px-3 py-2 text-gray-900">{{ $item->nama_sekolah ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    {{ $item->jumlah }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-400">
                            <i class="bi bi-inbox text-3xl"></i>
                            <p class="mt-2 text-sm">Belum ada data</p>
                        </div>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('mutasi.keluar.index') }}"
                            class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Lihat Semua Mutasi Keluar
                        </a>
                    </div>
                </div>

                <!-- Tabel Mutasi Masuk -->
                <div>
                    <h3 class="text-sm font-semibold text-blue-600 mb-3 flex items-center gap-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Mutasi Masuk (Top 10)
                    </h3>
                    @if(count($schoolStatsMasuk) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-blue-50 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Sekolah Tujuan</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-600">Siswa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($schoolStatsMasuk as $idx => $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                                            <td class="px-3 py-2 text-gray-900">{{ $item->nama_sekolah ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $item->jumlah }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-400">
                            <i class="bi bi-inbox text-3xl"></i>
                            <p class="mt-2 text-sm">Belum ada data</p>
                        </div>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('mutasi.masuk.index') }}"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Lihat Semua Mutasi Masuk
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(in_array($user->role, ['Dinas', 'SuperAdmin']))
            <!-- Verifikasi - Only for Dinas/SuperAdmin -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="bi bi-clipboard-check mr-2 text-yellow-600"></i>
                    Verifikasi Berkas
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('mutasi.keluar.index', ['status' => 'Menunggu Verifikasi Dinas']) }}"
                        class="flex items-center gap-4 p-4 bg-yellow-50 border-2 border-yellow-200 hover:border-yellow-400 rounded-xl transition">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-2xl text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Mutasi Keluar Menunggu</p>
                            <p class="text-sm text-gray-500">{{ $statsKeluar['menunggu'] }} perlu verifikasi</p>
                        </div>
                    </a>
                    <a href="{{ route('mutasi.masuk.index', ['status' => 'Menunggu Verifikasi Dinas']) }}"
                        class="flex items-center gap-4 p-4 bg-yellow-50 border-2 border-yellow-200 hover:border-yellow-400 rounded-xl transition">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-2xl text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Mutasi Masuk Menunggu</p>
                            <p class="text-sm text-gray-500">{{ $statsMasuk['menunggu'] }} perlu verifikasi</p>
                        </div>
                    </a>
                </div>
            </div>
        @endif

        <!-- Info Card -->
        <div class="bg-gradient-to-r from-primary-50 to-blue-50 rounded-xl border border-primary-100 p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-info-circle text-primary-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Informasi</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        SIMAK adalah Sistem Informasi Mutasi untuk mengelola perpindahan siswa antar sekolah di Kota Bogor.
                        Pastikan semua dokumen yang diupload lengkap dan jelas untuk mempercepat proses verifikasi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Telegram Chat ID Reminder Popup --}}
    @if(empty($user->telegram_chat_id) && in_array($user->role, ['Operator', 'Sekolah', 'Dinas', 'SuperAdmin']))
        <div x-data="{ showTelegramPopup: true }" x-show="showTelegramPopup" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(0,0,0,0.5);">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">

                {{-- Header --}}
                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="bi bi-telegram text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Aktifkan Notifikasi Telegram</h3>
                            <p class="text-sm text-blue-100">Terima update status mutasi langsung di HP Anda!</p>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="px-6 py-5">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-blue-600">
                                1</div>
                            <div>
                                <p class="text-sm text-gray-700">Buka Telegram, cari bot:</p>
                                <code
                                    class="block mt-1 px-3 py-1.5 bg-gray-100 rounded text-sm font-mono text-gray-800">@simak_smp_bot</code>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-blue-600">
                                2</div>
                            <div>
                                <p class="text-sm text-gray-700">Klik <strong>/start</strong> dan bot akan memberikan
                                    <strong>Chat ID</strong> Anda</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-blue-600">
                                3</div>
                            <div>
                                <p class="text-sm text-gray-700">Copy Chat ID, lalu masukkan di <strong>Menu Profil</strong>
                                    aplikasi SIMAK</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3 bg-green-50 rounded-lg border border-green-200">
                        <div class="flex items-center gap-2 text-green-700 text-sm">
                            <i class="bi bi-check-circle"></i>
                            <span>Notifikasi: Status Disetujui, Perlu Perbaikan, Proses TTD, Selesai</span>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 flex gap-3">
                    <button @click="showTelegramPopup = false"
                        class="flex-1 px-4 py-2.5 text-gray-600 hover:text-gray-800 text-sm font-medium transition">
                        Nanti Saja
                    </button>
                    <a href="https://t.me/simak_smp_bot" target="_blank"
                        class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium text-center transition">
                        <i class="bi bi-telegram mr-1"></i> Buka Telegram
                    </a>
                </div>
            </div>
        </div>
    @endif
>>>>>>> 9466a07 (Upload project pertama kali)
@endsection