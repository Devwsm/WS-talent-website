@props(['ukuran' => 'md'])
{{-- Logo WS Inner Circle: logo kustom dari CMS (Tampilan) bila ada, kalau tidak logo resmi dari aset tim. Tinggi diatur CSS
        lewat .ic-logo--md / --lg; lebar mengikuti rasio gambar. Teks alt memakai nama program. --}}
@php $logoKustom = ic_tampilan()->logoUrl(); @endphp
@if ($logoKustom)
    <img class="ic-logo ic-logo--{{ $ukuran }}" src="{{ $logoKustom }}" alt="{{ ic_program() }}" decoding="async">
@else
    <img class="ic-logo ic-logo--{{ $ukuran }}" src="{{ asset('aset/inner-circle/logo/wsic-wordmark.webp') }}"
        alt="{{ ic_program() }}" width="560" height="193" decoding="async">
@endif
