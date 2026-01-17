@extends('layouts.app')

@section('title', 'Laporan Rekapitulasi')

@section('content')
    <div class="space-y-6">
        <!-- Header with Back Button -->
        <div class="flex items-center gap-4">
            <a href="{{ route('laporan.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition text-gray-600">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Laporan Rekapitulasi</h1>
                <p class="text-gray-500 mt-1">Statistik mutasi periode
                    {{ \Carbon\Carbon::parse($start_date)->translatedFormat('j F Y') }} -
                    {{ \Carbon\Carbon::parse($end_date)->translatedFormat('j F Y') }}
                </p>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form action="{{ route('laporan.rekap') }}" method="GET"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $start_date }}"
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $end_date }}"
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex-1 flex items-center justify-center gap-2">
                        <i class="bi bi-filter"></i> Terapkan
                    </button>
                </div>
                <div class="flex gap-2">
                    <!-- Print PDF Button -->
                    <a href="{{ route('laporan.rekap.print', ['start_date' => $start_date, 'end_date' => $end_date]) }}"
                        target="_blank"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex-1 flex items-center justify-center gap-2">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <!-- Export Excel Button -->
                    <a href="{{ route('laporan.rekap.excel', ['start_date' => $start_date, 'end_date' => $end_date]) }}"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex-1 flex items-center justify-center gap-2">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="font-semibold text-gray-900">Preview Data</h3>
                <span class="text-sm text-gray-500">{{ count($data) }} data</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b text-gray-600 font-medium">
                        <!-- First Header Row - Grouped -->
                        <tr class="border-b">
                            <th rowspan="2" class="px-4 py-2 w-12 text-center border-r">No</th>
                            @if(Auth::user()->role === 'Sekolah')
                                <th rowspan="2" class="px-4 py-2 border-r">Periode</th>
                            @else
                                <th rowspan="2" class="px-4 py-2 border-r">Nama Sekolah</th>
                            @endif
                            <th colspan="4" class="px-4 py-2 text-center bg-blue-50 text-blue-700 border-r">Mutasi Masuk
                            </th>
                            <th colspan="4" class="px-4 py-2 text-center bg-orange-50 text-orange-700 border-r">Mutasi
                                Keluar</th>
                            @if(Auth::user()->role !== 'Sekolah')
                                <th rowspan="2" class="px-4 py-2 text-center">Jumlah</th>
                            @endif
                        </tr>
                        <!-- Second Header Row - Kelas -->
                        <tr>
                            <th class="px-2 py-2 text-center bg-blue-50 text-blue-600 text-xs">VII</th>
                            <th class="px-2 py-2 text-center bg-blue-50 text-blue-600 text-xs">VIII</th>
                            <th class="px-2 py-2 text-center bg-blue-50 text-blue-600 text-xs">IX</th>
                            <th class="px-2 py-2 text-center bg-blue-50 text-blue-700 text-xs border-r font-bold">Total</th>
                            <th class="px-2 py-2 text-center bg-orange-50 text-orange-600 text-xs">VII</th>
                            <th class="px-2 py-2 text-center bg-orange-50 text-orange-600 text-xs">VIII</th>
                            <th class="px-2 py-2 text-center bg-orange-50 text-orange-600 text-xs">IX</th>
                            <th class="px-2 py-2 text-center bg-orange-50 text-orange-700 text-xs border-r font-bold">Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($data as $index => $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-center text-gray-500 border-r">{{ $index + 1 }}</td>
                                @if(Auth::user()->role === 'Sekolah')
                                    <td class="px-4 py-3 font-medium text-gray-900 border-r">{{ $item['periode'] }}</td>
                                @else
                                    <td class="px-4 py-3 font-medium text-gray-900 border-r text-xs">{{ $item['nama_sekolah'] }}
                                    </td>
                                @endif

                                <!-- Masuk per Kelas -->
                                <td class="px-2 py-3 text-center text-xs">{{ $item['masuk_vii'] ?: '-' }}</td>
                                <td class="px-2 py-3 text-center text-xs">{{ $item['masuk_viii'] ?: '-' }}</td>
                                <td class="px-2 py-3 text-center text-xs">{{ $item['masuk_ix'] ?: '-' }}</td>
                                <td class="px-2 py-3 text-center border-r">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-semibold">
                                        {{ $item['masuk'] }}
                                    </span>
                                </td>

                                <!-- Keluar per Kelas -->
                                <td class="px-2 py-3 text-center text-xs">{{ $item['keluar_vii'] ?: '-' }}</td>
                                <td class="px-2 py-3 text-center text-xs">{{ $item['keluar_viii'] ?: '-' }}</td>
                                <td class="px-2 py-3 text-center text-xs">{{ $item['keluar_ix'] ?: '-' }}</td>
                                <td class="px-2 py-3 text-center border-r">
                                    <span class="bg-orange-100 text-orange-800 px-2 py-0.5 rounded text-xs font-semibold">
                                        {{ $item['keluar'] }}
                                    </span>
                                </td>

                                @if(Auth::user()->role !== 'Sekolah')
                                    <td class="px-4 py-3 text-center">
                                        @if($item['netto'] > 0)
                                            <span class="text-green-600 font-semibold">+{{ $item['netto'] }}</span>
                                        @elseif($item['netto'] < 0)
                                            <span class="text-red-600 font-semibold">{{ $item['netto'] }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ Auth::user()->role === 'Sekolah' ? 10 : 11 }}"
                                    class="px-6 py-12 text-center text-gray-500">
                                    <i class="bi bi-inbox text-3xl block mb-2"></i>
                                    Tidak ada data mutasi pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>@endsection