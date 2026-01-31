@extends('layouts.app')

@section('title', 'Kelola Permission - ' . $user->username)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pengguna.index') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="bi bi-arrow-left text-lg"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Permission</h1>
            </div>
            <p class="text-gray-500 mt-1">
                Atur akses fitur untuk user <strong>{{ $user->username }}</strong> 
                (Role: <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $user->role }}</span>)
            </p>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <i class="bi bi-info-circle text-blue-600 text-xl"></i>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Cara Kerja:</p>
                <ul class="list-disc ml-4 space-y-1">
                    <li><strong>Default</strong>: Mengikuti permission bawaan dari role <em>{{ $user->role }}</em></li>
                    <li><strong>Tambah</strong>: Memberikan akses meskipun role tidak memiliki permission ini</li>
                    <li><strong>Cabut</strong>: Mencabut akses meskipun role memiliki permission ini</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('permissions.update', $user) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="space-y-6">
            @foreach($permissions as $group => $groupPermissions)
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="bg-gray-50 px-6 py-3 border-b">
                        <h3 class="font-semibold text-gray-900">{{ $group }}</h3>
                    </div>
                    <div class="divide-y">
                        @foreach($groupPermissions as $permission)
                            @php
                                $isRoleDefault = in_array($permission->id, $roleDefaults);
                                $override = $userOverrides[$permission->id] ?? null;
                                
                                // Determine current state
                                if ($override === true) {
                                    $currentState = 'grant';
                                } elseif ($override === false) {
                                    $currentState = 'revoke';
                                } else {
                                    $currentState = 'default';
                                }
                            @endphp
                            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $permission->display_name }}</div>
                                    <div class="text-sm text-gray-500">
                                        <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ $permission->name }}</code>
                                        @if($isRoleDefault)
                                            <span class="ml-2 text-green-600">
                                                <i class="bi bi-check-circle-fill"></i> Default untuk {{ $user->role }}
                                            </span>
                                        @else
                                            <span class="ml-2 text-gray-400">
                                                <i class="bi bi-x-circle"></i> Tidak default untuk {{ $user->role }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" 
                                               name="permissions[{{ $permission->id }}]" 
                                               value="default"
                                               {{ $currentState === 'default' ? 'checked' : '' }}
                                               class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-sm text-gray-600">Default</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" 
                                               name="permissions[{{ $permission->id }}]" 
                                               value="grant"
                                               {{ $currentState === 'grant' ? 'checked' : '' }}
                                               class="text-green-600 focus:ring-green-500">
                                        <span class="text-sm text-green-600">Tambah</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" 
                                               name="permissions[{{ $permission->id }}]" 
                                               value="revoke"
                                               {{ $currentState === 'revoke' ? 'checked' : '' }}
                                               class="text-red-600 focus:ring-red-500">
                                        <span class="text-sm text-red-600">Cabut</span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 mt-6">
            <a href="{{ route('pengguna.index') }}" 
               class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Batal
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="bi bi-check-lg mr-1"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
