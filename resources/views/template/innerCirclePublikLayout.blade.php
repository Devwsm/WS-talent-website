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
    <x-inner-circle.meta-sosial :judul="trim($__env->yieldContent('title', 'WS Inner Circle')) . ' — Whisnu Santika'" :deskripsi="trim($__env->yieldContent('deskripsi'))" />
    <meta name="theme-color" content="#f8f7fc">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">

    @vite(['resources/css/inner-circle.css', 'resources/js/inner-circle.js'])
    <x-favicon merek="ws-inner-circle" />
    @stack('head')
</head>

<body class="ic-app">
    <a class="ic-lewati" href="#konten">Lewati ke konten</a>

    <div class="ic-shell ic-shell--publik">
        <header class="ic-bar">
            <a href="{{ route('ic.tentang') }}" class="ic-bar__logo" aria-label="WS Inner Circle, halaman utama">
                <x-inner-circle.logo ukuran="md" />
            </a>
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
