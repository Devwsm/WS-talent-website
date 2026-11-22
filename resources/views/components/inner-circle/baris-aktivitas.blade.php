@props(['aktivitas'])
{{-- Satu baris Riwayat aktivitas. Dua jenis: poin (model ic_poin) dan GL gratis (`jenis = gl`, tautan ke detail acara). --}}
@if (data_get($aktivitas, 'jenis') === 'gl')
    <a class="ic-aktivitas ic-aktivitas--gl" href="{{ $aktivitas->tautan }}">
        <span class="ic-aktivitas__ikon"><x-inner-circle.ikon :nama="$aktivitas->ikon" kelas="ic-ikon--kecil" /></span>
        <div>
            <b>{{ $aktivitas->judul }}</b>
            <small>{{ $aktivitas->keterangan }}</small>
        </div>
        <span
            class="ic-gl-saya__status ic-aktivitas__pil {{ $aktivitas->menang ? 'is-menang' : '' }}">{{ $aktivitas->status }}</span>
    </a>
@else
    <div class="ic-aktivitas">
        <span class="ic-aktivitas__ikon"><x-inner-circle.ikon :nama="$aktivitas->ikon" kelas="ic-ikon--kecil" /></span>
        <div>
            <b>{{ $aktivitas->judul }}</b>
            <small>{{ $aktivitas->tanggal->locale('id')->translatedFormat('j M Y') }} ·
                {{ match ($aktivitas->sumber ?? null) {'tukar' => 'Ditahan','kembali' => 'Dikembalikan',default => $aktivitas->jumlah < 0 ? 'Koreksi' : 'Terverifikasi'} }}</small>
        </div>
        <span class="ic-aktivitas__poin">{{ $aktivitas->jumlah > 0 ? '+' : '−' }}{{ ic_angka(abs($aktivitas->jumlah)) }}
            ✦</span>
    </div>
@endif
