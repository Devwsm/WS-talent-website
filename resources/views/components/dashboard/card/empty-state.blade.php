{{--
    Placeholder buat section yang datanya masih kosong, biar gak keliatan
    kayak error/bug pas belum ada data yang diinput.
    Expects: $message (opsional, default "Belum ada data.")
    Opsional: $cta_href + $cta_label -> tombol aksi (mis. "Tambah berita")
--}}
<div class="flex flex-col items-center justify-center gap-2 py-8 text-center text-white/40">
    <i class="bi bi-inbox text-3xl" aria-hidden="true"></i>
    <p class="text-sm">{{ $message ?? 'Belum ada data.' }}</p>
    @isset($cta_href)
        <a href="{{ $cta_href }}"
            class="mt-2 px-4 py-1.5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
            {{ $cta_label ?? 'Tambah sekarang' }}
        </a>
    @endisset
</div>
