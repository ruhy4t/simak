@extends('layouts.app')

@section('title', 'Daftar Mutasi Keluar')

@section('content')
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Mutasi Keluar</h1>
                <p class="text-slate-500 mt-1">Kelola data mutasi siswa keluar</p>
            </div>
            <a href="{{ route('mutasi.keluar.create') }}"
                class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-medium px-5 py-2.5 rounded-xl shadow-lg shadow-sky-500/30 transition-all hover:-translate-y-0.5 group">
                <i class="bi bi-plus-lg group-hover:rotate-90 transition-transform"></i>
                Buat Mutasi Baru
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-1.5">
            <form method="GET" id="filterForm" class="flex flex-col sm:flex-row gap-2">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-sm text-slate-700 placeholder:text-slate-400"
                        placeholder="Cari nama siswa, sekolah...">
                </div>

                <div class="relative sm:w-64">
                    <i class="bi bi-filter absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="status" id="statusFilter"
                        class="w-full pl-11 pr-8 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-sm text-slate-700 cursor-pointer appearance-none">
                        <option value="">Semua Status</option>
                        <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>📝 Draft (Belum Selesai)
                        </option>
                        <option value="Menunggu Verifikasi Dinas" {{ request('status') == 'Menunggu Verifikasi Dinas' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                        <option value="Perlu Perbaikan" {{ request('status') == 'Perlu Perbaikan' ? 'selected' : '' }}>Perlu
                            Perbaikan</option>
                        <option value="Disetujui Dinas" {{ request('status') == 'Disetujui Dinas' ? 'selected' : '' }}>
                            Disetujui Dinas</option>
                        <option value="Proses TTD" {{ request('status') == 'Proses TTD' ? 'selected' : '' }}>Proses TTD
                        </option>
                        <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>

                <button type="submit"
                    class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl transition font-medium shadow-md">
                    Cari
                </button>

                @if(request('search') || request('status'))
                    <a href="{{ route('mutasi.keluar.index') }}"
                        class="px-4 py-2.5 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 rounded-xl transition flex items-center justify-center"
                        title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            @if(count($mutasiList) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID
                                    Mutasi</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa
                                </th>
                                @if(!in_array(Auth::user()->role, ['Operator', 'Sekolah']))
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Sekolah Asal</th>
                                @endif
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Tujuan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($mutasiList as $mutasi)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                        {{ \Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('j F Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 font-mono text-xs">
                                        #{{ $mutasi->id_mutasi }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        {{ $mutasi->nama_murid }}
                                    </td>
                                    @if(!in_array(Auth::user()->role, ['Operator', 'Sekolah']))
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 truncate max-w-[180px]"
                                                    title="{{ $mutasi->nama_sekolah_asal }}">{{ $mutasi->nama_sekolah_asal }}</span>
                                                <span
                                                    class="text-xs text-slate-400 font-mono">{{ $mutasi->npsn_sekolah_asal ?? '-' }}</span>
                                            </div>
                                        </td>
                                    @endif
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900 truncate max-w-[200px]"
                                                title="{{ $mutasi->nama_sekolah_tujuan }}">{{ $mutasi->nama_sekolah_tujuan }}</span>
                                            <span
                                                class="text-xs text-slate-500 font-mono">{{ $mutasi->npsn_sekolah_tujuan ?? '-' }}</span>
                                            @if($mutasi->kabupaten_kota_tujuan)
                                                <span class="text-xs text-slate-500 mt-0.5 flex items-center">
                                                    <i
                                                        class="bi bi-geo-alt mr-1 text-slate-400"></i>{{ str_replace('Kabupaten', 'Kab.', $mutasi->kabupaten_kota_tujuan) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusMap = [
                                                'Draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'ring' => 'ring-gray-500/20', 'icon' => 'bi-pencil-square'],
                                                'Menunggu Verifikasi Dinas' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'ring' => 'ring-amber-600/20', 'icon' => 'bi-hourglass-split'],
                                                'Proses' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'ring' => 'ring-blue-600/20', 'icon' => 'bi-arrow-repeat'],
                                                'Proses TTD' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'ring' => 'ring-indigo-600/20', 'icon' => 'bi-pen'],
                                                'Disetujui Dinas' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'ring' => 'ring-emerald-600/20', 'icon' => 'bi-check-circle'],
                                                'Selesai' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-800', 'ring' => 'ring-sky-600/20', 'icon' => 'bi-check-all'],
                                                'Perlu Perbaikan' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-800', 'ring' => 'ring-rose-600/20', 'icon' => 'bi-exclamation-circle'],
                                                'Dibatalkan' => ['bg' => 'bg-slate-200', 'text' => 'text-slate-600', 'ring' => 'ring-slate-500/20', 'icon' => 'bi-x-circle'],
                                            ];
                                            $defaultStatus = ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'ring' => 'ring-slate-600/20', 'icon' => 'bi-circle'];
                                            $statusConfig = $statusMap[$mutasi->status] ?? $defaultStatus;
                                        @endphp
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }} ring-1 ring-inset {{ $statusConfig['ring'] }}">
                                            <i class="bi {{ $statusConfig['icon'] }}"></i>
                                            {{ $mutasi->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            {{-- Resume Draft Button --}}
                                            @if($mutasi->status === 'Draft')
                                                <a href="{{ route('mutasi.keluar.draft.resume', $mutasi->id_mutasi) }}"
                                                    class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-amber-500 text-white hover:bg-amber-600 shadow-md shadow-amber-500/20 transition-all hover:-translate-y-0.5 text-sm font-medium gap-1"
                                                    title="Lanjutkan Pengisian">
                                                    <i class="bi bi-pencil-square"></i>
                                                    Lanjutkan
                                                </a>
                                            @else
                                                <a href="{{ route('mutasi.keluar.show', $mutasi->id_mutasi) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all hover:-translate-y-0.5"
                                                    title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>

                                                @if($mutasi->status === 'Selesai')
                                                    @if($mutasi->file_arsip_scan)
                                                        <a href="{{ route('dokumen.view', ['path' => $mutasi->file_arsip_scan]) }}"
                                                            target="_blank"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-600 text-white hover:bg-green-700 shadow-md shadow-green-500/20 transition-all hover:-translate-y-0.5"
                                                            title="Lihat Arsip Digital">
                                                            <i class="bi bi-file-earmark-check"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('mutasi.keluar.print-letter', $mutasi->id_mutasi) }}" target="_blank"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-500/20 transition-all hover:-translate-y-0.5"
                                                            title="Cetak Surat">
                                                            <i class="bi bi-printer"></i>
                                                        </a>
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(method_exists($mutasiList, 'links'))
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                        {{ $mutasiList->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="p-16 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="bi bi-inbox text-3xl text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-1">Belum Ada Data</h3>
                    <p class="text-slate-500 mb-6">Belum ada data mutasi keluar yang sesuai dengan filter.</p>
                    <a href="{{ route('mutasi.keluar.create') }}"
                        class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-medium px-5 py-2.5 rounded-xl transition shadow-lg shadow-sky-500/30 hover:-translate-y-0.5">
                        <i class="bi bi-plus-lg"></i>
                        Buat Mutasi Baru
                    </a>
                </div>
            @endif
        </div>
    </div>

    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const filterForm = document.getElementById('filterForm');
            const statusFilter = document.getElementById('statusFilter');
            let debounceTimer;

            // Auto-search when typing 3+ characters
            if (searchInput && filterForm) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();

                    debounceTimer = setTimeout(function () {
                        if (query.length >= 3 || query.length === 0) {
                            filterForm.submit();
                        }
                    }, 500);
                });
            }

            // Auto-submit when status filter changes
            if (statusFilter && filterForm) {
                statusFilter.addEventListener('change', function () {
                    filterForm.submit();
                });
            }
        });
</script>@endsection