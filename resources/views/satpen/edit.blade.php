@extends('layouts.app')

@section('title', 'Edit Satuan Pendidikan')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('satpen.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Satuan Pendidikan</h1>
                <p class="text-gray-500 mt-1">{{ $satpen->nama }}</p>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-xs border p-6">
            <form action="{{ route('satpen.update', $satpen->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- NPSN -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NPSN <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="npsn" required maxlength="8" pattern="[0-9A-Za-z]{8}"
                            value="{{ old('npsn', $satpen->npsn) }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden @error('npsn') border-red-500 @enderror"
                            placeholder="8 digit NPSN">
                        @error('npsn')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nama Sekolah -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="nama" required maxlength="150" value="{{ old('nama', $satpen->nama) }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden @error('nama') border-red-500 @enderror"
                            placeholder="contoh: SMP Negeri 1 Bogor">
                        @error('nama')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bentuk Pendidikan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bentuk Pendidikan <span
                                class="text-red-500">*</span></label>
                        <select name="bentuk_pendidikan" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                            <option value="SMP" {{ old('bentuk_pendidikan', $satpen->bentuk_pendidikan) == 'SMP' ? 'selected' : '' }}>SMP</option>
                            <option value="SMP SPK" {{ old('bentuk_pendidikan', $satpen->bentuk_pendidikan) == 'SMP SPK' ? 'selected' : '' }}>SMP SPK</option>
                            <option value="SMP Terbuka" {{ old('bentuk_pendidikan', $satpen->bentuk_pendidikan) == 'SMP Terbuka' ? 'selected' : '' }}>SMP Terbuka</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status <span
                                class="text-red-500">*</span></label>
                        <select name="status_sp" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                            <option value="Negeri" {{ old('status_sp', $satpen->status_sp) == 'Negeri' ? 'selected' : '' }}>
                                Negeri</option>
                            <option value="Swasta" {{ old('status_sp', $satpen->status_sp) == 'Swasta' ? 'selected' : '' }}>
                                Swasta</option>
                        </select>
                    </div>
                </div>

                <!-- Alamat Section -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Alamat</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Alamat Jalan -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Jalan</label>
                            <textarea name="alamat_jalan" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                                placeholder="Jalan, nomor, RT/RW">{{ old('alamat_jalan', $satpen->alamat_jalan) }}</textarea>
                        </div>

                        <!-- Kelurahan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan</label>
                            <input type="text" name="kelurahan" maxlength="50"
                                value="{{ old('kelurahan', $satpen->kelurahan) }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                                placeholder="contoh: Paledang">
                        </div>

                        <!-- Kecamatan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                            <input type="text" name="kecamatan" maxlength="50"
                                value="{{ old('kecamatan', $satpen->kecamatan) }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                                placeholder="contoh: Bogor Tengah">
                        </div>

                        <!-- Kota -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kota <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="kota" required maxlength="50" value="{{ old('kota', $satpen->kota) }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden @error('kota') border-red-500 @enderror">
                            @error('kota')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Provinsi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="provinsi" required maxlength="50"
                                value="{{ old('provinsi', $satpen->provinsi) }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden @error('provinsi') border-red-500 @enderror">
                            @error('provinsi')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Sumber Data (is_local) -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Sumber Data</h3>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="is_local" value="1" 
                                {{ old('is_local', $satpen->is_local) == 1 ? 'checked' : '' }}
                                class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                            <span class="text-sm font-medium text-gray-700">
                                <i class="bi bi-geo-alt text-green-600 mr-1"></i> Lokal (Kota Bogor)
                            </span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="is_local" value="0"
                                {{ old('is_local', $satpen->is_local) == 0 ? 'checked' : '' }}
                                class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                            <span class="text-sm font-medium text-gray-700">
                                <i class="bi bi-globe text-gray-600 mr-1"></i> Eksternal (Luar Kota Bogor)
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Kepala Sekolah Section -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="bi bi-person-badge text-indigo-600 mr-2"></i>Data Kepala Sekolah
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap & Gelar</label>
                            <input type="text" name="kepala_sekolah_nama"
                                value="{{ old('kepala_sekolah_nama', $satpen->kepala_sekolah_nama ?? '') }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                                placeholder="contoh: Dr. H. Budi Santoso, M.Pd.">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NIP</label>
                            <input type="text" name="kepala_sekolah_nip"
                                value="{{ old('kepala_sekolah_nip', $satpen->kepala_sekolah_nip ?? '') }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden font-mono"
                                placeholder="Isi (-) jika Non-ASN">
                        </div>
                    </div>
                </div>

                <!-- Jumlah Siswa Section -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="bi bi-people text-blue-600 mr-2"></i>Jumlah Siswa Awal
                    </h3>
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-700"><i class="bi bi-info-circle mr-1"></i> Data siswa awal digunakan sebagai saldo awal laporan mutasi bulanan.</p>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <!-- Kelas 7 -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-bold text-gray-700 mb-3 text-center">Kelas VII</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">L</label>
                                    <input type="number" name="jumlah_siswa_7_l" min="0"
                                        value="{{ old('jumlah_siswa_7_l', $satpen->jumlah_siswa_7_l ?? 0) }}"
                                        class="w-full px-2 py-2 border rounded-lg text-center focus:ring-2 focus:ring-blue-100 focus:border-blue-500 outline-hidden">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">P</label>
                                    <input type="number" name="jumlah_siswa_7_p" min="0"
                                        value="{{ old('jumlah_siswa_7_p', $satpen->jumlah_siswa_7_p ?? 0) }}"
                                        class="w-full px-2 py-2 border rounded-lg text-center focus:ring-2 focus:ring-pink-100 focus:border-pink-500 outline-hidden">
                                </div>
                            </div>
                        </div>
                        <!-- Kelas 8 -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-bold text-gray-700 mb-3 text-center">Kelas VIII</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">L</label>
                                    <input type="number" name="jumlah_siswa_8_l" min="0"
                                        value="{{ old('jumlah_siswa_8_l', $satpen->jumlah_siswa_8_l ?? 0) }}"
                                        class="w-full px-2 py-2 border rounded-lg text-center focus:ring-2 focus:ring-blue-100 focus:border-blue-500 outline-hidden">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">P</label>
                                    <input type="number" name="jumlah_siswa_8_p" min="0"
                                        value="{{ old('jumlah_siswa_8_p', $satpen->jumlah_siswa_8_p ?? 0) }}"
                                        class="w-full px-2 py-2 border rounded-lg text-center focus:ring-2 focus:ring-pink-100 focus:border-pink-500 outline-hidden">
                                </div>
                            </div>
                        </div>
                        <!-- Kelas 9 -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-bold text-gray-700 mb-3 text-center">Kelas IX</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">L</label>
                                    <input type="number" name="jumlah_siswa_9_l" min="0"
                                        value="{{ old('jumlah_siswa_9_l', $satpen->jumlah_siswa_9_l ?? 0) }}"
                                        class="w-full px-2 py-2 border rounded-lg text-center focus:ring-2 focus:ring-blue-100 focus:border-blue-500 outline-hidden">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">P</label>
                                    <input type="number" name="jumlah_siswa_9_p" min="0"
                                        value="{{ old('jumlah_siswa_9_p', $satpen->jumlah_siswa_9_p ?? 0) }}"
                                        class="w-full px-2 py-2 border rounded-lg text-center focus:ring-2 focus:ring-pink-100 focus:border-pink-500 outline-hidden">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kop Surat Section -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="bi bi-envelope-paper text-rose-600 mr-2"></i>Kop Surat Sekolah
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Kop Surat Baru</label>
                            <input type="file" name="kop_surat" accept="image/*"
                                class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-lg file:border-0
                                    file:text-sm file:font-medium
                                    file:bg-rose-50 file:text-rose-700
                                    hover:file:bg-rose-100
                                    cursor-pointer border border-dashed border-gray-300 rounded-lg p-2">
                            <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG. Maks 2MB</p>
                        </div>
                        <div>
                            @if($satpen->kop_surat_path ?? false)
                                <label class="block text-sm font-medium text-gray-700 mb-1">Preview Terpasang</label>
                                <img src="{{ Storage::url($satpen->kop_surat_path) }}" alt="Kop Surat"
                                    class="w-full h-auto rounded-lg shadow-xs border">
                            @else
                                <div class="flex items-center justify-center h-full bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                    <p class="text-sm text-gray-400"><i class="bi bi-image mr-1"></i> Belum ada kop surat</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('satpen.index') }}"
                        class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-save mr-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>@endsection