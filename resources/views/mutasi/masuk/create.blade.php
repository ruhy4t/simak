@extends('layouts.app')

@section('title', isset($draft) && $draft ? 'Lanjutkan Draft - ' . $draft->id_mutasi : 'Buat Mutasi Masuk')

@section('content')
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('mutasi.masuk.index') }}"
                class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-all">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                @if(isset($draft) && $draft)
                    <h1 class="text-2xl font-bold text-slate-900">
                        <i class="bi bi-pencil-square text-amber-500 mr-2"></i>
                        Lanjutkan Draft
                    </h1>
                    <p class="text-slate-500 mt-1">Melanjutkan draft: <strong class="font-mono">{{ $draft->id_mutasi }}</strong></p>
                @else
                    <h1 class="text-2xl font-bold text-slate-900">Buat Mutasi Masuk</h1>
                    <p class="text-slate-500 mt-1">Input data siswa pindahan masuk ke sekolah Anda</p>
                @endif
            </div>
        </div>

        @if(isset($draft) && $draft)
        <!-- DEBUG: Draft Data -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <h3 class="font-bold text-amber-800 mb-2"><i class="bi bi-bug"></i> DEBUG: Draft dari Database</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                <div><strong>ID:</strong> {{ $draft->id_mutasi }}</div>
                <div><strong>Jumlah Siswa:</strong> {{ $draft->jumlah_siswa ?? 'N/A' }}</div>
                <div><strong>Nomor Surat:</strong> {{ $draft->nomor_surat ?? 'KOSONG' }}</div>
                <div><strong>Sekolah Tujuan:</strong> {{ $draft->nama_sekolah_tujuan ?? 'KOSONG' }}</div>
            </div>
        </div>
        @endif

        <!-- Form -->
        <form action="{{ route('mutasi.masuk.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6"
            id="formMutasiMasuk">
            @csrf
            @if(isset($draft) && $draft)
                <input type="hidden" name="draft_id" value="{{ $draft->id_mutasi }}">
            @endif
            @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                <input type="hidden" name="is_dinas_entry" value="1">
            @endif

            {{-- FAILSAFE ERROR DISPLAY: High Contrast & Raw Output --}}
            {{-- This helps users blocked by security.js to still see errors --}}
            @if ($errors->any() || session('error'))
                <div
                    style="background-color: #fee2e2; border: 2px solid #ef4444; color: #b91c1c; padding: 20px; border-radius: 12px; margin-bottom: 24px; font-weight: bold;">
                    <h3 style="font-size: 1.25rem; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                        ⚠️ TERJADI KESALAHAN (WAJIB DIBACA)
                    </h3>

                    @if(session('error'))
                        <div
                            style="margin-bottom: 15px; background: white; padding: 10px; border-radius: 6px; border-left: 4px solid #ef4444;">
                            <span style="display: block; font-size: 0.8em; text-transform: uppercase;">System Error:</span>
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <ul style="list-style-type: disc; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li style="margin-bottom: 4px;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            {{-- Validation Errors Display (Standard) --}}

            {{-- Session Success --}}
            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-check-circle-fill text-emerald-500 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-emerald-800">Berhasil!</h3>
                            <p class="text-sm text-emerald-700 mt-1">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Validation Errors Display --}}
            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 animate-shake">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-rose-800 mb-2">Mohon perbaiki kesalahan berikut:</h3>
                            <ul class="list-disc list-inside text-sm text-rose-700 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Header Surat -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center">
                        <i class="bi bi-envelope text-sky-600"></i>
                    </div>
                    Surat Permohonan Izin Mutasi Masuk
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jumlah Siswa *</label>
                        <input type="number" name="jumlah_siswa" id="jumlah_siswa" min="1" max="20"
                            value="{{ old('jumlah_siswa', $draft->jumlah_siswa ?? 1) }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400">
                        <p class="text-xs text-slate-500 mt-2"><i class="bi bi-info-circle mr-1"></i>Form siswa akan muncul
                            sesuai jumlah</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Surat *</label>
                        <input type="text" name="nomor_surat" value="{{ old('nomor_surat', $draft->nomor_surat ?? '') }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400"
                            placeholder="Nomor surat permohonan">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Surat *</label>
                        <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat', $draft->tanggal_surat ?? '') }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer">
                    </div>
                    <div class="md:col-span-3">
                        @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                            <label
                                class="flex items-center gap-4 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                                <input type="checkbox" name="has_surat_permohonan" value="1" required
                                    class="w-5 h-5 text-emerald-500 border-slate-300 rounded focus:ring-emerald-500 transition">
                                <div>
                                    <span class="font-semibold text-slate-700 group-hover:text-slate-900">Surat Permohonan Izin
                                        Mutasi Masuk</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Surat resmi dari Kepala Sekolah</p>
                                </div>
                            </label>
                        @else
                            <label class="block text-sm font-semibold text-slate-700 mb-2">File Surat Permohonan *</label>
                            <input type="file" name="file_surat_permohonan" accept=".pdf" required
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100"
                                onchange="validateFile(this, 1)">
                            <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                                <i class="bi bi-file-earmark-pdf"></i> PDF Maks 1MB
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sekolah Tujuan -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i class="bi bi-geo-alt text-emerald-600"></i>
                    </div>
                    @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                        Sekolah Tujuan
                    @else
                        Sekolah Tujuan (Sekolah Anda)
                    @endif
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))
                        <!-- Dinas/SuperAdmin: Bisa input manual / lookup -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Tujuan</label>
                            <div class="relative">
                                <input type="text" name="npsn_sekolah_tujuan" id="npsn_tujuan_input"
                                    value="{{ old('npsn_sekolah_tujuan') }}"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition duration-200"
                                    placeholder="Cari NPSN..." oninput="window.lookupNpsnTujuan(this.value)">
                                <span id="npsn_tujuan_status" class="absolute right-3 top-1/2 -translate-y-1/2"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Tujuan *</label>
                            <input type="text" name="nama_sekolah_tujuan" id="nama_tujuan_input"
                                value="{{ old('nama_sekolah_tujuan') }}" required
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition duration-200">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Tujuan *</label>
                            <textarea name="alamat_sekolah_tujuan" id="alamat_tujuan_input" rows="2" required
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition duration-200 resize-none">{{ old('alamat_sekolah_tujuan') }}</textarea>
                        </div>
                    @else
                        <!-- Non-Dinas: Readonly dengan data, Double-Binding Strategy for Robustness -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN *</label>
                            <input type="text" name="npsn_sekolah_tujuan" id="locked_npsn" value="{{ $npsnSekolahTujuan }}"
                                readonly
                                class="w-full px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-slate-500 cursor-not-allowed font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah *</label>
                            <input type="text" name="nama_sekolah_tujuan" id="locked_nama" value="{{ $namaSekolahTujuan }}"
                                readonly
                                class="w-full px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-slate-500 cursor-not-allowed">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat *</label>
                            <textarea name="alamat_sekolah_tujuan" id="locked_alamat" rows="2" readonly
                                class="w-full px-4 py-2.5 bg-slate-100 border-0 rounded-xl text-slate-500 cursor-not-allowed resize-none">{{ $alamatSekolahTujuan }}</textarea>
                        </div>

                        {{-- ROBUST DATA INJECTION SCRIPT --}}
                        {{-- Forces the value from server-side to client-side inputs, ignoring potentially empty HTML attributes
                        --}}
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                try {
                                    // Safely encode PHP variables to JS
                                    const schoolData = {
                                        npsn: @json($npsnSekolahTujuan ?? ''),
                                        nama: @json($namaSekolahTujuan ?? ''),
                                        alamat: @json($alamatSekolahTujuan ?? '')
                                    };

                                    // Force values into inputs
                                    const npsnInput = document.getElementById('locked_npsn');
                                    const namaInput = document.getElementById('locked_nama');
                                    const alamatInput = document.getElementById('locked_alamat');

                                    if (npsnInput && schoolData.npsn) npsnInput.value = schoolData.npsn;
                                    if (namaInput && schoolData.nama) namaInput.value = schoolData.nama;
                                    if (alamatInput && schoolData.alamat) alamatInput.value = schoolData.alamat;

                                    console.log('School Data Injected:', schoolData);
                                } catch (e) {
                                    console.error('Failed to inject school data:', e);
                                }
                            });
                        </script>
                    @endif
                </div>
            </div>

            <!-- Tab Navigation untuk Siswa -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <!-- Tab Header -->
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <i class="bi bi-people-fill text-indigo-600"></i>
                            </div>
                            Data Siswa
                        </h2>
                        <div id="siswaTabNav" class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                            <!-- Tabs akan di-generate oleh JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Tab Content -->
                <div id="siswaContainer" class="p-6 sm:p-8">
                    <!-- Form siswa akan di-generate oleh JavaScript -->
                </div>

                <!-- Navigation Buttons -->
                <div id="siswaNavButtons"
                    class="border-t border-slate-100 bg-slate-50 px-6 py-4 flex justify-between hidden">
                    <button type="button" id="prevSiswa"
                        class="px-5 py-2.5 border border-slate-200 bg-white text-slate-600 rounded-xl hover:bg-slate-50 hover:text-slate-800 transition font-medium">
                        <i class="bi bi-chevron-left mr-1"></i> Siswa Sebelumnya
                    </button>
                    <button type="button" id="nextSiswa"
                        class="px-5 py-2.5 bg-sky-500 text-white rounded-xl hover:bg-sky-600 transition font-medium shadow-lg shadow-sky-500/20">
                        Siswa Berikutnya <i class="bi bi-chevron-right ml-1"></i>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between gap-3 pt-4">
                <div class="text-sm text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-100">
                    Total Ukuran File: <span id="totalUploadSize" class="font-bold text-sky-600">0 Bytes</span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('mutasi.masuk.index') }}"
                        class="px-6 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-800 rounded-xl transition font-medium">
                        Batal
                    </a>
                    <button type="button" id="btnSaveDraft" onclick="saveDraftAndRedirect()"
                        class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition font-medium flex items-center shadow-lg shadow-amber-500/20">
                        <i class="bi bi-save mr-2"></i>
                        <span>Simpan Draft</span>
                    </button>
                    <button type="button" id="btnSubmitMasuk" onclick="showSubmittingModal()"
                        class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl transition font-medium flex items-center shadow-lg shadow-slate-900/20">
                        <i class="bi bi-send-fill mr-2"></i>
                        <span>Kirim Pengajuan</span>
                    </button>
                </div>
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
        // Hide loading modal if there are validation errors (page reloaded with errors)
        document.addEventListener('DOMContentLoaded', function () {
            @if($errors->any())
                const modal = document.getElementById('loadingModal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
                // Reset submitting flag so user can try again
                window.isSubmitting = false;
            @endif
                        });

        let isSubmitting = false;
        function showSubmittingModal() {
            if (isSubmitting) return;
            isSubmitting = true;

            // Show modal
            const modal = document.getElementById('loadingModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Submit form after showing modal
            setTimeout(() => {
                document.getElementById('formMutasiMasuk').submit();
            }, 100);
        }
    </script>

    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        window.MUTASI_CONFIG = {
            oldValues: @json(old('siswa', [])),
            oldJumlah: {{ old('jumlah_siswa', isset($draft) ? $draft->jumlah_siswa : 1) }},
            isDinasEntry: {{ in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) ? 'true' : 'false' }},
            draftId: @json(isset($draft) ? $draft->id_mutasi : null),
            routes: {
                index: '{{ route('mutasi.masuk.index') }}',
                apiNpsn: '{{ url('/mutasi-masuk/api/npsn') }}',
                apiProvinces: '{{ route('wilayah.provinces') }}',
                apiRegencies: '{{ route('wilayah.regencies', ['provinceId' => ':provinceId']) }}',
                createDraft: '{{ route('mutasi.masuk.draft.create') }}'
            },
            wilayahData: null // Loaded in JS file for optimization
        };
    </script>
<script src="{{ asset('js/mutasi-masuk.js') }}?v={{ time() }}"></script>@endpush