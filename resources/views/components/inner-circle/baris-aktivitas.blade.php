@props(['aktivitas'])
<div class="ic-aktivitas">
    <span class="ic-aktivitas__ikon"><x-inner-circle.ikon :nama="$aktivitas->ikon" kelas="ic-ikon--kecil" /></span>
    <div>
        <b>{{ $aktivitas->judul }}</b>
        <small>{{ $aktivitas->tanggal->locale('id')->translatedFormat('j M Y') }} · Terverifikasi</small>
    </div>
    <span class="ic-aktivitas__poin">+{{ $aktivitas->jumlah }} ✦</span>
</div>
