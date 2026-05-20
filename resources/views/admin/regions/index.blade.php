@extends('layouts.app')

@section('title', 'Data Wilayah')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Wilayah</h1>
                <p class="text-gray-500 mt-1">Kelola data referensi wilayah (Provinsi, Kota/Kabupaten, Kecamatan, Kelurahan)
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="$dispatch('open-modal', 'add-region-modal')"
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                    <i class="bi bi-plus-lg"></i> Tambah Manual
                </button>
                <form action="{{ route('admin.regions.sync') }}" method="POST"
                    onsubmit="return confirm('Proses ini akan mengambil data terbaru dari API dan memperbarui database lokal. Mungkin butuh waktu beberapa menit. Lanjutkan?');">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition flex items-center gap-2">
                        <i class="bi bi-cloud-arrow-down"></i> Sinkronisasi
                    </button>
                </form>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white p-4 rounded-xl shadow-xs border">
            <form action="{{ route('admin.regions.index') }}" method="GET" class="flex gap-2">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari nama provinsi atau kode..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                    Cari
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.regions.index') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 border">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-xs border overflow-hidden">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="font-semibold text-gray-900">Daftar Provinsi</h3>
                <span class="text-xs text-gray-500">Total: {{ $provinces->total() }} Data</span>
            </div>

            @if($provinces->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700 w-24">Kode</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Nama Provinsi</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700 w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($provinces as $prov)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 font-mono">{{ $prov->id }}</td>
                                    <td class="px-4 py-3 text-gray-900 font-medium">{{ $prov->nama }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('admin.regions.show', $prov->id) }}"
                                            class="text-primary-600 hover:text-primary-700 font-medium whitespace-nowrap">
                                            Lihat Kab/Kota
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t">
                    {{ $provinces->withQueryString()->links() }}
                </div>
            @else
                <div class="p-8 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                        <i class="bi bi-geo-alt text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Data Tidak Ditemukan</h3>
                    @if(request('search'))
                        <p class="text-gray-500 mt-1 mb-4">Tidak ada provinsi yang cocok dengan pencarian "{{ request('search') }}".
                        </p>
                        <a href="{{ route('admin.regions.index') }}" class="text-primary-600 hover:underline">Reset Pencarian</a>
                    @else
                        <p class="text-gray-500 mt-1 mb-4">Database wilayah masih kosong. Silakan lakukan sinkronisasi.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Tambah Manual (Alpine.js) -->
    <div x-data="{ show: false, type: 'provinsi' }" x-show="show"
        @open-modal.window="if ($event.detail === 'add-region-modal') show = true" @keydown.escape.window="show = false"
        style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">

        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity" aria-hidden="true"
                @click="show = false">
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-100">
                <form action="{{ route('admin.regions.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Tambah Data Wilayah
                        </h3>
                        <div class="mt-4 space-y-4">
                            <!-- Tipe Wilayah -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tingkat Wilayah</label>
                                <select name="type" x-model="type"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <option value="provinsi">Provinsi</option>
                                    <option value="kabupaten">Kabupaten/Kota</option>
                                    <option value="kecamatan">Kecamatan</option>
                                    <option value="kelurahan">Kelurahan</option>
                                </select>
                            </div>

                            <!-- ID Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kode Wilayah (ID)</label>
                                <input type="number" name="id" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    placeholder="Contoh: 32 (Jabar), 3271 (Bogor)">
                                <p class="text-xs text-gray-500 mt-1">Gunakan kode resmi Kemendagri (Angka).</p>
                            </div>

                            <!-- Parent ID -->
                            <div x-show="type !== 'provinsi'">
                                <label class="block text-sm font-medium text-gray-700">Kode Induk (Parent ID)</label>
                                <input type="number" name="parent_id" :required="type !== 'provinsi'"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    placeholder="Contoh: 32 (Jika input Kab/Kota)">
                            </div>

                            <!-- Nama -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nama Wilayah</label>
                                <input type="text" name="nama" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    placeholder="Contoh: Jawa Barat">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-xs px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Simpan
                        </button>
                        <button type="button" @click="show = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-xs px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>@endsection