{{-- Kontrol urutan tampil satu data di daftar CMS Artis: nomor urut, geser naik/turun, dan "jadikan pertama".
     Props: modul (awalan route: albums|banner|headers|merchandise), id, nama, posisi (dari 1), total, aktif (false saat
     daftar sedang disaring pencarian, karena tetangga di daftar tersaring bukan tetangga yang sebenarnya). --}}
@props(['modul', 'id', 'nama', 'posisi', 'total', 'aktif' => true])
@php
    $rute = route($modul . '.urutan', $id);
    $kelas =
        'inline-flex h-6 w-6 items-center justify-center rounded-md border border-white/10 text-xs text-white/60 transition hover:border-white/30 hover:text-white disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:border-white/10 disabled:hover:text-white/60';
@endphp
<div class="flex shrink-0 flex-col items-center gap-1" data-urutan>
    @if ($aktif)
        <form method="POST" action="{{ $rute }}">
            @csrf @method('PATCH')
            <input type="hidden" name="arah" value="naik">
            <button type="submit" class="{{ $kelas }}" @disabled($posisi <= 1) title="Naikkan"
                aria-label="Naikkan: {{ $nama }}"><i class="bi bi-chevron-up" aria-hidden="true"></i></button>
        </form>
        <span class="text-[11px] font-semibold tabular-nums text-white/50"
            title="Urutan tampil di halaman utama">{{ $posisi }}</span>
        <form method="POST" action="{{ $rute }}">
            @csrf @method('PATCH')
            <input type="hidden" name="arah" value="turun">
            <button type="submit" class="{{ $kelas }}" @disabled($posisi >= $total) title="Turunkan"
                aria-label="Turunkan: {{ $nama }}"><i class="bi bi-chevron-down"
                    aria-hidden="true"></i></button>
        </form>
    @else
        <span class="text-[11px] font-semibold tabular-nums text-white/50"
            title="Urutan tampil di halaman utama">{{ $posisi }}</span>
    @endif
</div>
