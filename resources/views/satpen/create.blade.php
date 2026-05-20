@extends('layouts.app')

@section('title', 'Tambah Satuan Pendidikan')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('satpen.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tambah Satuan Pendidikan</h1>
                <p class="text-gray-500 mt-1">Tambahkan sekolah baru ke database</p>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-xs border p-6">
            <form action="{{ route('satpen.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- NPSN -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NPSN <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="npsn" required maxlength="8" pattern="[0-9A-Za-z]{8}"
                            value="{{ old('npsn') }}"
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
                        <input type="text" name="nama" required maxlength="150" value="{{ old('nama') }}"
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
                            <option value="SMP" {{ old('bentuk_pendidikan') == 'SMP' ? 'selected' : '' }}>SMP</option>
                            <option value="SMP SPK" {{ old('bentuk_pendidikan') == 'SMP SPK' ? 'selected' : '' }}>SMP SPK
                            </option>
                            <option value="SMP Terbuka" {{ old('bentuk_pendidikan') == 'SMP Terbuka' ? 'selected' : '' }}>SMP
                                Terbuka</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status <span
                                class="text-red-500">*</span></label>
                        <select name="status_sp" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                            <option value="Negeri" {{ old('status_sp') == 'Negeri' ? 'selected' : '' }}>Negeri</option>
                            <option value="Swasta" {{ old('status_sp', 'Swasta') == 'Swasta' ? 'selected' : '' }}>Swasta
                            </option>
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
                                placeholder="Jalan, nomor, RT/RW">{{ old('alamat_jalan') }}</textarea>
                        </div>

                        <!-- Kelurahan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan</label>
                            <input type="text" name="kelurahan" maxlength="50" value="{{ old('kelurahan') }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                                placeholder="contoh: Paledang">
                        </div>

                        <!-- Kecamatan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                            <input type="text" name="kecamatan" maxlength="50" value="{{ old('kecamatan') }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                                placeholder="contoh: Bogor Tengah">
                        </div>

                        <!-- Kota (LOCKED to Kota Bogor) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kota <span
                                    class="text-red-500">*</span></label>
                            <input type="text" value="Kota Bogor" readonly
                                class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                            <input type="hidden" name="kota" value="Kota Bogor">
                            <p class="text-xs text-gray-500 mt-1">Fitur ini hanya untuk SMP binaan Kota Bogor</p>
                        </div>

                        <!-- Provinsi (LOCKED to Jawa Barat) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span
                                    class="text-red-500">*</span></label>
                            <input type="text" value="Jawa Barat" readonly
                                class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                            <input type="hidden" name="provinsi" value="Jawa Barat">
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
                        <i class="bi bi-save mr-2"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>@endsection