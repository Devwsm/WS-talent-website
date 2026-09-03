{{--
    Placeholder buat section yang datanya masih kosong, biar gak keliatan
    kayak error/bug pas belum ada data yang diinput.
    Expects: $message (opsional, default "Belum ada data.")
--}}
<div class="flex flex-col items-center justify-center gap-2 py-8 text-center text-white/40">
    <i class="bi bi-inbox text-3xl" aria-hidden="true"></i>
    <p class="text-sm">{{ $message ?? 'Belum ada data.' }}</p>
</div>
