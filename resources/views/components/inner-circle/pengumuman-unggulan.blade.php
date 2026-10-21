@props(['pengumuman'])
<div class="ic-unggulan">
    <p class="ic-unggulan__label">● KABAR PILIHAN</p>
    <h3 class="ic-unggulan__judul">{{ $pengumuman->judul }}</h3>
    <p class="ic-unggulan__isi">{{ $pengumuman->isi }}</p>
    <a class="ic-tombol" href="{{ route($pengumuman->cta_tujuan) }}">{{ $pengumuman->cta_label }}
        <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    <span class="ic-unggulan__bintang" aria-hidden="true">✳</span>
</div>
