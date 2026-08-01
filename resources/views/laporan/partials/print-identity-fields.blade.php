@php
    $printUser = auth()->user();
    $isSchoolPrint = in_array($printUser->role, ['Sekolah', 'Operator'], true);
    $defaultPosition = $isSchoolPrint ? 'Kepala Sekolah' : '';
    $defaultName = $isSchoolPrint ? ($printUser->sekolah->kepala_sekolah_nama ?? '') : '';
    $defaultNip = $isSchoolPrint ? ($printUser->sekolah->kepala_sekolah_nip ?? '') : '';
@endphp

<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="mb-4">
        <h3 class="font-bold text-slate-900">Identitas Atasan Langsung</h3>
        <p class="mt-1 text-xs text-slate-500">Opsional. Jika dikosongkan, laporan menyediakan ruang untuk ditulis manual.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Jabatan</label>
            <input type="text" name="atasan_jabatan" maxlength="100" value="{{ old('atasan_jabatan', $defaultPosition) }}"
                placeholder="{{ $isSchoolPrint ? 'Contoh: Kepala Sekolah' : 'Contoh: Kepala Bidang SMP' }}"
                class="w-full rounded-lg border-slate-300 bg-white focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
            <input type="text" name="atasan_nama" maxlength="100" value="{{ old('atasan_nama', $defaultName) }}"
                placeholder="Nama lengkap atasan"
                class="w-full rounded-lg border-slate-300 bg-white focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">NIP</label>
            <input type="text" name="atasan_nip" maxlength="30" inputmode="numeric" value="{{ old('atasan_nip', $defaultNip) }}"
                placeholder="NIP atasan langsung"
                class="w-full rounded-lg border-slate-300 bg-white focus:border-emerald-500 focus:ring-emerald-500">
        </div>
    </div>
</div>
