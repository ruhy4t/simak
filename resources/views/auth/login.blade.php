<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title>Login - SIMAK Kota Bogor</title>

    {{-- Preload Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    </noscript>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}?v={{ config('app.asset_version') }}">

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Animated Gradient Blob */
        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }

        /* Custom Modern Inputs */
        .modern-input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-input:focus {
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
            border-color: #0ea5e9;
        }

        /* Glass Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body class="bg-white min-h-screen selection:bg-sky-500 selection:text-white" oncontextmenu="return false;">

    <script defer src="{{ asset('js/security.js') }}?v={{ config('app.asset_version') }}"></script>

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">

        <!-- Left Side: Login Form -->
        <div class="flex flex-col justify-center items-center p-8 sm:p-12 lg:p-20 bg-white relative">
            <div class="w-full max-w-md space-y-8">

                <!-- Mobile Logo (visible only on small screens) -->
                <div class="lg:hidden text-center mb-8">
                    <div
                        class="w-16 h-16 bg-linear-to-tr from-sky-500 to-blue-600 rounded-2xl flex items-center justify-center text-white mx-auto shadow-lg shadow-sky-500/30">
                        <i class="bi bi-building text-3xl"></i>
                    </div>
                </div>

                <!-- Header -->
                <div class="text-center lg:text-left space-y-2">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Selamat Datang 👋</h1>
                    <p class="text-slate-500">Silakan masuk untuk mengakses SIMAK.</p>
                </div>

                <!-- Alerts -->
                @if ($errors->any())
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 flex items-start gap-4 animate-shake">
                        <div class="shrink-0 w-5 h-5 rounded-full bg-rose-100 flex items-center justify-center mt-0.5">
                            <i class="bi bi-exclamation-circle-fill text-rose-500 text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-rose-900">Login Gagal</h3>
                            <p class="text-sm text-rose-600 mt-1">{{ $errors->first() }}</p>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 flex items-start gap-4">
                        <div
                            class="shrink-0 w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center mt-0.5">
                            <i class="bi bi-check-circle-fill text-emerald-500 text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-emerald-900">Berhasil</h3>
                            <p class="text-sm text-emerald-600 mt-1">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6" autocomplete="on">
                    @csrf

                    <div class="space-y-5">
                        <div class="space-y-1.5">
                            <label for="username" class="text-sm font-medium text-slate-700">Username</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i
                                        class="bi bi-person text-slate-400 group-hover:text-sky-500 transition-colors"></i>
                                </div>
                                <input type="text" id="username" name="username"
                                    value="{{ old('username') }}" required
                                    autofocus
                                    autocomplete="username" autocapitalize="none" spellcheck="false"
                                    class="modern-input block w-full pl-11 pr-4 py-3.5 bg-slate-50 border-gray-200 border rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:bg-white focus:outline-hidden"
                                    placeholder="Masukkan username Anda">
                            </div>
                        </div>

                        <div class="space-y-1.5" x-data="{ show: false }">
                            <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-lock text-slate-400 group-hover:text-sky-500 transition-colors"></i>
                                </div>
                                <input :type="show ? 'text' : 'password'" id="password" name="password" required
                                    autocomplete="current-password"
                                    class="modern-input block w-full pl-11 pr-12 py-3.5 bg-slate-50 border-gray-200 border rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:bg-white focus:outline-hidden"
                                    placeholder="••••••••">
                                <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-slate-700 focus:outline-hidden transition z-10 cursor-pointer">
                                    <i class="bi" :class="show ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full relative group overflow-hidden rounded-xl bg-linear-to-r from-sky-500 to-blue-600 p-px shadow-lg shadow-sky-500/20 hover:shadow-sky-500/30 transition-all duration-300 hover:scale-[1.01]">
                        <div
                            class="relative flex items-center justify-center gap-2 bg-linear-to-r from-sky-500 to-blue-600 px-6 py-3.5 rounded-xl transition-all group-hover:bg-opacity-90">
                            <span class="font-semibold text-white tracking-wide">Masuk Aplikasi</span>
                            <i class="bi bi-arrow-right text-white group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </button>

                    <a href="{{ route('public.statistics') }}"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">
                        <i class="bi bi-bar-chart-line"></i>
                        Lihat Statistik Publik
                    </a>

                </form>

                <!-- Footer Mobile -->
                <p class="lg:hidden text-center text-xs text-slate-400 mt-8">
                    &copy; {{ date('Y') }} Kurikulum SMP - Dinas Pendidikan Kota Bogor<br>
                    SIMAK v{{ config('app.version') }}
                </p>
            </div>
        </div>

        <!-- Right Side: Visual Branding (Hidden on Mobile) -->
        <div class="hidden lg:flex relative bg-slate-900 items-center justify-center overflow-hidden">
            <!-- Background Elements -->
            <div
                class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1620121692029-d088224ddc84?q=80&w=2832&auto=format&fit=crop')] bg-cover bg-center opacity-20 transform scale-110">
            </div>
            <div class="absolute inset-0 bg-linear-to-br from-slate-900/95 via-slate-900/80 to-slate-900/40"></div>

            <!-- Animated Blobs -->
            <div
                class="absolute top-0 -left-4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob">
            </div>
            <div
                class="absolute top-0 -right-4 w-96 h-96 bg-sky-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000">
            </div>
            <div
                class="absolute -bottom-8 left-20 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000">
            </div>

            <!-- Content Card -->
            <div
                class="relative glass-card p-12 rounded-3xl max-w-lg text-center border-l border-t border-white/10 shadow-2xl backdrop-blur-md transform hover:scale-[1.01] transition-all duration-500">
                <div class="mb-8 inline-flex p-4 rounded-2xl bg-white/5 border border-white/10 shadow-inner">
                    <img src="{{ asset('images/logo simak.png') }}" alt="SIMAK Logo"
                        class="h-20 w-auto drop-shadow-2xl">
                </div>

                <h2 class="text-3xl font-bold text-white mb-4 tracking-tight">SIMAK Kota Bogor</h2>
                <p class="text-slate-300 text-lg leading-relaxed font-light">
                    Sistem Informasi Mutasi masuk dan keluar siswa untuk pengelolaan data pendidikan yang lebih
                    transparan dan efisien.
                </p>

                <div class="mt-8 flex items-center justify-center gap-2 text-sm text-slate-400 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sistem Terintegrasi</span>
                </div>
            </div>

            <!-- Absolute Footer -->
            <div class="absolute bottom-8 text-slate-500 text-xs font-medium tracking-wide text-center">
                <div>&copy; {{ date('Y') }} Kurikulum SMP - Dinas Pendidikan Kota Bogor</div>
                <div class="mt-1">SIMAK v{{ config('app.version') }}</div>
            </div>
        </div>
    </div>

</body>

</html>
