@props(['ukuran' => 'md'])
{{-- Logo resmi WS Inner Circle (aset tim). Tinggi diatur CSS lewat .ic-logo--md / --lg; lebar mengikuti rasio gambar. --}}
<img class="ic-logo ic-logo--{{ $ukuran }}" src="{{ asset('aset/inner-circle/logo/wsic-wordmark.webp') }}"
        alt="WS Inner Circle" width="560" height="193" decoding="async">
