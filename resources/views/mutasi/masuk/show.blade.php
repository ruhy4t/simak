@extends('layouts.app')

@section('title', 'Detail Mutasi Masuk')

@section('content')
    <div class="space-y-8 animate-fade-in-up" x-data="{ activeTab: 0, activeDoc: null }">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('mutasi.masuk.index') }}"
                class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-sm">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-900">Detail Mutasi Masuk</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-600 font-mono">#{{ $mutasi->id_mutasi }}</span>
                    <span>•</span>
                    <span>{{ $mutasi->jumlah_siswa }} Siswa</span>
                </div>
            </div>

            @php
                $user = auth()->user();
                $isOwner = ($user->role === 'Operator' && $mutasi->pengusul === $user->username)
                    || ($user->role === 'Sekolah' && $mutasi->npsn_sekolah_tujuan === $user->npsn);
                $canCancel = in_array($user->role, ['Operator', 'Sekolah'])
                    && $isOwner
                    && in_array($mutasi->status, ['Menunggu Verifikasi Dinas', 'Perlu Perbaikan']);
            @endphp
            @if($canCancel)
                <form action="{{ route('mutasi.masuk.cancel', $mutasi->id_mutasi) }}" method="POST"
                    data-confirm="PERINGATAN: Apakah Anda yakin ingin MENGHAPUS usulan mutasi ini? Tindakan ini akan MENGHAPUS SELURUH data siswa dan dokumen secara PERMANEN dan tidak dapat dikembalikan."
                    data-confirm-title="Hapus Usulan Secara Permanen" data-confirm-type="danger">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                        class="px-4 py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-xl text-sm font-medium transition flex items-center gap-2 shadow-sm">
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

        <!-- Info Surat -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Tanggal Pengajuan</p>
                    <p class="font-semibold text-slate-900 text-lg">
                        {{ \Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('j F Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Nomor Surat Pengantar</p>
                    <p class="font-medium text-slate-900">{{ $mutasi->nomor_surat }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Pengusul</p>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                            <i class="bi bi-person-fill text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 leading-tight">
                                {{ \App\Models\User::where('username', $mutasi->pengusul)->value('nama_pengguna') ?? $mutasi->pengusul }}
                            </p>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">ID: {{ $mutasi->pengusul }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TOP IMPORTANT NOTIFICATION: REVISION REQUIRED (Operator/Sekolah Only) --}}
            @php
                $user = auth()->user();
                // Case-insensitive owner check for Mutasi Masuk
                $isOwnerMasuk = (strtolower($user->role) === 'operator' && strtolower($user->username) === strtolower($mutasi->pengusul))
                    || (strtolower($user->role) === 'sekolah' && $user->npsn === $mutasi->npsn_sekolah_tujuan);

                $canReviseMasuk = in_array(strtolower($user->role), ['operator', 'sekolah'])
                    && $isOwnerMasuk
                    && in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan']);
            @endphp

            @if($canReviseMasuk)
                <div
                    class="bg-gradient-to-r from-amber-50 via-orange-50 to-red-50 border-2 border-amber-300 rounded-2xl p-6 sm:p-8 shadow-lg">
                    <div class="flex flex-col lg:flex-row items-center gap-6">
                        <div
                            class="w-20 h-20 rounded-full bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center shrink-0 ring-4 ring-amber-200/50">
                            <i class="bi bi-exclamation-triangle-fill text-amber-600 text-4xl"></i>
                        </div>
                        <div class="flex-1 text-center lg:text-left">
                            <h3 class="text-2xl font-bold text-amber-900 mb-2">
                                {{ $mutasi->status === 'Dibatalkan' ? 'Usulan Ini Telah Dibatalkan' : 'Data Perlu Diperbaiki' }}
                            </h3>

                            @if($mutasi->catatan_berkas ?? null)
                                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-4 border border-amber-200 mb-5 text-left">
                                    <p class="font-bold text-amber-900 text-sm mb-1 flex items-center gap-2">
                                        <i class="bi bi-chat-left-text-fill text-amber-600"></i> Catatan dari Dinas:
                                    </p>
                                    <p class="text-amber-800 whitespace-pre-line text-sm leading-relaxed">
                                        {{ $mutasi->catatan_berkas }}
                                    </p>
                                </div>
                            @else
                                        <p class="text-amber-800 mb-5 text-lg">
                                            {{ $mutasi->status === 'Dibatalkan'
                                ? 'Anda dapat merevisi dan mengirim ulang usulan ini dari awal.'
                                : 'Mohon periksa kembali data atau dokumen Anda sesuai catatan dinas, lalu kirim ulang.' }}
                                        </p>
                            @endif

                            <div class="flex justify-center lg:justify-start">
                                <a href="{{ route('mutasi.masuk.revision', $mutasi->id_mutasi) }}"
                                    class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl transition-all font-bold shadow-xl shadow-amber-500/30 hover:-translate-y-1 text-lg">
                                    <i class="bi bi-pencil-square mr-3 text-xl"></i> Perbaiki & Kirim Ulang Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            {{-- END NOTIFICATION --}}

            <!-- Layout Split untuk Verifikasi Dinas -->
            @if(in_array(auth()->user()->role, ['Operator', 'Sekolah']) && $mutasi->status === 'Selesai' && $mutasi->file_arsip_scan)
                <div x-data="{ showDoc: false }"
                    class="bg-white rounded-xl shadow-sm border border-slate-100 p-8 text-center max-w-4xl mx-auto my-12 animate-fade-in-up">
                    <div
                        class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 ring-8 ring-green-50">
                        <i class="bi bi-file-earmark-check-fill text-4xl text-green-600"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900 mb-2">Mutasi Selesai</h2>
                    <p class="text-slate-600 mb-8 leading-relaxed max-w-2xl mx-auto">
                        Pengajuan mutasi ini telah disetujui dan Surat Izin Mutasi Masuk telah diterbitkan pada tanggal
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
                            <span x-text="showDoc ? 'Tutup Surat Izin' : 'Lihat Surat Izin Mutasi Masuk'"></span>
                        </button>
                    </div>

                    <div x-show="showDoc" x-transition class="border-t border-slate-100 pt-8 mt-8">
                        <x-document-viewer :path="$mutasi->file_arsip_scan" height="800px" />
                    </div>
                </div>
            @elseif(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Menunggu Verifikasi Dinas')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="verifikasiMasuk()">
                    <!-- Kiri: Data Siswa & Preview Dokumen -->
                    <div class="space-y-4">
                        {{-- Sekolah Tujuan Info Card --}}
                        <div class="bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 rounded-xl p-4">
                            <h4 class="text-xs font-semibold text-emerald-800 mb-2 flex items-center gap-1">
                                <i class="bi bi-geo-alt-fill text-emerald-600"></i> Sekolah Tujuan (Penerima Mutasi)
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-emerald-700/70 shrink-0">Nama</span>
                                    <span
                                        class="font-semibold text-emerald-900 text-right">{{ $mutasi->nama_sekolah_tujuan }}</span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <span class="text-emerald-700/70">NPSN</span>
                                    <span
                                        class="font-mono text-emerald-900 bg-white/50 px-1.5 py-0.5 rounded border border-emerald-200">{{ $mutasi->npsn_sekolah_tujuan }}</span>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-emerald-700/70 shrink-0">Alamat</span>
                                    <span
                                        class="text-emerald-900 text-right leading-snug">{{ $mutasi->alamat_sekolah_tujuan ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Surat Permohonan Card - Prominent for Verifier --}}
                        @if($mutasi->file_surat_permohonan)
                            <div class="bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 rounded-xl p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 bg-sky-600 rounded-lg flex items-center justify-center shadow-md shadow-sky-200">
                                            <i class="bi bi-file-earmark-text-fill text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-sky-900 text-sm">Surat Permohonan Izin Mutasi</h4>
                                            <p class="text-xs text-sky-700">Diunggah oleh Pengusul</p>
                                        </div>
                                    </div>
                                    <button type="button"
                                        @click="loadedDocs['surat_permohonan'] = true; activeDoc = activeDoc === 'surat_permohonan' ? null : 'surat_permohonan'"
                                        :style="activeDoc === 'surat_permohonan' ? 'background-color: #0284c7 !important; color: white !important;' : ''"
                                        :class="activeDoc === 'surat_permohonan' ? '!bg-sky-600 !text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-700 hover:bg-sky-100 border border-sky-300'"
                                        class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                        <i class="bi bi-eye"></i> Lihat Surat
                                    </button>
                                </div>
                                {{-- Preview Surat Permohonan: Lazy-load saat klik --}}
                                <template x-if="loadedDocs['surat_permohonan']">
                                    <div x-show="activeDoc === 'surat_permohonan'" x-transition class="mt-4">
                                        <x-document-viewer :path="$mutasi->file_surat_permohonan" />
                                    </div>
                                </template>
                            </div>
                        @endif

                        <!-- Tab Siswa -->
                        <div class="bg-white rounded-xl shadow-sm border p-4">
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($siswaList as $idx => $siswa)
                                    <button type="button" @click="activeTab = {{ $idx }}; activeDoc = null"
                                        :class="activeTab === {{ $idx }} ? 'bg-primary-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition">
                                        Siswa {{ $idx + 1 }}
                                    </button>
                                @endforeach
                            </div>

                            @foreach($siswaList as $idx => $siswa)
                                @php
                                    $birthDate = \Carbon\Carbon::parse($siswa->tanggal_lahir);
                                    $age = $birthDate->diff(now());
                                @endphp
                                <div x-show="activeTab === {{ $idx }}" x-transition class="space-y-3">
                                    {{-- Header Siswa --}}
                                    <div class="flex items-start gap-3 bg-gradient-to-r from-blue-50 to-slate-50 rounded-lg p-3">
                                        <div
                                            class="w-11 h-11 bg-blue-600 rounded-lg flex items-center justify-center shadow-md shadow-blue-200 shrink-0">
                                            <i class="bi bi-person-fill text-white text-lg"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-gray-900 truncate">{{ $siswa->nama_murid }}</h4>
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5 text-xs">
                                                <span
                                                    class="inline-flex items-center gap-1 text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-mono">
                                                    <i class="bi bi-hash"></i>{{ $siswa->nisn }}
                                                </span>
                                                <span class="text-gray-400">•</span>
                                                <span class="text-gray-600">{{ $siswa->kelas }}</span>
                                                <span class="text-gray-400">•</span>
                                                <span
                                                    class="text-gray-600">{{ $siswa->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Info Grid Compact --}}
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="bg-white border border-slate-200 rounded-lg p-2">
                                            <p class="text-gray-500 uppercase tracking-wider mb-0.5" style="font-size: 10px;">TTL
                                            </p>
                                            <p class="font-medium text-gray-900">{{ $siswa->tempat_lahir }},
                                                {{ $birthDate->translatedFormat('j F Y') }}
                                            </p>
                                        </div>
                                        <div class="bg-white border border-slate-200 rounded-lg p-2">
                                            <p class="text-gray-500 uppercase tracking-wider mb-0.5" style="font-size: 10px;">Usia
                                            </p>
                                            <p class="font-medium text-gray-900">{{ $age->y }}th {{ $age->m }}bln {{ $age->d }}hr
                                            </p>
                                        </div>
                                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-2 col-span-2">
                                            <p class="text-emerald-700 uppercase tracking-wider mb-0.5" style="font-size: 10px;">
                                                Tanggal Diterima di Sekolah Tujuan
                                            </p>
                                            <p class="font-bold text-emerald-900">
                                                <i class="bi bi-calendar-check mr-1"></i>
                                                {{ \Carbon\Carbon::parse($siswa->tanggal_diterima)->translatedFormat('j F Y') }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Sekolah Asal Compact --}}
                                    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-3">
                                        <h5 class="text-xs font-semibold text-amber-800 mb-2 flex items-center gap-1">
                                            <i class="bi bi-building text-amber-600"></i> Sekolah Asal
                                        </h5>
                                        <div class="space-y-1.5 text-xs">
                                            <div class="flex justify-between items-start gap-2">
                                                <span class="text-amber-700/70 shrink-0">Nama</span>
                                                <span
                                                    class="font-semibold text-amber-900 text-right">{{ $siswa->nama_sekolah_asal }}</span>
                                            </div>
                                            <div class="flex justify-between items-center gap-2">
                                                <span class="text-amber-700/70">NPSN</span>
                                                <span
                                                    class="font-mono text-amber-900 bg-white/50 px-1.5 py-0.5 rounded border border-amber-200">{{ $siswa->npsn_sekolah_asal ?? '-' }}</span>
                                            </div>
                                            <div class="flex justify-between items-start gap-2">
                                                <span class="text-amber-700/70 shrink-0">Lokasi</span>
                                                <span
                                                    class="text-amber-900 text-right">{{ str_replace('Kabupaten', 'Kab.', $siswa->kabupaten_kota_asal) }},
                                                    {{ $siswa->provinsi_asal }}</span>
                                            </div>
                                            <div class="flex justify-between items-start gap-2">
                                                <span class="text-amber-700/70 shrink-0">Alamat</span>
                                                <span
                                                    class="text-amber-900 text-right leading-snug">{{ Str::limit($siswa->alamat_sekolah_asal, 60) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dokumen Siswa -->
                                    <div class="space-y-2">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Dokumen Siswa</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            @php
                                                $docs = [
                                                    ['key' => "siswa_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah, 'color' => 'blue'],
                                                    ['key' => "siswa_{$idx}_dapodik", 'label' => 'Dapodik', 'file' => $siswa->file_surat_dapodik, 'color' => 'blue'],
                                                    ['key' => "siswa_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi, 'color' => 'blue'],
                                                    ['key' => "siswa_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd, 'color' => 'blue'],
                                                    ['key' => "siswa_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor, 'color' => 'blue'],
                                                    ['key' => "siswa_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik, 'color' => 'blue'],
                                                ];
                                            @endphp
                                            @foreach($docs as $doc)
                                                @if($doc['file'])
                                                    <button type="button"
                                                        @click="loadedDocs['{{ $doc['key'] }}'] = true; activeDoc = activeDoc === '{{ $doc['key'] }}' ? null : '{{ $doc['key'] }}'"
                                                        :class="activeDoc === '{{ $doc['key'] }}' ? 'bg-blue-600 text-white ring-2 ring-offset-1 ring-blue-500' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                                                        class="px-3 py-2 rounded-lg text-xs font-medium transition flex items-center justify-center gap-2 border border-transparent">
                                                        <i class="bi bi-file-earmark-pdf"></i> {{ $doc['label'] }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Preview Dokumen: Lazy-load saat klik -->
                                    @foreach($docs as $doc)
                                        @if($doc['file'])
                                            <template x-if="loadedDocs['{{ $doc['key'] }}']">
                                                <div x-show="activeDoc === '{{ $doc['key'] }}'" x-transition class="mt-4">
                                                    <x-document-viewer :path="$doc['file']" />
                                                </div>
                                            </template>
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Kanan: Form Verifikasi -->
                    <div class="bg-white rounded-xl shadow-sm border p-6 h-fit sticky top-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="bi bi-clipboard-check text-primary-600 mr-2"></i>
                            Verifikasi Berkas
                        </h2>

                        <form action="{{ route('mutasi.masuk.verify', $mutasi->id_mutasi) }}" method="POST" class="space-y-4">
                            @csrf

                            {{-- Surat Permohonan Checklist --}}
                            <div class="border border-sky-200 bg-sky-50/50 rounded-lg p-3">
                                <h4 class="font-medium text-sky-900 mb-2 text-sm flex items-center gap-1">
                                    <i class="bi bi-file-earmark-text text-sky-600"></i>
                                    Dokumen Permohonan
                                </h4>
                                <label class="flex items-center p-2 rounded hover:bg-sky-100 cursor-pointer">
                                    <input type="checkbox" name="verifikasi_surat_permohonan" value="1"
                                        class="rounded text-primary-600 focus:ring-primary-500 mr-3" {{ $mutasi->file_surat_permohonan ? 'checked' : 'disabled' }}
                                        @change="updateNote($el.checked, 'Surat Permohonan Izin Mutasi')">
                                    <span class="text-sm text-gray-700">Surat Permohonan Izin Mutasi</span>
                                    @if(!$mutasi->file_surat_permohonan)
                                        <span class="ml-auto text-xs text-red-500 italic">Tidak ada file</span>
                                    @endif
                                </label>
                            </div>

                            <!-- Checklist per Siswa -->
                            @foreach($siswaList as $idx => $siswa)
                                <div class="border rounded-lg p-3" x-show="activeTab === {{ $idx }}" x-transition>
                                    <h4 class="font-medium text-gray-900 mb-2 text-sm">
                                        <i class="bi bi-person text-blue-600 mr-1"></i>
                                        {{ $siswa->nama_murid }}
                                    </h4>
                                    @php
                                        $siswaDocList = [
                                            ['key' => "siswa_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah],
                                            ['key' => "siswa_{$idx}_dapodik", 'label' => 'Surat Dapodik', 'file' => $siswa->file_surat_dapodik],
                                            ['key' => "siswa_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi],
                                            ['key' => "siswa_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd],
                                            ['key' => "siswa_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor],
                                            ['key' => "siswa_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik],
                                        ];
                                    @endphp
                                    <div class="grid grid-cols-1 gap-2">
                                        @foreach($siswaDocList as $doc)
                                            <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" name="verifikasi[{{ $siswa->id }}][{{ $doc['key'] }}]" value="1"
                                                    class="rounded text-primary-600 focus:ring-primary-500 mr-3" {{ $doc['file'] ? 'checked' : 'disabled' }}
                                                    @change="updateNote($el.checked, '{{ $doc['label'] }} ({{ $siswa->nama_murid }})')">
                                                <span class="text-sm text-gray-700">{{ $doc['label'] }}</span>
                                                @if(!$doc['file'])
                                                    <span class="ml-auto text-xs text-red-500 italic">Tidak ada file</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                                <textarea name="catatan_berkas" rows="3" x-model="catatan"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                    placeholder="Tambahkan catatan jika perlu perbaikan..."></textarea>
                            </div>

                            <div class="flex flex-col gap-2 pt-2">
                                <button type="submit" name="action" value="approve"
                                    class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition shadow-sm">
                                    <i class="bi bi-check-lg mr-2"></i> Setujui Berkas
                                </button>
                                <button type="submit" name="action" value="revision"
                                    class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium transition shadow-sm">
                                    <i class="bi bi-x-lg mr-2"></i> Minta Perbaikan
                                </button>
                            </div>
                        </form>
                        <script>
                            function verifikasiMasuk() {
                                return {
                                    activeTab: 0,
                                    activeDoc: null,
                                    loadedDocs: {},  // Lazy-load: dokumen hanya di-load setelah klik
                                    catatan: '',
                                    init() {
                                        // Auto-generate catatan untuk dokumen yang tidak ada (checkbox disabled)
                                        this.$nextTick(() => {
                                            this.generateInitialNotes();
                                        });
                                    },
                                    generateInitialNotes() {
                                        const missingDocs = [];
                                        // Scan for disabled checkboxes (no file uploaded)
                                        document.querySelectorAll('input[type="checkbox"][disabled]').forEach(cb => {
                                            const labelEl = cb.closest('label');
                                            if (labelEl) {
                                                const labelText = labelEl.querySelector('span.text-sm');
                                                if (labelText) {
                                                    missingDocs.push(`- ${labelText.textContent.trim()} tidak diunggah`);
                                                }
                                            }
                                        });
                                        if (missingDocs.length > 0) {
                                            this.catatan = missingDocs.join('\n');
                                        }
                                    },
                                    updateNote(checked, label) {
                                        // Default message to add
                                        const errorSuffix = "tidak sesuai,";
                                        const defaultMsg = `- ${label} ${errorSuffix}`;

                                        if (!checked) {
                                            // Unchecked: Add error note
                                            // We append default message. User can edit it later.
                                            if (!this.catatan.includes(defaultMsg)) {
                                                this.catatan = this.catatan ? this.catatan + '\n' + defaultMsg : defaultMsg;
                                            }
                                        } else {
                                            // Checked: Remove ANY line related to this label (even if edited)
                                            // We look for lines starting with "- {Label}"

                                            const escapedLabel = label.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                                            // Regex: Start of line, hyphen, space, label, any char until end of line
                                            const regex = new RegExp(`^-\\s+${escapedLabel}.*$`, 'gm');

                                            this.catatan = this.catatan.replace(regex, '');

                                            // Clean up empty lines
                                            this.catatan = this.catatan.replace(/^\s*[\r\n]/gm, '').trim();
                                        }
                                    }
                                }
                            }
                        </script>
                    </div>
                </div>
            @else
                <!-- Normal View -->
                <div class="space-y-4">
                    {{-- Surat Permohonan Card (Always Visible if Exists) --}}
                    @if($mutasi->file_surat_permohonan)
                        <div
                            class="bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 rounded-xl p-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-sky-600 rounded-lg flex items-center justify-center shadow-md shadow-sky-200">
                                    <i class="bi bi-file-earmark-text-fill text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sky-900 text-sm">Surat Permohonan Izin Mutasi</h4>
                                    <p class="text-xs text-sky-700">Dokumen Utama Pengajuan</p>
                                </div>
                            </div>
                            <button type="button"
                                @click="activeDoc = activeDoc === 'surat_permohonan_normal' ? null : 'surat_permohonan_normal'"
                                :style="activeDoc === 'surat_permohonan_normal' ? 'background-color: #0284c7 !important; color: white !important;' : ''"
                                :class="activeDoc === 'surat_permohonan_normal' ? '!bg-sky-600 !text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-700 hover:bg-sky-100 border border-sky-300'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                <i class="bi bi-eye"></i> Lihat Surat
                            </button>
                        </div>
                        <div x-show="activeDoc === 'surat_permohonan_normal'" x-transition class="mt-4">
                            <x-document-viewer :path="$mutasi->file_surat_permohonan" height="calc(100vh - 280px)"
                                minHeight="600px" />
                        </div>
                    @endif

                    <!-- Data Siswa dengan Tab -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6">
                        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="bi bi-people-fill text-blue-600 text-xl"></i>
                            </div>
                            Data Siswa
                        </h2>

                        @if(count($siswaList) > 1)
                            <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
                                @foreach($siswaList as $idx => $siswa)
                                    <button type="button" @click="activeTab = {{ $idx }}"
                                        :class="activeTab === {{ $idx }} ? 'bg-primary-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                        class="px-4 py-2 rounded-lg font-medium transition text-sm">
                                        Siswa {{ $idx + 1 }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Alert Perlu Perbaikan --}}
                        @if($mutasi->status === 'Perlu Perbaikan')
                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 mb-6">
                                <i class="bi bi-exclamation-triangle-fill text-amber-500 text-xl flex-shrink-0"></i>
                                <div>
                                    <h4 class="font-bold text-amber-800 text-sm">Perlu Perbaikan</h4>
                                    <p class="text-sm text-amber-700 mt-1">Mohon perbaiki data atau dokumen sesuai catatan dari
                                        verifikator. Cek catatan pada setiap siswa atau dokumen.</p>
                                    @if($mutasi->catatan_berkas)
                                        <div
                                            class="mt-2 bg-white/60 p-2 rounded text-xs font-mono text-amber-900 border border-amber-100">
                                            {{ $mutasi->catatan_berkas }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @foreach($siswaList as $idx => $siswa)
                            @php
                                $birthDate = \Carbon\Carbon::parse($siswa->tanggal_lahir);
                                $age = $birthDate->diff(now());
                            @endphp
                            <div x-show="activeTab === {{ $idx }}" x-transition class="space-y-6">

                                {{-- Card Data Siswa (High Clarity) --}}
                                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                                    {{-- Header: Identitas --}}
                                    <div class="p-6 bg-slate-50 border-b border-slate-200 flex items-start gap-5">
                                        <div
                                            class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg text-white shrink-0">
                                            <i class="bi bi-person-fill text-3xl"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-bold text-xl text-slate-900">{{ $siswa->nama_murid }}</h3>
                                            <div class="flex flex-wrap items-center gap-4 mt-2">
                                                <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                    <i class="bi bi-gender-ambiguous text-blue-600"></i>
                                                    {{ $siswa->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}
                                                </span>
                                                <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                    <i class="bi bi-mortarboard-fill text-blue-600"></i>
                                                    Kelas {{ $siswa->kelas }}
                                                </span>
                                                <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                    <i class="bi bi-upc-scan text-blue-600"></i>
                                                    NISN: <span class="font-bold font-mono">{{ $siswa->nisn }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Body: Detail Data --}}
                                    {{-- Body: Detail Data --}}
                                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                                        {{-- Kolom Kiri: Biodata Vital --}}
                                        <div class="space-y-6">
                                            <h4 class="font-bold text-slate-900 border-b border-slate-200 pb-2 mb-4">Biodata Siswa
                                            </h4>

                                            <div class="grid grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-sm font-semibold text-slate-500 mb-1">Tempat, Tanggal
                                                        Lahir</label>
                                                    <div class="text-base font-medium text-slate-900">
                                                        {{ $siswa->tempat_lahir }}<br>{{ $birthDate->translatedFormat('j F Y') }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-semibold text-slate-500 mb-1">Usia Saat
                                                        Ini</label>
                                                    <div class="text-base font-medium text-slate-900">
                                                        {{ $age->y }} Tahun {{ $age->m }} Bulan
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-slate-500 mb-1">Tanggal
                                                    Diterima</label>
                                                <div
                                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-lg text-base font-bold">
                                                    <i class="bi bi-calendar-check text-emerald-600"></i>
                                                    {{ \Carbon\Carbon::parse($siswa->tanggal_diterima)->translatedFormat('j F Y') }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Kolom Kanan: Sekolah Asal --}}
                                        <div class="space-y-6">
                                            <h4 class="font-bold text-slate-900 border-b border-slate-200 pb-2 mb-4">Asal Sekolah
                                            </h4>

                                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                                                <div class="mb-4">
                                                    <label class="block text-sm font-semibold text-amber-800 mb-1">Nama
                                                        Sekolah</label>
                                                    <div class="text-lg font-bold text-slate-900">
                                                        {{ $siswa->nama_sekolah_asal }}
                                                    </div>
                                                    <div class="mt-1 flex items-center gap-2">
                                                        <span
                                                            class="text-sm font-bold font-mono text-slate-700 bg-white/50 px-2 py-0.5 rounded border border-amber-100">NPSN:
                                                            {{ $siswa->npsn_sekolah_asal ?? '-' }}</span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-semibold text-amber-800 mb-1">Alamat
                                                        Lengkap</label>
                                                    <div class="text-sm font-medium text-slate-800 leading-relaxed">
                                                        {{ $siswa->alamat_sekolah_asal }}
                                                        <div
                                                            class="mt-1 text-slate-600 pt-2 border-t border-amber-200/50 flex items-center gap-1.5">
                                                            <i class="bi bi-geo-alt"></i>
                                                            {{ str_replace('Kabupaten', 'Kab.', $siswa->kabupaten_kota_asal) }},
                                                            {{ $siswa->provinsi_asal }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card Dokumen (Grid Button Besar) --}}
                                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm p-6">
                                    <h4
                                        class="font-bold text-slate-900 border-b border-slate-200 pb-3 mb-4 flex items-center gap-2">
                                        <i class="bi bi-files text-blue-600"></i> Dokumen Lampiran
                                    </h4>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @php
                                            $docList = [
                                                ['key' => "normal_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah, 'color' => 'rose'],
                                                ['key' => "normal_{$idx}_dapodik", 'label' => 'Dapodik', 'file' => $siswa->file_surat_dapodik, 'color' => 'blue'],
                                                ['key' => "normal_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi, 'color' => 'purple'],
                                                ['key' => "normal_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd, 'color' => 'emerald'],
                                                ['key' => "normal_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor, 'color' => 'orange'],
                                                ['key' => "normal_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik, 'color' => 'cyan'],
                                            ];
                                        @endphp

                                        @foreach($docList as $doc)
                                            @if($doc['file'])
                                                <button @click="activeDoc = activeDoc === '{{ $doc['key'] }}' ? null : '{{ $doc['key'] }}'"
                                                    :class="activeDoc === '{{ $doc['key'] }}' 
                                                                                                                                                                                                                                                                    ? 'ring-2 ring-{{ $doc['color'] }}-500 bg-{{ $doc['color'] }}-50' 
                                                                                                                                                                                                                                                                    : 'bg-white hover:bg-slate-50 border-slate-200'"
                                                    class="flex items-center gap-4 p-4 rounded-xl border transition group text-left w-full shadow-sm hover:shadow-md">

                                                    <div
                                                        class="w-12 h-12 rounded-lg bg-{{ $doc['color'] }}-100 text-{{ $doc['color'] }}-600 flex items-center justify-center shrink-0">
                                                        <i class="bi bi-file-earmark-pdf-fill text-2xl"></i>
                                                    </div>
                                                    <div class="flex-1">
                                                        <p
                                                            class="text-sm font-bold text-slate-500 mb-0.5 group-hover:text-{{ $doc['color'] }}-600">
                                                            Dokumen</p>
                                                        <p class="font-bold text-slate-900 text-base truncate">{{ $doc['label'] }}</p>
                                                    </div>
                                                    <div class="text-slate-400 group-hover:text-{{ $doc['color'] }}-600">
                                                        <i class="bi bi-eye-fill text-xl" x-show="activeDoc !== '{{ $doc['key'] }}'"></i>
                                                        <i class="bi bi-chevron-up text-xl font-bold"
                                                            x-show="activeDoc === '{{ $doc['key'] }}'"></i>
                                                    </div>
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Preview Area --}}
                                @foreach($docList as $doc)
                                    @if($doc['file'])
                                        <div x-show="activeDoc === '{{ $doc['key'] }}'" x-transition
                                            class="border-2 border-slate-300 rounded-xl overflow-hidden shadow-lg bg-white scroll-mt-24"
                                            x-ref="doc_{{ $doc['key'] }}">
                                            <x-document-viewer :path="$doc['file']" height="calc(100vh - 200px)" minHeight="600px" />
                                        </div>
                                    @endif
                                @endforeach

                            </div>
                        @endforeach

                        @if(count($siswaList) > 1)
                            <div class="flex items-center justify-between pt-4 border-t mt-4">
                                <button @click="if(activeTab > 0) activeTab--" :disabled="activeTab === 0"
                                    :class="activeTab === 0 ? 'opacity-50' : 'hover:bg-gray-200'"
                                    class="px-4 py-2 bg-gray-100 rounded-lg text-sm">← Sebelumnya</button>
                                <span class="text-sm text-gray-500">Siswa <span x-text="activeTab + 1"></span> /
                                    {{ count($siswaList) }}</span>
                                <button @click="if(activeTab < {{ count($siswaList) - 1 }}) activeTab++"
                                    :disabled="activeTab === {{ count($siswaList) - 1 }}"
                                    :class="activeTab === {{ count($siswaList) - 1 }} ? 'opacity-50' : 'hover:bg-gray-200'"
                                    class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Berikutnya →</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Form Surat Izin -->
            @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Disetujui Dinas')
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h2 class="text-lg font-semibold text-blue-900 mb-4"><i
                            class="bi bi-file-earmark-text text-blue-600 mr-2"></i>Terbitkan Surat Izin</h2>
                    <form action="{{ route('mutasi.masuk.issue-letter', $mutasi->id_mutasi) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-blue-900 mb-1">Nomor Surat Izin</label>
                                <input type="text" name="nomor_surat_izin"
                                    class="w-full rounded-lg border-blue-200 focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('nomor_surat_izin', $mutasi->nomor_surat_izin ?? '') }}"
                                    placeholder="Contoh: 421.3/123/Disdik">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-blue-900 mb-1">Tanggal Surat Izin</label>
                                <input type="date" name="tanggal_surat_izin" required value="{{ date('Y-m-d') }}"
                                    class="w-full rounded-lg border-blue-200 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition shadow-sm">
                            <i class="bi bi-send-check mr-2"></i> Siapkan Cetak
                        </button>
                    </form>
                </div>
            @endif

            <!-- Info Surat Izin -->
            @if(in_array($mutasi->status, ['Proses TTD', 'Selesai']) && in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                    <div class="grid grid-cols-2 gap-8 items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-emerald-900 mb-2">
                                <i class="bi bi-check-circle-fill text-emerald-600 mr-2"></i> Surat Izin Siap Cetak
                            </h3>
                            <p class="text-emerald-700">Nomor: <span
                                    class="font-mono font-bold">{{ $mutasi->nomor_surat_izin ?: 'Nomor diisi setelah TTD' }}</span>
                            </p>
                            <p class="text-sm text-emerald-600 mt-1">Diterbitkan pada:
                                {{ \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('j F Y') }}
                            </p>
                        </div>
                        <div class="flex justify-end gap-3">
                            @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                                @if($mutasi->file_arsip_scan)
                                    <a href="{{ route('dokumen.view', ['path' => $mutasi->file_arsip_scan]) }}" target="_blank"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition shadow-sm">
                                        <i class="bi bi-file-earmark-check mr-2"></i> Lihat Arsip Surat
                                    </a>
                                @else
                                    <div x-data="{ openPrint: false }" class="relative inline-block">
                                        <button @click="openPrint = !openPrint" @click.outside="openPrint = false"
                                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition shadow-sm flex items-center gap-2">
                                            <i class="bi bi-printer"></i> Cetak Surat Izin <i class="bi bi-chevron-down text-xs"></i>
                                        </button>
                                        <div x-show="openPrint" x-transition
                                            class="absolute right-0 mt-1 w-48 bg-white border border-slate-200 rounded-lg shadow-xl z-10 overflow-hidden">
                                            <a href="{{ route('mutasi.masuk.print-letter', $mutasi->id_mutasi) }}?mode=manual"
                                                target="_blank"
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                                                <i class="bi bi-printer text-emerald-500"></i> Cetak
                                            </a>
                                            <a href="{{ route('mutasi.masuk.print-letter', $mutasi->id_mutasi) }}?format=word"
                                                target="_blank"
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition border-t border-slate-100">
                                                <i class="bi bi-file-earmark-word text-blue-500"></i> Unduh Word
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endif
                            <!-- Route missing/not implemented
                                                                                                                        <a href="{{-- route('mutasi.masuk.print-keterangan', $mutasi->id_mutasi) --}}" target="_blank"
                                                                                                                            class="px-4 py-2 bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50 rounded-lg font-medium transition shadow-sm">
                                                                                                                            <i class="bi bi-printer mr-2"></i> Cetak Surat Keterangan
                                                                                                                        </a>
                                                                                                                        -->
                        </div>
                    </div>
                </div>
            @endif

            <!-- Form Selesai TTD -->
            @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Proses TTD')
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-6 mt-6">
                    <h2 class="text-lg font-semibold text-indigo-900 mb-4"><i
                            class="bi bi-pen-fill text-indigo-600 mr-2"></i>Konfirmasi Tanda Tangan</h2>
                    <form action="{{ route('mutasi.masuk.complete-letter', $mutasi->id_mutasi) }}" method="POST"
                        class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-indigo-900 mb-1">Nomor Surat Izin</label>
                            <input type="text" name="nomor_surat_izin"
                                value="{{ old('nomor_surat_izin', $mutasi->nomor_surat_izin ?? '') }}"
                                class="w-full rounded-lg border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-indigo-900 mb-1">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" value="{{ date('Y-m-d') }}"
                                class="w-full rounded-lg border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <button type="submit"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition shadow-sm">
                            <i class="bi bi-check-circle-fill mr-2"></i> Selesai Tanda Tangan
                        </button>
                    </form>
                </div>
            @endif

            {{-- Arsip Digital --}}
            @if($mutasi->status === 'Selesai' && in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">
                        <i class="bi bi-archive text-gray-600 mr-2"></i>
                        Arsip Digital (Scan Surat Asli)
                    </h3>

                    @if($mutasi->file_arsip_scan)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-file-earmark-pdf text-red-500 text-2xl"></i>
                                <div>
                                    <p class="font-medium text-green-900">Arsip Tersedia</p>
                                    <p class="text-sm text-green-700">Diupload pada:
                                        {{ \Carbon\Carbon::parse($mutasi->updated_at)->translatedFormat('j F Y H:i') }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="activeDoc = activeDoc === 'arsip_scan' ? null : 'arsip_scan'"
                                :style="activeDoc === 'arsip_scan' ? 'background-color: #15803d !important; color: white !important;' : ''"
                                :class="activeDoc === 'arsip_scan' ? '!bg-green-700 !text-white ring-2 ring-offset-1 ring-green-600 font-bold shadow-md' : 'bg-green-600 text-white hover:bg-green-700 border border-green-700'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                                <i class="bi bi-file-earmark-pdf mr-1"></i> Lihat Dokumen
                            </button>
                        </div>

                        <div x-show="activeDoc === 'arsip_scan'" x-transition class="mb-4">
                            <x-document-viewer :path="$mutasi->file_arsip_scan" height="calc(100vh - 280px)" minHeight="600px" />
                        </div>
                        @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                            <p class="text-sm text-gray-500 mb-2">Ingin mengganti file arsip? Upload ulang di bawah ini.</p>
                        @endif
                    @elseif(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-exclamation-circle text-yellow-600 text-xl"></i>
                                <p class="text-sm text-yellow-800">
                                    Belum ada arsip digital yang diunggah. Silakan unggah hasil scan surat yang telah
                                    ditandatangani.
                                </p>
                            </div>
                        </div>
                    @endif

                    @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                        <form action="{{ route('mutasi.masuk.upload-arsip', $mutasi->id_mutasi) }}" method="POST"
                            enctype="multipart/form-data" class="flex items-end gap-4">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Upload File Scan (PDF, Max 2MB)</label>
                                <input type="file" name="file_arsip_scan" required accept=".pdf"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            </div>
                            <button type="submit"
                                class="px-6 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg transition font-medium">
                                <i class="bi bi-upload mr-1"></i> Upload Arsip
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Catatan Dinas + Tombol Perbaiki untuk Operator/Sekolah --}}
            @if($mutasi->catatan_berkas)
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-2">
                        <i class="bi bi-exclamation-triangle text-yellow-600 mr-2"></i>
                        Catatan dari Dinas
                    </h3>
                    <p class="text-yellow-900 whitespace-pre-line mb-4">{{ $mutasi->catatan_berkas }}</p>

                    @php
                        $user = auth()->user();
                        $isOwner = ($user->username === $mutasi->pengusul)
                            || ($user->role === 'Sekolah' && $user->npsn === $mutasi->npsn_sekolah_tujuan);
                        $canRevise = in_array($user->role, ['Operator', 'Sekolah'])
                            && $isOwner
                            && in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan']);
                    @endphp

                    @if($canRevise)
                        <a href="{{ route('mutasi.masuk.revision', $mutasi->id_mutasi) }}"
                            class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                            <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang
                        </a>
                    @endif
                </div>
            @elseif(in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan']))
                {{-- Show button even without catatan --}}
                @php
                    $user = auth()->user();
                    $isOwner = ($user->username === $mutasi->pengusul)
                        || ($user->role === 'Sekolah' && $user->npsn === $mutasi->npsn_sekolah_tujuan);
                    $canRevise = in_array($user->role, ['Operator', 'Sekolah']) && $isOwner;
                @endphp

                @if($canRevise)
                    <div class="bg-orange-50 border border-orange-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-orange-800 mb-3">
                            <i class="bi bi-arrow-repeat text-orange-600 mr-2"></i>
                            {{ $mutasi->status === 'Dibatalkan' ? 'Usulan Dibatalkan' : 'Data Perlu Diperbaiki' }}
                        </h3>
                        <p class="text-orange-700 mb-4">
                            {{ $mutasi->status === 'Dibatalkan'
                        ? 'Usulan ini telah dibatalkan. Anda dapat mengedit dan mengirim ulang data ini (semua berkas wajib diupload ulang).'
                        : 'Data mutasi Anda perlu diperbaiki. Silakan review dan kirim ulang.' 
                                                                                                                                                                                                                                                                                                                                                                                                }}
                        </p>
                        <a href="{{ route('mutasi.masuk.revision', $mutasi->id_mutasi) }}"
                            class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                            <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang
                        </a>
                    </div>
                @endif
            @endif
</div>@endsection
