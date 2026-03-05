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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tanda Tangan</th>
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
                                
                                {{-- Signature Image Column --}}
                                <td class="px-6 py-4 text-center">
                                    @if(!empty($p->gambar_ttd))
                                        <img src="{{ url('dokumen/lihat/' . $p->gambar_ttd) }}" 
                                            alt="Tanda Tangan {{ $p->nama }}" 
                                            class="mx-auto h-12 object-contain cursor-pointer rounded border hover:shadow-lg transition"
                                            onclick="showSignaturePreview('{{ url('dokumen/lihat/' . $p->gambar_ttd) }}', '{{ $p->nama }}')"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                        <span class="hidden inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                            <i class="bi bi-exclamation-circle mr-1"></i> Error
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                            <i class="bi bi-image mr-1"></i> Belum ada
                                        </span>
                                    @endif
                                </td>
                                
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
                                            {{-- EDIT BUTTON --}}
                                            <button onclick="openEditModal({{ json_encode($p) }})"
                                                class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 rounded-lg transition"
                                                title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>

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
                                <td colspan="{{ isset($trash) ? 7 : 6 }}" class="px-6 py-12 text-center text-gray-500">
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
                
                <form action="{{ route('penandatangan.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
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
                    
                    {{-- Signature Image Upload --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Gambar Tanda Tangan
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-primary-400 transition cursor-pointer relative"
                            onclick="document.getElementById('addSignatureInput').click()">
                            <input type="file" name="gambar_ttd" id="addSignatureInput" 
                                accept="image/jpeg,image/png,image/webp" class="hidden"
                                onchange="previewSignature(this, 'addSignaturePreview')">
                            <div id="addSignaturePreview" class="hidden mb-2">
                                <img src="" alt="Preview" class="mx-auto max-h-24 object-contain">
                            </div>
                            <div id="addSignaturePlaceholder">
                                <i class="bi bi-cloud-upload text-2xl text-gray-400"></i>
                                <p class="text-sm text-gray-500 mt-1">Klik untuk upload gambar tanda tangan</p>
                                <p class="text-xs text-gray-400">JPG, PNG, WebP — Maks 2MB</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="bi bi-info-circle"></i>
                            Gambar akan menggantikan area TTD dan footer di surat mutasi
                        </p>
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

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="closeEditModal()"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Penandatangan</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <form id="editForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="nama" id="editNama" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP</label>
                        <input type="text" name="nip" id="editNip" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan *</label>
                        <input type="text" name="jabatan" id="editJabatan" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pangkat/Golongan</label>
                        <input type="text" name="pangkat_golongan" id="editPangkat" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                    </div>
                    
                    {{-- Current Signature Display + Upload New --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gambar Tanda Tangan</label>
                        
                        {{-- Current signature preview --}}
                        <div id="editCurrentSignature" class="hidden mb-2 p-2 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Tanda tangan saat ini:</span>
                                <button type="button" id="editDeleteSignatureBtn" 
                                    class="text-xs text-red-600 hover:text-red-800 transition"
                                    onclick="deleteSignature()">
                                    <i class="bi bi-trash mr-1"></i> Hapus
                                </button>
                            </div>
                            <img id="editCurrentSignatureImg" src="" alt="Tanda Tangan" 
                                class="mt-1 mx-auto max-h-20 object-contain">
                        </div>

                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-primary-400 transition cursor-pointer"
                            onclick="document.getElementById('editSignatureInput').click()">
                            <input type="file" name="gambar_ttd" id="editSignatureInput" 
                                accept="image/jpeg,image/png,image/webp" class="hidden"
                                onchange="previewSignature(this, 'editSignaturePreview')">
                            <div id="editSignaturePreview" class="hidden mb-2">
                                <img src="" alt="Preview" class="mx-auto max-h-24 object-contain">
                            </div>
                            <div id="editSignaturePlaceholder">
                                <i class="bi bi-cloud-upload text-2xl text-gray-400"></i>
                                <p class="text-sm text-gray-500 mt-1">Upload gambar baru</p>
                                <p class="text-xs text-gray-400">JPG, PNG, WebP — Maks 2MB</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closeEditModal()"
                            class="flex-1 px-4 py-2.5 border rounded-lg hover:bg-gray-50 transition font-medium">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                            <i class="bi bi-check-lg mr-1"></i> Simpan
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

    <!-- Signature Preview Modal -->
    <div id="signaturePreviewModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('signaturePreviewModal').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="signaturePreviewTitle" class="text-lg font-semibold text-gray-900">Tanda Tangan</h3>
                    <button onclick="document.getElementById('signaturePreviewModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <img id="signaturePreviewImg" src="" alt="Tanda Tangan" class="mx-auto max-h-64 object-contain">
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden form for delete signature --}}
    <form id="deleteSignatureForm" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
        let currentEditId = null;

        function openDeleteModal(id, name) {
            document.getElementById('deleteTargetName').innerText = name;
            document.getElementById('deleteForm').action = "{{ url('penandatangan') }}/" + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function openEditModal(data) {
            currentEditId = data.id;
            document.getElementById('editNama').value = data.nama || '';
            document.getElementById('editNip').value = data.nip || '';
            document.getElementById('editJabatan').value = data.jabatan || '';
            document.getElementById('editPangkat').value = data.pangkat_golongan || '';
            document.getElementById('editForm').action = "{{ url('penandatangan') }}/" + data.id;

            // Reset file input and preview
            document.getElementById('editSignatureInput').value = '';
            document.getElementById('editSignaturePreview').classList.add('hidden');
            document.getElementById('editSignaturePlaceholder').style.display = '';

            // Show current signature if exists
            if (data.gambar_ttd) {
                document.getElementById('editCurrentSignature').classList.remove('hidden');
                // Build URL - check if it's a full URL or relative path
                let imgUrl = data.gambar_ttd;
                if (!imgUrl.startsWith('http')) {
                    imgUrl = "{{ url('dokumen/lihat') }}/" + data.gambar_ttd;
                }
                document.getElementById('editCurrentSignatureImg').src = imgUrl;
            } else {
                document.getElementById('editCurrentSignature').classList.add('hidden');
            }

            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function deleteSignature() {
            if (!currentEditId) return;
            if (!confirm('Hapus gambar tanda tangan ini?')) return;

            const form = document.getElementById('deleteSignatureForm');
            form.action = "{{ url('penandatangan') }}/" + currentEditId + "/signature";
            form.submit();
        }

        function previewSignature(input, previewId) {
            const preview = document.getElementById(previewId);
            const placeholder = input.closest('.border-dashed').querySelector('[id$="Placeholder"]');
            
            if (input.files && input.files[0]) {
                // Validate size (2MB)
                if (input.files[0].size > 2 * 1024 * 1024) {
                    alert('Ukuran file maksimal 2MB');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                    if (placeholder) placeholder.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function showSignaturePreview(url, name) {
            document.getElementById('signaturePreviewTitle').innerText = 'Tanda Tangan — ' + name;
            document.getElementById('signaturePreviewImg').src = url;
            document.getElementById('signaturePreviewModal').classList.remove('hidden');
        }
    </script>
@endsection