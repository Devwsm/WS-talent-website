@props(['tipe', 'besar' => false])
{{-- Poster bawaan sebelum acara punya gambar: ikon tipe acara di lingkaran lembut, dengan label tipe di bawahnya.
    Dipakai kartu poster (daftar acara) dan detail acara supaya keduanya serupa dan rapi. --}}
<span class="ic-poster-kosong {{ $besar ? 'ic-poster-kosong--besar' : '' }}" aria-hidden="true">
    <span class="ic-poster-kosong__lingkar"><x-inner-circle.ikon :nama="$tipe['ikon']"
            kelas="ic-poster-kosong__ikon" /></span>
    <span class="ic-poster-kosong__label">{{ $tipe['label'] }}</span>
</span>
