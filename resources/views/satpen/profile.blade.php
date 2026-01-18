@extends('layouts.app')

@section('content')
    <div class="max-w-full mx-auto space-y-6" x-data="{ academicYear: '2025/2026' }">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Profil Sekolah</h1>
                <p class="text-slate-500 text-sm mt-1">Kelola data identitas dan pengaturan laporan sekolah.</p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-semibold text-slate-700 shadow-sm">
                    NPSN: {{ $satpen->npsn }}
                </span>
            </div>
        </div>

        <form action="{{ route('sekolah.profile.update') }}" method="POST" enctype="multipart/form-data"
            class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            @csrf
            @method('PUT')

            <!-- LEFT COLUMN -->
            <div class="space-y-6">

                <!-- 1. Identitas Sekolah (Read Only) -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Identitas Sekolah</h2>
                        <i class="bi bi-building text-slate-400"></i>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Nama Sekolah -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nama Sekolah</label>
                            <div
                                class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 font-medium text-sm">
                                {{ $satpen->nama }}
                            </div>
                        </div>

                        <!-- NPSN -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">NPSN</label>
                            <div
                                class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 font-medium text-sm font-mono">
                                {{ $satpen->npsn }}
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Alamat Sekolah</label>
                            <div
                                class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm leading-relaxed">
                                {{ $satpen->alamat_lengkap ?? 'Alamat belum diisi lengkap.' }}
                            </div>
                        </div>

                        <p class="text-xs text-slate-400 flex items-center gap-1 mt-2">
                            <i class="bi bi-lock-fill"></i> Data identitas disinkronisasi dari Dapodik / Admin Dinas.
                        </p>
                    </div>
                </div>

                <!-- 2. Data Siswa Awal -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Data Siswa Awal</h2>
                        <i class="bi bi-people text-slate-400"></i>
                    </div>
                    <div class="p-6 space-y-5">

                        <!-- Tahun Pelajaran Selector -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Tahun Pelajaran</label>
                            <select x-model="academicYear"
                                class="w-full border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-medium">
                                <option value="2025/2026">2025/2026 (Saat Ini)</option>
                                <option value="2026/2027">2026/2027 (Tahun Depan)</option>
                            </select>
                            <p class="mt-2 text-xs text-slate-500">
                                Tahun ajaran dimulai minggu kedua Juli dan berakhir minggu ketiga Juni.
                            </p>
                        </div>

                        <!-- Info Alert -->
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex gap-3">
                            <i class="bi bi-info-circle-fill text-blue-600 mt-0.5"></i>
                            <div class="text-xs text-blue-800 leading-relaxed">
                                <strong>Penting:</strong> Masukkan jumlah siswa awal tahun ajaran. Data ini digunakan
                                sebagai dasar perhitungan <strong>Jumlah Siswa Akhir</strong> di laporan.
                            </div>
                        </div>

                        <!-- Inputs Grid -->
                        <div class="grid grid-cols-3 gap-4">
                            <!-- Kelas 7 -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2 text-center">Kelas 7</label>
                                <input type="number" name="jumlah_siswa_awal_7" min="0"
                                    value="{{ old('jumlah_siswa_awal_7', $satpen->jumlah_siswa_awal_7 ?? 0) }}"
                                    class="w-full border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-center placeholder-slate-400 font-bold">
                            </div>

                            <!-- Kelas 8 -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2 text-center">Kelas 8</label>
                                <input type="number" name="jumlah_siswa_awal_8" min="0"
                                    :readonly="academicYear === '2026/2027'"
                                    :class="academicYear === '2026/2027' ? 'bg-slate-100 cursor-not-allowed text-slate-500' : 'bg-white text-slate-800'"
                                    value="{{ old('jumlah_siswa_awal_8', $satpen->jumlah_siswa_awal_8 ?? 0) }}"
                                    class="w-full border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-center placeholder-slate-400 font-bold">
                                <template x-if="academicYear === '2026/2027'">
                                    <p class="text-[10px] text-center text-slate-400 mt-1">Otomatis</p>
                                </template>
                            </div>

                            <!-- Kelas 9 -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2 text-center">Kelas 9</label>
                                <input type="number" name="jumlah_siswa_awal_9" min="0"
                                    :readonly="academicYear === '2026/2027'"
                                    :class="academicYear === '2026/2027' ? 'bg-slate-100 cursor-not-allowed text-slate-500' : 'bg-white text-slate-800'"
                                    value="{{ old('jumlah_siswa_awal_9', $satpen->jumlah_siswa_awal_9 ?? 0) }}"
                                    class="w-full border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-center placeholder-slate-400 font-bold">
                                <template x-if="academicYear === '2026/2027'">
                                    <p class="text-[10px] text-center text-slate-400 mt-1">Otomatis</p>
                                </template>
                            </div>
                        </div>

                        <template x-if="academicYear === '2026/2027'">
                            <div class="text-xs text-amber-600 bg-amber-50 border border-amber-100 p-2 rounded text-center">
                                <i class="bi bi-exclamation-triangle-fill"></i> Kelas 8 & 9 diisi otomatis dari Data Siswa
                                Akhir tahun sebelumnya.
                            </div>
                        </template>

                    </div>
                </div>

                <!-- Submit Button (Left) -->
                <div class="pt-2">
                    <button type="submit"
                        class="w-full px-6 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:shadow-blue-600/40 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <i class="bi bi-check-lg text-lg"></i>
                        Simpan Perubahan Profil
                    </button>
                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div class="space-y-6">

                <!-- 1. Bantuan -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Informasi & Bantuan</h2>
                        <i class="bi bi-question-circle text-blue-500"></i>
                    </div>
                    <div class="p-6 space-y-4 text-sm text-slate-600">
                        <div class="flex gap-3">
                            <i class="bi bi-people-fill text-slate-400 text-lg"></i>
                            <div>
                                <strong class="text-slate-800 block mb-0.5">Data Siswa Awal</strong>
                                <p class="text-xs leading-relaxed">Masukkan jumlah siswa di awal tahun. Data ini menjadi
                                    basis perhitungan laporan bulanan.</p>
                            </div>
                        </div>
                        <div class="w-full h-px bg-slate-100"></div>
                        <div class="flex gap-3">
                            <i class="bi bi-image-fill text-slate-400 text-lg"></i>
                            <div>
                                <strong class="text-slate-800 block mb-0.5">Kop Surat</strong>
                                <p class="text-xs leading-relaxed">Gunakan file JPG/PNG resolusi tinggi agar hasil cetak PDF
                                    tajam dan profesional.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Informasi Kepala Sekolah -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Kepala Sekolah</h2>
                        <i class="bi bi-person-badge text-slate-400"></i>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="bi bi-person text-slate-400"></i>
                                </div>
                                <input type="text" name="kepala_sekolah_nama"
                                    value="{{ old('kepala_sekolah_nama', $satpen->kepala_sekolah_nama) }}"
                                    placeholder="Nama Kepala Sekolah beserta gelar"
                                    class="w-full pl-10 border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-slate-400">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">NIP (Nomor Induk Pegawai)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="bi bi-card-text text-slate-400"></i>
                                </div>
                                <input type="text" name="kepala_sekolah_nip"
                                    value="{{ old('kepala_sekolah_nip', $satpen->kepala_sekolah_nip) }}"
                                    placeholder="NIP atau tanda (-)"
                                    class="w-full pl-10 border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-slate-400">
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500">
                                * Isi dengan tanda hubung (-) jika Non-ASN.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3. Kop Surat -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Kop Surat Sekolah</h2>
                        <i class="bi bi-file-earmark-image text-slate-400"></i>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Upload File Baru</label>
                            <input type="file" id="kop_surat" name="kop_surat" accept="image/*" class="block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2.5 file:px-4
                                    file:rounded-lg file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100
                                    cursor-pointer border border-slate-300 rounded-lg p-1 bg-white transition-all">
                            <p class="mt-2 text-xs text-slate-500">
                                Format: JPG/PNG, Max 2MB.
                            </p>
                        </div>

                        @if($satpen->kop_surat_path)
                            <div class="mt-6 pt-6 border-t border-slate-100">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 text-center">Preview
                                    Kop Surat Saat Ini</p>
                                <div class="w-full bg-white border border-slate-200 rounded-lg p-2 shadow-sm">
                                    <img src="{{ Storage::url($satpen->kop_surat_path) }}" alt="Preview Kop Surat"
                                        class="w-full h-auto object-contain rounded">
                                </div>
                            </div>
                        @else
                            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 text-slate-300 mb-3">
                                    <i class="bi bi-image text-2xl"></i>
                                </div>
                                <p class="text-sm text-slate-500">Belum ada kop surat diupload.</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection