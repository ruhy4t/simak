@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Pengguna</h1>
                <p class="text-gray-500 mt-1">Kelola akun Operator di sekolah Anda</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                <i class="bi bi-plus-lg mr-2"></i>
                Tambah Operator
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-xs border p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="bi bi-people text-xl text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Operator</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalOperators }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-xs border p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="bi bi-person-check text-xl text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Aktif</p>
                        <p class="text-2xl font-bold text-green-600">{{ $activeOperators }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-xs border p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="bi bi-person-x text-xl text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Non-Aktif</p>
                        <p class="text-2xl font-bold text-red-600">{{ $inactiveOperators }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-xs border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontak</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($operators as $operator)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $operator->username }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $operator->nama_pengguna }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $operator->no_kontak ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($operator->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="bi bi-check-circle mr-1"></i> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="bi bi-x-circle mr-1"></i> Non-Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Toggle Status -->
                                        <form action="{{ route('users.toggle', $operator->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                class="px-3 py-1.5 text-xs rounded-lg transition {{ $operator->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}"
                                                onclick="return confirm('{{ $operator->is_active ? 'Nonaktifkan' : 'Aktifkan' }} operator ini?')">
                                                <i class="bi {{ $operator->is_active ? 'bi-person-x' : 'bi-person-check' }} mr-1"></i>
                                                {{ $operator->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <!-- Reset Password -->
                                        <form action="{{ route('users.reset', $operator->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                class="px-3 py-1.5 text-xs bg-yellow-100 text-yellow-700 hover:bg-yellow-200 rounded-lg transition"
                                                onclick="return confirm('Reset password untuk operator ini?')">
                                                <i class="bi bi-key mr-1"></i> Reset Password
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <i class="bi bi-people text-4xl text-gray-300"></i>
                                    <p class="mt-2">Belum ada Operator di sekolah ini</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Operator Modal -->
    <div id="addModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addModal').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Operator Baru</h3>
                    <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                        <input type="text" id="username" name="username" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                            placeholder="contoh: operator1">
                        <p class="text-xs text-gray-400 mt-1">Username akan digunakan untuk login</p>
                    </div>
                    <div>
                        <label for="nama_pengguna" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                        <input type="text" id="nama_pengguna" name="nama_pengguna" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                            placeholder="Nama lengkap operator">
                    </div>
                    <div>
                        <label for="no_kontak" class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                        <input type="text" id="no_kontak" name="no_kontak"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                            placeholder="08xxxxxxxxxx">
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                        <i class="bi bi-info-circle mr-1"></i>
                        Password akan digenerate otomatis dan ditampilkan setelah operator berhasil ditambahkan.
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2.5 border rounded-lg hover:bg-gray-50 transition font-medium">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection