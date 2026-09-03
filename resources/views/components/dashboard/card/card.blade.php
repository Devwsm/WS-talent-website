{{--
    Kartu section reusable buat dashboard: header (judul + tombol "Kelola")
    + body bebas lewat slot. Dipakai di dashboard.blade.php supaya struktur
    "judul section + tombol kelola + border" gak diulang manual di tiap section.

    Cara pakai:
    @component('components.dashboard.card', ['title' => 'Banner', 'href' => route('banner')])
        ... isi body ...
    @endcomponent
--}}
<div class="rounded-lg border border-white/15 bg-white/5 overflow-hidden">
    <div class="flex justify-between items-center gap-4 p-6 border-b border-white/15">
        <h2 class="font-bold uppercase tracking-wide">{{ $title }}</h2>
        @isset($href)
            <a href="{{ $href }}"
                class="px-4 py-2 rounded-lg bg-white text-black text-sm font-semibold hover:bg-white/80 transition-colors">
                Kelola
            </a>
        @endisset
    </div>
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
