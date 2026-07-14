@extends('layouts.app')

@section('title', 'Pengaturan Jadwal')

@section('content')
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
            <div class="w-14 h-14 bg-linear-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="bi bi-calendar-check text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pengaturan Jadwal</h1>
                <p class="text-gray-500">Atur hari dan jam operasional pengajuan mutasi</p>
            </div>
            <div class="ml-auto">
                {{-- Link to System Status --}}
                <a href="{{ route('system.status') }}" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-xs">
                    <i class="bi bi-hdd-network text-indigo-500"></i> Status Sistem
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

    {{-- Academic Year Settings --}}
    <div class="mb-8 bg-white rounded-2xl shadow-xs border border-indigo-100 overflow-hidden">
        <div class="px-6 py-4 bg-linear-to-r from-indigo-50 to-white border-b border-indigo-100 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-sm">
                    <i class="bi bi-calendar3 text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Tahun Pelajaran</h2>
                    <p class="text-sm text-gray-500">Tahun aktif menentukan data siswa yang dipakai laporan lama.</p>
                </div>
            </div>
            @if($activeAcademicYear)
                <span class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700 font-bold">
                    Aktif: {{ $activeAcademicYear->name }}
                </span>
            @endif
        </div>

        <div class="p-6">
            @if($academicYears->isEmpty())
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-amber-800">
                    Migrasi tahun pelajaran belum dijalankan. Aplikasi masih menggunakan data siswa lama.
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Daftar Tahun</h3>
                        <div class="space-y-2">
                            @foreach($academicYears as $academicYear)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 p-3">
                                    <div>
                                        <div class="font-bold text-gray-800">{{ $academicYear->name }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $academicYear->starts_on?->format('d/m/Y') }}–{{ $academicYear->ends_on?->format('d/m/Y') }}
                                        </div>
                                    </div>
                                    @if($academicYear->is_active)
                                        <span class="px-3 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-sm font-semibold">Aktif</span>
                                    @else
                                        <form action="{{ route('academic-years.activate', $academicYear->id) }}" method="POST"
                                            onsubmit="return confirm('Aktifkan {{ $academicYear->name }}? Data siswa tahun ini akan menjadi dasar laporan aktif.')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="px-3 py-2 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-sm font-semibold">
                                                Jadikan Aktif
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Tambah Tahun Pelajaran</h3>
                        <form action="{{ route('academic-years.store') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: 2026/2027"
                                pattern="\d{4}/\d{4}" required
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            @error('name')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <button class="w-full px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                                <i class="bi bi-plus-circle mr-2"></i>Tambah sebagai Tahun Mendatang
                            </button>
                            <p class="text-xs text-gray-500">
                                Tahun baru dapat diisi lebih awal oleh sekolah. Aktivasi akan menutup tahun aktif sebelumnya.
                            </p>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        {{-- LEFT COLUMN: Main Settings (Hari & Jam) --}}
        <div>
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                <div class="space-y-6">
                    {{-- Hari yang Diperbolehkan --}}
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 bg-linear-to-r from-blue-50 to-white border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-linear-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-sm">
                                    <i class="bi bi-calendar-week text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-800">Hari Operasional</h2>
                                    <p class="text-sm text-gray-500">Pilih hari yang diperbolehkan untuk pengajuan mutasi</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                @foreach($dayNames as $dayNum => $dayName)
                                <label class="relative cursor-pointer group">
                                    <input type="checkbox" 
                                           name="allowed_days[]" 
                                           value="{{ $dayNum }}" 
                                           class="sr-only peer"
                                           {{ in_array($dayNum, $allowedDays) ? 'checked' : '' }}>
                                    <div class="p-4 rounded-xl border-2 border-gray-200 bg-gray-50 text-center transition-all
                                                peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:shadow-md
                                                group-hover:border-primary-300 group-hover:bg-primary-50/50">
                                        <div class="text-2xl mb-1 {{ $dayNum === 0 ? 'text-red-500' : '' }}">
                                            @if($dayNum === 0)
                                            <i class="bi bi-calendar-x"></i>
                                            @else
                                            <i class="bi bi-calendar-check"></i>
                                            @endif
                                        </div>
                                        <div class="font-semibold text-gray-700 peer-checked:text-primary-700">{{ $dayName }}</div>
                                        <div class="text-xs text-gray-400 mt-1 peer-checked:text-primary-500">
                                            <span class="peer-checked:hidden">Libur</span>
                                            <span class="hidden peer-checked:inline"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2 w-5 h-5 rounded-full bg-primary-500 text-white flex items-center justify-center text-xs opacity-0 peer-checked:opacity-100 transition-opacity shadow-sm">
                                        <i class="bi bi-check"></i>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Jam Operasional --}}
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 bg-linear-to-r from-amber-50 to-white border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-linear-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center shadow-sm">
                                    <i class="bi bi-clock text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-800">Jam Operasional</h2>
                                    <p class="text-sm text-gray-500">Atur batasan jam untuk pengajuan mutasi</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            {{-- Toggle Aktifkan --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                <div>
                                    <div class="font-medium text-gray-800">Aktifkan Pembatasan Jam</div>
                                    <div class="text-sm text-gray-500">Jika aktif, pengajuan hanya bisa pada jam yang ditentukan</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" 
                                           name="enable_hour_restriction" 
                                           value="1" 
                                           class="sr-only peer"
                                           {{ $enableHourRestriction ? 'checked' : '' }}
                                           id="toggleHours">
                                    <div class="w-14 h-7 bg-gray-300 peer-focus:ring-4 peer-focus:ring-amber-100 rounded-full peer 
                                                peer-checked:after:translate-x-full peer-checked:after:border-white 
                                                after:content-[''] after:absolute after:top-0.5 after:inset-s-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all 
                                                peer-checked:bg-amber-500 shadow-inner"></div>
                                </label>
                            </div>

                            {{-- Time Inputs --}}
                            <div id="hourInputs" class="{{ !$enableHourRestriction ? 'opacity-50 pointer-events-none' : '' }}">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Jam Mulai</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <i class="bi bi-sunrise text-amber-500"></i>
                                            </div>
                                            <input type="time" 
                                                   name="working_hours_start" 
                                                   value="{{ $workingHoursStart }}"
                                                   class="w-full pl-12 pr-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono text-lg">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Jam Selesai</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <i class="bi bi-sunset text-amber-500"></i>
                                            </div>
                                            <input type="time" 
                                                   name="working_hours_end" 
                                                   value="{{ $workingHoursEnd }}"
                                                   class="w-full pl-12 pr-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono text-lg">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Save Button --}}
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-3 px-8 py-4 bg-linear-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all text-lg w-full sm:w-auto justify-center">
                            <i class="bi bi-check2-circle text-xl"></i>
                            Simpan Pengaturan
                        </button>
                    </div>
                </div>
            </form>
        </div>

            {{-- RIGHT COLUMN: Holidays --}}
        <div x-data="{ showAddModal: false }">
            {{-- Hari Libur Section --}}
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden sticky top-6">
                <div class="px-6 py-4 bg-linear-to-r from-red-50 to-white border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-linear-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="bi bi-calendar-x text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Hari Libur</h2>
                            <p class="text-sm text-gray-500">Daftar hari libur nasional</p>
                        </div>
                    </div>
                    <button @click="showAddModal = true" class="flex items-center gap-2 px-3 py-1.5 bg-white border border-red-100 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50 transition shadow-xs">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>

                <div class="p-6">
                    {{-- Holiday List (Table) --}}
                    @if($holidays->isEmpty())
                    <div class="text-center py-8 text-gray-400">
                        <i class="bi bi-calendar-check text-4xl mb-2"></i>
                        <p>Belum ada hari libur yang ditambahkan</p>
                    </div>
                    @else
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/3">Tanggal</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($holidays as $holiday)
                                <tr class="hover:bg-gray-50 transition-colors" x-data="{ editing: false }">
                                    <td class="px-4 py-3 align-top">
                                        <template x-if="!editing">
                                            <div>
                                                <div class="font-medium text-gray-800">{{ $holiday->date->format('d/m/Y') }}</div>
                                                <div class="text-xs text-gray-400 mt-0.5">{{ $holiday->date->translatedFormat('l') }}</div>
                                            </div>
                                        </template>
                                        <template x-if="editing">
                                            <input type="date" form="edit_holiday_{{ $holiday->id }}" name="date" value="{{ $holiday->date->format('Y-m-d') }}" required class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-hidden">
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <template x-if="!editing">
                                            <span class="text-sm text-gray-600 leading-relaxed block">{{ $holiday->description }}</span>
                                        </template>
                                        <template x-if="editing">
                                            <input type="text" form="edit_holiday_{{ $holiday->id }}" name="description" value="{{ $holiday->description }}" required class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-hidden">
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 align-top text-right">
                                        <template x-if="!editing">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" @click="editing = true" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form action="{{ route('holidays.destroy', $holiday->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus hari libur ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </template>
                                        <template x-if="editing">
                                            <div class="flex items-center justify-end gap-1">
                                                <form id="edit_holiday_{{ $holiday->id }}" action="{{ route('holidays.update', $holiday->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="p-1.5 text-green-500 hover:text-green-700 hover:bg-green-50 rounded-lg transition" title="Simpan">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                <button type="button" @click="editing = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Batal">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    <div class="mt-4">
                        {{ $holidays->links() }}
                    </div>
                </div>
            </div>

            {{-- Add Holiday Modal --}}
            <div x-show="showAddModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" @click="showAddModal = false"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
                
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-lg border border-gray-100"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <div class="px-6 py-4 bg-linear-to-r from-red-50 to-white border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <i class="bi bi-calendar-plus text-red-500"></i> Tambah Hari Libur
                        </h3>
                        <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 transition">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form action="{{ route('holidays.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Libur</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <i class="bi bi-calendar-event"></i>
                                </span>
                                <input type="date" name="date" required 
                                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                            <textarea name="description" required rows="3"
                                      placeholder="Contoh: Hari Raya Idul Fitri 1445 H"
                                      class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showAddModal = false" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl shadow-lg shadow-red-500/30 font-medium transition-all transform hover:-translate-y-0.5">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    document.getElementById('toggleHours').addEventListener('change', function() {
        const inputs = document.getElementById('hourInputs');
        if (this.checked) {
            inputs.classList.remove('opacity-50', 'pointer-events-none');
        } else {
            inputs.classList.add('opacity-50', 'pointer-events-none');
        }
    });
</script>
@endpush

@endsection
