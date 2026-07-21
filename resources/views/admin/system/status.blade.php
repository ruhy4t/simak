@extends('layouts.app')

@section('title', 'Status Sistem')

@section('content')
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
            <div
                class="w-14 h-14 bg-linear-to-br from-indigo-500 to-indigo-700 rounded-2xl flex items-center justify-center shadow-lg">
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Database Card --}}
        <div
            class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="bi bi-database text-6xl text-blue-600"></i>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Database Aiven Terpakai</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $dbStats['formatted_size'] }}</h3>

                <div class="mt-3 mb-2">
                    <p class="text-xs text-gray-400">Host:</p>
                    <p class="text-xs font-mono text-gray-600 truncate" title="{{ $serverInfo['db_host'] }}">
                        {{ $serverInfo['db_host'] }}</p>
                </div>

                <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2 overflow-hidden">
                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ min($dbStats['percentage'], 100) }}%"></div>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400">
                    <span>{{ number_format($dbStats['percentage'], 2) }}% dari {{ $dbStats['formatted_limit'] }}</span>
                    <span>Sisa: {{ $dbStats['formatted_remaining'] }}</span>
                </div>
                @if($dbStats['error'])
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $dbStats['error'] }}</p>
                @endif
            </div>
        </div>

        {{-- B2 Files Card --}}
        <div
            class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="bi bi-files text-6xl text-red-600"></i>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-1">Total File (B2)</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ number_format($b2Stats['count']) }} <span
                        class="text-lg text-gray-500 font-normal">file</span></h3>
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

        {{-- B2 Size Card with Progress --}}
        <div
            class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 relative overflow-hidden group hover:shadow-md transition-all md:col-span-2">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="bi bi-cloud-arrow-up text-6xl text-green-600"></i>
            </div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Storage Cloud Terpakai</p>
                        <h3 class="text-3xl font-bold text-gray-800">
                            {{ $b2Stats['formatted_size'] }}
                        </h3>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400">Sisa Batas Pemantauan</p>
                        <p class="text-lg font-semibold text-gray-700">{{ $b2Stats['formatted_remaining'] ?? '-' }}</p>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="w-full bg-gray-100 rounded-full h-3 mb-2 overflow-hidden">
                    <div class="bg-linear-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-1000"
                        style="width: {{ min($b2Stats['percentage'], 100) }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>{{ number_format($b2Stats['percentage'], 2) }}% Terpakai</span>
                    <span>Batas: {{ $b2Stats['formatted_limit'] }}</span>
                </div>
                <p class="mt-2 text-[11px] text-gray-400">Dihitung dari metadata seluruh objek aktif di bucket.</p>
            </div>
        </div>
    </div>

    {{-- Details Section Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- App Environment --}}
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="bi bi-server"></i> Lingkungan Aplikasi
                </h3>
                <span class="px-2 py-1 text-xs font-bold text-white bg-slate-800 rounded-sm uppercase tracking-wider">
                    Vercel
                </span>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between border-b border-dashed border-gray-100 pb-2">
                        <span class="text-gray-500 text-sm">Framework</span>
                        <span class="font-medium text-gray-800">Laravel v{{ $serverInfo['laravel_version'] }}</span>
                    </div>
                    <div class="flex justify-between border-b border-dashed border-gray-100 pb-2">
                        <span class="text-gray-500 text-sm">PHP Version</span>
                        <span class="font-medium text-gray-800">v{{ $serverInfo['php_version'] }}</span>
                    </div>
                    <div class="flex justify-between border-b border-dashed border-gray-100 pb-2">
                        <span class="text-gray-500 text-sm">Environment</span>
                        <span class="font-medium capitalize text-gray-800">
                            @if($serverInfo['environment'] === 'production')
                                <i class="bi bi-shield-check text-green-500"></i> Production
                            @else
                                {{ $serverInfo['environment'] }}
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between border-b border-dashed border-gray-100 pb-2">
                        <span class="text-gray-500 text-sm">Web Server</span>
                        <span class="font-medium text-gray-800 text-right text-xs truncate max-w-[200px]"
                            title="{{ $serverInfo['server_software'] }}">
                            {{ Str::limit($serverInfo['server_software'], 30) }}
                        </span>
                    </div>
                    <div class="flex justify-between pt-1">
                        <span class="text-gray-500 text-sm">Region</span>
                        <span
                            class="font-medium text-gray-800">{{ $serverInfo['region'] !== 'Unknown' ? $serverInfo['region'] : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Storage Details --}}
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="bi bi-hdd-stack"></i> Detail Penyimpanan
                </h3>
            </div>
            <div class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Komponen</th>
                                <th class="px-6 py-3">Lokasi (Host)</th>
                                <th class="px-6 py-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">Dokumen Arsip</div>
                                    <div class="text-xs text-gray-500">Backblaze B2</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500 tracking-tight">
                                    {{ config('filesystems.disks.b2.bucket') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if(!$b2Stats['error'])
                                        <span
                                            class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Active</span>
                                    @else
                                        <span
                                            class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">Error</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">Database</div>
                                    <div class="text-xs text-gray-500">MySQL {{ $serverInfo['db_version'] }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500 tracking-tight"
                                    title="{{ $serverInfo['db_host'] }}">
                                    {{ Str::limit($serverInfo['db_host'], 25) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Active</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-blue-50 px-6 py-3 border-t border-blue-100 text-xs text-blue-700">
                    <i class="bi bi-info-circle mr-1"></i> Database dihitung dari data dan indeks schema SIMAK dengan batas pemantauan {{ $dbStats['formatted_limit'] }}.
                    Storage B2 dihitung langsung dari metadata objek aktif di bucket dan diperbarui saat tombol Refresh digunakan.
                </div>
            </div>
        </div>

    </div>
@endsection
