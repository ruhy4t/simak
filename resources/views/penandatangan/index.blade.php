@extends('layouts.app')

@section('title', 'Kelola Penandatangan')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Penandatangan</h1>
                <p class="text-gray-500 mt-1">Pejabat yang menandatangani surat mutasi</p>
            </div>
            
            @if(!isset($trash))
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                    <i class="bi bi-plus-lg mr-2"></i>
                    Tambah Penandatangan
                </button>
            @endif
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-6">
                <a href="{{ route('penandatangan.index') }}"
                    class="pb-4 px-1 border-b-2 font-medium text-sm {{ !isset($trash) ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="bi bi-person-lines-fill mr-1"></i> Data Aktif
                </a>
                <a href="{{ route('penandatangan.trash') }}"
                    class="pb-4 px-1 border-b-2 font-medium text-sm {{ isset($trash) ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="bi bi-trash mr-1"></i> Dihapus
                </a>
            </nav>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                {{-- TABLE START --}}
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                            @if(isset($trash))
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alasan Hapus</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Dihapus Pada</th>
                            @else
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            @endif
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($penandatangan as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $p->nama }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $p->nip ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $p->jabatan }}</td>
                                
                                @if(isset($trash))
                                    <td class="px-6 py-4 text-sm text-red-600 italic">"{{ $p->alasan_penghapusan ?? '-' }}"</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-500">{{ \Carbon\Carbon::parse($p->deleted_at)->translatedFormat('j M Y H:i') }}</td>
                                @else
                                    <td class="px-6 py-4">
                                        @if($p->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="bi bi-check-circle mr-1"></i> Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                <i class="bi bi-circle mr-1"></i> Non-Aktif
                                            </span>
                                        @endif
                                    </td>
                                @endif

                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if(isset($trash))
                                            {{-- RESTORE BUTTON --}}
                                            <form action="{{ route('penandatangan.restore', $p->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                    class="px-3 py-1.5 text-xs bg-green-100 text-green-700 hover:bg-green-200 rounded-lg transition"
                                                    onclick="return confirm('Kembalikan data ini ke daftar aktif?')">
                                                    <i class="bi bi-arrow-counterclockwise mr-1"></i> Restore
                                                </button>
                                            </form>
                                        @else
                                            {{-- TOGGLE STATUS --}}
                                            <form action="{{ route('penandatangan.toggle', $p->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                    class="w-24 px-3 py-1.5 text-xs rounded-lg transition {{ $p->is_active ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}"
                                                    onclick="return confirm('{{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }} penandatangan ini?')">
                                                    {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>

                                            {{-- DELETE BUTTON (Modal Trigger) --}}
                                            <button onclick="openDeleteModal('{{ $p->id }}', '{{ $p->nama }}')"
                                                class="px-3 py-1.5 text-xs bg-red-100 text-red-700 hover:bg-red-200 rounded-lg transition"
                                                title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($trash) ? 6 : 5 }}" class="px-6 py-12 text-center text-gray-500">
                                    <i class="bi bi-inbox text-4xl text-gray-300"></i>
                                    <p class="mt-2">{{ isset($trash) ? 'Belum ada yang dihapus' : 'Belum ada penandatangan' }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('addModal').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Penandatangan</h3>
                    <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <form action="{{ route('penandatangan.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="nama" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP</label>
                        <input type="text" name="nip" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none" placeholder="19700101 199001 1 001">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan *</label>
                        <input type="text" name="jabatan" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none" placeholder="Kepala Dinas Pendidikan">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pangkat/Golongan</label>
                        <input type="text" name="pangkat_golongan" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none" placeholder="Pembina Utama Madya (IV/d)">
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

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="closeDeleteModal()"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-sm w-full p-6 z-10">
                <div class="text-center mb-4">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-3">
                        <i class="bi bi-trash text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Hapus Penandatangan</h3>
                    <p class="text-sm text-gray-500 mt-1">Anda akan menghapus data <span id="deleteTargetName" class="font-bold"></span>.</p>
                </div>
                
                <form id="deleteForm" method="POST" class="space-y-4">
                    @csrf
                    @method('DELETE')
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penghapusan *</label>
                        <textarea name="alasan_penghapusan" required rows="3" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-100 focus:border-red-500 outline-none placeholder:text-sm"
                            placeholder="Contoh: Sudah pensiun, dimutasi, atau data ganda"></textarea>
                    </div>

                    <div class="flex gap-3 mt-4">
                        <button type="button" onclick="closeDeleteModal()"
                            class="flex-1 px-4 py-2.5 border rounded-lg hover:bg-gray-50 transition font-medium text-sm">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium text-sm">
                            Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(id, name) {
            document.getElementById('deleteTargetName').innerText = name;
            // Set action URL dynamically
            // Assuming route is /penandatangan/{id}
            document.getElementById('deleteForm').action = "{{ url('penandatangan') }}/" + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
@endsection
