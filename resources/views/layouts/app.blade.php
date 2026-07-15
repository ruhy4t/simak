<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    @php
        $simakAjaxSafeRoutes = config('ajax_navigation.safe_routes', []);
        $simakAjaxSafeUrls = collect($simakAjaxSafeRoutes)
            ->filter(fn ($routeName) => Route::has($routeName))
            ->map(fn ($routeName) => route($routeName))
            ->values();
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">

    <script>
        window.SIMAK_NAVIGATION = {
            safeUrls: @json($simakAjaxSafeUrls),
        };
    </script>

    <title>@yield('title', 'SIMAK') - Sistem Informasi Mutasi</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Preload critical fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    </noscript>

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}?v={{ config('app.asset_version') }}">

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Modern Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Glass/Modern Sidebar */
        .glass-sidebar {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            width: 18rem;
            /* 72 equivalent */
        }

        /* Layout Fixes (Force without Tailwind Build) */
        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        @media (min-width: 1024px) {
            .main-wrapper {
                margin-left: 18rem;
                /* Match sidebar width */
            }
        }

        /* Active States */
        .nav-item-active {
            background: linear-gradient(90deg, #0ea5e9 0%, #0284c7 100%);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-item-inactive {
            color: #94a3b8;
        }

        .nav-item-inactive:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #f1f5f9;
        }

        /* Page Transition */
        .page-enter {
            animation: fadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Notification Animations */
        @keyframes bounce-short {

            0%,
            100% {
                transform: translateY(0);
            }

            20% {
                transform: translateY(-5px);
            }

            40% {
                transform: translateY(0);
            }

            60% {
                transform: translateY(-2px);
            }

            80% {
                transform: translateY(0);
            }
        }

        .animate-bounce-short {
            animation: bounce-short 0.8s ease-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }
    </style>
    @stack('styles')
</head>

<body class="bg-slate-50 font-sans antialiased text-slate-800">

    <script defer src="{{ asset('js/security.js') }}?v={{ config('app.asset_version') }}"></script>

    @auth
        <script>
            window.SESSION_CONFIG = {
                lifetime: {{ config('session.lifetime') }}, // Get from Laravel config
                aliveUrl: '{{ route('session.alive') }}',
                loginUrl: '{{ route('login') }}',
                warningMinutes: 2
            };
        </script>
        <script defer src="{{ asset('js/session-monitor.js') }}?v={{ config('app.asset_version') }}"></script>
    @endauth

    @auth
        <div class="flex min-h-screen"
            x-data="{ sidebarOpen: false, mutasiOpen: {{ request()->routeIs('mutasi.*') ? 'true' : 'false' }} }">

            <!-- Sidebar Desktop -->
            <aside
                class="hidden lg:flex lg:flex-col glass-sidebar text-white fixed h-screen z-30 transition-all duration-300">
                <!-- Logo -->
                <div class="p-8 pb-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-linear-to-br from-white/10 to-white/5 rounded-2xl border border-white/10 flex items-center justify-center shadow-inner">
                            <i class="bi bi-building text-2xl text-sky-400"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-white">SIMAK</h1>
                            <p class="text-xs text-slate-400 font-medium tracking-wide">Kota Bogor</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav id="simak-desktop-navigation" class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                        <i
                            class="bi bi-grid-fill text-lg {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-500 group-hover:text-white transition-colors' }}"></i>
                        <span class="text-sm">Dashboard</span>
                    </a>

                    <!-- Mutasi Header -->
                    <div class="pt-4 pb-2 px-4 text-[10px] uppercase tracking-wider text-slate-500 font-bold">
                        Menu Utama
                    </div>

                    <!-- Mutasi Dropdown -->
                    <div x-data="{ open: mutasiOpen }" class="space-y-1">
                        <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('mutasi.*') ? 'bg-white/5 text-white' : 'nav-item-inactive' }}">
                            <div class="flex items-center gap-3">
                                <div class="p-1 rounded-sm bg-white/5 group-hover:bg-sky-500/20 transition-colors">
                                    <i class="bi bi-arrow-left-right text-sky-400"></i>
                                </div>
                                <span class="text-sm font-medium">Mutasi Siswa</span>
                            </div>
                            <i class="bi bi-chevron-down text-xs transition-transform duration-300 text-slate-500"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open" x-collapse class="pl-4 pr-2 space-y-1 pt-1">
                            <a href="{{ route('mutasi.masuk.index') }}"
                                class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm group {{ request()->routeIs('mutasi.masuk.*') ? 'bg-sky-500/10 text-sky-400 font-medium' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span
                                    class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('mutasi.masuk.*') ? 'bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.6)]' : 'bg-slate-600 group-hover:bg-slate-400' }}"></span>
                                <span>Mutasi Masuk</span>
                            </a>
                            <a href="{{ route('mutasi.keluar.index') }}"
                                class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm group {{ request()->routeIs('mutasi.keluar.*') ? 'bg-sky-500/10 text-sky-400 font-medium' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span
                                    class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('mutasi.keluar.*') ? 'bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.6)]' : 'bg-slate-600 group-hover:bg-slate-400' }}"></span>
                                <span>Mutasi Keluar</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Menus -->
                    @if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin', 'Sekolah', 'Operator']))
                        <div class="pt-6 pb-2 px-4 text-[10px] uppercase tracking-wider text-slate-500 font-bold">
                            Administrasi
                        </div>
                    @endif

                    @if(Auth::user()->role === 'Sekolah')
                        <a href="{{ route('users.manage') }}"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('users.manage') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                            <i
                                class="bi bi-people-fill text-lg {{ request()->routeIs('users.manage') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}"></i>
                            <span class="text-sm">Kelola Pengguna</span>
                        </a>
                    @endif

                    @if(in_array(Auth::user()->role, ['Sekolah', 'Operator']))
                        <a href="{{ route('sekolah.profile') }}"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('sekolah.profile') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                            <i
                                class="bi bi-building-gear text-lg {{ request()->routeIs('sekolah.profile') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}"></i>
                            <span class="text-sm">Profil Sekolah</span>
                        </a>
                    @endif

                    @if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin']))
                        <a href="{{ route('satpen.index') }}"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('satpen.*') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                            <i
                                class="bi bi-building-fill text-lg {{ request()->routeIs('satpen.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}"></i>
                            <span class="text-sm">Satuan Pendidikan</span>
                        </a>
                        <a href="{{ route('dinas.users') }}"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('dinas.users') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                            <i
                                class="bi bi-people-fill text-lg {{ request()->routeIs('dinas.users') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}"></i>
                            <span class="text-sm">Kelola Pengguna</span>
                        </a>
                        <a href="{{ route('penandatangan.index') }}"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('penandatangan.*') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                            <i
                                class="bi bi-pen-fill text-lg {{ request()->routeIs('penandatangan.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}"></i>
                            <span class="text-sm">Penandatangan</span>
                        </a>
                        <a href="{{ route('admin.regions.index') }}"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.regions.*') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                            <i
                                class="bi bi-map-fill text-lg {{ request()->routeIs('admin.regions.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}"></i>
                            <span class="text-sm">Wilayah</span>
                        </a>
                    @endif

                    <a href="{{ route('laporan.index') }}"
                        class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('laporan.*') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                        <i
                            class="bi bi-file-earmark-text-fill text-lg {{ request()->routeIs('laporan.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}"></i>
                        <span class="text-sm">Laporan</span>
                    </a>

                    @if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin']))
                        <a href="{{ route('settings.index') }}"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('settings.*') ? 'nav-item-active font-medium' : 'nav-item-inactive' }}">
                            <i
                                class="bi bi-gear-fill text-lg {{ request()->routeIs('settings.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}"></i>
                            <span class="text-sm">Pengaturan</span>
                        </a>
                    @endif

                    <div class="h-8"></div> {{-- Spacer --}}
                </nav>

                <!-- Bottom Profile Section -->
                <div class="p-4 border-t border-white/5 bg-black/20 backdrop-blur-md">
                    <a href="{{ route('profile.index') }}"
                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 transition-colors group">
                        <div
                            class="w-9 h-9 rounded-full bg-linear-to-tr from-sky-400 to-blue-600 flex items-center justify-center text-white font-bold shadow-lg shadow-sky-500/20 ring-2 ring-white/10">
                            {{ substr(Auth::user()->username, 0, 1) }}
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <p class="text-sm font-medium text-white truncate group-hover:text-sky-300 transition-colors">
                                {{ Auth::user()->nama_pengguna ?? Auth::user()->username }}
                            </p>
                            <p class="text-xs text-slate-400 truncate">{{ Auth::user()->role }}</p>
                        </div>
                    </a>

                    <form action="{{ route('logout') }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-medium bg-white/5 text-slate-300 hover:bg-red-500/10 hover:text-red-400 transition-all border border-white/5 hover:border-red-500/20">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Keluar Aplikasi</span>
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            {{-- Added lg:ml-72 to account for fixed sidebar width 18rem (72) --}}
            <div class="flex-1 flex flex-col main-wrapper min-h-screen transition-all duration-300">

                <!-- Top Bar (Mobile) -->
                <header
                    class="lg:hidden bg-white/80 backdrop-blur-md border-b border-gray-100 px-4 py-3 flex items-center justify-between sticky top-0 z-20">
                    <button @click="sidebarOpen = true"
                        class="p-2 -ml-2 text-slate-600 hover:bg-slate-100 rounded-lg transition">
                        <i class="bi bi-list text-2xl"></i>
                    </button>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-sky-600 flex items-center justify-center text-white">
                            <i class="bi bi-building"></i>
                        </div>
                        <span class="font-bold text-slate-800">SIMAK</span>
                    </div>
                    <div class="w-8"></div>
                </header>

                <!-- Page Content -->
                <main id="simak-page-content"
                    data-simak-navigation-safe="{{ request()->routeIs(...$simakAjaxSafeRoutes) ? 'true' : 'false' }}"
                    class="flex-1 p-4 lg:p-8 {{ $maxWidth ?? 'max-w-full' }} mx-auto w-full page-enter">

                    <!-- Notification Modals (Alpine) -->
                    @if(session('success'))
                        <div x-data="{ show: true }" x-show="show" style="display: none;"
                            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-0">
                            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                                @click="show = false"></div>
                            <div
                                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-md border border-slate-100 ring-1 ring-black/5 animate-bounce-short">
                                <div class="bg-linear-to-r from-emerald-50 to-white px-6 py-6 flex items-start gap-4">
                                    <div
                                        class="shrink-0 w-12 h-12 rounded-full bg-emerald-100/50 flex items-center justify-center shadow-xs">
                                        <i class="bi bi-check-lg text-emerald-600 text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">Berhasil!</h3>
                                        <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                                            {!! nl2br(e(session('success'))) !!}
                                        </p>
                                    </div>
                                </div>
                                <div class="bg-gray-50/50 px-6 py-4 flex justify-end">
                                    <button type="button" @click="show = false"
                                        class="inline-flex justify-center rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 hover:bg-emerald-500 hover:-translate-y-0.5 transition-all duration-200">
                                        OK, Mengerti
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- CUSTOM NOTIFICATION: WORKING HOURS --}}
                    @if(session('working_hours'))
                        <div x-data="{ show: true }" x-show="show" style="display: none;"
                            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-0">
                            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                                @click="show = false"></div>
                            <div
                                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-md border border-slate-100 ring-1 ring-black/5 animate-bounce-short">
                                <div class="bg-linear-to-r from-indigo-50 to-white px-6 py-6 flex items-start gap-4">
                                    <div
                                        class="shrink-0 w-12 h-12 rounded-full bg-indigo-100/50 flex items-center justify-center shadow-xs">
                                        <i class="bi bi-shop-window text-indigo-600 text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">Layanan Tutup Sementara</h3>
                                        <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                                            {!! nl2br(e(session('working_hours'))) !!}
                                        </p>
                                    </div>
                                </div>
                                <div class="bg-gray-50/50 px-6 py-4 flex justify-end">
                                    <button type="button" @click="show = false"
                                        class="inline-flex justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 hover:bg-indigo-500 hover:-translate-y-0.5 transition-all duration-200">
                                        OK, Saya Mengerti
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div x-data="{ show: true }" x-show="show" style="display: none;"
                            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-0">
                            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                                @click="show = false"></div>
                            <div
                                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-md border border-slate-100 ring-1 ring-black/5 animate-shake">
                                <div class="bg-linear-to-r from-rose-50 to-white px-6 py-6 flex items-start gap-4">
                                    <div
                                        class="shrink-0 w-12 h-12 rounded-full bg-rose-100/50 flex items-center justify-center shadow-xs">
                                        <i class="bi bi-x-lg text-rose-600 text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">Gagal!</h3>
                                        <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                                            {!! nl2br(e(session('error'))) !!}
                                        </p>
                                    </div>
                                </div>
                                <div class="bg-gray-50/50 px-6 py-4 flex justify-end">
                                    <button type="button" @click="show = false"
                                        class="inline-flex justify-center rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-rose-500/20 hover:bg-rose-500 hover:-translate-y-0.5 transition-all duration-200">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- GLOBAL VALIDATION ERRORS LINK --}}
                    @if($errors->any())
                        <div x-data="{ show: true }" x-show="show" style="display: none;"
                            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-0">
                            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                                @click="show = false"></div>
                            <div
                                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-md border border-slate-100 ring-1 ring-black/5 animate-shake">
                                <div class="bg-linear-to-r from-orange-50 to-white px-6 py-6 flex items-start gap-4">
                                    <div
                                        class="shrink-0 w-12 h-12 rounded-full bg-orange-100/50 flex items-center justify-center shadow-xs">
                                        <i class="bi bi-exclamation-triangle text-orange-600 text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">Perhatian!</h3>
                                        <div class="mt-2 text-sm text-gray-600 leading-relaxed">
                                            <ul class="list-disc list-inside space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50/50 px-6 py-4 flex justify-end">
                                    <button type="button" @click="show = false"
                                        class="inline-flex justify-center rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-orange-500/20 hover:bg-orange-500 hover:-translate-y-0.5 transition-all duration-200">
                                        Perbaiki
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @yield('content')

                </main>

                <!-- Footer -->
                <footer class="mt-auto py-6 text-center text-xs text-slate-400">
                    <p>&copy; {{ date('Y') }} Kurikulum SMP - Dinas Pendidikan Kota Bogor</p>
                    <p class="mt-1">SIMAK v{{ config('app.version') }}</p>
                </footer>

            </div>

            <!-- Mobile Sidebar Overlay -->
            <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-50 lg:hidden"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-xs" @click="sidebarOpen = false"></div>

                <aside class="absolute left-0 top-0 bottom-0 w-72 glass-sidebar text-white shadow-2xl"
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">

                    <!-- Same content as desktop sidebar basically but without fixed class -->
                    <div class="p-6 flex items-center justify-between border-b border-white/5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                                <i class="bi bi-building text-sky-400"></i>
                            </div>
                            <span class="font-bold text-lg">SIMAK</span>
                        </div>
                        <button @click="sidebarOpen = false" class="text-slate-400 hover:text-white transition">
                            <i class="bi bi-x-lg text-xl"></i>
                        </button>
                    </div>

                    <nav id="simak-mobile-navigation" class="p-4 space-y-1 overflow-y-auto h-[calc(100vh-80px)]">
                        <!-- Content duplicates desktop navigation logic suitable for mobile -->
                        {{-- Simplified Mobile Nav logic reusing same classes --}}
                        <a href="{{ route('dashboard') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('dashboard') ? 'nav-item-active' : 'nav-item-inactive' }}">
                            <i class="bi bi-grid-fill"></i> <span>Dashboard</span>
                        </a>

                        {{-- Mutasi Mobile --}}
                        <div x-data="{ open: true }" class="space-y-1 mt-2">
                            <button @click="open = !open"
                                class="w-full flex justify-between px-4 py-3 rounded-xl nav-item-inactive">
                                <div class="flex gap-3"><i class="bi bi-arrow-left-right"></i> <span>Mutasi</span></div>
                                <i class="bi bi-chevron-down text-xs" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open" class="pl-4 space-y-1">
                                <a href="{{ route('mutasi.masuk.index') }}"
                                    class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('mutasi.masuk.*') ? 'text-sky-400' : 'text-slate-400' }}">Mutasi
                                    Masuk</a>
                                <a href="{{ route('mutasi.keluar.index') }}"
                                    class="block px-4 py-2 rounded-lg text-sm {{ request()->routeIs('mutasi.keluar.*') ? 'text-sky-400' : 'text-slate-400' }}">Mutasi
                                    Keluar</a>
                            </div>
                        </div>

                        @if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin', 'Sekolah', 'Operator']))
                            <div class="pt-6 pb-2 px-4 text-[10px] uppercase tracking-wider text-slate-500 font-bold">
                                Administrasi
                            </div>
                        @endif

                        @if(Auth::user()->role === 'Sekolah')
                            <a href="{{ route('users.manage') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('users.manage') ? 'nav-item-active' : 'nav-item-inactive' }}">
                                <i class="bi bi-people-fill"></i> <span>Kelola Pengguna</span>
                            </a>
                        @endif

                        @if(in_array(Auth::user()->role, ['Sekolah', 'Operator']))
                            <a href="{{ route('sekolah.profile') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('sekolah.profile') ? 'nav-item-active' : 'nav-item-inactive' }}">
                                <i class="bi bi-building-gear"></i> <span>Profil Sekolah</span>
                            </a>
                        @endif

                        @if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin']))
                            <a href="{{ route('satpen.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('satpen.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                                <i class="bi bi-building-fill"></i> <span>Satuan Pendidikan</span>
                            </a>
                            <a href="{{ route('dinas.users') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('dinas.users') ? 'nav-item-active' : 'nav-item-inactive' }}">
                                <i class="bi bi-people-fill"></i> <span>Kelola Pengguna</span>
                            </a>
                            <a href="{{ route('penandatangan.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('penandatangan.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                                <i class="bi bi-pen-fill"></i> <span>Penandatangan</span>
                            </a>
                            <a href="{{ route('admin.regions.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('admin.regions.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                                <i class="bi bi-map-fill"></i> <span>Wilayah</span>
                            </a>
                        @endif

                        <a href="{{ route('laporan.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('laporan.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                            <i class="bi bi-file-earmark-text-fill"></i> <span>Laporan</span>
                        </a>

                        @if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin']))
                            <a href="{{ route('settings.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('settings.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                                <i class="bi bi-gear-fill"></i> <span>Pengaturan</span>
                            </a>
                        @endif

                        <a href="{{ route('profile.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('profile.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                            <i class="bi bi-person-gear"></i> <span>Profil Saya</span>
                        </a>

                        <!-- Mobile Logout -->
                        <form action="{{ route('logout') }}" method="POST" class="mt-8 pt-4 border-t border-white/10">
                            @csrf
                            <button
                                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-rose-400 hover:bg-rose-500/10 transition">
                                <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
                            </button>
                        </form>
                    </nav>
                </aside>
            </div>

        </div>

    @else
        @yield('content')
    @endauth

    {{-- Alpine.js now bundled via Vite in app.js --}}

    {{-- Custom Confirm Modal - Global --}}
    @include('components.confirm-modal')

    {{-- Manual Image Compression helper (Moved up for priority) --}}
    <script defer src="{{ asset('js/manual-compression.js') }}?v={{ config('app.asset_version') }}"></script>

    @stack('scripts')

    <!-- Instant.page for faster navigation (Preloading on hover) -->



</html>
