@extends('layouts.app')

@section('content')
    <div class="max-w-full mx-auto space-y-8" x-data="{ academicYear: '2025/2026' }">

        <!-- Header Page -->
        <div class="flex items-center justify-between border-b-2 border-slate-200 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Profil Sekolah</h1>
                <p class="text-slate-500 text-lg mt-1">Kelola data identitas, siswa, dan kepala sekolah.</p>
            </div>
            <div>
                <span
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-bold border border-blue-100">
                    <i class="bi bi-upc-scan mr-2"></i> NPSN: {{ $satpen->npsn }}
                </span>
            </div>
        </div>

        <form action="{{ route('sekolah.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Explicit Grid Layout with Inline Styles to Bypass Missing CSS -->
            <div class="grid-container"
                style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; align-items: start;">
                <!-- LEFT COLUMN -->
                <div class="left-column" style="min-width: 0;">

                    <!-- 1. Identitas Sekolah (Read Only) -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center gap-3">
                            <div class="bg-white p-2 rounded border border-slate-200 text-slate-500">
                                <i class="bi bi-building"></i>
                            </div>
                            <h2 class="font-bold text-slate-800 text-lg uppercase">Identitas Sekolah</h2>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Nama & NPSN -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-500 uppercase mb-2">Nama
                                        Sekolah</label>
                                    <input type="text" value="{{ $satpen->nama }}" disabled
                                        class="w-full bg-slate-100 border-2 border-slate-200 rounded-lg px-4 py-3 text-slate-600 font-bold cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-500 uppercase mb-2">NPSN</label>
                                    <input type="text" value="{{ $satpen->npsn }}" disabled
                                        class="w-full bg-slate-100 border-2 border-slate-200 rounded-lg px-4 py-3 text-slate-600 font-bold font-mono cursor-not-allowed">
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div>
                                <label class="block text-sm font-bold text-slate-500 uppercase mb-2">Alamat Lengkap</label>
                                <textarea disabled
                                    class="w-full bg-slate-100 border-2 border-slate-200 rounded-lg px-4 py-3 text-slate-600 font-medium cursor-not-allowed leading-relaxed"
                                    rows="2">{{ $satpen->alamat_lengkap ?? '-' }}</textarea>
                                <p class="mt-2 text-xs text-slate-400 flex items-center gap-1">
                                    <i class="bi bi-lock-fill"></i> Data disinkronisasi otomatis dari Dapodik.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Data Siswa Awal (Blue Theme) -->
                    <div class="bg-white rounded-xl shadow-md border-t-4 border-blue-500 overflow-hidden">
                        <div class="px-6 py-4 bg-white border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-people-fill text-blue-600 text-xl"></i>
                                <h2 class="font-extrabold text-slate-800 text-lg uppercase tracking-wide">Data Siswa Awal
                                </h2>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Year Selector -->
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-5">
                                <label class="block text-sm font-bold text-blue-900 mb-2">Tahun Pelajaran</label>
                                <select x-model="academicYear"
                                    class="w-full border-2 border-blue-300 bg-white text-blue-900 font-bold rounded-lg px-4 py-3 focus:outline-none focus:ring-4 focus:ring-blue-500/20 cursor-pointer text-lg">
                                    <option value="2025/2026">2025/2026 (Aktif)</option>
                                    <option value="2026/2027">2026/2027 (Mendatang)</option>
                                </select>
                                <p class="text-xs text-blue-600 mt-2 font-medium">
                                    * Pilih tahun ajaran sebelum mengisi jumlah siswa.
                                </p>
                            </div>

                            <!-- Inputs -->
                            <div class="grid grid-cols-3 gap-6">
                                <!-- Kelas 7 -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2 text-center uppercase">Kelas
                                        7</label>
                                    <input type="number" name="jumlah_siswa_awal_7" min="0" required
                                        value="{{ old('jumlah_siswa_awal_7', $satpen->jumlah_siswa_awal_7 ?? 0) }}"
                                        class="w-full border-2 border-slate-300 bg-white focus:border-blue-500 focus:bg-blue-50 rounded-xl px-2 py-4 text-center text-2xl font-black text-slate-800 transition-all shadow-sm">
                                </div>

                                <!-- Kelas 8 -->
                                <div class="relative">
                                    <label class="block text-sm font-bold text-slate-700 mb-2 text-center uppercase">Kelas
                                        8</label>
                                    <input type="number" name="jumlah_siswa_awal_8" min="0" required
                                        :readonly="academicYear === '2026/2027'"
                                        :class="academicYear === '2026/2027' ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed' : 'bg-white border-slate-300 text-slate-800 focus:border-blue-500 focus:bg-blue-50'"
                                        value="{{ old('jumlah_siswa_awal_8', $satpen->jumlah_siswa_awal_8 ?? 0) }}"
                                        class="w-full border-2 rounded-xl px-2 py-4 text-center text-2xl font-black transition-all shadow-sm">
                                </div>

                                <!-- Kelas 9 -->
                                <div class="relative">
                                    <label class="block text-sm font-bold text-slate-700 mb-2 text-center uppercase">Kelas
                                        9</label>
                                    <input type="number" name="jumlah_siswa_awal_9" min="0" required
                                        :readonly="academicYear === '2026/2027'"
                                        :class="academicYear === '2026/2027' ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed' : 'bg-white border-slate-300 text-slate-800 focus:border-blue-500 focus:bg-blue-50'"
                                        value="{{ old('jumlah_siswa_awal_9', $satpen->jumlah_siswa_awal_9 ?? 0) }}"
                                        class="w-full border-2 rounded-xl px-2 py-4 text-center text-2xl font-black transition-all shadow-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-lg rounded-xl shadow-lg transform active:scale-95 transition-all flex items-center justify-center gap-2">
                        <i class="bi bi-check-circle-fill"></i> SIMPAN PERUBAHAN PROFIL
                    </button>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="right-column" style="min-width: 0;">

                    <!-- 1. Informasi & Bantuan -->
                    <div class="bg-blue-50 rounded-xl border border-blue-100 p-6">
                        <h3 class="font-bold text-blue-900 text-lg mb-4 flex items-center gap-2">
                            <i class="bi bi-info-circle-fill text-blue-600"></i> INFORMASI PENTING
                        </h3>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span
                                    class="bg-blue-200 text-blue-800 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shrink-0">1</span>
                                <p class="text-sm text-blue-800 leading-relaxed"><strong>Data Siswa Awal</strong> digunakan
                                    sebagai saldo awal laporan mutasi bulanan. Wajib diisi di awal tahun.</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="bg-blue-200 text-blue-800 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shrink-0">2</span>
                                <p class="text-sm text-blue-800 leading-relaxed"><strong>Data Kepala Sekolah</strong> wajib
                                    diisi lengkap (Nama & Gelar) agar tanda tangan di laporan resmi valid.</p>
                            </li>
                        </ul>
                    </div>

                    <!-- 2. Kepala Sekolah (Indigo Theme) -->
                    <div class="bg-white rounded-xl shadow-md border-t-4 border-indigo-600 overflow-hidden">
                        <div class="px-6 py-4 bg-white border-b border-slate-100 flex items-center gap-3">
                            <i class="bi bi-person-badge-fill text-indigo-600 text-xl"></i>
                            <h2 class="font-extrabold text-slate-800 text-lg uppercase tracking-wide">Data Kepala Sekolah
                            </h2>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Nama Lengkap -->
                            <div>
                                <label class="block text-sm font-bold text-indigo-900 uppercase mb-2">
                                    Nama Lengkap & Gelar <span class="text-red-600 text-lg">*</span>
                                </label>
                                <input type="text" name="kepala_sekolah_nama" required
                                    value="{{ old('kepala_sekolah_nama', $satpen->kepala_sekolah_nama) }}"
                                    placeholder="Tulis nama lengkap beserta gelar..."
                                    class="w-full border-2 border-indigo-200 bg-indigo-50/30 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-500/20 rounded-xl px-4 py-3 text-slate-900 font-bold placeholder-indigo-300 transition-all">
                                <p class="text-xs text-slate-500 mt-2">Contoh: <strong>Dr. H. Budi Santoso, M.Pd.</strong>
                                </p>
                            </div>

                            <!-- NIP -->
                            <div>
                                <label class="block text-sm font-bold text-indigo-900 uppercase mb-2">
                                    NIP (Nomor Induk Pegawai)
                                </label>
                                <input type="text" name="kepala_sekolah_nip"
                                    value="{{ old('kepala_sekolah_nip', $satpen->kepala_sekolah_nip) }}"
                                    placeholder="Nomor NIP..."
                                    class="w-full border-2 border-indigo-200 bg-indigo-50/30 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-500/20 rounded-xl px-4 py-3 text-slate-900 font-mono font-bold placeholder-indigo-300 transition-all">
                                <p class="text-xs text-slate-500 mt-2">Isi (-) jika Non-ASN.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Kop Surat (Rose Theme) -->
                    <div class="bg-white rounded-xl shadow-md border-t-4 border-rose-500 overflow-hidden">
                        <div class="px-6 py-4 bg-white border-b border-slate-100 flex items-center gap-3">
                            <i class="bi bi-envelope-paper-fill text-rose-600 text-xl"></i>
                            <h2 class="font-extrabold text-slate-800 text-lg uppercase tracking-wide">Kop Surat Sekolah</h2>
                        </div>

                        <div class="p-6 space-y-6">
                            <div class="relative group">
                                <label class="block text-sm font-bold text-rose-900 uppercase mb-2">Upload File Baru</label>
                                <input type="file" name="kop_surat" accept="image/*"
                                    class="block w-full text-sm text-slate-500
                                                        file:mr-4 file:py-3 file:px-6
                                                        file:rounded-xl file:border-0
                                                        file:text-sm file:font-bold
                                                        file:bg-rose-50 file:text-rose-700
                                                        hover:file:bg-rose-100
                                                        cursor-pointer border-2 border-dashed border-rose-200 rounded-xl p-2 h-20 leading-10">
                            </div>

                            @if($satpen->kop_surat_path)
                                <div class="pt-4 border-t border-slate-100">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 text-center">
                                        Preview
                                        Terpasang</p>
                                    <img src="{{ Storage::url($satpen->kop_surat_path) }}" alt="Preview"
                                        class="w-full h-auto rounded-lg shadow-sm border border-slate-200">
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection