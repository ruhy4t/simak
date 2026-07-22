@extends('layouts.app')

@section('title', 'Detail Mutasi Keluar')

@section('content')
    <div class="space-y-6 animate-fade-in-up">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('mutasi.keluar.index') }}"
                class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-xs">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-900">Detail Mutasi Keluar</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded-sm text-slate-600 font-mono">#{{ $mutasi->id_mutasi }}</span>
                    <span>•</span>
                    <x-submission-date :original="$mutasi->tanggal_pengajuan" :latest="$mutasi->last_submitted_at ?? null"
                        :revision-count="$mutasi->revision_count ?? 0" compact class="min-w-0" />
                </div>
            </div>

            @php
                $user = auth()->user();
                $isOwner = ($user->role === 'Operator' && $mutasi->pengusul === $user->username)
                    || ($user->role === 'Sekolah' && $mutasi->npsn_sekolah_asal === $user->npsn);
                $canCancel = in_array($user->role, ['Operator', 'Sekolah'])
                    && $isOwner
                    && $mutasi->status === 'Menunggu Verifikasi Dinas';
            @endphp
            @if($canCancel)
                <form action="{{ route('mutasi.keluar.cancel', $mutasi->id_mutasi) }}" method="POST"
                    data-confirm="PERINGATAN: Apakah Anda yakin ingin MENGHAPUS usulan mutasi ini? Tindakan ini akan MENGHAPUS SELURUH data dan dokumen secara PERMANEN dan tidak dapat dikembalikan."
                    data-confirm-title="Hapus Usulan Secara Permanen" data-confirm-type="danger">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                        class="px-4 py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-xl text-sm font-medium transition flex items-center gap-2 shadow-xs">
                        <i class="bi bi-trash"></i> Batalkan & Hapus
                    </button>
                </form>
            @endif

            @php
                $statusMap = [
                    'Menunggu Verifikasi Dinas' => 'bg-amber-100 text-amber-800 border border-amber-200',
                    'Disetujui Dinas' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                    'Proses TTD' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                    'Selesai' => 'bg-sky-100 text-sky-800 border border-sky-200',
                    'Perlu Perbaikan' => 'bg-rose-100 text-rose-800 border border-rose-200',
                    'Dibatalkan' => 'bg-slate-200 text-slate-600 border border-slate-300',
                ];
                $statusClass = $statusMap[$mutasi->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200';
            @endphp
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold {{ $statusClass }}">
                {{ $mutasi->status }}
            </span>
        </div>

        @php
            $docs = [
                ['key' => 'file_suket_keluar', 'label' => 'Surat Keterangan Keluar', 'file' => $mutasi->file_suket_keluar, 'color' => 'blue'],
                ['key' => 'file_suket_dapo', 'label' => 'Surat Dapodik', 'file' => $mutasi->file_suket_dapo, 'color' => 'purple'],
                ['key' => 'file_suket_diterima', 'label' => 'Surat Diterima', 'file' => $mutasi->file_suket_diterima, 'color' => 'emerald'],
                ['key' => 'file_rapor', 'label' => 'Rapor', 'file' => $mutasi->file_rapor, 'color' => 'orange'],
            ];

            $isDinas = in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']);
            $isVerificationMode = $isDinas && $mutasi->status === 'Menunggu Verifikasi Dinas';
        @endphp

        {{-- TOP IMPORTANT NOTIFICATION: REVISION REQUIRED --}}
        @php
            $user = auth()->user();
            // Case-insensitive owner check
            $isOwner = (strtolower($user->role) === 'operator' && (strtolower($user->username) === strtolower($mutasi->pengusul)))
                || (strtolower($user->role) === 'sekolah' && ($user->npsn === $mutasi->npsn_sekolah_asal));

            $canRevise = in_array(strtolower($user->role), ['operator', 'sekolah'])
                && $isOwner
                && in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan']);
        @endphp

        @if($canRevise)
            <div class="bg-linear-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                <div class="flex flex-col md:flex-row items-center gap-6 text-center md:text-left">
                    <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                        <i class="bi bi-exclamation-triangle-fill text-amber-600 text-3xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-amber-900 mb-2">
                            {{ $mutasi->status === 'Dibatalkan' ? 'Usulan Ini Telah Dibatalkan' : 'Data Perlu Diperbaiki' }}
                        </h3>

                        @if($mutasi->catatan_berkas)
                            <div class="bg-white/60 rounded-xl p-4 border border-amber-100 mb-4 inline-block md:block text-left">
                                <p class="font-bold text-amber-900 text-sm mb-1 flex items-center gap-2">
                                    <i class="bi bi-chat-left-text-fill"></i> Catatan dari Dinas:
                                </p>
                                <p class="text-amber-900 whitespace-pre-line text-sm">{{ $mutasi->catatan_berkas }}</p>
                            </div>
                        @else
                                <p class="text-amber-800 mb-4">
                                    {{ $mutasi->status === 'Dibatalkan'
                            ? 'Anda dapat merevisi dan mengirim ulang usulan ini dari awal.'
                            : 'Mohon periksa kembali data atau dokumen Anda sesuai catatan dinas, lalu kirim ulang.' }}
                                </p>
                        @endif

                        <div class="flex justify-center md:justify-start">
                            <a href="{{ route('mutasi.keluar.revision', $mutasi->id_mutasi) }}"
                                class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white text-white! rounded-xl transition font-bold shadow-lg shadow-orange-500/20 hover:-translate-y-1">
                                <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- END NOTIFICATION --}}

        <!-- Full Width School Info -->
        <div class="bg-linear-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5">
            <h4 class="font-bold text-slate-800 mb-3 border-b border-blue-200 pb-2">Informasi Perpindahan Sekolah</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Asal -->
                <div class="relative pl-4 border-l-2 border-blue-400">
                    <span class="absolute left-[-5px] top-0 w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <h5 class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-1">Sekolah Asal</h5>
                    <p class="font-bold text-slate-900 text-sm">{{ $mutasi->nama_sekolah_asal }}</p>
                    <div class="text-xs text-slate-600 mt-1 space-y-0.5">
                        <p>NPSN: {{ $mutasi->npsn_sekolah_asal }}</p>
                        <p>{{ $mutasi->alamat_sekolah_asal }}</p>
                    </div>
                </div>

                <!-- Tujuan -->
                <div class="relative pl-4 border-l-2 border-indigo-400">
                    <span class="absolute left-[-5px] top-0 w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                    <h5 class="text-xs font-bold text-indigo-700 uppercase tracking-wider mb-1">Sekolah Tujuan</h5>
                    <p class="font-bold text-slate-900 text-sm">{{ $mutasi->nama_sekolah_tujuan }}</p>
                    <div class="text-xs text-slate-600 mt-1 space-y-0.5">
                        <p>NPSN: {{ $mutasi->npsn_sekolah_tujuan }}</p>
                        <p>{{ $mutasi->alamat_sekolah_tujuan ?? '-' }}</p>
                        <p>{{ str_replace('Kabupaten', 'Kab.', $mutasi->kabupaten_kota_tujuan) }},
                            {{ $mutasi->provinsi_tujuan }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layout Split untuk Verifikasi Dinas -->
        @if(in_array(auth()->user()->role, ['Operator', 'Sekolah']) && $mutasi->status === 'Selesai' && $mutasi->file_arsip_scan)
            <div x-data="{ showDoc: false }"
                class="bg-white rounded-xl shadow-xs border border-slate-100 p-8 text-center max-w-4xl mx-auto my-12 animate-fade-in-up">
                <div
                    class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 ring-8 ring-green-50">
                    <i class="bi bi-file-earmark-check-fill text-4xl text-green-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Mutasi Selesai</h2>
                <p class="text-slate-600 mb-8 leading-relaxed max-w-2xl mx-auto">
                    Pengajuan mutasi ini telah disetujui dan Surat Keterangan Pindah Masuk telah diterbitkan pada tanggal
                    <span
                        class="font-semibold text-slate-900">{{ \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('j F Y') }}</span>
                    dengan nomor <span class="font-semibold text-slate-900 font-mono">{{ $mutasi->nomor_surat_izin }}</span>
                    dan diunggah pada tanggal <span
                        class="font-semibold text-slate-900">{{ \Carbon\Carbon::parse($mutasi->updated_at)->translatedFormat('j F Y') }}</span>
                    jam <span
                        class="font-semibold text-slate-900">{{ \Carbon\Carbon::parse($mutasi->updated_at)->format('H:i') }}</span>
                </p>

                <div class="flex justify-center mb-0">
                    <button @click="showDoc = !showDoc"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 hover:shadow-lg hover:shadow-green-500/30 transition-all hover:-translate-y-1">
                        <i class="bi" :class="showDoc ? 'bi-chevron-up' : 'bi-eye-fill'"></i>
                        <span x-text="showDoc ? 'Tutup Surat Keterangan' : 'Lihat Surat Keterangan'"></span>
                    </button>
                    <!-- Optional: Direct Download/Print Button if needed, but Viewer is best -->
                </div>

                <div x-show="showDoc" x-transition class="border-t border-slate-100 pt-8 mt-8">
                    <x-document-viewer :path="$mutasi->file_arsip_scan" height="800px" />
                </div>
            </div>
        @endif

        <!-- Main Layout - 2-Column for Dinas, Single-Column for Others -->
        <div class="{{ $isDinas ? 'grid grid-cols-1 lg:grid-cols-2 gap-6' : '' }}"
            x-data="outgoingVerification('{{ $firstDocKey }}')">

            <!-- Kiri: Student Info & Dokumen Viewer -->
            <div class="space-y-4">
                <!-- Student Info Detail -->
                <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-5">
                    <h4 class="font-bold text-slate-800 mb-3 border-b border-slate-200 pb-2">Informasi Murid</h4>
                    <div class="flex items-start gap-4">
                        <div
                            class="w-14 h-14 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0 text-xl">
                            {{ substr($mutasi->nama_murid ?? '-', 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0 space-y-2">
                            <div>
                                <h3 class="font-bold text-slate-900 leading-tight">{{ $mutasi->nama_murid }}</h3>
                                <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $mutasi->nisn }}</p>
                            </div>
                            <div class="grid grid-cols-1 gap-y-2 text-sm">
                                <div class="flex items-center gap-2 text-slate-700">
                                    <i class="bi bi-gender-ambiguous text-blue-500 w-4"></i>
                                    <span>{{ $mutasi->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-700">
                                    <i class="bi bi-cake text-rose-500 w-4"></i>
                                    <span>{{ $mutasi->tempat_lahir }},
                                        {{ \Carbon\Carbon::parse($mutasi->tanggal_lahir)->translatedFormat('j F Y') }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-700">
                                    <i class="bi bi-mortarboard text-indigo-500 w-4"></i>
                                    <span>Kelas {{ $mutasi->kelas }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Tabs + Inline Viewer -->
                <div class="bg-white rounded-xl shadow-xs border overflow-hidden">
                    <!-- Tabs: Klik = load & show, klik lagi = hide -->
                    <div class="flex flex-wrap gap-2 p-4 bg-slate-50 border-b border-slate-200">
                        @foreach($docs as $doc)
                            @if($doc['file'])
                                <button type="button"
                                    @click="toggleDocument('{{ $doc['key'] }}')"
                                    :class="activeDoc === '{{ $doc['key'] }}' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-2">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                    {{ $doc['label'] }}
                                </button>
                            @endif
                        @endforeach
                    </div>

                    <!-- Inline Viewer: Lazy-load saat pertama klik -->
                    @foreach($docs as $doc)
                        @if($doc['file'])
                            <template x-if="loadedDocs['{{ $doc['key'] }}']">
                                <div x-show="activeDoc === '{{ $doc['key'] }}'" x-cloak>
                                    <x-document-viewer :path="$doc['file']" />
                                </div>
                            </template>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Kanan: Panel Aksi (Sticky) - ONLY for Dinas -->
            @if($isDinas)
                <div class="space-y-4">
                    <!-- Verifikasi Berkas (Only for Dinas + Menunggu Verifikasi) -->
                    @if($isVerificationMode)
                        <div class="bg-white rounded-xl shadow-xs border p-6 sticky top-4">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <i class="bi bi-clipboard-check text-blue-600"></i>
                                Verifikasi Berkas
                            </h2>
                            <form action="{{ route('mutasi.keluar.verify', $mutasi->id_mutasi) }}" method="POST" class="space-y-4">
                                @csrf
                                <div class="border rounded-lg p-3 space-y-2">
                                    @foreach($docs as $doc)
                                        @if($doc['file'])
                                            <label
                                                class="flex items-center p-3 rounded-lg border border-transparent hover:bg-slate-50 hover:border-slate-200 cursor-pointer transition select-none"
                                                x-data="{ checked: true }">
                                                <input type="checkbox" name="{{ $doc['key'] }}_ok" value="1"
                                                    class="rounded-sm text-blue-600 focus:ring-blue-500 mr-3 w-5 h-5" x-model="checked"
                                                    @change="updateNote(checked, '{{ $doc['label'] }}')">
                                                <span class="text-sm text-slate-700 font-medium flex-1">{{ $doc['label'] }}</span>
                                                <span class="text-xs font-bold px-2 py-1 rounded-sm"
                                                    :class="checked ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                                    x-text="checked ? 'Sesuai' : 'Tidak Sesuai'">
                                                </span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                                    <textarea name="catatan_berkas" rows="4" x-model="catatan"
                                        class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm"
                                        placeholder="Tambahkan catatan..."></textarea>
                                </div>

                                <div class="flex flex-col gap-2 pt-2">
                                    <button type="submit" name="action" value="approve"
                                        class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition shadow-xs">
                                        <i class="bi bi-check-lg mr-2"></i> Setujui Berkas
                                    </button>
                                    <button type="submit" name="action" value="revision"
                                        class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium transition shadow-xs">
                                        <i class="bi bi-x-lg mr-2"></i> Minta Perbaikan
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <!-- Status Info Panel (For other statuses) -->
                    @if(!$isVerificationMode)
                        <div class="bg-white rounded-xl shadow-xs border p-6 sticky top-4">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <i class="bi bi-info-circle text-blue-600"></i>
                                Status Pengajuan
                            </h2>

                            <!-- Status Badge Large -->
                            <div class="text-center p-4 rounded-xl mb-4 {{ $statusClass }}">
                                <span class="text-lg font-bold">{{ $mutasi->status }}</span>
                            </div>

                            <!-- Catatan Berkas -->
                            @if($mutasi->catatan_berkas)
                                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                                    <h4 class="font-semibold text-amber-800 mb-2 flex items-center gap-2">
                                        <i class="bi bi-exclamation-triangle"></i> Catatan dari Dinas
                                    </h4>
                                    <p class="text-amber-900 text-sm whitespace-pre-line">{{ $mutasi->catatan_berkas }}</p>
                                </div>
                            @endif

                            <!-- Dinas Actions -->
                            @if($isDinas)
                                <!-- Issue Letter -->
                                @if($mutasi->status === 'Disetujui Dinas')
                                    <form action="{{ route('mutasi.keluar.issue-letter', $mutasi->id_mutasi) }}" method="POST"
                                        class="space-y-3">
                                        @csrf
                                        <label class="block text-sm font-bold text-slate-700">Siapkan Surat Izin</label>
                                        <input type="text" name="nomor_surat_izin" placeholder="Nomor Surat..."
                                            value="{{ old('nomor_surat_izin', $mutasi->nomor_surat_izin ?? '') }}"
                                            class="w-full rounded-lg border-slate-300">
                                        <input type="date" name="tanggal_surat_izin" value="{{ date('Y-m-d') }}"
                                            class="w-full rounded-lg border-slate-300" required>
                                        <button class="w-full py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700">Simpan &
                                            Siapkan Cetak</button>
                                    </form>
                                @endif

                                <!-- Print Letter -->
                                @if(in_array($mutasi->status, ['Proses TTD', 'Selesai']))
                                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4">
                                        <p class="font-bold text-emerald-900">Surat Izin Siap Cetak</p>
                                        <p class="text-sm text-emerald-700 font-mono mb-3">{{ $mutasi->nomor_surat_izin ?: 'Nomor diisi setelah TTD' }}</p>
                                        @if($mutasi->file_arsip_scan)
                                            <a href="{{ route('dokumen.view', ['path' => $mutasi->file_arsip_scan]) }}" target="_blank"
                                                class="w-full py-2 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 flex items-center justify-center gap-2">
                                                <i class="bi bi-file-earmark-check"></i> Lihat Arsip Surat
                                            </a>
                                        @else
                                            <div x-data="{ openPrint: false }" class="relative">
                                                <button @click="openPrint = !openPrint" @click.outside="openPrint = false"
                                                    class="w-full py-2 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 flex items-center justify-center gap-2">
                                                    <i class="bi bi-printer"></i> Cetak Surat <i class="bi bi-chevron-down text-xs"></i>
                                                </button>
                                                <div x-show="openPrint" x-transition
                                                    class="absolute right-0 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-xl z-10 overflow-hidden">
                                                    <a href="{{ route('mutasi.keluar.print-letter', $mutasi->id_mutasi) }}?mode=manual"
                                                        target="_blank"
                                                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                                                        <i class="bi bi-printer text-emerald-500"></i> Cetak
                                                    </a>
                                                    <a href="{{ route('mutasi.keluar.print-letter', $mutasi->id_mutasi) }}?format=word"
                                                        target="_blank"
                                                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition border-t border-slate-100">
                                                        <i class="bi bi-file-earmark-word text-blue-500"></i> Unduh Word
                                                    </a>
                                                    <a href="{{ route('mutasi.keluar.word-srikandi', $mutasi->id_mutasi) }}"
                                                        target="_blank"
                                                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition border-t border-slate-100">
                                                        <i class="bi bi-file-earmark-word text-indigo-500"></i> Unduh Word SRIKANDI
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Finish TTD -->
                                @if($mutasi->status === 'Proses TTD')
                                    <form action="{{ route('mutasi.keluar.complete-letter', $mutasi->id_mutasi) }}" method="POST"
                                        class="space-y-3">
                                        @csrf
                                        <div>
                                            <label class="block text-sm font-medium text-indigo-900 mb-1">Nomor Surat</label>
                                            <input type="text" name="nomor_surat_izin"
                                                value="{{ old('nomor_surat_izin', $mutasi->nomor_surat_izin ?? '') }}"
                                                class="w-full rounded-lg border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500"
                                                required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-indigo-900 mb-1">Tanggal Selesai TTD</label>
                                            <input type="date" name="tanggal_selesai" value="{{ date('Y-m-d') }}"
                                                class="w-full rounded-lg border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500" required>
                                        </div>
                                        <button
                                            class="w-full py-3 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 flex items-center justify-center gap-2">
                                            <i class="bi bi-check-circle"></i> Selesai Tanda Tangan
                                        </button>
                                    </form>
                                @endif

                                <!-- Arsip Digital Upload (Dinas Only, Status Selesai) -->
                                @if($mutasi->status === 'Selesai')
                                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mt-4">
                                        <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                                            <i class="bi bi-archive text-gray-600"></i> Arsip Digital
                                        </h4>

                                        @if($mutasi->file_arsip_scan)
                                            <div
                                                class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i class="bi bi-file-earmark-pdf text-red-500 text-xl"></i>
                                                    <div>
                                                        <p class="font-medium text-green-900 text-sm">Arsip Tersedia</p>
                                                        <p class="text-xs text-green-700">
                                                            {{ \Carbon\Carbon::parse($mutasi->updated_at)->translatedFormat('j F Y H:i') }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <a href="{{ route('dokumen.view', ['path' => $mutasi->file_arsip_scan]) }}" target="_blank"
                                                    class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium transition">
                                                    <i class="bi bi-eye mr-1"></i> Lihat
                                                </a>
                                            </div>
                                            <p class="text-xs text-gray-500 mb-2">Upload ulang untuk mengganti:</p>
                                        @else
                                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-3">
                                                <p class="text-xs text-yellow-800">
                                                    <i class="bi bi-exclamation-circle mr-1"></i>
                                                    Belum ada arsip digital. Silakan upload scan surat yang telah ditandatangani.
                                                </p>
                                            </div>
                                        @endif

                                        <form action="{{ route('mutasi.keluar.upload-arsip', $mutasi->id_mutasi) }}" method="POST"
                                            enctype="multipart/form-data" class="flex items-end gap-2">
                                            @csrf
                                            <div class="flex-1">
                                                <input type="file" name="file_arsip_scan" required accept=".pdf"
                                                    class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300">
                                            </div>
                                            <button type="submit"
                                                class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-xs font-bold transition">
                                                <i class="bi bi-upload mr-1"></i> Upload
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            @endif {{-- End @if($isDinas) --}}
        </div> {{-- Close grid --}}
    </div> {{-- Close main space-y container --}}
@endsection
