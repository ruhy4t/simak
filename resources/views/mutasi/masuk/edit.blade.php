@extends('layouts.app')

@section('title', 'Edit Mutasi Masuk')

@section('content')
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('mutasi.masuk.show', $mutasi->id_mutasi) }}"
                class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-xs">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Edit Mutasi Masuk</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded-sm text-slate-600 font-mono">#{{ $mutasi->id_mutasi }}</span>
                    <span>•</span>
                    <span>{{ $mutasi->jumlah_siswa }} Murid</span>
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
        <form action="{{ route('mutasi.masuk.update', $mutasi->id_mutasi) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Data Pengajuan -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center">
                        <i class="bi bi-file-earmark-text-fill text-sky-600"></i>
                    </div>
                    Data Pengajuan
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Surat <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nomor_surat" value="{{ old('nomor_surat', $mutasi->nomor_surat) }}"
                            required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900 placeholder:text-slate-400">
                        @error('nomor_surat')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Surat</label>
                        <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat', $mutasi->tanggal_surat) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-900">
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
                            <input type="text" name="npsn_sekolah_tujuan" id="npsn_tujuan_input"
                                value="{{ old('npsn_sekolah_tujuan', $mutasi->npsn_sekolah_tujuan) }}" required
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900 placeholder:text-slate-400 uppercase font-mono"
                                maxlength="8" oninput="window.lookupNpsnTujuan(this.value)">
                            <div id="npsn_tujuan_status" class="absolute right-3 top-1/2 -translate-y-1/2"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Ketik 8 digit NPSN untuk mengisi otomatis.</p>
                        @error('npsn_sekolah_tujuan')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Tujuan <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="nama_sekolah_tujuan" id="nama_tujuan_input"
                            value="{{ old('nama_sekolah_tujuan', $mutasi->nama_sekolah_tujuan) }}" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                        @error('nama_sekolah_tujuan')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Tujuan</label>
                        <textarea name="alamat_sekolah_tujuan" id="alamat_tujuan_input" rows="2"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">{{ old('alamat_sekolah_tujuan', $mutasi->alamat_sekolah_tujuan) }}</textarea>
                    </div>

                    <!-- Optional Prov/City for Manual Input -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Provinsi Tujuan</label>
                        <input type="text" name="provinsi_tujuan" id="provinsi_tujuan_input"
                            value="{{ old('provinsi_tujuan', $mutasi->provinsi_tujuan) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kabupaten/Kota Tujuan</label>
                        <input type="text" name="kabupaten_kota_tujuan" id="kabupaten_kota_tujuan_input"
                            value="{{ old('kabupaten_kota_tujuan', $mutasi->kabupaten_kota_tujuan) }}"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:bg-white transition text-slate-900">
                    </div>
                </div>
            </div>

            <!-- Daftar Murid (Read Only) -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="bi bi-people-fill text-blue-600"></i>
                    </div>
                    Daftar Murid ({{ count($siswaList) }} murid)
                </h2>
                <div
                    class="bg-slate-50 rounded-xl p-4 text-sm text-slate-600 mb-6 border border-slate-100 flex items-start gap-3">
                    <i class="bi bi-info-circle-fill text-slate-400 mt-0.5 text-lg"></i>
                    <div>
                        Data murid tidak dapat diubah di halaman ini. Untuk mengubah data murid, silakan buat pengajuan
                        baru.
                    </div>
                </div>
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-slate-700">#</th>
                                <th class="px-6 py-4 font-semibold text-slate-700">Nama</th>
                                <th class="px-6 py-4 font-semibold text-slate-700">NISN</th>
                                <th class="px-6 py-4 font-semibold text-slate-700">Kelas</th>
                                <th class="px-6 py-4 font-semibold text-slate-700">Sekolah Asal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($siswaList as $idx => $siswa)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 text-slate-500">{{ $idx + 1 }}</td>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $siswa->nama_murid }}</td>
                                    <td class="px-6 py-4 text-slate-600 bg-slate-50 font-mono text-xs">{{ $siswa->nisn }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $siswa->kelas }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $siswa->nama_sekolah_asal }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4 sticky bottom-6 z-10">
                <a href="{{ route('mutasi.masuk.show', $mutasi->id_mutasi) }}"
                    class="px-6 py-3 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl transition font-medium shadow-xs">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition font-bold shadow-lg shadow-amber-500/20">
                    <i class="bi bi-save-fill mr-2"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="{{ asset('js/mutasi-masuk.js') }}?v={{ config('app.asset_version') }}"></script>
        <script>
            window.MUTASI_CONFIG = {
                routes: {
                    index: '{{ route('mutasi.masuk.index') }}',
                    apiNpsn: '{{ url('/mutasi-masuk/api/npsn') }}',
                    apiProvinces: '{{ route('mutasi.masuk.api.provinces') }}',
                    apiRegencies: '{{ route('mutasi.masuk.api.regencies', ['provinceId' => ':provinceId']) }}'
                },
                isDinasEntry: {{ auth()->user()->role === 'Dinas' || auth()->user()->role === 'SuperAdmin' ? 'true' : 'false' }} 
                                    };
        </script>
    @endpush@endsection
