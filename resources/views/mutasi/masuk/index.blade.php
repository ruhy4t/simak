@extends('layouts.app')

@section('title', 'Daftar Mutasi Masuk')

@section('content')
    @php
        $requiresTelegramLink = in_array(Auth::user()->role, ['Sekolah', 'Operator'], true)
            && blank(Auth::user()->telegram_chat_id);
    @endphp
    <div class="space-y-6">
        @if($requiresTelegramLink)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900" role="status">
                Tautkan Telegram melalui <a href="{{ route('profile.index') }}" class="font-semibold underline">profil</a> sebelum membuat atau melanjutkan usulan mutasi, termasuk draft.
            </div>
        @endif
        @if(request()->boolean('draft_saved'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800" role="status">
                <i class="bi bi-check-circle-fill mt-0.5"></i>
                <div>
                    <p class="font-semibold">Draft berhasil disimpan</p>
                    <p class="text-sm text-emerald-700">Data dapat dilanjutkan kembali dari daftar berstatus Draft.</p>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-600">Pengajuan Murid</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Mutasi Masuk</h1>
                <p class="mt-1 text-sm text-slate-500">Daftar pengajuan murid pindahan masuk ke sekolah</p>
            </div>
            @if(in_array(Auth::user()->role, ['Sekolah', 'Operator', 'Dinas', 'SuperAdmin']) && !$requiresTelegramLink)
                <a href="{{ route('mutasi.masuk.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2.5 font-semibold text-white transition-colors hover:bg-sky-700">
                    <i class="bi bi-plus-lg mr-2"></i>
                    Buat Mutasi Masuk
                </a>
            @endif
        </div>

        <!-- Filters -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4">
            <form action="{{ route('mutasi.masuk.index') }}" method="GET" id="filterForm"
                class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                        class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-11 pr-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                        placeholder="Cari nomor surat, nama sekolah...">
                </div>
                <div class="relative sm:w-64">
                    <i class="bi bi-filter absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="status" id="statusFilter"
                        class="w-full cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white py-2.5 pl-11 pr-8 text-sm text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
                        <option value="">Semua Status</option>
                        <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>📝 Draft (Belum Selesai)</option>
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
                    <a href="{{ route('mutasi.masuk.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white" x-data="incomingStudentModal">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-slate-900">Daftar Pengajuan</h2>
                    <p class="text-xs text-slate-500">{{ number_format($mutasiList->count()) }} data ditampilkan</p>
                </div>
                @if(request('search') || request('status'))
                    <span class="inline-flex self-start items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                        <i class="bi bi-funnel"></i> Filter aktif
                    </span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <caption class="sr-only">Daftar pengajuan mutasi masuk</caption>
                    <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50/95">
                        <tr>
                            @if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin']))
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Aktivitas Terakhir</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID
                                    Mutasi</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Tujuan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Asal</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Jumlah Murid</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Pengusul</th>
                            @else
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID
                                    Mutasi</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Aktivitas Terakhir</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor
                                    Surat</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Asal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Murid
                                </th>
                            @endif

                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($mutasiList as $mutasi)
                            <tr class="transition-colors hover:bg-sky-50/40">

                                @if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin']))
                                                    {{-- DINAS COLUMNS --}}
                                                    <td class="px-6 py-4 text-sm text-slate-600">
                                                        <x-submission-date :original="$mutasi->tanggal_pengajuan"
                                                            :latest="$mutasi->last_submitted_at ?? null" :revision-count="$mutasi->revision_count ?? 0" />
                                                    </td>
                                                    <td class="px-6 py-4 text-sm font-mono text-slate-500">
                                                        #{{ $mutasi->id_mutasi }}
                                                    </td>
                                                    <td class="px-6 py-4 text-sm">
                                                        <div class="flex flex-col">
                                                            <span class="font-bold text-slate-800">{{ $mutasi->nama_sekolah_tujuan }}</span>
                                                            <span class="text-xs text-slate-400 font-mono">{{ $mutasi->npsn_sekolah_tujuan }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-slate-600">
                                                        @php
                                                            $uniqueSchools = $mutasi->siswa->unique('nama_sekolah_asal');
                                                            $count = $uniqueSchools->count();
                                                            $first = $uniqueSchools->first();
                                                        @endphp
                                                        @if($count === 1)
                                                            <span class="block truncate max-w-[200px]"
                                                                title="{{ $first->nama_sekolah_asal }}">{{ $first->nama_sekolah_asal }}</span>
                                                        @else
                                                            <span class="italic text-slate-500">Gabungan {{ $count }} Sekolah</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        <button @click="openStudents('#{{ $mutasi->id_mutasi }}', {{ json_encode($mutasi->siswa->map(fn($s) => [
                                        'nama' => $s->nama_murid,
                                        'nisn' => $s->nisn,
                                        'asal' => $s->nama_sekolah_asal
                                    ])) }})"
                                                            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-all ring-1 ring-inset ring-indigo-600/10 hover:ring-indigo-600">
                                                            <i class="bi bi-people-fill"></i>
                                                            {{ $mutasi->jumlah_siswa }}
                                                        </button>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-slate-600">
                                                        {{ $mutasi->pengusul ?: '-' }}
                                                    </td>
                                @else
                                    {{-- STANDARD COLUMNS --}}
                                    <td class="px-6 py-4 text-sm font-mono text-slate-500">
                                        #{{ $mutasi->id_mutasi }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <x-submission-date :original="$mutasi->tanggal_pengajuan"
                                            :latest="$mutasi->last_submitted_at ?? null" :revision-count="$mutasi->revision_count ?? 0" />
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-800">
                                        {{ $mutasi->nomor_surat }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        @php
                                            $firstSiswa = $mutasi->siswa->first();
                                            $siswaCount = $mutasi->siswa->count();
                                        @endphp
                                        <div class="flex flex-col">
                                            @if($firstSiswa)
                                                <span class="font-bold text-slate-900 truncate max-w-[200px]" title="{{ $firstSiswa->nama_sekolah_asal }}">{{ $firstSiswa->nama_sekolah_asal }}</span>
                                                <span class="text-xs font-mono text-slate-500">{{ $firstSiswa->npsn_sekolah_asal ?? '-' }}</span>
                                                @if($firstSiswa->kabupaten_kota_asal)
                                                    <span class="text-xs text-slate-500 mt-0.5 flex items-center">
                                                        <i class="bi bi-geo-alt mr-1 text-slate-400"></i>{{ str_replace('Kabupaten', 'Kab.', $firstSiswa->kabupaten_kota_asal) }}
                                                    </span>
                                                @endif
                                                @if($siswaCount > 1)
                                                     <span class="text-xs italic text-slate-500 mt-1">(+{{ $siswaCount - 1 }} murid lain)</span>
                                                @endif
                                            @else
                                                <span class="text-slate-500 italic">Data Sekolah Asal Tidak Tersedia</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="openStudents('#{{ $mutasi->id_mutasi }}', {{ json_encode($mutasi->siswa->map(fn($s) => [
                                                    'nama' => $s->nama_murid,
                                                    'nisn' => $s->nisn,
                                                    'asal' => $s->nama_sekolah_asal
                                                ])) }})"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-all ring-1 ring-inset ring-indigo-600/10 hover:ring-indigo-600">
                                            <i class="bi bi-people-fill"></i> {{ $mutasi->jumlah_siswa }}
                                        </button>
                                    </td>
                                @endif

                                <td class="px-6 py-4">
                                    <x-status-badge :status="$mutasi->status" />
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Resume Draft Button --}}
                                        @if($mutasi->status === 'Draft')
                                            <a href="{{ route('mutasi.masuk.draft.resume', $mutasi->id_mutasi) }}"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-600"
                                                title="Lanjutkan Pengisian">
                                                <i class="bi bi-pencil-square"></i>
                                                Lanjutkan
                                            </a>
                                            <form action="{{ route('mutasi.masuk.draft.delete', $mutasi->id_mutasi) }}" method="POST" class="inline"
                                                data-confirm="Batalkan dan hapus draft #{{ $mutasi->id_mutasi }} beserta berkasnya secara permanen? Tindakan ini tidak dapat dibatalkan."
                                                data-confirm-title="Batalkan &amp; Hapus Draft" data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50"
                                                    title="Batalkan & Hapus Draft" aria-label="Batalkan & Hapus Draft">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('mutasi.masuk.show', $mutasi->id_mutasi) }}"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                                <span>Detail</span>
                                            </a>

                                            @if($mutasi->status === 'Perlu Perbaikan')
                                                <form action="{{ route('mutasi.masuk.cancel', $mutasi->id_mutasi) }}" method="POST" class="inline"
                                                    data-confirm="Batalkan dan hapus usulan #{{ $mutasi->id_mutasi }} beserta data murid dan seluruh dokumennya secara permanen? Tindakan ini tidak dapat dibatalkan."
                                                    data-confirm-title="Batalkan &amp; Hapus Usulan" data-confirm-type="danger">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50"
                                                        title="Batalkan & Hapus Usulan" aria-label="Batalkan & Hapus Usulan">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if($mutasi->status === 'Selesai')
                                                @if($mutasi->file_arsip_scan)
                                                    <a href="{{ route('dokumen.view', ['path' => $mutasi->file_arsip_scan]) }}"
                                                        target="_blank"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 text-emerald-600 hover:bg-emerald-50"
                                                        title="Lihat Arsip Digital">
                                                        <i class="bi bi-file-earmark-check"></i>
                                                    </a>
                                                @elseif(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin']))
                                                    <div x-data="{ openPrint: false }" class="relative inline-block">
                                                        <button @click="openPrint = !openPrint" @click.outside="openPrint = false"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                            title="Cetak Surat">
                                                            <i class="bi bi-printer"></i>
                                                        </button>
                                                        <div x-show="openPrint" x-transition
                                                            class="absolute right-0 mt-1 w-40 bg-white border border-slate-200 rounded-lg shadow-xl z-10 overflow-hidden">
                                                            <a href="{{ route('mutasi.masuk.print-letter', $mutasi->id_mutasi) }}?mode=tnd" target="_blank"
                                                                class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                                                                <i class="bi bi-pen text-indigo-500"></i> Cetak TND
                                                            </a>
                                                            <a href="{{ route('mutasi.masuk.print-letter', $mutasi->id_mutasi) }}?mode=manual" target="_blank"
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
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <i class="bi bi-inbox text-3xl text-slate-300"></i>
                                        </div>
                                        <h3 class="text-slate-900 font-medium">Belum ada data</h3>
                                        <p class="text-slate-500 text-sm mt-1">Belum ada pengajuan mutasi masuk yang ditemukan.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($mutasiList, 'links'))
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $mutasiList->links() }}
                </div>
            @endif

            {{-- CENTERED MODAL FOR STUDENTS --}}
            <div x-show="showModal" style="display: none;"
                class="fixed inset-0 z-60 flex items-center justify-center px-4"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showModal = false"></div>

                {{-- Modal Content --}}
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col overflow-hidden"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <i class="bi bi-people-fill text-indigo-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900">Daftar Murid Pindah</h3>
                                <p class="text-sm text-slate-500" x-text="'ID: ' + modalTitle"></p>
                            </div>
                        </div>
                        <button @click="showModal = false"
                            class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body (Scrollable) --}}
                    <div class="p-6 overflow-y-auto" style="max-height: 50vh;">
                        <template x-if="siswaList.length === 0">
                            <div class="text-center py-8 text-slate-500">
                                <div
                                    class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-100 flex items-center justify-center">
                                    <i class="bi bi-person-x text-2xl text-slate-400"></i>
                                </div>
                                Tidak ada data murid.
                            </div>
                        </template>

                        <div class="space-y-3">
                            <template x-for="(siswa, index) in siswaList" :key="index">
                                <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:shadow-lg transition-all duration-200"
                                    style="background: linear-gradient(to right, #f8fafc, #ffffff);">
                                    <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                        style="background: linear-gradient(135deg, #6366f1, #a855f7); box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);"
                                        x-text="index + 1"></div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-slate-900 text-base" x-text="siswa.nama"></h4>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono"
                                                style="background: #eef2ff; color: #4338ca; border: 1px solid rgba(99, 102, 241, 0.2);">
                                                <i class="bi bi-upc-scan mr-1.5" style="opacity: 0.7;"></i>
                                                <span x-text="siswa.nisn"></span>
                                            </span>
                                        </div>
                                        <div class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                                            <i class="bi bi-building" style="opacity: 0.7;"></i>
                                            <span class="truncate" x-text="siswa.asal || 'Sekolah tidak terdata'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button @click="showModal = false"
                            class="px-6 py-2.5 bg-slate-900 text-white font-medium rounded-xl hover:bg-slate-800 transition shadow-lg">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
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

        /* Custom Scrollbar Styles */
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #94a3b8, #64748b);
            border-radius: 4px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #64748b, #475569);
        }

        /* Firefox scrollbar */
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterForm');
        const statusFilter = document.getElementById('statusFilter');
        let debounceTimer;

        /* Auto-search when typing 3+ characters */
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

        /* Auto-submit when status filter changes */
        if (statusFilter && filterForm) {
            statusFilter.addEventListener('change', function () {
                filterForm.submit();
            });
        }
    });
    </script>
@endsection
