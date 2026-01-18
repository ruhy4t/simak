@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Profil Sekolah</h1>
                <p class="text-slate-500 text-sm mt-1">Kelola data identitas dan pengaturan laporan sekolah.</p>
            </div>
            <span
                class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-semibold text-slate-700 shadow-sm">
                NPSN: {{ $satpen->npsn }}
            </span>
        </div>

        <form action="{{ route('sekolah.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main Content (Left) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- 1. Identitas Sekolah (Read Only) -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Identitas Sekolah</h2>
                            <i class="bi bi-building text-slate-400"></i>
                        </div>
                        <div class="p-6">
                            <div class="form-group">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah</label>
                                <input type="text" value="{{ $satpen->nama }}" disabled
                                    class="w-full bg-slate-100 border border-slate-300 rounded-lg px-3 py-2 text-slate-500 font-medium text-sm cursor-not-allowed">
                                <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
                                    <i class="bi bi-info-circle"></i> Nama sekolah tidak dapat diubah dari sini.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Data Siswa Awal (New Feature) -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Data Siswa Awal</h2>
                            <i class="bi bi-people text-slate-400"></i>
                        </div>
                        <div class="p-6">
                            <div class="mb-4 bg-blue-50 border border-blue-100 rounded-lg p-3">
                                <p class="text-xs text-blue-800 leading-relaxed">
                                    <i class="bi bi-info-circle-fill mr-1"></i>
                                    <strong>Penting:</strong> Masukkan jumlah siswa awal tahun ajaran. Data ini digunakan
                                    sebagai dasar perhitungan
                                    <strong>Jumlah Siswa Akhir</strong> di laporan (Jumlah Awal + Mutasi Masuk - Mutasi
                                    Keluar).
                                </p>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas 7</label>
                                    <input type="number" name="jumlah_siswa_awal_7" min="0"
                                        value="{{ old('jumlah_siswa_awal_7', $satpen->jumlah_siswa_awal_7 ?? 0) }}"
                                        class="w-full border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-center placeholder-slate-400">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas 8</label>
                                    <input type="number" name="jumlah_siswa_awal_8" min="0"
                                        value="{{ old('jumlah_siswa_awal_8', $satpen->jumlah_siswa_awal_8 ?? 0) }}"
                                        class="w-full border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-center placeholder-slate-400">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas 9</label>
                                    <input type="number" name="jumlah_siswa_awal_9" min="0"
                                        value="{{ old('jumlah_siswa_awal_9', $satpen->jumlah_siswa_awal_9 ?? 0) }}"
                                        class="w-full border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-center placeholder-slate-400">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Pengaturan Laporan -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Pengaturan Laporan PDF</h2>
                            <i class="bi bi-file-earmark-pdf text-slate-400"></i>
                        </div>
                        <div class="p-6 space-y-6">

                            <!-- Kop Surat -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Kop Surat Sekolah</label>
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <div class="relative group">
                                            <input type="file" id="kop_surat" name="kop_surat" accept="image/*" class="block w-full text-sm text-slate-500
                                                    file:mr-4 file:py-2 file:px-4
                                                    file:rounded-lg file:border-0
                                                    file:text-sm file:font-semibold
                                                    file:bg-slate-100 file:text-slate-700
                                                    hover:file:bg-slate-200
                                                    cursor-pointer border border-slate-300 rounded-lg p-1 bg-white">
                                        </div>
                                        <p class="mt-2 text-xs text-slate-500">
                                            Format: JPG/PNG, Max 2MB. Gambar akan menggantikan header standar di Laporan.
                                        </p>
                                    </div>
                                    @if($satpen->kop_surat_path)
                                        <div
                                            class="w-32 h-16 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-center p-1">
                                            <img src="{{ Storage::url($satpen->kop_surat_path) }}" alt="Preview"
                                                class="max-w-full max-h-full object-contain">
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <hr class="border-slate-100">

                            <!-- Kepala Sekolah -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Kepala
                                        Sekolah</label>
                                    <input type="text" name="kepala_sekolah_nama"
                                        value="{{ old('kepala_sekolah_nama', $satpen->kepala_sekolah_nama) }}"
                                        placeholder="Contoh: Dr. H. Budi Santoso, M.Pd."
                                        class="w-full border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-slate-400">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIP Kepala
                                        Sekolah</label>
                                    <div class="relative">
                                        <input type="text" name="kepala_sekolah_nip"
                                            value="{{ old('kepala_sekolah_nip', $satpen->kepala_sekolah_nip) }}"
                                            placeholder="NIP atau tanda -"
                                            class="w-full border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-slate-400">

                                        <!-- Tooltip / Help Text -->
                                        <div
                                            class="mt-2 p-2 bg-amber-50 border border-amber-100 rounded text-xs text-amber-800 font-medium">
                                            <i class="bi bi-exclamation-circle-fill mr-1"></i>
                                            Isi NIP jika ASN Aktif, jika bukan isi tanda min (-)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit"
                            class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center gap-2">
                            <i class="bi bi-save"></i>
                            Simpan Perubahan
                        </button>
                    </div>

                </div>

                <!-- Right Sidebar (Help) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sticky top-6">
                        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="bi bi-question-circle text-blue-500"></i>
                            Bantuan
                        </h3>
                        <div class="space-y-4 text-sm text-slate-600">
                            <div>
                                <strong class="text-slate-800 block mb-1">Data Siswa Awal</strong>
                                <p>Jumlah siswa pada awal tahun ajaran. Data ini krusial untuk rekapitulasi laporan bulanan.
                                </p>
                            </div>
                            <hr class="border-slate-100">
                            <div>
                                <strong class="text-slate-800 block mb-1">Kop Surat</strong>
                                <p>Upload gambar (JPG/PNG) lengkap dengan logo dan alamat sekolah untuk branding laporan
                                    otomatis.</p>
                            </div>
                            <hr class="border-slate-100">
                            <div>
                                <strong class="text-slate-800 block mb-1">Penandatangan</strong>
                                <p>Nama dan NIP Kepala Sekolah akan otomatis muncul di kolom tanda tangan dokumen.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection