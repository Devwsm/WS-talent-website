{{-- Teaser WS Inner Circle di homepage. Dipanggil dari pages/home.blade.php hanya bila INNER_CIRCLE_AKTIF menyala.
     Mengikuti tema situs publik (hitam, pill rounded-full, Bootstrap Icons) — bukan tema lavender area anggota,
     karena CSS Inner Circle sengaja tidak dimuat di halaman publik (lihat README bagian 4.2). --}}
@php
    $sudahMasuk = \Illuminate\Support\Facades\Auth::guard('member')->check();
    // Semua teks dan poin di bawah bisa diubah dari CMS Inner Circle, tab Konten (bagian "Teaser di beranda situs artis").
    $poin = ic_daftar('teaser_poin');
@endphp

<section id="inner-circle" aria-labelledby="inner-circle-heading"
    class="bg-black border-b border-white/10 px-6 py-16 md:px-16 md:py-24 lg:px-52">
    <div class="max-w-3xl mx-auto flex flex-col items-center text-center gap-6">
        <p class="text-xs uppercase tracking-[0.2em] text-white/60 wrap-break-word max-w-full" data-motion="fade-up">
            {{ ic_teks('teaser.eyebrow') }}</p>

        <h2 id="inner-circle-heading"
            class="max-w-full wrap-break-word text-3xl md:text-5xl font-bold uppercase tracking-tight text-white"
            data-motion="blur-up">
            {{ ic_teks('teaser.judul') }}
        </h2>

        <p class="max-w-xl wrap-break-word text-sm md:text-base text-white/70 leading-relaxed" data-motion="fade-up">
            {{ ic_html('teaser.deskripsi') }}
        </p>

        @if ($poin->isNotEmpty())
            <ul class="flex flex-wrap justify-center gap-2" data-motion-stagger="0.08" data-motion-children="pop">
                @foreach ($poin as $butir)
                    <li
                        class="inline-flex max-w-full items-center gap-2 px-3 py-1.5 rounded-full border border-white/20 text-xs md:text-sm text-white/70">
                        <i class="bi bi-{{ $butir->ikon }} shrink-0" aria-hidden="true"></i>
                        <span class="min-w-0 wrap-break-word">{{ $butir->judul }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 w-full sm:w-auto"
            data-motion="fade-up" data-motion-delay="0.15">
            @if ($sudahMasuk)
                <a href="{{ route('ic.beranda') }}"
                    class="inline-flex items-center justify-center text-center min-h-11 px-6 py-2 rounded-full bg-white text-black text-sm font-bold uppercase transition-colors hover:bg-white/85 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    {{ ic_teks('teaser.tombol.buka') }}
                </a>
            @else
                <a href="{{ route('ic.daftar') }}"
                    class="inline-flex items-center justify-center text-center min-h-11 px-6 py-2 rounded-full bg-white text-black text-sm font-bold uppercase transition-colors hover:bg-white/85 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    {{ ic_teks('teaser.tombol.gabung') }}
                </a>
                <a href="{{ route('ic.masuk') }}"
                    class="inline-flex items-center justify-center text-center min-h-11 px-6 py-2 rounded-full border border-white/30 text-white text-sm font-bold uppercase transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    {{ ic_teks('teaser.tombol.masuk') }}
                </a>
            @endif
        </div>

        <div data-motion="fade-up" data-motion-delay="0.2">
            <a href="{{ route('ic.tentang') }}"
                class="text-sm text-white/60 underline underline-offset-4 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                {{ ic_teks('teaser.tautan') }}
            </a>
        </div>
    </div>
</section>
