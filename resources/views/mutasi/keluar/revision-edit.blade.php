@extends('layouts.app')

@section('title', 'Perbaiki Mutasi Keluar')

@section('content')
    <div class="space-y-8 animate-fade-in-up" x-data="{ activeDoc: null }">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('mutasi.keluar.show', $mutasi->id_mutasi) }}"
                class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-sm">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-900">Perbaiki Data Mutasi Keluar</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-600 font-mono">#{{ $mutasi->id_mutasi }}</span>
                </div>
            </div>
            <span
                class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold bg-rose-100 text-rose-700 border border-rose-200">
                Perlu Perbaikan
            </span>
        </div>

        <!-- Catatan Dinas -->
        @if($mutasiArr['catatan_berkas'] ?? null)
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="bi bi-exclamation-triangle-fill text-amber-900 text-9xl"></i>
                </div>
                <div class="relative z-10">
                    <h3 class="text-lg font-bold text-amber-900 mb-3 flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-amber-600"></i>
                        Catatan Perbaikan dari Dinas
                    </h3>
                    <div class="bg-white/50 backdrop-blur-sm rounded-xl p-4 border border-amber-100/50">
                        <p class="text-amber-900 whitespace-pre-line font-medium leading-relaxed">
                            {{ $mutasiArr['catatan_berkas'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Form Perbaikan -->
        <!-- Form Perbaikan -->
        <form action="{{ route('mutasi.keluar.revision.submit', $mutasi->id_mutasi) }}" method="POST"
            enctype="multipart/form-data"
            class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8 space-y-8">
            @csrf
            @method('PUT')

            <!-- Data Siswa -->
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="bi bi-person-fill text-blue-600 text-xl"></i>
                    </div>
                    Data Siswa
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Siswa <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nama_murid" required value="{{ old('nama_murid', $mutasi->nama_murid) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NISN <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nisn" required maxlength="10" value="{{ old('nisn', $mutasi->nisn) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NIS</label>
                        <input type="text" name="nis" value="{{ old('nis', $mutasi->nis) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select name="kelas" required
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-100 focus:bg-white transition text-slate-900 appearance-none">
                                <option value="VII" {{ $mutasi->kelas == 'VII' ? 'selected' : '' }}>VII</option>
                                <option value="VIII" {{ $mutasi->kelas == 'VIII' ? 'selected' : '' }}>VIII</option>
                                <option value="IX" {{ $mutasi->kelas == 'IX' ? 'selected' : '' }}>IX</option>
                            </select>
                            <i
                                class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $mutasi->tempat_lahir) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $mutasi->tanggal_lahir) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin</label>
                        <div class="relative">
                            <select name="jenis_kelamin"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-100 focus:bg-white transition text-slate-900 appearance-none">
                                <option value="L" {{ $mutasi->jenis_kelamin == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ $mutasi->jenis_kelamin == 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            <i
                                class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Surat Mutasi dari Sekolah Asal -->
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                        <i class="bi bi-file-earmark-text-fill text-orange-600 text-xl"></i>
                    </div>
                    Surat Mutasi dari Sekolah Asal
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Surat Mutasi <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nomor_surat_sekolah" required
                            value="{{ old('nomor_surat_sekolah', $mutasi->nomor_surat_sekolah) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-orange-100 focus:bg-white transition text-slate-900"
                            placeholder="contoh: 421/XXX/SMP/2024">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Surat Mutasi <span
                                class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal_surat_sekolah" required
                            value="{{ old('tanggal_surat_sekolah', $mutasi->tanggal_surat_sekolah) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-orange-100 focus:bg-white transition text-slate-900">
                    </div>
                </div>
            </div>
            <!-- Sekolah Tujuan -->
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i class="bi bi-geo-alt-fill text-emerald-600 text-xl"></i>
                    </div>
                    Sekolah Tujuan
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Sekolah Tujuan <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="npsn_sekolah_tujuan" id="npsn_sekolah_tujuan" required maxlength="8"
                                value="{{ old('npsn_sekolah_tujuan', $mutasi->npsn_sekolah_tujuan) }}"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                            <span id="npsn_loading" class="hidden absolute right-3 top-2.5 text-slate-400">
                                <i class="bi bi-arrow-repeat animate-spin"></i>
                            </span>
                        </div>
                        <p id="npsn_status" class="text-xs mt-1 text-slate-500">Contoh: 12345678</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Tujuan <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nama_sekolah_tujuan" id="nama_sekolah_tujuan" required
                            value="{{ old('nama_sekolah_tujuan', $mutasi->nama_sekolah_tujuan) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Provinsi Tujuan</label>
                        <select id="provinsi_tujuan_select"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                            <option value="">Pilih Provinsi</option>
                        </select>
                        <input type="hidden" id="provinsi_tujuan" name="provinsi_tujuan"
                            value="{{ old('provinsi_tujuan', $mutasi->provinsi_tujuan) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kabupaten/Kota Tujuan</label>
                        <select id="kabupaten_kota_tujuan_select"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                            <option value="">Pilih Kabupaten/Kota</option>
                        </select>
                        <input type="hidden" id="kabupaten_kota_tujuan" name="kabupaten_kota_tujuan"
                            value="{{ old('kabupaten_kota_tujuan', $mutasi->kabupaten_kota_tujuan) }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Tujuan</label>
                        <textarea name="alamat_sekolah_tujuan" id="alamat_sekolah_tujuan" rows="2"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">{{ old('alamat_sekolah_tujuan', $mutasi->alamat_sekolah_tujuan) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label
                            class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-100">
                            <input type="checkbox" name="tujuan_luar_negeri" id="tujuan_luar_negeri" value="1"
                                class="w-5 h-5 rounded text-sky-600 focus:ring-sky-500" {{ old('tujuan_luar_negeri_flag', $mutasi->tujuan_luar_negeri ?? 0) ? 'checked' : '' }} onchange="toggleTujuanLuarNegeri()">
                            <span class="text-sm font-medium text-slate-700">Sekolah Tujuan di Luar Negeri</span>
                        </label>
                        <input type="hidden" id="tujuan_luar_negeri_hidden" name="tujuan_luar_negeri_flag"
                            value="{{ old('tujuan_luar_negeri_flag', $mutasi->tujuan_luar_negeri ?? 0) }}">
                    </div>
                </div>
            </div>

            <!-- Alasan Pindah -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Alasan Pindah <span
                        class="text-rose-500">*</span></label>
                <textarea name="alasan_pindah" required rows="3"
                    class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-slate-100 focus:bg-white transition text-slate-900">{{ old('alasan_pindah', $mutasi->alasan_pindah) }}</textarea>
            </div>

            <!-- Upload Dokumen - Smart Filtering berdasarkan verifikasi -->
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                        <i class="bi bi-file-earmark-pdf-fill text-orange-600 text-xl"></i>
                    </div>
                    Dokumen yang Perlu Diperbaiki
                </h3>
                @php
                    // Logika dokumen wajib Mutasi Keluar
                    $mutasiArr = (array) $mutasi;
                    $isLuarNegeri = ($mutasiArr['tujuan_luar_negeri'] ?? 0) == 1;

                    // Mapping dokumen dengan status verifikasi dari database
                    $dokumen = [
                        'file_suket_keluar' => [
                            'label' => 'Surat Pindah Keluar',
                            'verif' => 'verif_suket_keluar',
                            'max' => 1,
                            'required' => true
                        ],
                        'file_suket_dapo' => [
                            'label' => 'Surat Dapodik',
                            'verif' => 'verif_suket_dapo',
                            'max' => 1,
                            'required' => true
                        ],
                        'file_suket_diterima' => [
                            'label' => 'Surat Diterima',
                            'verif' => 'verif_suket_diterima',
                            'max' => 1,
                            'required' => !$isLuarNegeri // Surat Diterima wajib jika bukan ke Luar Negeri
                        ],
                        'file_rapor' => [
                            'label' => 'Rapor',
                            'verif' => 'verif_rapor',
                            'max' => 5,
                            'required' => true
                        ],
                    ];

                    // Filter dokumen:
                    $filteredDokumen = [];
                    $mutasiArr = (array) $mutasi; // Cast to array for safe access
                    foreach ($dokumen as $field => $info) {
                        $verifField = $info['verif'];
                        // Safe access: check if column exists, default to null
                        $verifStatus = $mutasiArr[$verifField] ?? null;
                        $fileExists = !empty($mutasiArr[$field] ?? null);

                        // Skip jika tidak wajib DAN file tidak ada
                        if (!$info['required'] && !$fileExists) {
                            continue;
                        }

                        // Tampilkan jika:
                        // 1. Ditolak (0)
                        // 2. Belum diverifikasi (null)
                        // 3. File wajib tapi kosong (Missing)
                        if ($verifStatus === 0 || $verifStatus === null || ($info['required'] && !$fileExists)) {
                            $filteredDokumen[$field] = $info;
                        }
                    }
                @endphp

                @if(count($filteredDokumen) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($filteredDokumen as $field => $info)
                            @php
                                $verifField = $info['verif'];
                                $isRejected = ($mutasiArr[$verifField] ?? null) === 0;
                                $currentFile = $mutasiArr[$field] ?? null;
                            @endphp
                            <div
                                class="p-4 {{ $isRejected ? 'bg-rose-50 border-rose-200' : 'bg-amber-50 border-amber-200' }} rounded-xl border transition-all hover:shadow-sm">
                                <label
                                    class="block text-xs font-bold uppercase tracking-wide {{ $isRejected ? 'text-rose-800' : 'text-amber-800' }} mb-2 flex items-center gap-2">
                                    <i class="bi {{ $isRejected ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill' }} text-lg"></i>
                                    {{ $info['label'] }} (PDF)
                                </label>
                                <input type="file" name="{{ $field }}" accept=".pdf"
                                    onchange="validateFile(this, {{ $info['max'] }})"
                                    class="w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold {{ $isRejected ? 'file:bg-rose-100 file:text-rose-700 hover:file:bg-rose-200' : 'file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200' }} transition">
                                @if($currentFile)
                                    <div class="mt-3">
                                        <button type="button" @click="activeDoc = activeDoc === '{{ $field }}' ? null : '{{ $field }}'"
                                            :class="activeDoc === '{{ $field }}' ? 'bg-sky-600 text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-600 border border-slate-200 hover:bg-sky-50'"
                                            class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                            <i class="bi bi-eye"></i> Lihat file saat ini
                                        </button>
                                        <div x-show="activeDoc === '{{ $field }}'" x-transition
                                            class="mt-2 border rounded-lg overflow-hidden shadow-sm">
                                            <iframe src="{{ route('dokumen.view', ['path' => $currentFile]) }}"
                                                class="w-full bg-slate-100 h-64"></iframe>
                                            <a href="{{ route('dokumen.view', ['path' => $currentFile]) }}" target="_blank"
                                                class="block text-center py-2 bg-slate-50 text-sky-600 text-xs font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                                <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh (Tab Baru)
                                            </a>
                                        </div>
                                    </div>
                                @endif
                                @if($isRejected)
                                    <p class="mt-2 text-xs font-semibold text-rose-600 bg-white/50 px-2 py-1 rounded inline-block">
                                        <i class="bi bi-arrow-up-circle mr-1"></i> Perbaiki sesuai catatan
                                    </p>
                                @elseif(empty($currentFile) && $info['required'])
                                    <p class="mt-2 text-xs font-semibold text-amber-600 bg-white/50 px-2 py-1 rounded inline-block">
                                        <i class="bi bi-exclamation-circle mr-1"></i> File wajib belum diunggah
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div
                        class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center gap-3 text-emerald-700">
                        <i class="bi bi-check-circle-fill text-2xl text-emerald-500"></i>
                        <p class="text-sm font-medium">
                            Semua dokumen telah lolos verifikasi. Tidak ada dokumen yang perlu diperbaiki.
                        </p>
                    </div>
                @endif
            </div>

            <!-- Tombol Submit -->
            <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="{{ route('mutasi.keluar.show', $mutasi->id_mutasi) }}"
                    class="px-6 py-3 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl transition font-medium shadow-sm">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
                    <i class="bi bi-send-fill"></i> Kirim Ulang ke Dinas
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/mutasi-keluar.js') }}"></script>
    <script>
        window.MUTASI_CONFIG = {
            routes: {
                index: '{{ route('mutasi.keluar.index') }}',
                apiNpsn: '{{ route('mutasi.keluar.api.npsn', ['npsn' => ':npsn']) }}',
                apiSatpen: '{{ route('satpen.api.find', ['npsn' => ':npsn']) }}',
                apiProvinces: '{{ route('mutasi.keluar.api.provinces') }}',
                apiRegencies: '{{ route('mutasi.keluar.api.regencies', ['provinceId' => ':provinceId']) }}'
            }
        };
    </script>
@endpush