@props(['item'])
@if ($item->terjadwal)
    <span
        class="inline-flex w-fit shrink-0 items-center gap-1 rounded-full border border-sky-300/30 bg-sky-300/10 px-2 py-0.5 text-[10px] font-semibold text-sky-200"
        title="Belum tampil di website">
        <i class="bi bi-clock" aria-hidden="true"></i>
        Terjadwal · {{ $item->terbit_pada->locale('id')->translatedFormat('j M Y, H.i') }}
    </span>
@endif
