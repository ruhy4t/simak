@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('dinas.users') }}" class="p-2 hover:bg-gray-100 rounded-lg transition">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Pengguna</h1>
                <p class="text-gray-500">{{ $targetUser->username }}</p>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form action="{{ route('dinas.users.update', $targetUser->id) }}" method="POST" class="space-y-5"
                x-data="{ role: '{{ old('role', $targetUser->role) }}' }">
                @csrf
                @method('PUT')

                <!-- Username (read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" value="{{ $targetUser->username }}" disabled
                        class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-600">
                    <p class="text-xs text-gray-500 mt-1">Username tidak dapat diubah</p>
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select name="role" x-model="role" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none @error('role') border-red-500 @enderror">
                        @foreach($availableRoles as $role)
                            <option value="{{ $role }}" {{ old('role', $targetUser->role) == $role ? 'selected' : '' }}>
                                {{ $role }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama_pengguna" value="{{ old('nama_pengguna', $targetUser->nama_pengguna) }}"
                        required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none @error('nama_pengguna') border-red-500 @enderror">
                    @error('nama_pengguna')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- NPSN & Nama Sekolah (for Sekolah/Operator only) -->
                <template x-if="role !== 'Dinas'">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NPSN *</label>
                            <div class="relative">
                                <input type="text" name="npsn" id="npsnInput" maxlength="8"
                                    value="{{ old('npsn', $targetUser->npsn) }}"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none @error('npsn') border-red-500 @enderror"
                                    oninput="lookupNpsn(this.value)">
                                <span id="npsnStatus" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm"></span>
                            </div>
                            @error('npsn')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah *</label>
                            <input type="text" name="nama_sekolah" id="namaSekolahInput"
                                value="{{ old('nama_sekolah', $targetUser->nama_sekolah) }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none @error('nama_sekolah') border-red-500 @enderror">
                            @error('nama_sekolah')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </template>

                <!-- Telegram Chat ID (optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telegram Chat ID</label>
                    <input type="text" name="telegram_chat_id"
                        value="{{ old('telegram_chat_id', $targetUser->telegram_chat_id) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                        placeholder="Opsional - untuk notifikasi Telegram">
                    <p class="text-xs text-gray-500 mt-1">Bisa diperoleh dengan mengirim /start ke bot Telegram</p>
                </div>

                <!-- Status Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Status Akun:</span>
                        @if($targetUser->is_active)
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Non-Aktif</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-sm mt-2">
                        <span class="text-gray-600">Terakhir Diupdate:</span>
                        <span
                            class="text-gray-900">{{ $targetUser->updated_at ? $targetUser->updated_at->format('d/m/Y H:i') : '-' }}</span>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-4">
                    <a href="{{ route('dinas.users') }}"
                        class="flex-1 px-4 py-2.5 border rounded-lg hover:bg-gray-50 transition font-medium text-center">
                        Batal
                    </a>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-check-lg mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
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