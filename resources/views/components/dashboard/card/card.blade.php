{{--
    Tile reusable buat halaman utama dashboard: header (judul + tombol "Kelola")
    + body bebas lewat slot. Didesain buat disusun jadi layout bento —
    ukuran tile (col-span/row-span) diatur dari luar lewat wrapper div,
    komponen ini fokus ke isi & styling tile-nya aja.

    Props:
    - title  : judul tile (wajib)
    - href   : link tombol "Kelola" (opsional, kalau gak ada tombol gak muncul)
    - icon   : nama class bootstrap-icons tanpa "bi " (opsional, muncul di sebelah judul)
    - flush  : true kalau body-nya gambar full-bleed (padding jadi 0, biar gambar nempel ke tepi)

    Cara pakai:
    @component('components.dashboard.card.card', ['title' => 'Banner', 'href' => route('banner'), 'icon' => 'bi-images'])
        ... isi body ...
    @endcomponent
--}}
<div
    class="group relative h-full flex flex-col rounded-3xl border border-white/10 bg-white/3 overflow-hidden
    transition-colors hover:border-white/20">
    <div class="flex justify-between items-center gap-4 px-6 py-5 border-b border-white/10 shrink-0">
        <h2 class="flex items-center gap-2 font-bold uppercase tracking-wide text-sm">
            @isset($icon)
                <i class="bi {{ $icon }} text-white/50" aria-hidden="true"></i>
            @endisset
            {{ $title }}
        </h2>
        @isset($href)
            <a href="{{ $href }}"
                class="px-4 py-1.5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors shrink-0">
                Kelola
            </a>
        @endisset
    </div>
    <div class="flex-1 {{ $flush ?? false ? '' : 'p-6' }}">
        {{ $slot }}
    </div>
</div>
