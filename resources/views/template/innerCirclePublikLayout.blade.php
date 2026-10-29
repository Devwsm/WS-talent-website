<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Halaman publik penjelasan program: boleh diindeks (area anggota tetap noindex). --}}
    <meta name="robots" content="index, follow">
    <title>@yield('title', 'WS Inner Circle') — Whisnu Santika</title>
    <meta name="description" content="@yield('deskripsi')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'WS Inner Circle') — Whisnu Santika">
    <meta property="og:description" content="@yield('deskripsi')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}">

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

    <div class="ic-shell ic-shell--publik">
        <header class="ic-bar">
            <div>
                <x-inner-circle.logo ukuran="md" />
                <p class="ic-bar__sub">INNER CIRCLE</p>
            </div>
            <div class="ic-bar__aksi">
                @if ($sudahMasuk)
                    <a href="{{ route('ic.beranda') }}" class="ic-tombol">Buka Inner Circle</a>
                @else
                    <a href="{{ route('ic.masuk') }}" class="ic-tombol ic-tombol--garis">Masuk</a>
                    <a href="{{ route('ic.daftar') }}" class="ic-tombol">Daftar</a>
                @endif
            </div>
        </header>

        <main class="ic-konten ic-konten--publik" id="konten">
            @yield('content')
        </main>

        <footer class="ic-footer-publik">
            <p>&copy; {{ date('Y') }} Whisnu Santika ·
                <a href="{{ route('home') }}">Situs resmi</a> ·
                <a href="{{ route('privasi') }}">Kebijakan Privasi</a>
            </p>
        </footer>
    </div>
</body>

</html>
