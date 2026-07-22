@extends('layouts.public')

@section('title', 'Statistik Mutasi Siswa - SIMAK Kota Bogor')

@section('content')
    @php
        $totalMasuk = $chartData['totals']['masuk'] ?? collect($chartData['masuk'])->flatten()->sum();
        $totalKeluar = $chartData['totals']['keluar'] ?? collect($chartData['keluar'])->flatten()->sum();
        $periodLabel = $month
            ? \Carbon\Carbon::create($year, $month)->translatedFormat('F Y')
            : 'Tahun '.$year;
        $schoolFilterLabel = $selectedSchool
            ? $selectedSchool->nama.' · NPSN '.$selectedSchool->npsn
            : ($schoolStatus ? 'SMP/SPK SMP '.$schoolStatus : 'Seluruh SMP/SPK SMP Kota Bogor');
    @endphp

    <header class="border-b border-slate-800 bg-slate-950 text-white">
        <div class="mx-auto flex w-full max-w-[1680px] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('public.statistics') }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-400/10 text-sky-400 ring-1 ring-sky-300/10">
                    <i class="bi bi-bar-chart-line-fill"></i>
                </span>
                <span>
                    <span class="block font-bold tracking-tight">SIMAK Kota Bogor</span>
                    <span class="block text-xs text-slate-400">Statistik Publik Mutasi Siswa</span>
                </span>
            </a>
            <a href="{{ route('login') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-3.5 py-2 text-sm font-semibold text-slate-200 transition hover:border-sky-400 hover:text-white">
                <i class="bi bi-box-arrow-in-right"></i>
                <span class="hidden sm:inline">Login Petugas</span>
                <span class="sm:hidden">Login</span>
            </a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1680px] space-y-6 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-sky-950 px-5 py-7 text-white sm:px-8 sm:py-9">
            <div class="max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-sky-400">Data Terbuka</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Tren Mutasi Siswa Kota Bogor</h1>
                <details class="mt-4 max-w-2xl text-sm text-slate-300">
                    <summary class="w-fit cursor-pointer font-semibold text-sky-300">Tentang data</summary>
                    <p class="mt-2 leading-6">
                        Ringkasan hanya memuat data agregat tanpa identitas siswa, dokumen, atau rincian akun sekolah.
                    </p>
                </details>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <form action="{{ route('public.statistics') }}" method="GET"
                data-public-statistics-filter
                class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1.2fr_auto] xl:items-end">
                <div>
                    <label for="public-month" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Bulan</label>
                    <select name="month" id="public-month"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">Semua Bulan</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month === $m)>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="public-year" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Tahun</label>
                    <select name="year" id="public-year"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="public-school-status" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Status Sekolah</label>
                    <select name="school_status" id="public-school-status" data-school-status
                        @disabled($npsn)
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">Semua Status</option>
                        <option value="Negeri" @selected($schoolStatus === 'Negeri')>Negeri</option>
                        <option value="Swasta" @selected($schoolStatus === 'Swasta')>Swasta</option>
                    </select>
                    <p data-status-help class="mt-1 text-xs text-slate-400 {{ $npsn ? '' : 'hidden' }}">Diabaikan karena NPSN digunakan.</p>
                </div>
                <div>
                    <label for="public-npsn" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">NPSN Sekolah</label>
                    <input type="text" inputmode="numeric" maxlength="8" pattern="[0-9]{8}"
                        name="npsn" id="public-npsn" data-school-npsn value="{{ $npsn }}"
                        placeholder="Masukkan 8 digit NPSN"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                    @error('npsn')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-700">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
            </form>

            @if($selectedSchool)
                <div class="mt-4 flex flex-col gap-1 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-sky-700">Sekolah Terpilih</p>
                        <p class="mt-0.5 font-bold text-sky-950">{{ $selectedSchool->nama }}</p>
                    </div>
                    <p class="text-sm font-semibold text-sky-800">NPSN {{ $selectedSchool->npsn }}</p>
                </div>
            @endif
        </section>

        <section aria-label="Ringkasan statistik {{ $periodLabel }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-medium text-slate-500">Periode</p>
                <p class="mt-2 text-2xl font-bold text-slate-950">{{ $periodLabel }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $schoolFilterLabel }}</p>
            </article>
            <article class="rounded-2xl border border-sky-100 bg-sky-50 p-5">
                <p class="text-sm font-medium text-sky-700">Mutasi Masuk Selesai</p>
                <p class="mt-2 text-3xl font-bold text-sky-950">{{ number_format($totalMasuk) }}</p>
            </article>
            <article class="rounded-2xl border border-orange-100 bg-orange-50 p-5">
                <p class="text-sm font-medium text-orange-700">Mutasi Keluar Selesai</p>
                <p class="mt-2 text-3xl font-bold text-orange-950">{{ number_format($totalKeluar) }}</p>
            </article>
        </section>

        <x-trend-chart-panels :chart-data="$chartData" />

        <x-class-distribution-chart :chart-data="$chartData" />

        @if($cohortYear && $cohort)
            <section id="rekonsiliasi-tahunan" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
                <form action="{{ route('public.statistics') }}#rekonsiliasi-tahunan" method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <input type="hidden" name="year" value="{{ $year }}">
                    @if($month)<input type="hidden" name="month" value="{{ $month }}">@endif
                    @if($cohortEntryYear)<input type="hidden" name="cohort_entry_year" value="{{ $cohortEntryYear->id }}">@endif
                    @if($npsn)
                        <input type="hidden" name="npsn" value="{{ $npsn }}">
                    @elseif($schoolStatus)
                        <input type="hidden" name="school_status" value="{{ $schoolStatus }}">
                    @endif
                    <div class="min-w-0 flex-1">
                        <label for="public-academic-year" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Rekonsiliasi Tahun Pelajaran</label>
                        <select name="academic_year" id="public-academic-year" class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($cohortYears as $academicYear)
                                <option value="{{ $academicYear->id }}" @selected($cohortYear->id === $academicYear->id)>{{ $academicYear->name }}{{ $academicYear->is_active ? ' (Aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Tampilkan Rekonsiliasi</button>
                </form>
            </section>

            <x-cohort-summary :cohort="$cohort" title="Rekonsiliasi {{ $schoolFilterLabel }} · {{ $cohortYear->name }}"
                :description="$cohortYear->is_active
                    ? 'Tahun pelajaran masih aktif. Ringkasan berjalan mencakup seluruh sekolah yang memiliki data awal; data akhir hanya tersedia setelah dikunci.'
                    : 'Hanya data agregat sekolah yang telah mengunci data akhir.'"
                :compact="true" />
        @endif

        @if($cohortEntryYear && $longitudinalCohort)
            <section id="kohort-angkatan" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
                <form action="{{ route('public.statistics') }}#kohort-angkatan" method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <input type="hidden" name="year" value="{{ $year }}">
                    @if($month)<input type="hidden" name="month" value="{{ $month }}">@endif
                    @if($cohortYear)<input type="hidden" name="academic_year" value="{{ $cohortYear->id }}">@endif
                    @if($npsn)
                        <input type="hidden" name="npsn" value="{{ $npsn }}">
                    @elseif($schoolStatus)
                        <input type="hidden" name="school_status" value="{{ $schoolStatus }}">
                    @endif
                    <div class="min-w-0 flex-1">
                        <label for="public-cohort-entry-year" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Angkatan Masuk</label>
                        <select name="cohort_entry_year" id="public-cohort-entry-year" class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($cohortEntryYears as $entryYear)
                                <option value="{{ $entryYear->id }}" @selected($cohortEntryYear->id === $entryYear->id)>{{ $entryYear->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Tampilkan Kohort</button>
                </form>
            </section>

            <x-longitudinal-cohort :report="$longitudinalCohort"
                title="Angkatan {{ $cohortEntryYear->name }} · {{ $schoolFilterLabel }}"
                :compact="true" />
        @endif
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Kurikulum SMP - Dinas Pendidikan Kota Bogor · SIMAK v{{ config('app.version') }}
    </footer>
@endsection
