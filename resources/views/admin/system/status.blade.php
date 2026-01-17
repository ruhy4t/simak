@extends('layouts.app')

@section('title', 'Status Sistem')

@section('content')
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
            <div
                class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="bi bi-hdd-network text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Status Sistem</h1>
                <p class="text-gray-500">Informasi penggunaan resource dan penyimpanan</p>
            </div>
            <div class="ml-auto">
                <a href="{{ route('system.status.refresh') }}"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3 animate-fade-in">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="bi bi-check-lg text-green-600 text-xl"></i>
            </div>
            <span class="text-green-800 font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Database Card --}}
        <div
            class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="bi bi-database text-6xl text-blue-600"></i>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Ukuran Database</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $dbSize }} <span
                        class="text-lg text-gray-500 font-normal">MB</span></h3>
                <div class="mt-4 flex items-center gap-2 text-xs text-blue-600 bg-blue-50 w-fit px-2 py-1 rounded-lg">
                    <i class="bi bi-check-circle-fill"></i> MySQL / MariaDB
                </div>
            </div>
        </div>

        {{-- B2 Files Card --}}
        <div
            class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="bi bi-files text-6xl text-red-600"></i>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Total File (B2)</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ number_format($b2Stats['count']) }} <span
                        class="text-lg text-gray-500 font-normal">files</span></h3>
                @if($b2Stats['error'])
                    <div class="mt-4 text-xs text-red-600 bg-red-50 w-fit px-2 py-1 rounded-lg">
                        Error: {{ Str::limit($b2Stats['error'], 20) }}
                    </div>
                @else
                    <div class="mt-4 flex items-center gap-2 text-xs text-red-600 bg-red-50 w-fit px-2 py-1 rounded-lg">
                        <i class="bi bi-cloud-check-fill"></i> Backblaze B2
                    </div>
                @endif
            </div>
        </div>

        {{-- B2 Size Card --}}
        <div
            class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="bi bi-cloud-arrow-up text-6xl text-green-600"></i>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Space Terpakai (B2)</p>
                <h3 class="text-3xl font-bold text-gray-800">
                    {{ $b2Stats['formatted_size'] }}
                </h3>
                @if(isset($b2Stats['size_warning']))
                    <p class="text-xs text-orange-500 mt-1">{{ $b2Stats['size_warning'] }}</p>
                @endif
                <div class="mt-4 flex items-center gap-2 text-xs text-green-600 bg-green-50 w-fit px-2 py-1 rounded-lg">
                    <i class="bi bi-hdd-fill"></i> Cloud Storage
                </div>
            </div>
        </div>
    </div>

    {{-- Details Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-semibold text-gray-800">Detail Penyimpanan</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Tipe Storage</th>
                            <th class="px-4 py-3">Lokasi</th>
                            <th class="px-4 py-3">Koneksi</th>
                            <th class="px-4 py-3 rounded-r-lg">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">Dokumen & Arsip</td>
                            <td class="px-4 py-3 text-gray-600">Backblaze B2 (Cloud)</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">
                                {{ config('filesystems.disks.b2.bucket') ?? 'Not Configured' }}</td>
                            <td class="px-4 py-3">
                                @if(!$b2Stats['error'])
                                    <span
                                        class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Connected</span>
                                @else
                                    <span
                                        class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">Error</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">Database Utama</td>
                            <td class="px-4 py-3 text-gray-600">MySQL / MariaDB</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">
                                {{ config('database.connections.mysql.database') }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Connected</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection