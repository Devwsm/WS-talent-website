@props(['merek' => 'ws-artis'])
@php
    // Set favicon per merek ada di public/aset/favicon/{ws-artis|ws-inner-circle}/ (nama berkas standar).
    $dir = 'aset/favicon/' . $merek;
    $warna = $merek === 'ws-inner-circle' ? '#1b68a0' : '#000000';
@endphp
<link rel="icon" href="{{ asset($dir . '/favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset($dir . '/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset($dir . '/favicon-16x16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset($dir . '/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset($dir . '/site.webmanifest') }}">
<meta name="msapplication-TileColor" content="{{ $warna }}">
