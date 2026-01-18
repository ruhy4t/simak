@extends('layouts.app')

@section('content')
    <div class="max-w-full mx-auto space-y-8" x-data="{ academicYear: '2025/2026' }">

        <!-- Header Section -->
        <div class="flex items-center justify-between bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Profil Sekolah</h1>
                <p class="text-slate-500 text-sm mt-1">Kelola data resmi, kepala sekolah, dan konfigurasi laporan.</p>
            </div>
            <div class="hidden md:block">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm font-bold border border-slate-200">
                    NPSN: {{ $satpen->npsn }}
                </span>
            </div>
        </div>

        <form action="{{ route('sekolah.profile.update') }}" method="POST" enctype="multipart/form-data"
            class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            @csrf
            @method('PUT')

            <!-- LEFT COLUMN (Identitas & Data Siswa) -->
            <div class="lg:col-span-7 space-y-8">

                <!-- 1. Identitas Sekolah (Read Only) -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-orange-50 border-b border-orange-100 flex items-center gap-3">
                        <div class="bg-orange-100 p-2 rounded-lg text-orange-600">
                            <i class="bi bi-building-check-fill text-lg"></i>
                        </div>
                        <h2 class="font-bold text-orange-800 text-base uppercase tracking-wide">Identitas Sekolah</h2>
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Sekolah</label>
                                <div
                                    class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-800 font-bold">
                                    {{ $satpen->nama }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">NPSN</label>
                                <div
                                    class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-800 font-bold font-mono">
                                    {{ $satpen->npsn }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Alamat Lengkap</label>
                            <div
                                class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm leading-relaxed">
                                {{ $satpen->alamat_lengkap ?? 'Alamat belum diisi lengkap.' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Data Siswa Awal -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-blue-50 border-b border-blue-100 flex items-center gap-3">
                        <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                            <i class="bi bi-people-fill text-lg"></i>
                        </div>
                        <h2 class="font-bold text-blue-800 text-base uppercase tracking-wide">Data Siswa Awal</h2>
                    </div>

                    <div class="p-6 space-y-6">

                        <!-- Tahun Pelajaran Selector -->
                        <div class="bg-blue-50/50 rounded-xl p-5 border border-blue-100">
                            <label class="block text-sm font-bold text-blue-900 mb-2">Tahun Pelajaran (Saat Ini)</label>
                            <div class="relative">
                                <select x-model="academicYear"
                                    class="w-full bg-white border-2 border-blue-200 rounded-xl px-4 py-2.5 text-blue-900 font-bold text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all cursor-pointer">
                                    <option value="2025/2026">2025/2026 (Aktif)</option>
                                    <option value="2026/2027">2026/2027 (Mendatang)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Inputs Grid -->
                        <div class="grid grid-cols-3 gap-5">
                            <!-- Kelas 7 -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 text-center uppercase">Kelas
                                    7</label>
                                <input type="number" name="jumlah_siswa_awal_7" min="0" required
                                    value="{{ old('jumlah_siswa_awal_7', $satpen->jumlah_siswa_awal_7 ?? 0) }}"
                                    class="w-full bg-white border-2 border-slate-200 rounded-xl px-2 py-3 text-slate-800 text-xl font-bold text-center focus:ring-4 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                            </div>

                            <!-- Kelas 8 -->
                            <div class="relative">
                                <label class="block text-xs font-bold text-slate-600 mb-2 text-center uppercase">Kelas
                                    8</label>
                                <input type="number" name="jumlah_siswa_awal_8" min="0" required
                                    :readonly="academicYear === '2026/2027'"
                                    :class="academicYear === '2026/2027' ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800'"
                                    value="{{ old('jumlah_siswa_awal_8', $satpen->jumlah_siswa_awal_8 ?? 0) }}"
                                    class="w-full border-2 border-slate-200 rounded-xl px-2 py-3 text-xl font-bold text-center focus:ring-4 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                                <template x-if="academicYear === '2026/2027'">
                                    <div class="absolute -bottom-5 left-0 w-full text-center">
                                        <span
                                            class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded font-bold">OTOMATIS</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Kelas 9 -->
                            <div class="relative">
                                <label class="block text-xs font-bold text-slate-600 mb-2 text-center uppercase">Kelas
                                    9</label>
                                <input type="number" name="jumlah_siswa_awal_9" min="0" required
                                    :readonly="academicYear === '2026/2027'"
                                    :class="academicYear === '2026/2027' ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800'"
                                    value="{{ old('jumlah_siswa_awal_9', $satpen->jumlah_siswa_awal_9 ?? 0) }}"
                                    class="w-full border-2 border-slate-200 rounded-xl px-2 py-3 text-xl font-bold text-center focus:ring-4 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                                <template x-if="academicYear === '2026/2027'">
                                    <div class="absolute -bottom-5 left-0 w-full text-center">
                                        <span
                                            class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded font-bold">OTOMATIS</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-4 bg-blue-600 text-white text-lg font-bold rounded-xl shadow-lg hover:bg-blue-700 hover:shadow-xl transition-all flex items-center justify-center gap-3">
                    <i class="bi bi-save2-fill"></i>
                    SIMPAN PERUBAHAN
                </button>
            </div>

            <!-- RIGHT COLUMN (Informasi & Settings) -->
            <div class="lg:col-span-5 space-y-8">

                <!-- 1. Data Kepala Sekolah (High Visibility) -->
                <div
                    class="bg-white rounded-xl shadow-lg border-2 border-indigo-100 overflow-hidden ring-4 ring-indigo-50/50">
                    <div class="px-6 py-4 bg-indigo-600 border-b border-indigo-700 flex items-center gap-3 text-white">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <i class="bi bi-person-badge-fill text-lg"></i>
                        </div>
                        <h2 class="font-bold text-base uppercase tracking-wide">Data Kepala Sekolah</h2>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 flex gap-3 items-start">
                            <i class="bi bi-info-circle-fill text-indigo-600 mt-0.5"></i>
                            <p class="text-xs text-indigo-800 leading-relaxed font-medium">
                                Wajib diisi dengan <strong>Nama Lengkap & Gelar</strong> untuk tanda tangan laporan.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-indigo-900 uppercase tracking-wide mb-2">Nama Lengkap
                                & Gelar <span class="text-red-500">*</span></label>
                            <input type="text" name="kepala_sekolah_nama" required
                                value="{{ old('kepala_sekolah_nama', $satpen->kepala_sekolah_nama) }}"
                                placeholder="Contoh: Dr. H. Budi Santoso, M.Pd."
                                class="w-full bg-white border-2 border-indigo-100 rounded-xl px-4 py-3 text-indigo-900 font-bold text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all placeholder-indigo-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-indigo-900 uppercase tracking-wide mb-2">NIP (Nomor
                                Induk Pegawai)</label>
                            <input type="text" name="kepala_sekolah_nip"
                                value="{{ old('kepala_sekolah_nip', $satpen->kepala_sekolah_nip) }}"
                                placeholder="Isi (-) jika Non-ASN"
                                class="w-full bg-white border-2 border-indigo-100 rounded-xl px-4 py-3 text-indigo-900 font-medium text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all placeholder-indigo-300 font-mono">
                        </div>
                    </div>
                </div>

                <!-- 2. Kop Surat -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-rose-50 border-b border-rose-100 flex items-center gap-3">
                        <div class="bg-rose-100 p-2 rounded-lg text-rose-600">
                            <i class="bi bi-images text-lg"></i>
                        </div>
                        <h2 class="font-bold text-rose-800 text-base uppercase tracking-wide">Kop Surat Sekolah</h2>
                    </div>

                    <div class="p-6 space-y-5">
                        <div
                            class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:bg-slate-50 transition-colors group cursor-pointer relative">
                            <input type="file" name="kop_surat" accept="image/*"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <i
                                class="bi bi-cloud-arrow-up text-3xl text-slate-400 group-hover:text-rose-500 mb-2 transition-colors"></i>
                            <p class="text-sm font-bold text-slate-600 group-hover:text-rose-600">Klik untuk Ganti File</p>
                            <p class="text-xs text-slate-400">JPG/PNG, Max 2MB</p>
                        </div>

                        @if($satpen->kop_surat_path)
                            <div class="bg-slate-100 rounded-lg p-2 border border-slate-200">
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider text-center mb-2">
                                    Preview Saat Ini</p>
                                <img src="{{ Storage::url($satpen->kop_surat_path) }}" alt="Preview"
                                    class="w-full h-auto rounded bg-white shadow-sm">
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 3. Bantuan -->
                <div class="bg-teal-50 rounded-xl border border-teal-100 p-5">
                    <h3 class="font-bold text-teal-800 text-sm mb-3 flex items-center gap-2">
                        <i class="bi bi-question-circle-fill"></i> Pusat Bantuan
                    </h3>
                    <div class="space-y-3">
                        <div class="flex gap-3 text-xs text-teal-700">
                            <i class="bi bi-person-check-fill text-lg"></i>
                            <div>
                                <strong>Data Kepala Sekolah:</strong> Pastikan nama terisi lengkap dengan gelar untuk
                                validitas laporan resmi.
                            </div>
                        </div>
                        <div class="flex gap-3 text-xs text-teal-700">
                            <i class="bi bi-file-earmark-pdf-fill text-lg"></i>
                            <div>
                                <strong>Kop Surat:</strong> Gunakan gambar Landscape High-Res agar tidak pecah saat dicetak.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection