<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    @php
        // Default SEO values dipakai kalau halaman nggak nge-override lewat @section.
        $seoTitle = trim($__env->yieldContent('title')) ?: 'Whisnu Santika — DJ & Produser Musik Indonesia';
        $seoDescription =
            trim($__env->yieldContent('meta_description')) ?:
            'Whisnu Santika — DJ & produser asal Jakarta, pionir Indonesian Bounce. Info tur, rilisan musik, merchandise, dan berita terbaru.';
        $seoImage = trim($__env->yieldContent('og_image')) ?: asset('aset/logo/Whisnu-Santika_Logo-2025-White.png');
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph — biar preview link di WA/IG/Twitter nggak kosong --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Whisnu Santika">
    <meta property="og:locale" content="id_ID">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:image" content="{{ $seoImage }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    {{-- Preconnect lebih awal supaya browser mulai konek ke Google Fonts
            paralel dengan proses lain, bukan nunggu CSS ke-parse dulu --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" type="image/png">

    {{-- Structured data (JSON-LD) — tiap halaman bisa push schema-nya sendiri --}}
    @stack('schema')
</head>

<body class="sora bg-black">
    <main>
        @yield('content')
    </main>
</body>

</html>
