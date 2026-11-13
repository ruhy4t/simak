@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
    <div x-data="dinasUserManagement"
        data-open-on-error="{{ $errors->any() ? 'true' : 'false' }}"
        data-initial-role="{{ old('role', 'Operator') }}"
        data-initial-school-name="{{ old('nama_sekolah') }}"
        data-lookup-url="{{ url('/dinas/pengguna/npsn') }}"
        @keydown.escape.window="closeAddModal">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Pengguna</h1>
                <p class="text-gray-500 mt-1">Kelola akun Sekolah dan Operator</p>
            </div>
            <button type="button" @click="openAddModal"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                <i class="bi bi-plus-lg mr-2"></i>
                Tambah Pengguna
            </button>
        </div>

        <!-- Stats per Role -->
        <div class="grid grid-cols-1 md:grid-cols-{{ count($availableRoles ?? ['Sekolah', 'Operator']) + 1 }} gap-4">
            <div class="bg-white rounded-xl shadow-xs border p-4">
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
        <div class="bg-white rounded-xl shadow-xs border p-4">
            <form action="{{ route('dinas.users') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <div class="relative">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                            placeholder="Cari NPSN, username, nama, atau sekolah...">
                    </div>
                </div>
                <div class="w-full md:w-48">
                    <select name="role" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
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
        <div class="bg-white rounded-xl shadow-xs border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sekolah</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tautan Telegram</th>
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
                                    <span class="inline-flex px-2 py-0.5 rounded-sm text-xs font-medium {{ $roleColors[$u->role] ?? 'bg-gray-100 text-gray-800' }}">
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

                                    @if($u->isLocked())
                                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-sm text-xs font-medium bg-red-50 text-red-600 border border-red-200" title="Terkunci: Salah password berkali-kali">
                                            <i class="bi bi-shield-lock-fill mr-1"></i> Locked
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if(filled($u->telegram_chat_id))
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                            Tertaut
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                            Belum tertaut
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- 1. Edit --}}
                                        <a href="{{ route('dinas.users.edit', $u->id) }}" 
                                            class="p-1.5 text-blue-600 hover:text-blue-800 rounded-sm hover:bg-blue-50 transition" title="Edit Data">
                                            <i class="bi bi-pencil-square text-lg"></i>
                                        </a>

                                        {{-- 2. Kelola Permission --}}
                                        <a href="{{ route('permissions.edit', $u->id) }}" 
                                            class="p-1.5 text-purple-600 hover:text-purple-800 rounded-sm hover:bg-purple-50 transition" title="Kelola Permission">
                                            <i class="bi bi-shield-lock text-lg"></i>
                                        </a>

                                        {{-- 3. Toggle Status (Active/Inactive) --}}
                                        <form action="{{ route('dinas.users.toggle', $u->id) }}" method="POST" class="inline"
                                            data-confirmation="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $u->nama_pengguna }}?"
                                            @submit="confirmAction">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-sm hover:bg-gray-100 transition" 
                                                title="{{ $u->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                                <i class="bi {{ $u->is_active ? 'bi-toggle-on text-green-600' : 'bi-toggle-off text-gray-400' }} text-2xl align-middle"></i>
                                            </button>
                                        </form>

                                        {{-- 3. Unlock (Only if Locked) --}}
                                        @if($u->isLocked())
                                            <form action="{{ route('dinas.users.unlock', $u->id) }}" method="POST" class="inline"
                                                data-confirmation="Buka blokir akun {{ $u->nama_pengguna }}? Pengguna akan bisa login lagi."
                                                @submit="confirmAction">
                                                @csrf
                                                <button type="submit" class="p-1.5 text-orange-500 hover:text-orange-700 rounded-sm hover:bg-orange-50 transition" title="Buka Blokir (Unlock)">
                                                    <i class="bi bi-unlock-fill text-lg"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- 4. Reset Password --}}
                                        <form action="{{ route('dinas.users.reset', $u->id) }}" method="POST" class="inline"
                                            data-confirmation="Reset password untuk {{ $u->nama_pengguna }}? Password baru akan dibuat."
                                            @submit="confirmAction">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-yellow-600 hover:text-yellow-800 rounded-sm hover:bg-yellow-50 transition" title="Reset Password">
                                                <i class="bi bi-key-fill text-lg"></i>
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
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
        aria-labelledby="add-user-title">
        <div class="flex items-center justify-center min-h-screen px-4">
            <button type="button" class="fixed inset-0 w-full bg-black/50" aria-label="Tutup modal"
                @click="closeAddModal"></button>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="add-user-title" class="text-lg font-semibold text-gray-900">Tambah Pengguna Baru</h3>
                    <button type="button" @click="closeAddModal" class="text-gray-400 hover:text-gray-600"
                        aria-label="Tutup modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <form action="{{ route('dinas.users.store') }}" method="POST" class="space-y-4" @submit="submitCreate">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select name="role" x-model="role" @change="changeRole" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                            <option value="Operator">Operator</option>
                            <option value="Sekolah">Sekolah</option>
                            @if(auth()->user()->role === 'SuperAdmin')
                            <option value="Dinas">Dinas</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                        <input type="text" name="username" value="{{ old('username') }}" required x-ref="username"
                            autocomplete="username" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                        @error('username')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="nama_pengguna" value="{{ old('nama_pengguna') }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                    </div>
                    
                    <!-- NPSN & Nama Sekolah hanya untuk Sekolah/Operator -->
                    <template x-if="role !== 'Dinas'">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NPSN *</label>
                                <div class="relative">
                                    <input type="text" name="npsn" maxlength="8" inputmode="numeric"
                                        value="{{ old('npsn') }}" :required="role !== 'Dinas'"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden"
                                        @input.debounce.500ms="lookupNpsn">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm">
                                        <i x-show="npsnStatus === 'loading'" class="bi bi-hourglass-split text-gray-400"></i>
                                        <i x-show="npsnStatus === 'found'" class="bi bi-check-circle text-green-600"></i>
                                        <span x-show="npsnStatus === 'not-found'" class="text-yellow-600 text-xs">Bukan sekolah Kota Bogor</span>
                                        <i x-show="npsnStatus === 'error'" class="bi bi-exclamation-circle text-red-500"></i>
                                    </span>
                                </div>
                                @error('npsn')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah *</label>
                                <input type="text" name="nama_sekolah" x-model="schoolName" :required="role !== 'Dinas'"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-hidden">
                            </div>
                        </div>
                    </template>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                        <i class="bi bi-info-circle mr-1"></i>
                        Password akan digenerate otomatis.
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="closeAddModal"
                            class="flex-1 px-4 py-2.5 border rounded-lg hover:bg-gray-50 transition font-medium">Batal</button>
                        <button type="submit" :disabled="submitting"
                            class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-60 disabled:cursor-not-allowed text-white rounded-lg transition font-medium">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection
