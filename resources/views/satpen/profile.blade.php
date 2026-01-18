@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto space-y-6" x-data="{ academicYear: '2025/2026' }">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Profil Sekolah</h1>
                <p class="text-slate-500 text-sm mt-1">Kelola data identitas sekolah, kepala sekolah, dan kop surat.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded text-xs font-bold uppercase tracking-wider">NPSN
                    : {{ $satpen->npsn }}</span>
            </div>
        </div>

        <form action="{{ route('sekolah.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- LEFT COLUMN: Identitas & Data Siswa (Span 7) -->
                <div class="lg:col-span-7 space-y-8">

                    <!-- 1. Identitas Sekolah (Read Only) -->
                    <div class="bg-white rounded border border-slate-200 shadow-sm">
                        <div class="bg-slate-50 px-5 py-3 border-b border-slate-100 flex items-center gap-2">
                            <i class="bi bi-building text-slate-400"></i>
                            <h3 class="font-bold text-slate-700 text-sm uppercase">Identitas Sekolah</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <!-- Nama Sekolah & NPSN Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Sekolah</label>
                                    <input type="text" value="{{ $satpen->nama }}" disabled
                                        class="w-full bg-slate-100 border border-slate-300 rounded px-3 py-2 text-slate-700 text-sm font-semibold cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">NPSN</label>
                                    <input type="text" value="{{ $satpen->npsn }}" disabled
                                        class="w-full bg-slate-100 border border-slate-300 rounded px-3 py-2 text-slate-700 text-sm font-semibold font-mono cursor-not-allowed">
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Alamat Sekolah</label>
                                <textarea disabled
                                    class="w-full bg-slate-100 border border-slate-300 rounded px-3 py-2 text-slate-700 text-sm cursor-not-allowed"
                                    rows="2">{{ $satpen->alamat_lengkap ?? '-' }}</textarea>
                            </div>

                            <div class="text-xs text-slate-400 flex items-center gap-1">
                                <i class="bi bi-lock-fill"></i> Data disinkronisasi otomatis dari Dapodik.
                            </div>
                        </div>
                    </div>

                    <!-- 2. Data Siswa Awal -->
                    <div class="bg-white rounded border border-slate-200 shadow-sm">
                        <div class="bg-slate-50 px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="bi bi-people text-slate-400"></i>
                                <h3 class="font-bold text-slate-700 text-sm uppercase">Data Siswa Awal</h3>
                            </div>
                        </div>

                        <div class="p-5 space-y-5">
                            <!-- Year Selector -->
                            <div
                                class="flex items-center justify-between bg-blue-50 border border-blue-100 rounded px-4 py-3">
                                <div>
                                    <label class="block text-sm font-bold text-blue-900">Tahun Pelajaran</label>
                                    <p class="text-xs text-blue-600">Pilih tahun ajaran untuk input data siswa.</p>
                                </div>
                                <select x-model="academicYear"
                                    class="bg-white border-2 border-blue-200 text-blue-900 text-sm font-bold rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                                    <option value="2025/2026">2025/2026 (Aktif)</option>
                                    <option value="2026/2027">2026/2027 (Depan)</option>
                                </select>
                            </div>

                            <!-- Input Grid -->
                            <div class="grid grid-cols-3 gap-4">
                                <!-- Kelas 7 -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1 text-center uppercase">Kelas
                                        7</label>
                                    <input type="number" name="jumlah_siswa_awal_7" min="0" required
                                        value="{{ old('jumlah_siswa_awal_7', $satpen->jumlah_siswa_awal_7 ?? 0) }}"
                                        class="w-full border-slate-300 rounded px-3 py-2 text-center font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <!-- Kelas 8 -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1 text-center uppercase">Kelas
                                        8</label>
                                    <div class="relative">
                                        <input type="number" name="jumlah_siswa_awal_8" min="0" required
                                            :readonly="academicYear === '2026/2027'"
                                            :class="academicYear === '2026/2027' ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800'"
                                            value="{{ old('jumlah_siswa_awal_8', $satpen->jumlah_siswa_awal_8 ?? 0) }}"
                                            class="w-full border-slate-300 rounded px-3 py-2 text-center font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <template x-if="academicYear === '2026/2027'">
                                            <div
                                                class="absolute -bottom-4 left-0 w-full text-[9px] text-center text-slate-400 font-medium">
                                                OTOMATIS</div>
                                        </template>
                                    </div>
                                </div>
                                <!-- Kelas 9 -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1 text-center uppercase">Kelas
                                        9</label>
                                    <div class="relative">
                                        <input type="number" name="jumlah_siswa_awal_9" min="0" required
                                            :readonly="academicYear === '2026/2027'"
                                            :class="academicYear === '2026/2027' ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800'"
                                            value="{{ old('jumlah_siswa_awal_9', $satpen->jumlah_siswa_awal_9 ?? 0) }}"
                                            class="w-full border-slate-300 rounded px-3 py-2 text-center font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <template x-if="academicYear === '2026/2027'">
                                            <div
                                                class="absolute -bottom-4 left-0 w-full text-[9px] text-center text-slate-400 font-medium">
                                                OTOMATIS</div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Button Simpan (Visible on Mobile/Desktop) -->
                    <div class="pt-4">
                        <button type="submit"
                            class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2">
                            <i class="bi bi-save2-fill"></i> Simpan Perubahan
                        </button>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Kepala Sekolah & Kop (Span 5) -->
                <div class="lg:col-span-5 space-y-8">

                    <!-- 1. Kepala Sekolah -->
                    <div class="bg-white rounded border border-indigo-200 shadow-sm ring-2 ring-indigo-50">
                        <div class="bg-indigo-50 px-5 py-3 border-b border-indigo-100 flex items-center gap-2">
                            <i class="bi bi-person-badge-fill text-indigo-600"></i>
                            <h3 class="font-bold text-indigo-800 text-sm uppercase">Data Kepala Sekolah</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="bg-indigo-50 border border-indigo-100 rounded p-3 text-xs text-indigo-700 mb-2">
                                <strong>Info:</strong> Pastikan nama lengkap beserta gelar akademik diisi dengan benar untuk
                                keperluan tandatangan laporan.
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap & Gelar
                                    <span class="text-red-500">*</span></label>
                                <input type="text" name="kepala_sekolah_nama" required
                                    value="{{ old('kepala_sekolah_nama', $satpen->kepala_sekolah_nama) }}"
                                    placeholder="Contoh: Dr. H. Budi Santoso, M.Pd."
                                    class="w-full border-slate-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-medium placeholder-slate-400">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">NIP (Nomor Induk
                                    Pegawai)</label>
                                <input type="text" name="kepala_sekolah_nip"
                                    value="{{ old('kepala_sekolah_nip', $satpen->kepala_sekolah_nip) }}"
                                    placeholder="Isi (-) jika Non-ASN"
                                    class="w-full border-slate-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono placeholder-slate-400">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Kop Surat -->
                    <div class="bg-white rounded border border-slate-200 shadow-sm">
                        <div class="bg-slate-50 px-5 py-3 border-b border-slate-100 flex items-center gap-2">
                            <i class="bi bi-images text-slate-400"></i>
                            <h3 class="font-bold text-slate-700 text-sm uppercase">Kop Surat Sekolah</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <!-- Preview -->
                            <div
                                class="bg-slate-100 rounded border border-slate-200 p-4 flex flex-col items-center justify-center min-h-[100px]">
                                @if($satpen->kop_surat_path)
                                    <img src="{{ Storage::url($satpen->kop_surat_path) }}" alt="Preview Kop Surat"
                                        class="w-full h-auto object-contain max-h-40 rounded bg-white shadow-sm">
                                    <span
                                        class="mt-2 text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded font-bold">TERPASANG
                                        SEKARANG</span>
                                @else
                                    <span class="text-slate-400 text-xs italic">Belum ada kop surat</span>
                                @endif
                            </div>

                            <!-- Upload -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Upload File Baru</label>
                                <input type="file" name="kop_surat" accept="image/*"
                                    class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 border border-slate-300 rounded cursor-pointer">
                                <p class="text-[10px] text-slate-400 mt-1">Format: JPG/PNG, Max 2MB. Resolusi tinggi
                                    disarankan.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection