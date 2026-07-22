<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Statistik publik mutasi murid Kota Bogor.">
    <meta name="theme-color" content="#0f172a">
    <title>@yield('title', 'Statistik Publik SIMAK')</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}?v={{ config('app.asset_version') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    @yield('content')
</body>

</html>
