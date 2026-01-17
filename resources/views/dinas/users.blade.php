@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Pengguna</h1>
                <p class="text-gray-500 mt-1">Kelola akun Sekolah dan Operator</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                <i class="bi bi-plus-lg mr-2"></i>
                Tambah Pengguna
            </button>
        </div>

        <!-- Stats per Role -->
        <div class="grid grid-cols-1 md:grid-cols-{{ count($availableRoles ?? ['Sekolah', 'Operator']) + 1 }} gap-4">
            <div class="bg-white rounded-xl shadow-sm border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="bi bi-people text-gray-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Pengguna</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalUsers }}</p>
                    </div>
                </div>
            </div>
            @if(in_array('Dinas', $availableRoles ?? []))
            <div class="bg-purple-50 rounded-xl border border-purple-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-purple-800">Role Dinas</span>
                    <span class="text-lg font-bold text-purple-900">{{ $byRole['Dinas'] ?? 0 }}</span>
                </div>
                <div class="flex gap-4 text-xs">
                    <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>{{ $activeByRole['Dinas'] ?? 0 }}</span>
                    <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>{{ $inactiveByRole['Dinas'] ?? 0 }}</span>
                </div>
            </div>
            @endif
            <div class="bg-blue-50 rounded-xl border border-blue-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-blue-800">Role Sekolah</span>
                    <span class="text-lg font-bold text-blue-900">{{ $byRole['Sekolah'] ?? 0 }}</span>
                </div>
                <div class="flex gap-4 text-xs">
                    <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>{{ $activeByRole['Sekolah'] ?? 0 }}</span>
                    <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>{{ $inactiveByRole['Sekolah'] ?? 0 }}</span>
                </div>
            </div>
            <div class="bg-green-50 rounded-xl border border-green-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-green-800">Role Operator</span>
                    <span class="text-lg font-bold text-green-900">{{ $byRole['Operator'] ?? 0 }}</span>
                </div>
                <div class="flex gap-4 text-xs">
                    <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>{{ $activeByRole['Operator'] ?? 0 }}</span>
                    <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>{{ $inactiveByRole['Operator'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <form action="{{ route('dinas.users') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <div class="relative">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                            placeholder="Cari NPSN, username, nama, atau sekolah...">
                    </div>
                </div>
                <div class="w-full md:w-48">
                    <select name="role" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                        <option value="">Semua Role</option>
                        @foreach($availableRoles ?? ['Sekolah', 'Operator'] as $role)
                            <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium">
                    <i class="bi bi-filter mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'role']))
                    <a href="{{ route('dinas.users') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sekolah</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $u)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-medium text-primary-600">{{ $u->username }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $u->nama_pengguna }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $roleColors = [
                                            'Dinas' => 'bg-purple-100 text-purple-800',
                                            'Sekolah' => 'bg-blue-100 text-blue-800',
                                            'Operator' => 'bg-green-100 text-green-800',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $roleColors[$u->role] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $u->role }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    @if($u->npsn)
                                        <span class="text-xs text-gray-400">{{ $u->npsn }}</span><br>
                                        {{ Str::limit($u->nama_sekolah, 25) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($u->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('dinas.users.edit', $u->id) }}" 
                                            class="p-1.5 text-gray-500 hover:text-primary-600 rounded hover:bg-primary-50" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('dinas.users.toggle', $u->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-gray-500 hover:text-gray-700 rounded hover:bg-gray-100" title="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="bi {{ $u->is_active ? 'bi-toggle-on text-green-600' : 'bi-toggle-off' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('dinas.users.reset', $u->id) }}" method="POST" class="inline" onsubmit="return confirm('Reset password untuk {{ $u->nama_pengguna }}?')">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-gray-500 hover:text-yellow-600 rounded hover:bg-yellow-50" title="Reset Password">
                                                <i class="bi bi-key"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                    <i class="bi bi-inbox text-4xl text-gray-300"></i>
                                    <p class="mt-2">Tidak ada data</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($users->hasPages())
                <div class="px-4 py-3 border-t bg-gray-50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addModal').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Pengguna Baru</h3>
                    <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <form action="{{ route('dinas.users.store') }}" method="POST" class="space-y-4" x-data="{ role: 'Operator' }">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select name="role" x-model="role" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            <option value="Operator">Operator</option>
                            <option value="Sekolah">Sekolah</option>
                            @if(auth()->user()->role === 'SuperAdmin')
                            <option value="Dinas">Dinas</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                        <input type="text" name="username" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="nama_pengguna" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                    </div>
                    
                    <!-- NPSN & Nama Sekolah hanya untuk Sekolah/Operator -->
                    <template x-if="role !== 'Dinas'">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NPSN *</label>
                                <div class="relative">
                                    <input type="text" name="npsn" id="npsnInput" maxlength="8" 
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                                        oninput="lookupNpsn(this.value)">
                                    <span id="npsnStatus" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah *</label>
                                <input type="text" name="nama_sekolah" id="namaSekolahInput"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            </div>
                        </div>
                    </template>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                        <i class="bi bi-info-circle mr-1"></i>
                        Password akan digenerate otomatis.
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2.5 border rounded-lg hover:bg-gray-50 transition font-medium">Batal</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let lookupTimeout;
        function lookupNpsn(npsn) {
            const statusEl = document.getElementById('npsnStatus');
            const namaEl = document.getElementById('namaSekolahInput');
            
            if (npsn.length < 8) {
                statusEl.innerHTML = '';
                return;
            }
            
            clearTimeout(lookupTimeout);
            statusEl.innerHTML = '<i class="bi bi-hourglass-split text-gray-400"></i>';
            
            lookupTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`{{ url('/dinas/pengguna/npsn') }}/${npsn}`);
                    const data = await response.json();
                    
                    if (data.found) {
                        namaEl.value = data.nama_sekolah;
                        statusEl.innerHTML = '<i class="bi bi-check-circle text-green-600"></i>';
                    } else {
                        statusEl.innerHTML = '<span class="text-yellow-600 text-xs">Bukan sekolah Kota Bogor</span>';
                    }
                } catch (error) {
                    statusEl.innerHTML = '<i class="bi bi-exclamation-circle text-red-500"></i>';
                }
            }, 500);
        }
    </script>
@endsection