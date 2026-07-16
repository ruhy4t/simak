@props(['status'])

@php
    $statusStyles = [
        'Draft' => ['class' => 'bg-slate-100 text-slate-700 ring-slate-500/15', 'dot' => 'bg-slate-400', 'label' => 'Draft'],
        'Menunggu Verifikasi Dinas' => ['class' => 'bg-amber-50 text-amber-700 ring-amber-600/15', 'dot' => 'bg-amber-500', 'label' => 'Menunggu Verifikasi'],
        'Proses' => ['class' => 'bg-sky-50 text-sky-700 ring-sky-600/15', 'dot' => 'bg-sky-500', 'label' => 'Diproses'],
        'Disetujui Dinas' => ['class' => 'bg-blue-50 text-blue-700 ring-blue-600/15', 'dot' => 'bg-blue-500', 'label' => 'Disetujui Dinas'],
        'Proses TTD' => ['class' => 'bg-violet-50 text-violet-700 ring-violet-600/15', 'dot' => 'bg-violet-500', 'label' => 'Proses TTD'],
        'Selesai' => ['class' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15', 'dot' => 'bg-emerald-500', 'label' => 'Selesai'],
        'Perlu Perbaikan' => ['class' => 'bg-rose-50 text-rose-700 ring-rose-600/15', 'dot' => 'bg-rose-500', 'label' => 'Perlu Perbaikan'],
        'Dibatalkan' => ['class' => 'bg-slate-100 text-slate-600 ring-slate-500/15', 'dot' => 'bg-slate-400', 'label' => 'Dibatalkan'],
    ];
    $config = $statusStyles[$status] ?? [
        'class' => 'bg-slate-100 text-slate-700 ring-slate-500/15',
        'dot' => 'bg-slate-400',
        'label' => $status,
    ];
@endphp

<span {{ $attributes->class("inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$config['class']}") }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $config['dot'] }}" aria-hidden="true"></span>
    {{ $config['label'] }}
</span>
