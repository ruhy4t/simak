<<<<<<< HEAD
@extends('layouts.app')

@section('title', 'Daftar Mutasi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Daftar Mutasi Keluar</h1>
                <p class="text-gray-500 mt-1">Kelola data mutasi siswa</p>
            </div>
            <a href="{{ route('mutasi.create') }}"
                class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-medium px-5 py-2.5 rounded-lg transition shadow-sm">
                <i class="bi bi-plus-lg"></i>
                Buat Mutasi Baru
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition"
                        placeholder="Nama/NISN/Sekolah...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition">
                        <option value="">Semua Status</option>
                        <option value="Menunggu Verifikasi Dinas" {{ request('status') == 'Menunggu Verifikasi Dinas' ? 'selected' : '' }}>Menunggu</option>
                        <option value="Proses" {{ request('status') == 'Proses' ? 'selected' : '' }}>Proses</option>
                        <option value="Disetujui Dinas" {{ request('status') == 'Disetujui Dinas' ? 'selected' : '' }}>
                            Disetujui</option>
                        <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                    <input type="month" name="bulan" value="{{ request('bulan') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-medium px-4 py-2 rounded-lg transition">
                        <i class="bi bi-search mr-1"></i> Filter
                    </button>
                    <a href="{{ route('mutasi.index') }}"
                        class="px-4 py-2 border border-gray-300 hover:bg-gray-50 rounded-lg transition">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            @if($mutasiList->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama Siswa</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">NISN</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Sekolah Asal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tujuan</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($mutasiList as $mutasi)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('j F Y') }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $mutasi->nama_murid }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $mutasi->nisn }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div class="max-w-[150px] truncate" title="{{ $mutasi->nama_sekolah_asal }}">
                                            {{ $mutasi->nama_sekolah_asal }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div class="max-w-[150px] truncate" title="{{ $mutasi->nama_sekolah_tujuan }}">
                                            {{ $mutasi->nama_sekolah_tujuan }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $mutasi->getStatusBadgeClass() }}">
                                            {{ $mutasi->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('mutasi.show', $mutasi->id_mutasi) }}"
                                            class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 font-medium transition">
                                            <i class="bi bi-eye"></i>
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t bg-gray-50">
                    {{ $mutasiList->appends(request()->query())->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="bi bi-inbox text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum Ada Data</h3>
                    <p class="text-gray-500 mb-4">Belum ada data mutasi yang sesuai dengan filter.</p>
                    <a href="{{ route('mutasi.create') }}"
                        class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-medium px-5 py-2.5 rounded-lg transition">
                        <i class="bi bi-plus-lg"></i>
                        Buat Mutasi Baru
                    </a>
                </div>
            @endif
        </div>
    </div>
=======
@extends('layouts.app')

@section('title', 'Daftar Mutasi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Daftar Mutasi Keluar</h1>
                <p class="text-gray-500 mt-1">Kelola data mutasi siswa</p>
            </div>
            <a href="{{ route('mutasi.create') }}"
                class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-medium px-5 py-2.5 rounded-lg transition shadow-sm">
                <i class="bi bi-plus-lg"></i>
                Buat Mutasi Baru
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition"
                        placeholder="Nama/NISN/Sekolah...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition">
                        <option value="">Semua Status</option>
                        <option value="Menunggu Verifikasi Dinas" {{ request('status') == 'Menunggu Verifikasi Dinas' ? 'selected' : '' }}>Menunggu</option>
                        <option value="Proses" {{ request('status') == 'Proses' ? 'selected' : '' }}>Proses</option>
                        <option value="Disetujui Dinas" {{ request('status') == 'Disetujui Dinas' ? 'selected' : '' }}>
                            Disetujui</option>
                        <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                    <input type="month" name="bulan" value="{{ request('bulan') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-medium px-4 py-2 rounded-lg transition">
                        <i class="bi bi-search mr-1"></i> Filter
                    </button>
                    <a href="{{ route('mutasi.index') }}"
                        class="px-4 py-2 border border-gray-300 hover:bg-gray-50 rounded-lg transition">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            @if($mutasiList->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama Siswa</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">NISN</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Sekolah Asal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tujuan</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($mutasiList as $mutasi)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('j F Y') }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $mutasi->nama_murid }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $mutasi->nisn }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div class="max-w-[150px] truncate" title="{{ $mutasi->nama_sekolah_asal }}">
                                            {{ $mutasi->nama_sekolah_asal }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div class="max-w-[150px] truncate" title="{{ $mutasi->nama_sekolah_tujuan }}">
                                            {{ $mutasi->nama_sekolah_tujuan }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $mutasi->getStatusBadgeClass() }}">
                                            {{ $mutasi->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('mutasi.show', $mutasi->id_mutasi) }}"
                                            class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 font-medium transition">
                                            <i class="bi bi-eye"></i>
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t bg-gray-50">
                    {{ $mutasiList->appends(request()->query())->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="bi bi-inbox text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum Ada Data</h3>
                    <p class="text-gray-500 mb-4">Belum ada data mutasi yang sesuai dengan filter.</p>
                    <a href="{{ route('mutasi.create') }}"
                        class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-medium px-5 py-2.5 rounded-lg transition">
                        <i class="bi bi-plus-lg"></i>
                        Buat Mutasi Baru
                    </a>
                </div>
            @endif
        </div>
    </div>
>>>>>>> 9466a07 (Upload project pertama kali)
@endsection