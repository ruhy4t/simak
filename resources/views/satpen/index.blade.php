@extends('layouts.app')

@section('title', 'Satuan Pendidikan')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manajemen Satuan Pendidikan</h1>
                <p class="text-gray-500 mt-1">Kelola data sekolah di Kota Bogor</p>
            </div>
            <a href="{{ route('satpen.create') }}"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                <i class="bi bi-plus-lg mr-2"></i> Tambah Sekolah
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-xs border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="bi bi-geo-alt text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Kota Bogor</p>
                        <p class="text-xl font-bold text-gray-900">{{ $totalLocal }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-xs border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="bi bi-globe text-orange-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Luar Kota Bogor</p>
                        <p class="text-xl font-bold text-gray-900">{{ $totalExternal }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-xs border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="bi bi-building text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Negeri</p>
                        <p class="text-xl font-bold text-gray-900">{{ $byStatus['Negeri'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-xs border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="bi bi-mortarboard text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Swasta</p>
                        <p class="text-xl font-bold text-gray-900">{{ $byStatus['Swasta'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section (Pure CSS) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Status & Location Chart -->
            <div class="bg-white rounded-xl shadow-xs border p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Distribusi Status & Lokasi</h3>
                <div class="space-y-4">
                    @php
                        $totalAll = $totalLocal + $totalExternal;
                        $localPct = $totalAll > 0 ? round(($totalLocal / $totalAll) * 100) : 0;
                        $externalPct = $totalAll > 0 ? round(($totalExternal / $totalAll) * 100) : 0;
                        $statusTotal = ($byStatus['Negeri'] ?? 0) + ($byStatus['Swasta'] ?? 0);
                        $negeriPct = $statusTotal > 0 ? round((($byStatus['Negeri'] ?? 0) / $statusTotal) * 100) : 0;
                        $swastaPct = $statusTotal > 0 ? round((($byStatus['Swasta'] ?? 0) / $statusTotal) * 100) : 0;
                    @endphp
                    
                    <!-- Kota Bogor vs Luar -->
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span><i class="bi bi-geo-alt text-green-600 mr-1"></i>Kota Bogor</span>
                            <span>{{ $totalLocal }} ({{ $localPct }}%)</span>
                        </div>
                        <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full transition-all duration-500" style="width: {{ $localPct }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span><i class="bi bi-globe text-orange-500 mr-1"></i>Luar Kota Bogor</span>
                            <span>{{ $totalExternal }} ({{ $externalPct }}%)</span>
                        </div>
                        <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-orange-500 rounded-full transition-all duration-500" style="width: {{ $externalPct }}%"></div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Negeri vs Swasta (Kota Bogor only) -->
                    <p class="text-xs text-gray-500 mb-2">Status (Kota Bogor saja):</p>
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span><i class="bi bi-building text-blue-600 mr-1"></i>Negeri</span>
                            <span>{{ $byStatus['Negeri'] ?? 0 }} ({{ $negeriPct }}%)</span>
                        </div>
                        <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full transition-all duration-500" style="width: {{ $negeriPct }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span><i class="bi bi-mortarboard text-purple-600 mr-1"></i>Swasta</span>
                            <span>{{ $byStatus['Swasta'] ?? 0 }} ({{ $swastaPct }}%)</span>
                        </div>
                        <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full transition-all duration-500" style="width: {{ $swastaPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kecamatan Chart (Horizontal Stacked Bar - Negeri/Swasta) -->
            <div class="bg-white rounded-xl shadow-xs border p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Per Kecamatan (Kota Bogor)</h3>
                <!-- Legend -->
                <div class="flex gap-4 mb-3 text-xs">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-500 rounded-sm"></span> Negeri</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-purple-500 rounded-sm"></span> Swasta</span>
                </div>
                <div class="space-y-2 max-h-72 overflow-y-auto pr-2">
                    @php
                        $maxKecamatan = $byKecamatan->max('total') ?: 1;
                    @endphp
                    @forelse($byKecamatan as $kec)
                        @php
                            $negeriPctLocal = $maxKecamatan > 0 ? round(($kec->negeri / $maxKecamatan) * 100) : 0;
                            $swastaPctLocal = $maxKecamatan > 0 ? round(($kec->swasta / $maxKecamatan) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600 w-24 truncate" title="{{ $kec->kecamatan }}">{{ $kec->kecamatan }}</span>
                            <div class="flex-1 h-6 bg-gray-100 rounded-sm overflow-hidden flex">
                                @if($kec->negeri > 0)
                                    <div class="h-full bg-blue-500 flex items-center justify-center transition-all duration-500" 
                                         style="width: {{ $negeriPctLocal }}%"
                                         title="Negeri: {{ $kec->negeri }}">
                                        <span class="text-xs text-white font-medium px-1">{{ $kec->negeri }}</span>
                                    </div>
                                @endif
                                @if($kec->swasta > 0)
                                    <div class="h-full bg-purple-500 flex items-center justify-center transition-all duration-500" 
                                         style="width: {{ $swastaPctLocal }}%"
                                         title="Swasta: {{ $kec->swasta }}">
                                        <span class="text-xs text-white font-medium px-1">{{ $kec->swasta }}</span>
                                    </div>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500 w-8 text-right font-medium">{{ $kec->total }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">Tidak ada data kecamatan</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-xs border p-4">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" placeholder="Cari nama atau NPSN..."
                        value="{{ request('search') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                </div>
                <div>
                    <select name="status"
                        class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                        <option value="">Semua Status</option>
                        <option value="Negeri" {{ request('status') == 'Negeri' ? 'selected' : '' }}>Negeri</option>
                        <option value="Swasta" {{ request('status') == 'Swasta' ? 'selected' : '' }}>Swasta</option>
                    </select>
                </div>
                <div>
                    <select name="is_local"
                        class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                        <option value="">Semua Sumber</option>
                        <option value="1" {{ request('is_local') === '1' ? 'selected' : '' }}>Kota Bogor</option>
                        <option value="0" {{ request('is_local') === '0' ? 'selected' : '' }}>Luar Kota Bogor</option>
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                    <i class="bi bi-search mr-1"></i> Cari
                </button>
                @if(request()->hasAny(['search', 'status', 'is_local']))
                    <a href="{{ route('satpen.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-xs border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">NPSN</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nama Sekolah</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kepala Sekolah</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Bentuk</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kota</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sumber</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($satpenList as $satpen)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-mono text-sm">{{ $satpen->npsn }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $satpen->nama }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs">{{ $satpen->alamat_lengkap ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900 font-medium">{{ $satpen->kepala_sekolah_nama ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $satpen->kepala_sekolah_nip ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $satpen->bentuk_pendidikan ?? 'SMP' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $satpen->status_sp == 'Negeri' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ $satpen->status_sp ?? 'Swasta' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        // Calculate Totals since accessors might need refreshing or manual calculation here
                                        $total = ($satpen->jumlah_siswa_awal_7 ?? 0) + ($satpen->jumlah_siswa_awal_8 ?? 0) + ($satpen->jumlah_siswa_awal_9 ?? 0);
                                        $totalL = ($satpen->jumlah_siswa_7_l ?? 0) + ($satpen->jumlah_siswa_8_l ?? 0) + ($satpen->jumlah_siswa_9_l ?? 0);
                                        $totalP = ($satpen->jumlah_siswa_7_p ?? 0) + ($satpen->jumlah_siswa_8_p ?? 0) + ($satpen->jumlah_siswa_9_p ?? 0);
                                    @endphp
                                    <div class="font-bold text-gray-900">{{ $total }}</div>
                                    <div class="text-xs text-gray-500 flex gap-2">
                                        <span class="text-blue-600" title="Laki-laki"><i class="bi bi-gender-male"></i> {{ $totalL }}</span>
                                        <span class="text-pink-600" title="Perempuan"><i class="bi bi-gender-female"></i> {{ $totalP }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $satpen->kota ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($satpen->is_local)
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                            <i class="bi bi-geo-alt mr-1"></i> Kota Bogor
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-600">
                                            <i class="bi bi-globe mr-1"></i> Luar Kota
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('satpen.edit', $satpen->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-sm text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition">
                                        <i class="bi bi-pencil mr-1"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <i class="bi bi-building text-4xl text-gray-300"></i>
                                    <p class="mt-2">Tidak ada data ditemukan</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($satpenList->hasPages())
                <div class="px-4 py-3 border-t bg-gray-50">
                    {{ $satpenList->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- Stats -->
        <div class="text-sm text-gray-500 text-center">
            Total: {{ $satpenList->total() }} satuan pendidikan
        </div>
    </div>
@endsection