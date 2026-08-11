@extends('layouts.app')

@section('title', 'Koreksi Jumlah Murid')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('satpen.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    <i class="bi bi-arrow-left mr-1"></i>Kembali ke Satuan Pendidikan
                </a>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Koreksi Jumlah Murid</h1>
                <p class="mt-1 text-gray-500">{{ $school->nama }} · NPSN {{ $school->npsn }}</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                <i class="bi bi-check-circle-fill mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border bg-white p-5 shadow-xs">
            <form method="GET" action="{{ route('satpen.student-counts.edit', $school->id) }}">
                <label for="academic-year" class="mb-2 block text-sm font-semibold text-gray-700">Tahun Pelajaran</label>
                <select id="academic-year" name="academic_year_id" onchange="this.form.submit()"
                    class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:max-w-sm">
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected($selectedAcademicYear?->id === $year->id)>
                            {{ $year->name }}{{ $year->is_active ? ' (Aktif)' : ($year->isClosed() ? ' (Ditutup)' : ' (Mendatang)') }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($selectedAcademicYear)
            @php
                $closingLocked = $countRows->contains(fn ($row) => $row->closing_confirmed_at !== null);
            @endphp
            <form method="POST" action="{{ route('satpen.student-counts.update', $school->id) }}"
                class="space-y-6 rounded-xl border bg-white p-5 shadow-xs"
                onsubmit="return confirm('Simpan koreksi jumlah murid untuk tahun pelajaran {{ $selectedAcademicYear->name }}?');">
                @csrf
                @method('PATCH')
                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYear->id }}">

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-semibold"><i class="bi bi-shield-exclamation mr-1"></i>Koreksi khusus Dinas/SuperAdmin</p>
                    <p class="mt-1">Data awal dapat dikoreksi meskipun tahun pelajaran telah ditutup atau Data Akhir telah dikunci. Data Akhir tidak ikut berubah.</p>
                    @if($closingLocked)
                        <p class="mt-2 font-semibold"><i class="bi bi-lock-fill mr-1"></i>Data Akhir tahun ini sedang terkunci.</p>
                    @endif
                </div>

                @if($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px]">
                        <thead class="border-b bg-gray-50 text-xs uppercase text-gray-600">
                            <tr><th class="px-4 py-3 text-left">Kelas</th><th class="px-4 py-3 text-center">Laki-laki</th><th class="px-4 py-3 text-center">Perempuan</th><th class="px-4 py-3 text-center">Total</th><th class="px-4 py-3 text-center">Status</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach([7 => 'VII', 8 => 'VIII', 9 => 'IX'] as $grade => $label)
                                @php($row = $countRows->get($grade))
                                <tr>
                                    <td class="px-4 py-4 font-bold text-gray-800">{{ $label }}</td>
                                    <td class="px-4 py-4"><input type="number" min="0" max="99999" required name="jumlah_siswa_{{ $grade }}_l" value="{{ old('jumlah_siswa_'.$grade.'_l', $row?->male_count ?? 0) }}" data-count-input class="w-full rounded-lg border-gray-300 text-center focus:border-blue-500 focus:ring-blue-500"></td>
                                    <td class="px-4 py-4"><input type="number" min="0" max="99999" required name="jumlah_siswa_{{ $grade }}_p" value="{{ old('jumlah_siswa_'.$grade.'_p', $row?->female_count ?? 0) }}" data-count-input class="w-full rounded-lg border-gray-300 text-center focus:border-pink-500 focus:ring-pink-500"></td>
                                    <td class="px-4 py-4 text-center font-bold text-gray-800" data-row-total>{{ (int) ($row?->male_count ?? 0) + (int) ($row?->female_count ?? 0) }}</td>
                                    <td class="px-4 py-4 text-center text-xs">
                                        @if($row?->confirmed_at)<span class="rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-700"><i class="bi bi-lock-fill"></i> Terkonfirmasi</span>@else<span class="rounded-full bg-yellow-100 px-2 py-1 font-semibold text-yellow-700">Belum lengkap</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>
                    <label for="reason" class="mb-2 block text-sm font-semibold text-gray-700">Alasan koreksi <span class="text-red-600">*</span></label>
                    <textarea id="reason" name="reason" rows="3" minlength="10" maxlength="500" required
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Contoh: Koreksi berdasarkan rekap resmi sekolah tanggal ...">{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Minimal 10 karakter. Nilai sebelum dan sesudah beserta alasan akan dicatat pada audit log.</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-primary-600 px-5 py-2.5 font-semibold text-white hover:bg-primary-700">
                        <i class="bi bi-save mr-2"></i>Simpan Koreksi
                    </button>
                </div>
            </form>
        @else
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5 text-yellow-800">Belum ada tahun pelajaran yang tersedia.</div>
        @endif
    </div>

    <script>
        document.querySelectorAll('tbody tr').forEach((row) => {
            const inputs = row.querySelectorAll('[data-count-input]');
            const total = row.querySelector('[data-row-total]');
            inputs.forEach((input) => input.addEventListener('input', () => {
                total.textContent = Array.from(inputs).reduce((sum, item) => sum + (parseInt(item.value, 10) || 0), 0);
            }));
        });
    </script>
@endsection
