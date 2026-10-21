<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Area anggota: jangan diindeks mesin pencari. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'WS Inner Circle') — Whisnu Santika</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">

    @vite(['resources/css/inner-circle.css', 'resources/js/inner-circle.js'])
    <link rel="icon" href="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" type="image/png">
    @stack('head')
</head>

<body class="ic-app">
    <a class="ic-lewati" href="#konten">Lewati ke konten</a>

    <div class="ic-shell">
        <header class="ic-bar">
            <div>
                <x-inner-circle.logo ukuran="md" />
                <p class="ic-bar__sub">INNER CIRCLE</p>
            </div>
            <div class="ic-bar__aksi">
                {{-- Sementara: tombol keluar baru berfungsi setelah login anggota ada (Fase 4). --}}
                <button type="button" class="ic-tombol ic-tombol--garis" data-ic-keluar>Keluar</button>
                <a href="{{ route('ic.profil') }}" class="ic-bar__avatar" aria-label="Profil saya">
                    <x-inner-circle.avatar :member="$member" />
                </a>
            </div>
        </header>

        <main class="ic-konten" id="konten">
            @yield('content')
        </main>
    </div>

    <x-inner-circle.dock :member="$member" />

    @stack('modal')
</body>

</html>
