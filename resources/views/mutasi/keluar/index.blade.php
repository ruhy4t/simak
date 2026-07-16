@extends('layouts.app')

@section('title', 'Daftar Mutasi Keluar')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-600">Pengajuan Siswa</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Mutasi Keluar</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola data mutasi siswa keluar</p>
            </div>
            <a href="{{ route('mutasi.keluar.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 font-semibold text-white transition-colors hover:bg-sky-700">
                <i class="bi bi-plus-lg"></i>
                Buat Mutasi Baru
            </a>
        </div>

        <!-- Filters -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4">
            <form method="GET" id="filterForm" class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                        class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-11 pr-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                        placeholder="Cari nama siswa, sekolah...">
                </div>

                <div class="relative sm:w-64">
                    <i class="bi bi-filter absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="status" id="statusFilter"
                        class="w-full cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white py-2.5 pl-11 pr-8 text-sm text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
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
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800">
                    <i class="bi bi-search"></i> Terapkan
                </button>

                @if(request('search') || request('status'))
                    <a href="{{ route('mutasi.keluar.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        title="Reset Filter">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white">
            @if(count($mutasiList) > 0)
                <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900">Daftar Pengajuan</h2>
                        <p class="text-xs text-slate-500">{{ method_exists($mutasiList, 'total') ? number_format($mutasiList->total()) : number_format(count($mutasiList)) }} data ditemukan</p>
                    </div>
                    @if(request('search') || request('status'))
                        <span class="inline-flex self-start items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                            <i class="bi bi-funnel"></i> Filter aktif
                        </span>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <caption class="sr-only">Daftar pengajuan mutasi keluar</caption>
                        <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50/95">
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
                        <tbody class="divide-y divide-slate-100">
                            @foreach($mutasiList as $mutasi)
                                <tr class="transition-colors hover:bg-sky-50/40">
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                        {{ \Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('j F Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 font-mono text-xs">
                                        #{{ $mutasi->id_mutasi }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        {{ $mutasi->nama_murid ?: 'Draft belum diisi' }}
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
                                                title="{{ $mutasi->nama_sekolah_tujuan ?: 'Draft belum diisi' }}">{{ $mutasi->nama_sekolah_tujuan ?: 'Draft belum diisi' }}</span>
                                            <span
                                                class="text-xs text-slate-500 font-mono">{{ ($mutasi->npsn_sekolah_tujuan ?? '') === '00000000' ? '-' : ($mutasi->npsn_sekolah_tujuan ?? '-') }}</span>
                                            @if($mutasi->kabupaten_kota_tujuan)
                                                <span class="text-xs text-slate-500 mt-0.5 flex items-center">
                                                    <i
                                                        class="bi bi-geo-alt mr-1 text-slate-400"></i>{{ str_replace('Kabupaten', 'Kab.', $mutasi->kabupaten_kota_tujuan) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <x-status-badge :status="$mutasi->status" />
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            {{-- Resume Draft Button --}}
                                            @if($mutasi->status === 'Draft')
                                                <a href="{{ route('mutasi.keluar.draft.resume', $mutasi->id_mutasi) }}"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-600"
                                                    title="Lanjutkan Pengisian">
                                                    <i class="bi bi-pencil-square"></i>
                                                    Lanjutkan
                                                </a>
                                                <form action="{{ route('mutasi.keluar.draft.delete', $mutasi->id_mutasi) }}"
                                                    method="POST" class="inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus draft ini?\n\nData yang sudah diisi akan dihapus permanen.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50"
                                                        title="Hapus Draft">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('mutasi.keluar.show', $mutasi->id_mutasi) }}"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                    title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                    <span>Detail</span>
                                                </a>

                                                @if($mutasi->status === 'Selesai')
                                                    @if($mutasi->file_arsip_scan)
                                                        <a href="{{ route('dokumen.view', ['path' => $mutasi->file_arsip_scan]) }}"
                                                            target="_blank"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 text-emerald-600 hover:bg-emerald-50"
                                                            title="Lihat Arsip Digital">
                                                            <i class="bi bi-file-earmark-check"></i>
                                                        </a>
                                                    @else
                                                        <div x-data="{ openPrint: false }" class="relative inline-block">
                                                            <button @click="openPrint = !openPrint" @click.outside="openPrint = false"
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                                title="Cetak Surat">
                                                                <i class="bi bi-printer"></i>
                                                            </button>
                                                            <div x-show="openPrint" x-transition
                                                                class="absolute right-0 mt-1 w-40 bg-white border border-slate-200 rounded-lg shadow-xl z-10 overflow-hidden">
                                                                <a href="{{ route('mutasi.keluar.print-letter', $mutasi->id_mutasi) }}?mode=tnd"
                                                                    target="_blank"
                                                                    class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                                                                    <i class="bi bi-pen text-indigo-500"></i> Cetak TND
                                                                </a>
                                                                <a href="{{ route('mutasi.keluar.print-letter', $mutasi->id_mutasi) }}?mode=manual"
                                                                    target="_blank"
                                                                    class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition border-t border-slate-100">
                                                                    <i class="bi bi-pencil-square text-blue-500"></i> Cetak Manual
                                                                </a>
                                                            </div>
                                                        </div>
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

    {{-- Schedule Block Modal --}}
    @if(session('schedule_block'))
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
            x-data="{ show: true }" x-show="show" x-transition.opacity>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-100">
                <div class="p-8 text-center">
                    <div
                        class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-5">
                        <i class="bi bi-shop text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Layanan Tutup Sementara</h3>
                    <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                        Mohon maaf, layanan ini sedang tutup sementara. Silakan kembali pada jadwal operasional.
                    </p>

                    <div class="bg-slate-50 rounded-2xl p-5 text-left text-sm space-y-3 mb-8 border border-slate-100">
                        <p class="text-slate-700 font-semibold mb-2">Layanan hanya tersedia pada:</p>
                        <div class="flex items-start gap-3 text-slate-600">
                            <div class="w-6 flex justify-center"><i class="bi bi-calendar4-week text-indigo-500"></i></div>
                            <span class="flex-1">{{ session('schedule_block')['days'] }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-slate-600">
                            <div class="w-6 flex justify-center"><i class="bi bi-alarm text-rose-500"></i></div>
                            <span class="flex-1">{{ session('schedule_block')['hours'] }}</span>
                        </div>
                    </div>

                    <button @click="show = false" type="button"
                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-lg shadow-indigo-500/30 active:scale-95">
                        OK, Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    @endif

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
