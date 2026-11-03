<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Area anggota: jangan diindeks mesin pencari. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', ic_program()) — Whisnu Santika</title>
    <x-inner-circle.meta-sosial :judul="trim($__env->yieldContent('title', ic_program())) . ' — Whisnu Santika'" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">

    @vite(['resources/css/inner-circle.css', 'resources/js/inner-circle.js'])
    <x-favicon merek="ws-inner-circle" />
    <x-inner-circle.tema />
    @stack('head')
</head>

{{-- Layout halaman Masuk dan Daftar: kartu di tengah, tanpa header dan dock (belum ada anggota yang masuk). --}}

<body class="ic-app ic-app--auth" data-ic-toast="{{ session('ic_toast') }}">
    <a class="ic-lewati" href="#konten">Lewati ke konten</a>

    <main class="ic-auth" id="konten">
        <div class="ic-auth__kartu">
            <x-inner-circle.logo ukuran="lg" />
            @yield('content')
        </div>
    </main>
</body>

</html>
