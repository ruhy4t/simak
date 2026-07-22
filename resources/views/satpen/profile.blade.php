@extends('layouts.app')

@section('content')
    @php($canEditStudentCounts = !$selectedAcademicYear || (!$selectedAcademicYear->isClosed() && !$closingCountsLocked))
    @php($canEditGrade7 = $canEditStudentCounts)
    <div class="max-w-full mx-auto space-y-8">

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900" role="status">
            <p class="font-bold"><i class="bi bi-exclamation-triangle-fill mr-2"></i>Prasyarat pengajuan mutasi</p>
            <p class="mt-1">Sekolah atau Operator dapat mengusulkan mutasi setelah profil sekolah, Data Murid Awal, dan Data Akhir tahun pelajaran aktif sudah lengkap.</p>
        </div>

        <!-- Header Page -->
        <div class="flex items-center justify-between border-b-2 border-slate-200 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Profil Sekolah</h1>
                <p class="text-slate-500 text-lg mt-1">Kelola data identitas, murid, dan kepala sekolah.</p>
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
            @if($selectedAcademicYear)
                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYear->id }}">
            @endif

            <!-- Explicit Grid Layout with Inline Styles to Bypass Missing CSS -->
            <div class="grid-container"
                style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; align-items: start;">
                <!-- LEFT COLUMN -->
                <div class="left-column" style="min-width: 0;">

                    <!-- 1. Identitas Sekolah (Read Only) -->
                    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center gap-3">
                            <div class="bg-white p-2 rounded-sm border border-slate-200 text-slate-500">
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

                    <!-- 2. Data Murid Awal (Blue Theme) -->
                    <div class="bg-white rounded-xl shadow-md border-t-4 border-blue-500 overflow-hidden">
                        <div class="px-6 py-4 bg-white border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-people-fill text-blue-600 text-xl"></i>
                                <h2 class="font-extrabold text-slate-800 text-lg uppercase tracking-wide">Data Murid Awal
                                </h2>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Year Selector -->
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-5">
                                <label class="block text-sm font-bold text-blue-900 mb-2">Tahun Pelajaran</label>
                                <select name="academic_year_selector"
                                    data-navigation-url="{{ route('sekolah.profile') }}"
                                    data-navigation-param="academic_year"
                                    class="w-full border-2 border-blue-300 bg-white text-blue-900 font-bold rounded-lg px-4 py-3 focus:outline-hidden focus:ring-4 focus:ring-blue-500/20 cursor-pointer text-lg">
                                    @forelse($academicYears as $academicYear)
                                        <option value="{{ $academicYear->name }}" @selected($selectedAcademicYear?->id === $academicYear->id)>
                                            {{ $academicYear->name }}
                                            @if($academicYear->is_active)
                                                (Aktif)
                                            @elseif($academicYear->status === 'upcoming')
                                                (Mendatang)
                                            @else
                                                (Ditutup)
                                            @endif
                                        </option>
                                    @empty
                                        <option value="2025/2026">2025/2026 (Data Lama)</option>
                                    @endforelse
                                </select>
                                <p class="text-xs text-blue-600 mt-2 font-medium">
                                    @if($closingCountsLocked)
                                        * Data awal tidak dapat diubah karena data akhir tahun ini sudah dikunci.
                                    @elseif($canEditStudentCounts)
                                        * Sekolah mengisi kelas VII. Kelas VIII dan IX berasal dari kenaikan kelas dan hanya dapat dikoreksi Dinas.
                                    @else
                                        * Tahun pelajaran ini sudah ditutup dan hanya dapat dilihat.
                                    @endif
                                </p>
                            </div>

                            <!-- Inputs -->
                            <div class="space-y-6">
                                <!-- Headers -->
                                <div
                                    class="grid grid-cols-12 gap-4 border-b border-blue-100 pb-2 text-center text-sm font-bold text-blue-900 uppercase">
                                    <div class="col-span-2 text-left pl-2">Kelas</div>
                                    <div class="col-span-5 bg-blue-100 rounded-lg py-1">Laki-laki</div>
                                    <div class="col-span-5 bg-pink-100 text-pink-700 rounded-lg py-1">Perempuan</div>
                                </div>

                                <!-- Kelas 7 -->
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-2 font-black text-slate-700 text-xl pl-2">VII</div>
                                    <div class="col-span-5">
                                        <input type="number" name="jumlah_siswa_7_l" min="0" required
                                            value="{{ old('jumlah_siswa_7_l', $studentCounts[7]['male']) }}"
                                            @readonly(!$canEditGrade7)
                                            class="w-full border-2 border-slate-300 bg-white focus:border-blue-500 focus:bg-blue-50 rounded-xl px-2 py-3 text-center text-lg font-bold text-slate-800 transition-all shadow-xs"
                                            placeholder="L">
                                    </div>
                                    <div class="col-span-5">
                                        <input type="number" name="jumlah_siswa_7_p" min="0" required
                                            value="{{ old('jumlah_siswa_7_p', $studentCounts[7]['female']) }}"
                                            @readonly(!$canEditGrade7)
                                            class="w-full border-2 border-slate-300 bg-white focus:border-pink-500 focus:bg-pink-50 rounded-xl px-2 py-3 text-center text-lg font-bold text-slate-800 transition-all shadow-xs"
                                            placeholder="P">
                                    </div>
                                </div>

                                <!-- Kelas 8 -->
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-2 font-black text-slate-700 text-xl pl-2">VIII</div>
                                    <div class="col-span-5 relative">
                                        <input type="number" name="jumlah_siswa_8_l" min="0" required
                                            value="{{ old('jumlah_siswa_8_l', $studentCounts[8]['male']) }}"
                                            readonly
                                            class="w-full border-2 rounded-xl px-2 py-3 text-center text-lg font-bold transition-all shadow-xs">
                                    </div>
                                    <div class="col-span-5 relative">
                                        <input type="number" name="jumlah_siswa_8_p" min="0" required
                                            value="{{ old('jumlah_siswa_8_p', $studentCounts[8]['female']) }}"
                                            readonly
                                            class="w-full border-2 rounded-xl px-2 py-3 text-center text-lg font-bold transition-all shadow-xs">
                                    </div>
                                </div>

                                <!-- Kelas 9 -->
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-2 font-black text-slate-700 text-xl pl-2">IX</div>
                                    <div class="col-span-5 relative">
                                        <input type="number" name="jumlah_siswa_9_l" min="0" required
                                            value="{{ old('jumlah_siswa_9_l', $studentCounts[9]['male']) }}"
                                            readonly
                                            class="w-full border-2 rounded-xl px-2 py-3 text-center text-lg font-bold transition-all shadow-xs">
                                    </div>
                                    <div class="col-span-5 relative">
                                        <input type="number" name="jumlah_siswa_9_p" min="0" required
                                            value="{{ old('jumlah_siswa_9_p', $studentCounts[9]['female']) }}"
                                            readonly
                                            class="w-full border-2 rounded-xl px-2 py-3 text-center text-lg font-bold transition-all shadow-xs">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-lg rounded-xl shadow-lg transform active:scale-95 transition-all flex items-center justify-center gap-2">
                        <i class="bi bi-check-circle-fill"></i> SIMPAN PERUBAHAN PROFIL
                    </button>

                    <a href="{{ route('sekolah.closing-counts') }}"
                        class="flex w-full items-center justify-between rounded-xl border-2 border-emerald-200 bg-emerald-50 p-5 text-emerald-900 transition hover:border-emerald-400 hover:bg-emerald-100">
                        <span>
                            <span class="block font-extrabold">Isi Data Akhir Aktual</span>
                            <span class="mt-1 block text-xs text-emerald-700">Rekonsiliasi kohort tanpa nama murid atau dokumen.</span>
                        </span>
                        <i class="bi bi-arrow-right-circle-fill text-2xl"></i>
                    </a>
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
                                <p class="text-sm text-blue-800 leading-relaxed"><strong>Data Murid Awal</strong> digunakan
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
                                <input type="file" name="kop_surat" accept=".jpg,.jpeg,image/jpeg"
                                    class="block w-full text-sm text-slate-500
                                                            file:mr-4 file:py-3 file:px-6
                                                            file:rounded-xl file:border-0
                                                            file:text-sm file:font-bold
                                                            file:bg-rose-50 file:text-rose-700
                                                            hover:file:bg-rose-100
                                                            cursor-pointer border-2 border-dashed border-rose-200 rounded-xl p-2 h-20 leading-10">
                                <p class="mt-2 text-xs font-medium text-slate-500">
                                    Format wajib JPG/JPEG, ukuran maksimal 2 MB. Gunakan gambar kop surat berbentuk lebar (landscape).
                                </p>
                            </div>

                            @if($satpen->kop_surat_path)
                                <div class="pt-4 border-t border-slate-100">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 text-center">
                                        Preview
                                        Terpasang</p>
                                    <img src="{{ \App\Services\FileUploadService::getStorageUrl($satpen->kop_surat_path) }}" alt="Preview"
                                        class="w-full h-auto rounded-lg shadow-xs border border-slate-200">
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection
