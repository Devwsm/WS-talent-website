@props(['pengumuman'])
<article class="ic-pengumuman">
    <div class="ic-pengumuman__ikon ic-warna-{{ $pengumuman->warna }}">
        <x-inner-circle.ikon :nama="$pengumuman->ikon" kelas="ic-ikon--kecil" />
    </div>
    <div>
        <p class="ic-pengumuman__meta">
            {{ $pengumuman->tag ? $pengumuman->tag . ' · ' : '' }}{{ $pengumuman->terbit_pada->locale('id')->translatedFormat('j M Y') }}
        </p>
        <h3 class="ic-pengumuman__judul">{{ $pengumuman->judul }}</h3>
        <p class="ic-pengumuman__isi">{{ $pengumuman->isi }}</p>
        <a class="ic-tombol ic-tombol--kecil ic-tombol--krem"
            href="{{ $pengumuman->cta_url ?? route($pengumuman->cta_tujuan) }}">{{ $pengumuman->cta_label }}
            <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
</article>
