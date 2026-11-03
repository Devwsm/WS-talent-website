@props(['judul', 'deskripsi' => null])
@php
    // Gambar Open Graph resmi WS Inner Circle (1200x630) dibuat dari aset tim: public/aset/og/og-ws-inner-circle.jpg.
    $gambarOg = asset('aset/og/og-ws-inner-circle.jpg');
    $deskripsiOg =
        $deskripsi ?:
        'Komunitas penggemar resmi Whisnu Santika. Datang ke acara, kumpulkan stempel, dan buka hadiah eksklusif.';
@endphp
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ ic_program() }}">
<meta property="og:locale" content="id_ID">
<meta property="og:title" content="{{ $judul }}">
<meta property="og:description" content="{{ $deskripsiOg }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $gambarOg }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ ic_program() }}, Official Fanbase Whisnu Santika">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $judul }}">
<meta name="twitter:description" content="{{ $deskripsiOg }}">
<meta name="twitter:image" content="{{ $gambarOg }}">
