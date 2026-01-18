@extends('layouts.app')

@section('content')
    <div class="max-w-full mx-auto space-y-8" x-data="{ academicYear: '2025/2026' }">

        <!-- Header Section with Gradient -->
        <div
            class="relative bg-gradient-to-r from-blue-700 to-indigo-700 rounded-3xl p-8 shadow-xl overflow-hidden mb-8 text-white">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="bi bi-building text-9xl"></i>
            </div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-white mb-2">Profil Sekolah</h1>
                    <p class="text-blue-100 text-lg font-medium">Kelola identitas resmi dan konfigurasi laporan sekolah
                        Anda.</p>
                </div>
                <div
                    class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/20">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <i class="bi bi-upc-scan text-xl text-white"></i>
                    </div>
                    <div>
                        <p class="text-xs text-blue-200 uppercase font-bold tracking-wider">NPSN Sekolah</p>
                        <p class="text-xl font-bold font-mono">{{ $satpen->npsn }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('sekolah.profile.update') }}" method="POST" enctype="multipart/form-data"
            class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            @csrf
            @method('PUT')

            <!-- LEFT COLUMN (Identitas & Data Siswa) -->
            <div class="lg:col-span-7 space-y-8">

                <!-- 1. Identitas Sekolah (Read Only) -->
                <div
                    class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div
                        class="px-8 py-5 bg-gradient-to-r from-slate-50 to-white border-b border-slate-100 flex items-center gap-4">
                        <div
                            class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600 shadow-sm group-hover:scale-110 transition-transform">
                            <i class="bi bi-building-check text-xl"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-800 text-lg">Identitas Sekolah</h2>
                            <p class="text-slate-500 text-xs">Data pokok pendidikan (Dapodik)</p>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <!-- Nama Sekolah -->
                        <div class="relative">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nama Sekolah
                                Resmi</label>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 flex items-center gap-3">
                                <i class="bi bi-mortarboard text-slate-400 text-lg"></i>
                                <span class="text-slate-800 font-bold text-lg">{{ $satpen->nama }}</span>
                            </div>
                        </div>

                        <!-- NPSN -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NPSN</label>
                            <div
                                class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 flex items-center gap-3 w-full md:w-1/2">
                                <i class="bi bi-upc text-slate-400 text-lg"></i>
                                <span class="text-slate-800 font-bold font-mono text-lg">{{ $satpen->npsn }}</span>
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Alamat
                                Lengkap</label>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 flex items-start gap-3">
                                <i class="bi bi-geo-alt text-slate-400 text-lg mt-0.5"></i>
                                <span class="text-slate-700 text-base leading-relaxed font-medium">
                                    {{ $satpen->alamat_lengkap ?? 'Alamat belum diisi lengkap.' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 p-3 bg-orange-50 rounded-lg border border-orange-100/50">
                            <i class="bi bi-lock-fill text-orange-400"></i>
                            <span class="text-xs font-semibold text-orange-700">Data identitas dikunci dan disinkronisasi
                                otomatis.</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Data Siswa Awal -->
                <div
                    class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-300 relative">
                    <!-- Decorator Line -->
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-500"></div>

                    <div
                        class="px-8 py-5 bg-gradient-to-r from-blue-50/50 to-white border-b border-blue-50 flex items-center gap-4">
                        <div
                            class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 shadow-sm group-hover:scale-110 transition-transform">
                            <i class="bi bi-people-fill text-xl"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-800 text-lg">Data Siswa Awal</h2>
                            <p class="text-slate-500 text-xs">Basis perhitungan laporan mutasi</p>
                        </div>
                    </div>

                    <div class="p-8 space-y-8">

                        <!-- Tahun Pelajaran Selector -->
                        <div class="bg-blue-50/50 rounded-xl p-6 border border-blue-100">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Tahun Pelajaran</label>
                            <div class="relative">
                                <select x-model="academicYear"
                                    class="w-full appearance-none bg-white border-2 border-blue-200 rounded-xl px-5 py-3 text-blue-900 font-bold text-base focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all cursor-pointer">
                                    <option value="2025/2026">Tahun Pelajaran 2025/2026 (Aktif)</option>
                                    <option value="2026/2027">Tahun Pelajaran 2026/2027 (Mendatang)</option>
                                </select>
                                <div
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 pointer-events-none text-blue-500">
                                    <i class="bi bi-chevron-down stroke-2"></i>
                                </div>
                            </div>
                            <p class="mt-3 text-xs font-medium text-blue-600 flex items-center gap-1.5">
                                <i class="bi bi-calendar-check-fill"></i>
                                Periode: Minggu ke-2 Juli s.d. Minggu ke-3 Juni.
                            </p>
                        </div>

                        <!-- Inputs Grid -->
                        <div>
                            <div class="grid grid-cols-3 gap-6">
                                <!-- Kelas 7 -->
                                <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100">
                                    <label
                                        class="block text-sm font-bold text-indigo-900 mb-3 text-center uppercase tracking-wide">Kelas
                                        7</label>
                                    <div class="relative">
                                        <input type="number" name="jumlah_siswa_awal_7" min="0" required
                                            value="{{ old('jumlah_siswa_awal_7', $satpen->jumlah_siswa_awal_7 ?? 0) }}"
                                            class="w-full bg-white border-2 border-indigo-200 rounded-xl px-2 py-3 text-indigo-900 text-2xl font-black text-center focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                                    </div>
                                    <p class="text-[10px] text-center text-indigo-400 font-bold mt-2 uppercase">Input Manual
                                    </p>
                                </div>

                                <!-- Kelas 8 -->
                                <div class="bg-purple-50/50 p-4 rounded-2xl border border-purple-100 relative">
                                    <label
                                        class="block text-sm font-bold text-purple-900 mb-3 text-center uppercase tracking-wide">Kelas
                                        8</label>
                                    <div class="relative">
                                        <input type="number" name="jumlah_siswa_awal_8" min="0" required
                                            :readonly="academicYear === '2026/2027'"
                                            :class="academicYear === '2026/2027' ? 'bg-purple-100/50 cursor-not-allowed text-purple-400' : 'bg-white text-purple-900'"
                                            value="{{ old('jumlah_siswa_awal_8', $satpen->jumlah_siswa_awal_8 ?? 0) }}"
                                            class="w-full border-2 border-purple-200 rounded-xl px-2 py-3 text-2xl font-black text-center focus:ring-4 focus:ring-purple-500/20 focus:border-purple-600 transition-all">
                                    </div>
                                    <template x-if="academicYear === '2026/2027'">
                                        <div
                                            class="absolute inset-0 bg-white/50 backdrop-blur-[1px] rounded-2xl flex items-center justify-center">
                                            <span
                                                class="bg-purple-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow">OTOMATIS</span>
                                        </div>
                                    </template>
                                    <template x-if="academicYear !== '2026/2027'">
                                        <p class="text-[10px] text-center text-purple-400 font-bold mt-2 uppercase">Input
                                            Manual</p>
                                    </template>
                                </div>

                                <!-- Kelas 9 -->
                                <div class="bg-pink-50/50 p-4 rounded-2xl border border-pink-100 relative">
                                    <label
                                        class="block text-sm font-bold text-pink-900 mb-3 text-center uppercase tracking-wide">Kelas
                                        9</label>
                                    <div class="relative">
                                        <input type="number" name="jumlah_siswa_awal_9" min="0" required
                                            :readonly="academicYear === '2026/2027'"
                                            :class="academicYear === '2026/2027' ? 'bg-pink-100/50 cursor-not-allowed text-pink-400' : 'bg-white text-pink-900'"
                                            value="{{ old('jumlah_siswa_awal_9', $satpen->jumlah_siswa_awal_9 ?? 0) }}"
                                            class="w-full border-2 border-pink-200 rounded-xl px-2 py-3 text-2xl font-black text-center focus:ring-4 focus:ring-pink-500/20 focus:border-pink-600 transition-all">
                                    </div>
                                    <template x-if="academicYear === '2026/2027'">
                                        <div
                                            class="absolute inset-0 bg-white/50 backdrop-blur-[1px] rounded-2xl flex items-center justify-center">
                                            <span
                                                class="bg-pink-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow">OTOMATIS</span>
                                        </div>
                                    </template>
                                    <template x-if="academicYear !== '2026/2027'">
                                        <p class="text-[10px] text-center text-pink-400 font-bold mt-2 uppercase">Input
                                            Manual</p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <template x-if="academicYear === '2026/2027'">
                            <div class="flex items-start gap-3 p-4 bg-amber-50 rounded-xl border border-amber-100">
                                <i class="bi bi-lightbulb-fill text-amber-500 text-xl"></i>
                                <div class="text-sm text-amber-800 leading-relaxed">
                                    <strong>Mode Tahun Depan:</strong> Jumlah Siswa Kelas 8 & 9 akan <em>dihitung
                                        otomatis</em> oleh sistem berdasarkan data kelulusan kelas sebelumnya. Anda hanya
                                    perlu mengisi proyeksi Kelas 7.
                                </div>
                            </div>
                        </template>

                    </div>
                </div>

                <!-- Submit Button Area (Main Action) -->
                <div class="sticky bottom-4 z-20">
                    <div class="absolute inset-0 bg-white/80 backdrop-blur-xl rounded-xl -m-2"></div>
                    <button type="submit"
                        class="relative w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 rounded-2xl shadow-xl shadow-blue-500/30 transform transition focus:scale-[0.98] hover:shadow-2xl hover:-translate-y-1 flex items-center justify-center gap-3 group">
                        <span class="text-lg font-bold tracking-wide">SIMPAN PERUBAHAN PROFILE</span>
                        <div class="bg-white/20 p-1.5 rounded-lg group-hover:rotate-12 transition-transform">
                            <i class="bi bi-save2-fill"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- RIGHT COLUMN (Informasi & Settings) -->
            <div class="lg:col-span-5 space-y-8">

                <!-- 2. Informasi Kepala Sekolah (Highlight) -->
                <div
                    class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-300 ring-1 ring-indigo-50">
                    <div
                        class="px-6 py-5 bg-gradient-to-r from-indigo-50 to-white border-b border-indigo-100 flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                            <i class="bi bi-person-badge-fill text-xl"></i>
                        </div>
                        <h2 class="font-bold text-slate-800 text-lg">Kepala Sekolah</h2>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Alert Info -->
                        <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 flex gap-3">
                            <i class="bi bi-info-circle-fill text-indigo-500 text-lg mt-0.5"></i>
                            <div class="text-xs text-indigo-800 space-y-1 leading-relaxed">
                                <p><strong>Penting:</strong> Pastikan nama yang diinput adalah <strong>Nama Lengkap berserta
                                        Gelar Akademik</strong> untuk keperluan laporan resmi.</p>
                            </div>
                        </div>

                        <div class="group/input">
                            <label
                                class="block text-xs font-bold text-indigo-900 uppercase tracking-wide mb-2 group-focus-within/input:text-indigo-600 transition-colors">
                                Nama Lengkap & Gelar <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="kepala_sekolah_nama" required
                                value="{{ old('kepala_sekolah_nama', $satpen->kepala_sekolah_nama) }}"
                                placeholder="Contoh: Dr. H. Budi Santoso, M.Pd."
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-800 font-bold text-sm focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all placeholder-slate-400">
                            <p class="mt-2 text-[10px] text-slate-400">Tulis nama lengkap beserta gelar depan dan belakang.
                            </p>
                        </div>

                        <div class="group/input">
                            <label
                                class="block text-xs font-bold text-indigo-900 uppercase tracking-wide mb-2 group-focus-within/input:text-indigo-600 transition-colors">
                                NIP (Nomor Induk Pegawai)
                            </label>
                            <input type="text" name="kepala_sekolah_nip"
                                value="{{ old('kepala_sekolah_nip', $satpen->kepala_sekolah_nip) }}"
                                placeholder="Isi (-) jika Non-ASN"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-800 font-mono font-medium text-sm focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all placeholder-slate-400">
                            <p class="mt-2 text-[10px] text-slate-400">Jika Kepala Sekolah Non-ASN, isikan tanda strip (-).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3. Kop Surat Upload -->
                <div
                    class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div
                        class="px-6 py-5 bg-gradient-to-r from-slate-50 to-white border-b border-slate-100 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600">
                            <i class="bi bi-images text-xl"></i>
                        </div>
                        <h2 class="font-bold text-slate-800 text-lg">Kop Surat Sekolah</h2>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Upload Box -->
                        <div class="relative">
                            <input type="file" id="kop_surat" name="kop_surat" accept="image/*"
                                class="peer absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                onchange="document.getElementById('file-name-display').innerText = this.files[0].name">

                            <div
                                class="border-2 border-dashed border-slate-300 peer-hover:border-rose-400 peer-hover:bg-rose-50 rounded-2xl p-6 flex flex-col items-center justify-center text-center transition-all">
                                <i
                                    class="bi bi-cloud-arrow-up text-4xl text-slate-300 peer-hover:text-rose-400 mb-3 transition-colors"></i>
                                <p class="text-sm font-bold text-slate-600 peer-hover:text-rose-700">Klik untuk upload file
                                    baru</p>
                                <p class="text-xs text-slate-400 mt-1">JPG/PNG, Max 2MB</p>
                                <p id="file-name-display"
                                    class="mt-3 text-xs font-medium text-rose-600 bg-rose-50 px-3 py-1 rounded hidden peer-focus:inline-block">
                                </p>
                            </div>
                        </div>

                        <!-- Preview Area (Full Width) -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Preview Kop
                                    Surat</label>
                                @if($satpen->kop_surat_path)
                                    <span
                                        class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">TERPASANG</span>
                                @endif
                            </div>

                            <div
                                class="bg-slate-100 border border-slate-200 rounded-xl p-4 flex items-center justify-center min-h-[120px] shadow-inner">
                                @if($satpen->kop_surat_path)
                                    <img src="{{ Storage::url($satpen->kop_surat_path) }}" alt="Preview Kop Surat"
                                        class="w-full h-auto object-contain rounded-lg shadow-sm bg-white">
                                @else
                                    <div class="text-center text-slate-400">
                                        <i class="bi bi-image-alt text-3xl mb-2 block opacity-50"></i>
                                        <span class="text-xs">Belum ada kop surat</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. Bantuan Card -->
                <div
                    class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl shadow-lg border border-teal-100 p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-teal-200/20 rounded-full blur-3xl -mr-16 -mt-16"></div>

                    <h3 class="font-bold text-teal-900 text-lg mb-4 flex items-center gap-2 relative z-10">
                        <i class="bi bi-life-preserver text-teal-600"></i>
                        Pusat Bantuan
                    </h3>
                    <div class="space-y-4 relative z-10">
                        <div class="bg-white/60 p-4 rounded-xl border border-white/50 shadow-sm">
                            <strong class="text-teal-900 block text-sm mb-1">Data Siswa Awal</strong>
                            <p class="text-teal-700 text-xs leading-relaxed">Wajib diisi di awal tahun ajaran. Data ini
                                menjadi "Saldo Awal" untuk perhitungan mutasi bulanan (Saldo Awal + Masuk - Keluar = Akhir).
                            </p>
                        </div>
                        <div class="bg-white/60 p-4 rounded-xl border border-white/50 shadow-sm">
                            <strong class="text-teal-900 block text-sm mb-1">Upload Kop Surat</strong>
                            <p class="text-teal-700 text-xs leading-relaxed">Gunakan gambar resolusi tinggi (min width:
                                1000px) agar tidak pecah saat dicetak di PDF ukuran A4.</p>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection