@extends('layouts.app')

@section('title', 'Detail Wilayah - ' . $province->nama)

@section('content')
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.regions.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $province->nama }}</h1>
                <p class="text-gray-500 mt-1">Kode: {{ $province->id }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-4 border-b bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-gray-900">Daftar Kabupaten/Kota</h3>
                    <span
                        class="text-xs px-2 py-0.5 rounded-full bg-gray-200 text-gray-700">{{ $regencies->total() }}</span>
                </div>

                <form action="{{ route('admin.regions.show', $province->id) }}" method="GET"
                    class="relative w-full sm:w-64">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kab/Kota..."
                        class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </form>
            </div>

            @if($regencies->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700 w-24">Kode</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Nama Kabupaten/Kota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($regencies as $reg)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 font-mono">{{ $reg->id }}</td>
                                    <td class="px-4 py-3 text-gray-900">{{ str_replace('Kabupaten', 'Kab.', $reg->nama) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t">
                    {{ $regencies->withQueryString()->links() }}
                </div>
            @else
                <div class="p-8 text-center">
                    <p class="text-gray-500">Data tidak ditemukan.</p>
                    @if(request('search'))
                        <a href="{{ route('admin.regions.show', $province->id) }}"
                            class="text-primary-600 hover:underline text-sm">Reset Pencarian</a>
                    @endif
                </div>
            @endif
        </div>
    </div>@endsection