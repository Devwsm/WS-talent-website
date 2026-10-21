@props(['nama', 'kelas' => ''])
{{-- Ikon gambar tetap (WebP) supaya tampilannya sama di semua perangkat; kalau berkas belum ada,
    jatuh ke emoji dari config/inner_circle.php. --}}
@php
    $berkas = 'aset/inner-circle/ikon/' . $nama . '.webp';
@endphp
@if (file_exists(public_path($berkas)))
    <img src="{{ asset($berkas) }}" alt="" width="256" height="256" loading="lazy" decoding="async"
        class="ic-ikon {{ $kelas }}">
@else
    <span class="ic-ikon ic-ikon--emoji {{ $kelas }}"
        aria-hidden="true">{{ config('inner_circle.ikon.' . $nama, '✦') }}</span>
@endif
