@props(['reward', 'lebar' => false, 'href' => '#klaim', 'tampilTag' => true])
<a href="{{ $href }}" class="ic-ubin ic-warna-{{ $reward->warna }} {{ $lebar ? 'ic-ubin--lebar' : '' }}">
    @if ($tampilTag)
        <span
            class="ic-ubin__tag">{{ match ($reward->status_member) {'terkunci' => 'TERKUNCI','habis' => 'HABIS',default => 'TERBUKA'} }}</span>
    @endif
    <span class="ic-ubin__angka">{{ ic_angka($reward->harga_poin) }}<small>Poin</small></span>
    <x-inner-circle.ikon :nama="$reward->ikon" kelas="ic-ubin__ikon" />
    <span class="ic-ubin__judul">{{ $reward->judul }}</span>
    <span class="ic-ubin__deskripsi">{{ $reward->deskripsi }}</span>
    <span class="ic-ubin__panah" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
</a>
