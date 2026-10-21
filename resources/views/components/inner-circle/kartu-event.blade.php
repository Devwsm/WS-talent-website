@props(['event', 'dipilih' => false, 'href' => null])
@php
    $tipe = config('inner_circle.tipe_event.' . $event->tipe);
    $statusWaktu =
        ['lewat' => 'Sudah lewat', 'hari_ini' => 'Hari ini', 'akan_datang' => 'Akan datang'][$event->status_waktu] ??
        '';
    $catatan =
        $event->tipe === 'private' ? 'Tidak untuk umum' : (!$event->venue ? 'Venue belum diumumkan' : $statusWaktu);
    $bisaDipilih = $event->bisa_checkin && $href;
    $tag = $bisaDipilih ? 'a' : 'div';
@endphp
{{-- Event privat dan event tanpa venue tetap tampil, tapi tidak bisa dipilih untuk check-in. --}}
<{{ $tag }} @if ($bisaDipilih) href="{{ $href }}" @else aria-disabled="true" @endif
    class="ic-event ic-warna-{{ $tipe['warna'] }} {{ $dipilih ? 'is-dipilih' : '' }} {{ $bisaDipilih ? '' : 'is-nonaktif' }}"
    @if ($dipilih) aria-current="true" @endif>
    <span class="ic-event__seni"><x-inner-circle.ikon :nama="$tipe['ikon']" kelas="ic-ikon--event" /></span>
    <span class="ic-event__isi">
        <span class="ic-event__tanggal">{{ $event->tanggal->locale('id')->translatedFormat('D, j M Y') }}</span>
        <b class="ic-event__venue">{{ $event->venue ?? 'Venue belum diumumkan' }}</b>
        <span class="ic-event__kota"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
            {{ $event->kota ?? 'Kota belum diumumkan' }}</span>
        <span class="ic-event__lencana">{{ $tipe['label'] }} · {{ $catatan }}</span>
        @if ($event->sudah_checkin)
            <span class="ic-event__lencana ic-event__lencana--ok"><i class="bi bi-check-circle-fill"
                    aria-hidden="true"></i> Sudah check-in</span>
        @endif
    </span>
    <span class="ic-event__panah" aria-hidden="true"><i
            class="bi {{ $bisaDipilih ? 'bi-chevron-right' : 'bi-dash' }}"></i></span>
    </{{ $tag }}>
