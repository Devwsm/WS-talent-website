<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Area anggota: jangan diindeks mesin pencari. --}}
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WS Inner Circle') — Whisnu Santika</title>
    <x-inner-circle.meta-sosial :judul="trim($__env->yieldContent('title', 'WS Inner Circle')) . ' — Whisnu Santika'" />
    <meta name="theme-color" content="#f8f7fc">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">

    @vite(['resources/css/inner-circle.css', 'resources/js/inner-circle.js'])
    <x-favicon merek="ws-inner-circle" />
    @stack('head')
</head>

<body class="ic-app" data-ic-toast="{{ session('ic_toast') }}" data-ic-toast-galat="{{ session('ic_toast_galat') }}">
    <a class="ic-lewati" href="#konten">Lewati ke konten</a>

    <div class="ic-shell">
        <header class="ic-bar">
            <a href="{{ route('ic.beranda') }}" class="ic-bar__logo" aria-label="WS Inner Circle, beranda">
                <x-inner-circle.logo ukuran="md" />
            </a>
            <div class="ic-bar__aksi">
                {{-- Keluar wajib POST + CSRF (guard `member`); sesi admin CMS di browser yang sama tidak ikut keluar. --}}
                <form method="POST" action="{{ route('ic.keluar') }}">
                    @csrf
                    <button type="submit" class="ic-tombol ic-tombol--garis">Keluar</button>
                </form>
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
