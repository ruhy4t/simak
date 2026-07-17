@extends('layouts.public')

@section('title', 'Statistik Mutasi Siswa - SIMAK Kota Bogor')

@section('content')
    @php
        $totalMasuk = collect($chartData['masuk'])->flatten()->sum();
        $totalKeluar = collect($chartData['keluar'])->flatten()->sum();
        $periodLabel = $month
            ? \Carbon\Carbon::create($year, $month)->translatedFormat('F Y')
            : 'Tahun '.$year;
    @endphp

    <header class="border-b border-slate-800 bg-slate-950 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
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

    <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-sky-950 px-5 py-7 text-white sm:px-8 sm:py-9">
            <div class="max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-sky-400">Data Terbuka</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Tren Mutasi Siswa Kota Bogor</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                    Ringkasan agregat mutasi selesai dan siswa keluar non-mutasi yang telah tercatat.
                    Halaman ini tidak menampilkan identitas siswa, dokumen, maupun rincian akun sekolah.
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <form action="{{ route('public.statistics') }}" method="GET"
                class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
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
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-700">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
            </form>
        </section>

        <section aria-label="Ringkasan statistik {{ $periodLabel }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-medium text-slate-500">Periode</p>
                <p class="mt-2 text-2xl font-bold text-slate-950">{{ $periodLabel }}</p>
                <p class="mt-1 text-xs text-slate-400">Dapat difilter per bulan</p>
            </article>
            <article class="rounded-2xl border border-sky-100 bg-sky-50 p-5">
                <p class="text-sm font-medium text-sky-700">Mutasi Masuk Selesai</p>
                <p class="mt-2 text-3xl font-bold text-sky-950">{{ number_format($totalMasuk) }}</p>
                <p class="mt-1 text-xs text-sky-600">Siswa tercatat pada periode ini</p>
            </article>
            <article class="rounded-2xl border border-orange-100 bg-orange-50 p-5">
                <p class="text-sm font-medium text-orange-700">Mutasi Keluar Selesai</p>
                <p class="mt-2 text-3xl font-bold text-orange-950">{{ number_format($totalKeluar) }}</p>
                <p class="mt-1 text-xs text-orange-600">Siswa tercatat pada periode ini</p>
            </article>
        </section>

        <x-trend-chart-panels :chart-data="$chartData" />

        @if($cohortYear && $cohort)
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
                <form action="{{ route('public.statistics') }}" method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <input type="hidden" name="year" value="{{ $year }}">
                    @if($month)<input type="hidden" name="month" value="{{ $month }}">@endif
                    <div class="min-w-0 flex-1">
                        <label for="public-academic-year" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Tahun Pelajaran Selesai</label>
                        <select name="academic_year" id="public-academic-year" class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($cohortYears as $academicYear)
                                <option value="{{ $academicYear->id }}" @selected($cohortYear->id === $academicYear->id)>{{ $academicYear->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Tampilkan Kohort</button>
                </form>
            </section>

            <x-cohort-summary :cohort="$cohort" title="Kohort Siswa Kota Bogor · {{ $cohortYear->name }}"
                description="Hanya data agregat sekolah yang telah mengunci data akhir. Tidak memuat nama, NISN, atau identitas siswa." />
        @endif

        <p class="text-center text-xs leading-5 text-slate-500">
            Statistik diperbarui berkala dari data tercatat di SIMAK Kota Bogor.
        </p>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Kurikulum SMP - Dinas Pendidikan Kota Bogor · SIMAK v{{ config('app.version') }}
    </footer>
@endsection
