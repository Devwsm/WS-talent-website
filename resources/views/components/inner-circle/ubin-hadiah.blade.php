@props(['reward', 'lebar' => false, 'href' => '#klaim'])
<a href="{{ $href }}" class="ic-ubin ic-warna-{{ $reward->warna }} {{ $lebar ? 'ic-ubin--lebar' : '' }}">
    <span class="ic-ubin__tag">{{ $reward->status_member === 'terkunci' ? 'TERKUNCI' : 'TERBUKA' }}</span>
    <span class="ic-ubin__angka">{{ $reward->target_stempel }}<small>Stempel</small></span>
    <x-inner-circle.ikon :nama="$reward->ikon" kelas="ic-ubin__ikon" />
    <span class="ic-ubin__judul">{{ $reward->judul }}</span>
    <span class="ic-ubin__deskripsi">{{ $reward->deskripsi }}</span>
    <span class="ic-ubin__panah" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
</a>
