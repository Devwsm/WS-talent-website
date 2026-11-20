@props(['event', 'href' => null])
@php
    $tipe = ic_tipe_event()[$event->tipe] ?? ['label' => 'Acara', 'ikon' => 'ticket', 'warna' => 'lilac'];
    $bisaDibuka = $event->bisa_checkin && $href;
    $tag = $bisaDibuka ? 'a' : 'div';
    $catatan = $event->tipe === 'private' ? 'Tidak untuk umum' : (!$event->venue ? 'Venue belum diumumkan' : null);
@endphp
{{-- Kartu poster di daftar acara: poster tegak, lalu judul, tempat, dan tanggal. Acara privat atau tanpa venue tampil tanpa tautan. --}}
<{{ $tag }} @if ($bisaDibuka) href="{{ $href }}" @else aria-disabled="true" @endif
    class="ic-poster-kartu {{ $bisaDibuka ? '' : 'is-nonaktif' }}">
    <span class="ic-poster-kartu__gambar ic-warna-{{ $tipe['warna'] }}">
        @if ($event->poster_url)
            <img class="ic-foto-poster" src="{{ $event->poster_url }}" alt="Poster {{ $event->venue }}" width="300"
                height="400" loading="lazy" decoding="async">
        @else
            <x-inner-circle.poster-kosong :tipe="$tipe" />
        @endif
        @if ($event->sudah_checkin)
            <span class="ic-poster-kartu__lencana"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Sudah
                check-in</span>
        @elseif ($event->status_waktu === 'hari_ini')
            <span class="ic-poster-kartu__lencana">Hari ini</span>
        @endif
    </span>
    <b class="ic-poster-kartu__judul">{{ $event->venue ?? 'Venue belum diumumkan' }}</b>
    <span class="ic-poster-kartu__info">{{ $event->kota ?? 'Kota belum diumumkan' }}</span>
    <span
        class="ic-poster-kartu__info ic-poster-kartu__tanggal">{{ $event->tanggal->locale('id')->translatedFormat('D, j M Y') }}
        @if ($catatan)
            · {{ $catatan }}
        @endif
    </span>
    </{{ $tag }}>
