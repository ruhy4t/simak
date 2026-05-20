@extends('layouts.app')

@section('title', 'Edit Mutasi Keluar')

@section('content')
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('mutasi.keluar.show', $mutasi->id_mutasi) }}"
                class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-xs">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Edit Mutasi Keluar</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded-sm text-slate-600 font-mono">#{{ $mutasi->id_mutasi }}</span>
                </div>
            </div>
        </div>

        <!-- Warning -->
        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 sm:p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-amber-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-amber-800 text-lg mb-1">Perhatian!</h3>
                    <p class="text-amber-700">Setelah menyimpan perubahan, status akan dikembalikan ke
                        <strong>"Menunggu Verifikasi Dinas"</strong> dan surat izin sebelumnya akan dibatalkan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form action="{{ route('mutasi.keluar.update', $mutasi->id_mutasi) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Data Siswa -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="bi bi-person-fill text-blue-600"></i>
                    </div>
                    Data Siswa
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Siswa <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nama_murid" value="{{ old('nama_murid', $mutasi->nama_murid) }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900 placeholder:text-slate-400">
                        @error('nama_murid')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NISN <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nisn" value="{{ old('nisn', $mutasi->nisn) }}" required maxlength="10"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900">
                        @error('nisn')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NIS</label>
                        <input type="text" name="nis" value="{{ old('nis', $mutasi->nis) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select name="kelas" required
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900 appearance-none">
                                <option value="VII" {{ old('kelas', $mutasi->kelas) == 'VII' ? 'selected' : '' }}>VII</option>
                                <option value="VIII" {{ old('kelas', $mutasi->kelas) == 'VIII' ? 'selected' : '' }}>VIII
                                </option>
                                <option value="IX" {{ old('kelas', $mutasi->kelas) == 'IX' ? 'selected' : '' }}>IX</option>
                            </select>
                            <i
                                class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $mutasi->tempat_lahir) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $mutasi->tanggal_lahir) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin</label>
                        <div class="relative">
                            <select name="jenis_kelamin"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900 appearance-none">
                                <option value="L" {{ old('jenis_kelamin', $mutasi->jenis_kelamin) == 'L' ? 'selected' : '' }}>
                                    Laki-laki</option>
                                <option value="P" {{ old('jenis_kelamin', $mutasi->jenis_kelamin) == 'P' ? 'selected' : '' }}>
                                    Perempuan</option>
                            </select>
                            <i
                                class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekolah Asal -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                        <i class="bi bi-building-fill text-orange-600"></i>
                    </div>
                    Sekolah Asal
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Sekolah Asal</label>
                        <input type="text" name="npsn_sekolah_asal"
                            value="{{ old('npsn_sekolah_asal', $mutasi->npsn_sekolah_asal) }}"
                            class="w-full px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-slate-500 cursor-not-allowed"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Asal</label>
                        <input type="text" name="nama_sekolah_asal"
                            value="{{ old('nama_sekolah_asal', $mutasi->nama_sekolah_asal) }}"
                            class="w-full px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-slate-500 cursor-not-allowed"
                            readonly>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Asal</label>
                        <textarea name="alamat_sekolah_asal" rows="2"
                            class="w-full px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-slate-500 cursor-not-allowed"
                            readonly>{{ old('alamat_sekolah_asal', $mutasi->alamat_sekolah_asal) }}</textarea>
                    </div>
                    <!-- Di section Sekolah Asal, setelah alamat_sekolah_asal -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Surat Mutasi</label>
                        <input type="text" name="nomor_surat_sekolah"
                            value="{{ old('nomor_surat_sekolah', $mutasi->nomor_surat_sekolah) }}"
                            class="w-full px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-slate-500 cursor-not-allowed"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Surat Mutasi</label>
                        <input type="date" name="tanggal_surat_sekolah"
                            value="{{ old('tanggal_surat_sekolah', $mutasi->tanggal_surat_sekolah) }}"
                            class="w-full px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-slate-500 cursor-not-allowed"
                            readonly>
                    </div>
                </div>
            </div>

            <!-- Sekolah Tujuan -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i class="bi bi-geo-alt-fill text-emerald-600"></i>
                    </div>
                    Sekolah Tujuan
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Sekolah Tujuan <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="npsn_sekolah_tujuan" id="npsn_sekolah_tujuan"
                                value="{{ old('npsn_sekolah_tujuan', $mutasi->npsn_sekolah_tujuan) }}" required
                                maxlength="8"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                            <span id="npsn_loading" class="hidden absolute right-3 top-2.5 text-slate-400">
                                <i class="bi bi-arrow-repeat animate-spin"></i>
                            </span>
                        </div>
                        <p id="npsn_status" class="text-xs mt-1 text-slate-500">Contoh: 12345678</p>
                        @error('npsn_sekolah_tujuan')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Tujuan <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nama_sekolah_tujuan" id="nama_sekolah_tujuan"
                            value="{{ old('nama_sekolah_tujuan', $mutasi->nama_sekolah_tujuan) }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                        @error('nama_sekolah_tujuan')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
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
                                class="w-5 h-5 rounded-sm text-sky-600 focus:ring-sky-500" {{ old('tujuan_luar_negeri_flag', $mutasi->tujuan_luar_negeri ?? 0) ? 'checked' : '' }} onchange="toggleTujuanLuarNegeri()">
                            <span class="text-sm font-medium text-slate-700">Sekolah Tujuan di Luar Negeri</span>
                        </label>
                        <input type="hidden" id="tujuan_luar_negeri_hidden" name="tujuan_luar_negeri_flag"
                            value="{{ old('tujuan_luar_negeri_flag', $mutasi->tujuan_luar_negeri ?? 0) }}">
                    </div>
                </div>
            </div>

            <!-- Alasan Pindah -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <i class="bi bi-chat-left-text-fill text-indigo-600"></i>
                    </div>
                    Alasan Pindah
                </h2>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alasan Mutasi/Pindah</label>
                    <textarea name="alasan_pindah" rows="3"
                        class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:bg-white transition text-slate-900">{{ old('alasan_pindah', $mutasi->alasan_pindah) }}</textarea>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4 sticky bottom-6 z-10 p-2">
                <div class="flex gap-4 bg-white/80 backdrop-blur-md p-2 rounded-2xl shadow-xl border border-slate-200/50">
                    <a href="{{ route('mutasi.keluar.show', $mutasi->id_mutasi) }}"
                        class="px-6 py-3 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl transition font-medium shadow-xs">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition font-bold shadow-lg shadow-amber-500/20">
                        <i class="bi bi-save-fill mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
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
    </script>@endpush