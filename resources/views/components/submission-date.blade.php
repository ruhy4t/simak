@props([
    'original',
    'latest' => null,
    'revisionCount' => 0,
    'compact' => false,
])

@php
    $originalDate = \Carbon\Carbon::parse($original);
    $latestDate = $latest ? \Carbon\Carbon::parse($latest) : $originalDate;
    $isResubmitted = (int) $revisionCount > 0;
@endphp

<div {{ $attributes->class(['flex flex-col', 'gap-1' => ! $compact]) }}>
    <div class="flex flex-wrap items-center gap-2">
        <span class="font-semibold text-slate-800">
            {{ ($isResubmitted ? $latestDate : $originalDate)->translatedFormat('j F Y') }}
        </span>
        @if($isResubmitted)
            <span class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-bold text-violet-700 ring-1 ring-inset ring-violet-200">
                <i class="bi bi-arrow-repeat"></i>
                Kirim ulang ke-{{ number_format((int) $revisionCount) }}
            </span>
        @endif
    </div>

    @if($isResubmitted)
        <span class="text-xs text-slate-500">
            {{ $latestDate->format('H:i') }} WIB
            <span class="mx-1 text-slate-300">&bull;</span>
            Awal {{ $originalDate->translatedFormat('j M Y') }}
        </span>
    @elseif(! $compact)
        <span class="text-xs text-slate-400">Pengajuan awal</span>
    @endif
</div>
