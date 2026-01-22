@extends('layouts.app')

@section('title', 'Buat Mutasi Keluar')

@section('content')
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('mutasi.keluar.index') }}" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-all">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Buat Mutasi Keluar</h1>
                <p class="text-slate-500 mt-1">Ajukan permohonan mutasi siswa keluar</p>
            </div>
        </div>

        <!-- Form -->
        <form id="formMutasiKeluar" action="{{ route('mutasi.keluar.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if(isset($draft) && $draft)
                <input type="hidden" name="draft_id" value="{{ $draft->id_mutasi }}">
            @endif

            <!-- Data Siswa -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center">
                        <i class="bi bi-person-fill text-sky-600"></i>
                    </div>
                    Data Siswa
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Siswa *</label>
                        <input type="text" name="nama_murid" value="{{ old('nama_murid', $draft->nama_murid ?? '') }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 @error('nama_murid') ring-2 ring-rose-100 @enderror"
                            placeholder="Nama lengkap siswa">
                        @error('nama_murid')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <!-- NISN & NIS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            NISN <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nisn" value="{{ old('nisn', $draft->nisn ?? '') }}" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition"
                            placeholder="Nomor Induk Siswa Nasional" minlength="10" maxlength="10">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            NIS/NIPD <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nis" value="{{ old('nis', $draft->nis ?? '') }}" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition"
                            placeholder="Nomor Induk Siswa / Peserta Didik">
                    </div>
                </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas *</label>
                        <select name="kelas" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer appearance-none @error('kelas') ring-2 ring-rose-100 @enderror">
                            <option value="">Pilih Kelas</option>
                            @php $selectedKelas = old('kelas', $draft->kelas ?? ''); @endphp
                            <option value="VII" {{ $selectedKelas == 'VII' ? 'selected' : '' }}>VII (Tujuh)</option>
                            <option value="VIII" {{ $selectedKelas == 'VIII' ? 'selected' : '' }}>VIII (Delapan)</option>
                            <option value="IX" {{ $selectedKelas == 'IX' ? 'selected' : '' }}>IX (Sembilan)</option>
                        </select>
                        @error('kelas')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tempat Lahir *</label>
                        <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $draft->tempat_lahir ?? '') }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 @error('tempat_lahir') ring-2 ring-rose-100 @enderror"
                            placeholder="Kota/Kabupaten">
                        @error('tempat_lahir')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $draft->tanggal_lahir ?? '') }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer @error('tanggal_lahir') ring-2 ring-rose-100 @enderror">
                        @error('tanggal_lahir')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer appearance-none @error('jenis_kelamin') ring-2 ring-rose-100 @enderror">
                            @php $selectedJK = old('jenis_kelamin', $draft->jenis_kelamin ?? ''); @endphp
                            <option value="">Pilih</option>
                            <option value="L" {{ $selectedJK == 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ $selectedJK == 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                        @error('jenis_kelamin')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Sekolah Asal -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                        <i class="bi bi-building text-orange-600"></i>
                    </div>
                    Sekolah Asal
                </h2>
                @php
                    $isLocked = in_array(auth()->user()->role, ['Operator', 'Sekolah']);
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Sekolah Asal *</label>
                        <div class="relative">
                            <input type="text" name="npsn_sekolah_asal" id="npsn_sekolah_asal"
                                value="{{ old('npsn_sekolah_asal', $npsnSekolahAsal ?? $user->npsn ?? '') }}" required
                                maxlength="8"
                                {{ $isLocked ? 'readonly' : '' }}
                                class="w-full px-4 py-2.5 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition outline-none {{ $isLocked ? 'bg-slate-100 text-slate-500 cursor-not-allowed font-mono' : 'bg-slate-50 focus:bg-white text-slate-800' }}"
                                oninput="lookupNpsnSekolahAsal(this.value)">
                            <span id="npsn_asal_status" class="absolute right-4 top-1/2 -translate-y-1/2 text-sm"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Asal *</label>
                        <input type="text" name="nama_sekolah_asal" id="nama_sekolah_asal"
                            value="{{ old('nama_sekolah_asal', $namaSekolahAsal ?? $user->nama_sekolah ?? '') }}" required
                            {{ $isLocked ? 'readonly' : '' }}
                            class="w-full px-4 py-2.5 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition outline-none {{ $isLocked ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-slate-50 focus:bg-white text-slate-800' }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Asal *</label>
                        <textarea name="alamat_sekolah_asal" id="alamat_sekolah_asal" required rows="2"
                            {{ $isLocked ? 'readonly' : '' }}
                            class="w-full px-4 py-2.5 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition outline-none resize-none @error('alamat_sekolah_asal') ring-2 ring-rose-100 @enderror {{ $isLocked ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-slate-50 focus:bg-white text-slate-800' }}">{{ old('alamat_sekolah_asal', $alamatSekolahAsal ?? '') }}</textarea>
                        @error('alamat_sekolah_asal')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <!-- Di section Sekolah Asal, setelah field alamat_sekolah_asal -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Surat Mutasi *</label>
                        <p class="text-xs text-rose-500 italic mb-2">Silahkan isi dengan Nomor Surat Keterangan Pindah dari
                            Sekolah</p>
                        <input type="text" name="nomor_surat_sekolah" value="{{ old('nomor_surat_sekolah', $draft->nomor_surat_sekolah ?? '') }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 @error('nomor_surat_sekolah') ring-2 ring-rose-100 @enderror"
                            placeholder="contoh: 421/XXX/SMP/2024">
                        @error('nomor_surat_sekolah')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Surat Mutasi *</label>
                        <p class="text-xs text-rose-500 italic mb-2">Silahkan isi dengan Tanggal Surat Keterangan Pindah dari
                            Sekolah</p>
                        <input type="date" name="tanggal_surat_sekolah" value="{{ old('tanggal_surat_sekolah', $draft->tanggal_surat_sekolah ?? '') }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer @error('tanggal_surat_sekolah') ring-2 ring-rose-100 @enderror">
                        @error('tanggal_surat_sekolah')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Sekolah Tujuan -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i class="bi bi-geo-alt-fill text-emerald-600"></i>
                    </div>
                    Sekolah Tujuan
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Sekolah Tujuan <span
                                id="npsn_required_label">*</span></label>
                        <div class="relative">
                            <input type="text" name="npsn_sekolah_tujuan" id="npsn_sekolah_tujuan"
                                value="{{ old('npsn_sekolah_tujuan', $draft->npsn_sekolah_tujuan ?? '') }}" maxlength="8"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 font-mono @error('npsn_sekolah_tujuan') ring-2 ring-rose-100 @enderror"
                                placeholder="8 karakter (contoh: 12345678 atau P1234567)">
                            <span id="npsn_loading" class="hidden absolute right-3 top-2.5 text-slate-400">
                                <i class="bi bi-arrow-repeat animate-spin"></i>
                            </span>
                        </div>
                        <p id="npsn_status" class="text-xs mt-1 text-slate-500">Contoh: 12345678 atau P1234567 (PKBM)</p>
                        @error('npsn_sekolah_tujuan')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Tujuan *</label>
                        <input type="text" name="nama_sekolah_tujuan" id="nama_sekolah_tujuan"
                            value="{{ old('nama_sekolah_tujuan', $draft->nama_sekolah_tujuan ?? '') }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 @error('nama_sekolah_tujuan') ring-2 ring-rose-100 @enderror"
                            placeholder="Nama sekolah tujuan">
                        @error('nama_sekolah_tujuan')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Provinsi Tujuan *</label>
                        <select id="provinsi_tujuan_select"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer appearance-none @error('provinsi_tujuan') ring-2 ring-rose-100 @enderror">
                            <option value="">Pilih Provinsi</option>
                        </select>
                        <input type="hidden" id="provinsi_tujuan" name="provinsi_tujuan"
                            value="{{ old('provinsi_tujuan', $draft->provinsi_tujuan ?? '') }}">
                        @error('provinsi_tujuan')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kabupaten/Kota Tujuan *</label>
                        <select id="kabupaten_kota_tujuan_select"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer appearance-none @error('kabupaten_kota_tujuan') ring-2 ring-rose-100 @enderror">
                            <option value="">Pilih Kabupaten/Kota</option>
                        </select>
                        <input type="hidden" id="kabupaten_kota_tujuan" name="kabupaten_kota_tujuan"
                            value="{{ old('kabupaten_kota_tujuan', $draft->kabupaten_kota_tujuan ?? '') }}">
                        @error('kabupaten_kota_tujuan')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Tujuan *</label>
                        <textarea name="alamat_sekolah_tujuan" id="alamat_sekolah_tujuan" rows="2" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 resize-none @error('alamat_sekolah_tujuan') ring-2 ring-rose-100 @enderror"
                            placeholder="Alamat lengkap sekolah tujuan">{{ old('alamat_sekolah_tujuan', $draft->alamat_sekolah_tujuan ?? '') }}</textarea>
                        @error('alamat_sekolah_tujuan')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer p-4 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                            <input type="checkbox" name="tujuan_luar_negeri" id="tujuan_luar_negeri" value="1"
                                class="w-5 h-5 rounded text-sky-600 focus:ring-sky-500 border-slate-300" onchange="toggleTujuanLuarNegeri()">
                            <div>
                                <span class="text-sm font-semibold text-slate-700">Sekolah Tujuan di Luar Negeri</span>
                                <p class="text-xs text-slate-500 mt-0.5">Centang jika siswa pindah ke sekolah di luar negeri. NPSN menjadi opsional.</p>
                            </div>
                        </label>
                        <input type="hidden" id="tujuan_luar_negeri_hidden" name="tujuan_luar_negeri_flag" value="0">
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-4 flex items-center gap-1.5 p-3 bg-slate-50 rounded-lg">
                    <i class="bi bi-info-circle-fill text-sky-500"></i>
                    <span>Jika NPSN tidak ditemukan di database, silakan cari di <a href="https://referensi.data.kemendikdasmen.go.id/pendidikan/dikdas" target="_blank"
                        class="text-sky-600 hover:text-sky-700 hover:underline font-medium">referensi.data.kemendikdasmen.go.id</a></span>
                </p>
            </div>

            <!-- Alasan Pindah -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="bi bi-chat-left-text-fill text-purple-600"></i>
                    </div>
                    Alasan Pindah / Mutasi Keluar
                </h2>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alasan Pindah Sekolah *</label>
                    <textarea name="alasan_pindah" required rows="3"
                        class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 resize-none @error('alasan_pindah') ring-2 ring-rose-100 @enderror"
                        placeholder="Contoh: Permohonan / Permintaan Orang Tua.">{{ old('alasan_pindah', $draft->alasan_pindah ?? '') }}</textarea>
                    @error('alasan_pindah')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Dokumen -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center">
                        <i class="bi bi-file-earmark-pdf-fill text-rose-600"></i>
                    </div>
                    @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                        Konfirmasi Berkas
                    @else
                        Upload Dokumen
                    @endif
                </h2>

                @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                    <!-- Mode Dinas: Checkbox konfirmasi berkas tersedia -->
                    <input type="hidden" name="is_dinas_entry" value="1">
                    <div class="bg-sky-50 border border-sky-100 rounded-xl p-4 mb-6 text-sm text-sky-800 flex gap-3">
                        <i class="bi bi-info-circle-fill text-lg"></i>
                        <span class="mt-0.5">Centang dokumen yang sudah diserahkan secara fisik oleh pemohon.</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                            <input type="checkbox" name="has_suket_keluar" value="1" required
                                class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 transition">
                            <div>
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Surat Keterangan Keluar</span>
                                <p class="text-xs text-slate-500 mt-0.5">Dari Kepala Sekolah Asal</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                            <input type="checkbox" name="has_suket_dapo" value="1" required
                                class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 transition">
                            <div>
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Surat Keterangan Dapodik</span>
                                <p class="text-xs text-slate-500 mt-0.5">Dicetak dari Aplikasi Dapodik</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                            <input type="checkbox" name="has_suket_diterima" value="1" required
                                class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 transition">
                            <div>
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Surat Keterangan Diterima</span>
                                <p class="text-xs text-slate-500 mt-0.5">Dari Kepala Sekolah Tujuan</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                            <input type="checkbox" name="has_rapor" value="1" required
                                class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 transition">
                            <div>
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Rapor Lengkap</span>
                                <p class="text-xs text-slate-500 mt-0.5">Rapor SMP lengkap</p>
                            </div>
                        </label>
                    </div>
                @else
                    <!-- Mode Normal: Upload File -->
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-6 text-sm text-amber-800 flex gap-3">
                        <i class="bi bi-exclamation-circle-fill text-lg"></i>
                        <span class="mt-0.5">Semua dokumen harus dalam format <strong>PDF</strong>. Maksimal 1MB per file (kecuali rapor 2MB).</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Surat Keterangan Keluar *</label>
                            <p class="text-xs text-rose-500 italic mb-2">Silahkan unggah PDF hasil scan dari dokumen asli Surat Keterangan Mutasi / Keluar dari Kepala Sekolah</p>
                            <input type="file" name="file_suket_keluar" accept=".pdf" required onchange="validateFile(this, 1)"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 text-slate-500 @error('file_suket_keluar') ring-2 ring-rose-100 @enderror">
                            @error('file_suket_keluar')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Surat Keterangan Dapodik *</label>
                            <p class="text-xs text-rose-500 italic mb-2">Silahkan unggah PDF hasil scan Surat Keterangan yang dicetak dari Aplikasi Dapodik (sudah TTD Kepala Sekolah)</p>
                            <input type="file" name="file_suket_dapo" accept=".pdf" required onchange="validateFile(this, 1)"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 text-slate-500 @error('file_suket_dapo') ring-2 ring-rose-100 @enderror">
                            @error('file_suket_dapo')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Surat Keterangan Diterima *</label>
                            <p class="text-xs text-rose-500 italic mb-2">Silahkan unggah PDF hasil scan Surat Keterangan Diterima dari Sekolah Tujuan (sudah TTD)</p>
                            <input type="file" name="file_suket_diterima" accept=".pdf" required
                                onchange="validateFile(this, 1)"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 text-slate-500 @error('file_suket_diterima') ring-2 ring-rose-100 @enderror">
                            @error('file_suket_diterima')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Rapor Lengkap *</label>
                            <p class="text-xs text-rose-500 italic mb-2">Upload Rapor SMP lengkap (Identitas, Nilai, Mutasi) yang sudah ditandatangani. Maks 2MB.</p>
                            <input type="file" name="file_rapor" accept=".pdf" required onchange="validateFile(this, 2)"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 text-slate-500 @error('file_rapor') ring-2 ring-rose-100 @enderror">
                            @error('file_rapor')<p class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endif
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('mutasi.keluar.index') }}"
                    class="px-6 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-800 rounded-xl transition font-medium">
                    Batal
                </a>
                <button type="button" id="btnSaveDraft" onclick="saveDraftKeluar()"
                    class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition font-medium flex items-center shadow-lg shadow-amber-500/20">
                    <i class="bi bi-save mr-2"></i>
                    <span>Simpan Draft</span>
                </button>
                <button type="button" id="btnSubmitKeluar"
                    onclick="showSubmittingModal()"
                    class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl transition font-medium flex items-center shadow-lg shadow-slate-900/20">
                    <i class="bi bi-send-fill mr-2"></i>
                    <span>Kirim Pengajuan</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4 text-center animate-fade-in-up">
            <div class="w-16 h-16 bg-sky-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="bi bi-send text-3xl text-sky-600 animate-pulse"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Mengirim Pengajuan...</h3>
            <p class="text-slate-600 mb-4">Mohon tunggu, data sedang dikirim ke server.</p>
            <div class="flex justify-center gap-1">
                <span class="w-2 h-2 bg-sky-600 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                <span class="w-2 h-2 bg-sky-600 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                <span class="w-2 h-2 bg-sky-600 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
            </div>
            <p class="text-xs text-slate-400 mt-4">Jangan tutup halaman ini.</p>
        </div>
    </div>

    <script>
        let isSubmitting = false;
        function showSubmittingModal() {
            if (isSubmitting) return;
            isSubmitting = true;
            
            // Calculate total file size
            let totalSize = 0;
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                if (input.files.length > 0) {
                    totalSize += input.files[0].size;
                }
            });

            // Warn if total size > 8MB (Safe limit for standard post_max_size)
            const MAX_TOTAL_SIZE = 8 * 1024 * 1024; // 8MB
            if (totalSize > MAX_TOTAL_SIZE) {
                alert('Total ukuran file terlalu besar (' + (totalSize / 1024 / 1024).toFixed(2) + ' MB). Mohon perkecil ukuran file agar total < 8MB untuk mencegah kegagalan sistem.');
                isSubmitting = false;
                return; // Stop submission
            }

            // Show modal
            const modal = document.getElementById('loadingModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Submit form after showing modal
            setTimeout(() => {
                const form = document.getElementById('formMutasiKeluar');
                if (form) {
                    form.submit();
                } else {
                    console.error('Form not found!');
                    // Fallback just in case
                    document.querySelector('form[action*="mutasi-keluar"]').submit();
                }
            }, 100);
        }
    </script>
    
    <style>
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
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
                apiRegencies: '{{ route('mutasi.keluar.api.regencies', ['provinceId' => ':provinceId']) }}',
                saveDraft: '{{ route('mutasi.keluar.draft.save') }}'
            },
            draftId: @json(isset($draft) ? $draft->id_mutasi : null)
        };

        // Populate draft values if resuming
        @if(isset($draft) && $draft)
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DRAFT RESUME MODE ===');
            const draft = @json($draft);
            console.log('Draft data from DB:', draft);
            
            // Force populate ALL form fields from draft
            const fieldMappings = {
                'nama_murid': draft.nama_murid,
                'nisn': draft.nisn,
                'nis': draft.nis,
                'kelas': draft.kelas,
                'tempat_lahir': draft.tempat_lahir,
                'tanggal_lahir': draft.tanggal_lahir,
                'jenis_kelamin': draft.jenis_kelamin,
                'npsn_sekolah_tujuan': draft.npsn_sekolah_tujuan,
                'nama_sekolah_tujuan': draft.nama_sekolah_tujuan,
                'alamat_sekolah_tujuan': draft.alamat_sekolah_tujuan,
                'alasan_pindah': draft.alasan_pindah,
                'nomor_surat_sekolah': draft.nomor_surat_sekolah,
                'tanggal_surat_sekolah': draft.tanggal_surat_sekolah
            };
            
            Object.entries(fieldMappings).forEach(([fieldName, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    const input = document.querySelector(`[name="${fieldName}"]`);
                    if (input) {
                        input.value = value;
                        console.log(`✓ Set ${fieldName} = "${value}"`);
                    } else {
                        console.warn(`✗ Input not found: ${fieldName}`);
                    }
                }
            });
            
            // Handle hidden inputs for province/city
            if (draft.provinsi_tujuan) {
                const provHidden = document.getElementById('provinsi_tujuan');
                if (provHidden) {
                    provHidden.value = draft.provinsi_tujuan;
                    console.log('✓ Set provinsi_tujuan =', draft.provinsi_tujuan);
                }
            }
            if (draft.kabupaten_kota_tujuan) {
                const kabHidden = document.getElementById('kabupaten_kota_tujuan');
                if (kabHidden) {
                    kabHidden.value = draft.kabupaten_kota_tujuan;
                    console.log('✓ Set kabupaten_kota_tujuan =', draft.kabupaten_kota_tujuan);
                }
            }
            
            console.log('=== DRAFT POPULATION COMPLETE ===');
        });
        @else
        console.log('No draft detected - new submission mode');
        @endif

        /**
         * Save current form as Draft
         */
        function saveDraftKeluar() {
            const form = document.getElementById('formMutasiKeluar');
            if (!form) return;

            // Change form action to draft route
            const originalAction = form.action;
            form.action = '{{ route('mutasi.keluar.draft.save') }}';
            
            // Show saving indicator
            const btn = document.getElementById('btnSaveDraft');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split animate-spin mr-2"></i> Menyimpan...';
            btn.disabled = true;
            
            // Submit form
            form.submit();
        }
    </script>
@endpush