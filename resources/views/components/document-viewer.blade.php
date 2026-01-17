@props([
    'path',
    'height' => 'calc(100vh - 280px)',
    'minHeight' => '500px'
])

 
<div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">

    <iframe src="{{ route('dokumen.view', ['path' => $path]) }}" 
        class="w-full bg-slate-100" 
        style="height: {{ $height }}; min-height: {{ $minHeight }};">
    </iframe>

    <a href="{{ route('dokumen.view', ['path' => $path]) }}" target="_blank"
        class="block text-center py-3 bg-slate-50 text-sky-600 text-sm font-medium hover:bg-slate-100 transition border-t border-slate-200">
        <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh (Tab Baru)
    </a>
</div>