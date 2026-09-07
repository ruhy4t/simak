@extends('layouts.app')

@section('title', 'Perbaiki Mutasi Masuk')

@section('content')
    <div class="space-y-8 animate-fade-in-up" x-data="{ activeDoc: null }">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('mutasi.masuk.show', $mutasi->id_mutasi) }}" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-xs">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-900">Perbaiki Data Mutasi Masuk</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded-sm text-slate-600 font-mono">#{{ $mutasi->id_mutasi }}</span>
                </div>
            </div>
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold bg-rose-100 text-rose-700 border border-rose-200">
                Perlu Perbaikan
            </span>
        </div>

        <!-- Catatan Dinas -->
        @if($mutasi->catatan_berkas)
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="bi bi-exclamation-triangle-fill text-amber-900 text-9xl"></i>
                </div>
                <div class="relative z-10">
                    <h3 class="text-lg font-bold text-amber-900 mb-3 flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-amber-600"></i>
                        Catatan Perbaikan dari Dinas
                    </h3>
                    <div class="bg-white/50 backdrop-blur-xs rounded-xl p-4 border border-amber-100/50">
                        <p class="text-amber-900 whitespace-pre-line font-medium leading-relaxed">{{ $mutasi->catatan_berkas }}</p>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('mutasi.masuk.revision.submit', $mutasi->id_mutasi) }}" method="POST"
            enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Data Surat & Sekolah -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 sm:p-8">
                <h3 class="text-lg font-bold text-slate-900 mb-8 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-sky-100 flex items-center justify-center">
                        <i class="bi bi-building-fill text-sky-600 text-xl"></i>
                    </div>
                    Data Surat & Sekolah Tujuan
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nomor Surat -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Surat Permohonan</label>
                        <input type="text" name="nomor_surat" value="{{ old('nomor_surat', $mutasi->nomor_surat) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900 placeholder:text-slate-400" required>
                    </div>

                    <!-- Tanggal Surat -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Surat</label>
                        <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat', $mutasi->tanggal_surat) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900" required>
                    </div>

                    <!-- NPSN (Lookup) -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Sekolah Tujuan</label>
                        <div class="relative">
                            <input type="text" name="npsn_sekolah_tujuan" id="npsn_tujuan_input"
                                value="{{ old('npsn_sekolah_tujuan', $mutasi->npsn_sekolah_tujuan) }}"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900 uppercase font-mono"
                                maxlength="8" required oninput="window.lookupNpsnTujuan(this.value)">
                            <div id="npsn_tujuan_status" class="absolute right-3 top-1/2 -translate-y-1/2"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Ketik 8 digit NPSN untuk mengisi otomatis.</p>
                    </div>

                    <!-- Nama Sekolah -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Tujuan</label>
                        <input type="text" name="nama_sekolah_tujuan" id="nama_tujuan_input"
                            value="{{ old('nama_sekolah_tujuan', $mutasi->nama_sekolah_tujuan) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900" required>
                    </div>

                    <!-- Alamat Sekolah -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Tujuan</label>
                        <textarea name="alamat_sekolah_tujuan" id="alamat_tujuan_input" rows="2"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900"
                            required>{{ old('alamat_sekolah_tujuan', $mutasi->alamat_sekolah_tujuan) }}</textarea>
                    </div>

                    <!-- Optional Prov/City for Manual Input -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Provinsi Tujuan</label>
                        <input type="text" name="provinsi_tujuan" id="provinsi_tujuan_input"
                            value="{{ old('provinsi_tujuan', $mutasi->provinsi_tujuan ?? '') }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kabupaten/Kota Tujuan</label>
                        <input type="text" name="kabupaten_kota_tujuan" id="kabupaten_kota_tujuan_input"
                            value="{{ old('kabupaten_kota_tujuan', $mutasi->kabupaten_kota_tujuan ?? '') }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900">
                    </div>

                    <!-- File Surat (Optional Update) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">File Surat Permohonan (Update jika
                            perlu)</label>
                        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="flex-1">
                                <input type="file" name="file_surat_permohonan" accept="application/pdf"
                                    class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 transition">
                                <p class="text-xs text-slate-500 mt-1">Maks 2MB. Kosongkan jika tidak ada perubahan.</p>
                            </div>
                            @if($mutasi->file_surat_permohonan)
                                <div class="w-full">
                                    <button type="button" @click="activeDoc = activeDoc === 'surat_permohonan' ? null : 'surat_permohonan'"
                                        :class="activeDoc === 'surat_permohonan' ? 'bg-sky-600 text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-600 border border-sky-100 hover:text-sky-800 hover:shadow-md'"
                                        class="flex items-center justify-center gap-2 text-sm px-4 py-2 rounded-xl transition w-full">
                                        <i class="bi bi-eye"></i> Lihat File Saat Ini
                                    </button>
                                    <div x-show="activeDoc === 'surat_permohonan'" x-transition class="mt-2 border rounded-xl overflow-hidden shadow-xs">
                                        <iframe src="{{ route('dokumen.view', ['path' => $mutasi->file_surat_permohonan]) }}" 
                                            class="w-full bg-slate-100 h-96"></iframe>
                                        <a href="{{ route('dokumen.view', ['path' => $mutasi->file_surat_permohonan]) }}" target="_blank"
                                           class="block text-center py-2 bg-slate-50 text-sky-600 text-xs font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                            <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh (Tab Baru)
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Murid -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 sm:p-8">
                <h3 class="text-lg font-bold text-slate-900 mb-8 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="bi bi-people-fill text-blue-600 text-xl"></i>
                    </div>
                    Data Murid
                </h3>

                <div class="space-y-8">
                    @foreach($siswaList as $index => $siswa)
                            <div class="bg-slate-50 rounded-2xl p-6 sm:p-8 border border-slate-200 relative">
                                <div
                                    class="absolute -top-4 -left-3 w-10 h-10 bg-linear-to-br from-blue-500 to-blue-600 text-white rounded-xl flex items-center justify-center font-bold shadow-lg shadow-blue-500/30 text-lg">
                                    {{ $index + 1 }}
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Identitas Murid -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Murid</label>
                                        <input type="text" name="siswa[{{ $siswa->id }}][nama_murid]"
                                            value="{{ old("siswa.{$siswa->id}.nama_murid", $siswa->nama_murid) }}"
                                            class="w-full px-4 py-2.5 bg-white border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition text-slate-900 shadow-xs"
                                            required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">NISN</label>
                                        <input type="text" name="siswa[{{ $siswa->id }}][nisn]"
                                            value="{{ old("siswa.{$siswa->id}.nisn", $siswa->nisn) }}"
                                            class="w-full px-4 py-2.5 bg-white border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition text-slate-900 shadow-xs"
                                            required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin</label>
                                        <div class="relative">
                                            <select name="siswa[{{ $siswa->id }}][jenis_kelamin]"
                                                class="w-full px-4 py-2.5 bg-white border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition text-slate-900 shadow-xs appearance-none"
                                                required>
                                                <option value="L" {{ $siswa->jenis_kelamin == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                                <option value="P" {{ $siswa->jenis_kelamin == 'P' ? 'selected' : '' }}>Perempuan</option>
                                            </select>
                                            <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas</label>
                                        <input type="text" name="siswa[{{ $siswa->id }}][kelas]"
                                            value="{{ old("siswa.{$siswa->id}.kelas", $siswa->kelas) }}"
                                            class="w-full px-4 py-2.5 bg-white border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition text-slate-900 shadow-xs"
                                            required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Diterima</label>
                                        <input type="date" name="siswa[{{ $siswa->id }}][tanggal_diterima]"
                                            value="{{ old("siswa.{$siswa->id}.tanggal_diterima", $siswa->tanggal_diterima) }}"
                                            class="w-full px-4 py-2.5 bg-white border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition text-slate-900 shadow-xs"
                                            required>
                                    </div>
                                </div>

                                <!-- Dokumen Murid -->
                                <div class="mt-6 border-t pt-4">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Dokumen Pendukung yang Perlu Diperbaiki
                                    </h4>
                                    @php
                                        // Logika untuk menentukan dokumen wajib berdasarkan status Luar Negeri
                                        $isLuarNegeri = $siswa->asal_luar_negeri == 1;
                                        
                                        $dokumen = [
                                            'file_surat_pindah' => [
                                                'label' => 'Surat Keterangan Pindah Sekolah', 
                                                'verif' => 'verif_surat_pindah',
                                                'required' => !$isLuarNegeri
                                            ],
                                            'file_surat_dapodik' => [
                                                'label' => 'Surat Keterangan Pindah Dapodik', 
                                                'verif' => 'verif_surat_dapodik',
                                                'required' => !$isLuarNegeri
                                            ],
                                            'file_rekomendasi' => [
                                                'label' => 'Surat Rekomendasi (Dinas/Kemenag)', 
                                                'verif' => 'verif_rekomendasi',
                                                'required' => !$isLuarNegeri
                                            ],
                                            'file_ijazah_sd' => [
                                                'label' => 'Ijazah SD/Sederajat', 
                                                'verif' => 'verif_ijazah_sd',
                                                'required' => true
                                            ],
                                            'file_rapor' => [
                                                'label' => 'Rapor Terakhir', 
                                                'verif' => 'verif_rapor',
                                                'required' => true
                                            ],
                                            'file_surat_penyaluran' => [
                                                'label' => 'Surat Penyaluran Pendidikan', 
                                                'verif' => 'verif_surat_penyaluran',
                                                'required' => $isLuarNegeri
                                            ],
                                            'file_rekam_didik' => [
                                                'label' => 'Rekam Didik', 
                                                'verif' => 'verif_rekam_didik',
                                                'required' => !$isLuarNegeri
                                            ],
                                        ];

                                        $filteredDokumen = [];
                                        foreach ($dokumen as $field => $info) {
                                            $verifField = $info['verif'];
                                            $verifStatus = $siswa->$verifField ?? null;
                                            $fileExists = !empty($siswa->$field);

                                            // Skip jika tidak wajib DAN file tidak ada (berarti memang tidak perlu)
                                            if (!$info['required'] && !$fileExists) {
                                                continue;
                                            }

                                            // Tampilkan jika:
                                            // 1. Ditolak (0)
                                            // 2. Belum diverifikasi (null)
                                            // 3. File wajib tapi kosong (Missing)
                                            if ($verifStatus === 0 || $verifStatus === null || ($info['required'] && !$fileExists)) {
                                                $filteredDokumen[$field] = $info['label'];
                                            }
                                        }
                                    @endphp

                                    @if(count($filteredDokumen) > 0)
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            @foreach($filteredDokumen as $field => $label)
                                                @php
                                                    $verifField = $dokumen[$field]['verif'];
                                                    $isRejected = ($siswa->$verifField ?? null) === 0;
                                                @endphp
                                                <div
                                                    class="p-4 {{ $isRejected ? 'bg-rose-50 border-rose-200' : 'bg-amber-50 border-amber-200' }} rounded-xl border transition-all hover:shadow-xs">
                                                    <label
                                                        class="block text-xs font-bold uppercase tracking-wide {{ $isRejected ? 'text-rose-800' : 'text-amber-800' }} mb-2 flex items-center gap-2">
                                                        <i class="bi {{ $isRejected ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill' }} text-lg"></i>
                                                        {{ $label }}
                                                    </label>
                                                    <div class="flex items-center gap-2">
                                                        <input type="file" name="siswa[{{ $siswa->id }}][{{ $field }}]"
                                                            accept="application/pdf"
                                                            class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold {{ $isRejected ? 'file:bg-rose-100 file:text-rose-700 hover:file:bg-rose-200' : 'file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200' }} transition">

                                                        @if($siswa->$field ?? null)
                                                            <button type="button" @click="activeDoc = activeDoc === '{{ "siswa_{$siswa->id}_{$field}" }}' ? null : '{{ "siswa_{$siswa->id}_{$field}" }}'"
                                                                :class="activeDoc === '{{ "siswa_{$siswa->id}_{$field}" }}' ? 'bg-sky-600 text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-600 border border-slate-200 hover:border-sky-200 hover:text-sky-800'"
                                                                class="p-2 rounded-lg transition" title="Lihat file saat ini">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    @if($siswa->$field ?? null)
                                                        <div x-show="activeDoc === '{{ "siswa_{$siswa->id}_{$field}" }}'" x-transition class="mt-2 border rounded-lg overflow-hidden shadow-xs">
                                                            <iframe src="{{ route('dokumen.view', ['path' => $siswa->$field]) }}" 
                                                                class="w-full bg-slate-100 h-80"></iframe>
                                                            <a href="{{ route('dokumen.view', ['path' => $siswa->$field]) }}" target="_blank"
                                                            class="block text-center py-2 bg-slate-50 text-sky-600 text-xs font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                                                <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh
                                                            </a>
                                                        </div>
                                                    @endif
                                                    @if($isRejected)
                                                        <p class="mt-2 text-xs font-semibold text-rose-600 bg-white/50 px-2 py-1 rounded-sm inline-block">
                                                            <i class="bi bi-arrow-up-circle mr-1"></i> Perbaiki sesuai catatan
                                                        </p>
                                                    @elseif(!$siswa->$field)
                                                        <p class="mt-2 text-xs font-semibold text-amber-600 bg-white/50 px-2 py-1 rounded-sm inline-block">
                                                            <i class="bi bi-exclamation-circle mr-1"></i> File wajib belum diunggah
                                                        </p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center gap-3 text-emerald-700">
                                            <i class="bi bi-check-circle-fill text-2xl text-emerald-500"></i>
                                            <p class="text-sm font-medium">
                                                Semua dokumen murid ini telah lolos verifikasi.
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
            </div>
    </div>

    <!-- Submit Buttons -->
    <div class="flex justify-end gap-3 sticky bottom-6 z-10 w-full">
        <div class="flex gap-4 bg-white/80 backdrop-blur-md p-2 rounded-2xl shadow-xl border border-slate-200/50">
            <a href="{{ route('mutasi.masuk.show', $mutasi->id_mutasi) }}"
                class="px-6 py-3 bg-white border border-slate-300 shadow-xs hover:bg-slate-50 text-slate-700 rounded-xl transition font-medium">
                Batal
            </a>
            <button type="submit"
                class="px-6 py-3 bg-blue-600 shadow-lg shadow-blue-500/20 hover:bg-blue-700 text-white rounded-xl transition font-bold flex items-center">
                <i class="bi bi-check-lg mr-2"></i> Simpan Perubahan & Kirim Ulang
            </button>
        </div>
    </div>
    </form>
    </div>

    @push('scripts')
        <script src="{{ asset('js/mutasi-masuk.js') }}?v={{ config('app.asset_version') }}"></script>
        <script>
            window.MUTASI_CONFIG = {
                routes: {
                    index: '{{ route('mutasi.masuk.index') }}',
                    apiNpsn: '{{ route('mutasi.masuk.api.npsn', ['npsn' => ':npsn']) }}',
                    apiProvinces: '{{ route('mutasi.masuk.api.provinces') }}',
                    apiRegencies: '{{ route('mutasi.masuk.api.regencies', ['provinceId' => ':provinceId']) }}'
                },
                isDinasEntry: {{ auth()->user()->role === 'Dinas' || auth()->user()->role === 'SuperAdmin' ? 'true' : 'false' }} 
                                                            };
        </script>
    @endpush@endsection
