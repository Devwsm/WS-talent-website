{{-- Teaser WS Inner Circle di homepage. Dipanggil dari pages/home.blade.php hanya bila INNER_CIRCLE_AKTIF menyala.
     Mengikuti tema situs publik (hitam, pill rounded-full, Bootstrap Icons) — bukan tema lavender area anggota,
     karena CSS Inner Circle sengaja tidak dimuat di halaman publik (lihat README bagian 4.2). --}}
@php
    $sudahMasuk = \Illuminate\Support\Facades\Auth::guard('member')->check();
@endphp

<section id="inner-circle" aria-labelledby="inner-circle-heading"
    class="bg-black border-b border-white/10 px-6 py-16 md:px-16 md:py-24 lg:px-52">
    <div class="max-w-3xl mx-auto flex flex-col items-center text-center gap-6">
        <p class="text-xs uppercase tracking-[0.2em] text-white/60" data-motion="fade-up">Komunitas penggemar</p>

        <h2 id="inner-circle-heading" class="text-3xl md:text-5xl font-bold uppercase tracking-tight text-white"
            data-motion="blur-up">
            WS Inner Circle
        </h2>

        <p class="max-w-xl text-sm md:text-base text-white/70 leading-relaxed" data-motion="fade-up">
            Kumpulkan stempel setiap kali hadir di acara Whisnu Santika, buka hadiah di tiap tahap, dan bagikan
            kenanganmu bersama penggemar lain.
        </p>

        <ul class="flex flex-wrap justify-center gap-2" data-motion-stagger="0.08" data-motion-children="pop">
            <li
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/20 text-xs md:text-sm text-white/70">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Check-in QR di acara
            </li>
            <li
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/20 text-xs md:text-sm text-white/70">
                <i class="bi bi-gift" aria-hidden="true"></i> Hadiah di tiap tahap stempel
            </li>
            <li
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/20 text-xs md:text-sm text-white/70">
                <i class="bi bi-images" aria-hidden="true"></i> Galeri kenangan penggemar
            </li>
        </ul>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 w-full sm:w-auto"
            data-motion="fade-up" data-motion-delay="0.15">
            @if ($sudahMasuk)
                <a href="{{ route('ic.beranda') }}"
                    class="inline-flex items-center justify-center min-h-11 px-6 rounded-full bg-white text-black text-sm font-bold uppercase transition-colors hover:bg-white/85 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Buka Inner Circle
                </a>
            @else
                <a href="{{ route('ic.daftar') }}"
                    class="inline-flex items-center justify-center min-h-11 px-6 rounded-full bg-white text-black text-sm font-bold uppercase transition-colors hover:bg-white/85 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Gabung sekarang
                </a>
                <a href="{{ route('ic.masuk') }}"
                    class="inline-flex items-center justify-center min-h-11 px-6 rounded-full border border-white/30 text-white text-sm font-bold uppercase transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Sudah punya akun? Masuk
                </a>
            @endif
        </div>
    </div>
</section>
