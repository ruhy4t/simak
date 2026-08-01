@extends('layouts.app')

@section('title', 'Laporan Daftar Nominatif')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Laporan Daftar Nominatif</h1>
                <p class="text-gray-500 mt-1">Daftar detail murid mutasi periode
                    {{ \Carbon\Carbon::parse($start_date)->translatedFormat('d M Y') }} -
                    {{ \Carbon\Carbon::parse($end_date)->translatedFormat('d M Y') }}.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('laporan.index') }}"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Kembali
                </a>
                <a href="{{ route('laporan.nominatif.excel', request()->all()) }}"
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 shadow-xs flex items-center gap-2">
                    <i class="bi bi-file-excel"></i> Excel
                </a>
            </div>
        </div>

        <form action="{{ route('laporan.nominatif.print') }}" method="POST" target="_blank"
                class="space-y-4 rounded-xl border bg-white p-6 shadow-xs">
                @csrf
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <input type="hidden" name="end_date" value="{{ $end_date }}">
                <input type="hidden" name="type" value="{{ $type }}">
                @include('laporan.partials.print-identity-fields')
                <div class="flex justify-end">
                    <button type="submit"
                        class="flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 font-medium text-white transition hover:bg-red-700">
                        <i class="bi bi-file-pdf"></i> Cetak PDF
                    </button>
                </div>
        </form>

        <!-- Filter -->
        <div class="bg-white rounded-xl shadow-xs border p-4">
            <form action="{{ route('laporan.nominatif') }}" method="GET"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $start_date }}"
                        class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $end_date }}"
                        class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Jenis Mutasi</label>
                    <select name="type" id="type"
                        class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500">
                        <option value="masuk" {{ $type == 'masuk' ? 'selected' : '' }}>Mutasi Masuk</option>
                        <option value="keluar" {{ $type == 'keluar' ? 'selected' : '' }}>Mutasi Keluar</option>
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-xs">
                        Tampilkan Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-xs border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                No</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                L/P</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Murid
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asal
                                Sekolah</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tujuan Sekolah</th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($data as $index => $item)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ $index + 1 }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-900">
                                                {{ $item->jenis_kelamin }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $item->nama_siswa }}</div>
                                                <div class="text-xs text-gray-500">NISN: {{ $item->nisn }} | Kelas: {{ $item->kelas }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $item->asal }}">
                                                {{ $item->asal }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $item->tujuan }}">
                                                {{ $item->tujuan }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                                {{ $item->status == 'Selesai' ? 'bg-green-100 text-green-800' :
                            ($item->status == 'Ditolak' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                    {{ $item->status }}
                                                </span>
                                            </td>
                                        </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="bi bi-inbox text-4xl mb-3 block"></i>
                                    Tidak ada data mutasi {{ $type }} pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200 bg-gray-50 text-xs text-gray-500 text-right">
                Total Data: {{ count($data) }}
            </div>
        </div>
</div>@endsection
