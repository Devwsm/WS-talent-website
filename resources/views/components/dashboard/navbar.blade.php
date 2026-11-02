@php
    /*
     * Navbar bersama CMS Artis dan CMS Inner Circle: bentuknya sama persis, yang beda hanya isi menu dan tema warna.
     * Mode ditentukan dari route (ic.admin.* = CMS Inner Circle), tidak disimpan di sesi.
     */
    $icAktif = config('inner_circle.aktif');
    $modeIc = $icAktif && request()->routeIs('ic.admin.*');

    if ($modeIc) {
        // Menu CMS Inner Circle diambil dari config('inner_circle.tab_admin'); Ringkasan jadi tombol rumah.
        $navLinks = [];
        foreach (config('inner_circle.tab_admin') as $kunci => $tab) {
            if ($kunci === 'ringkasan') {
                continue;
            }
            $navLinks[] = [
                'route' => $tab['route'],
                'pola' => $tab['route'] . '*',
                'icon' => $tab['ikon'],
                'label' => $tab['label'],
                'lencana' => $kunci === 'klaim' ? (int) ($jumlahKlaimMenunggu ?? 0) : 0,
            ];
        }
        $rumah = ['route' => 'ic.admin.ringkasan', 'label' => 'Ringkasan Inner Circle'];
        $judulMenu = 'Menu CMS Inner Circle';
        $saklar = [
            'route' => 'dashboard',
            'icon' => 'bi-music-note-beamed',
            'label' => 'CMS Artis',
            'judul' => 'Pindah ke CMS Artis',
        ];
    } else {
        $navLinks = [
            ['route' => 'banner', 'icon' => 'bi-images', 'label' => 'Banner'],
            ['route' => 'headers', 'icon' => 'bi-card-image', 'label' => 'Headers'],
            ['route' => 'dashboard.profile', 'icon' => 'bi-person-fill', 'label' => 'Profile'],
            ['route' => 'color_pages.index', 'icon' => 'bi-palette-fill', 'label' => 'Warna Web'],
            ['route' => 'albums', 'icon' => 'bi-disc-fill', 'label' => 'Albums'],
            ['route' => 'news', 'icon' => 'bi-newspaper', 'label' => 'News'],
            ['route' => 'merchandise', 'icon' => 'bi-basket-fill', 'label' => 'Merchandise'],
            ['route' => 'log.index', 'pola' => 'log.*', 'icon' => 'bi-journal-text', 'label' => 'Log'],
        ];
        $rumah = ['route' => 'dashboard', 'label' => 'Dashboard'];
        $judulMenu = 'Menu Dashboard';
        // Saklar ke CMS Inner Circle hanya ada bila flag INNER_CIRCLE_AKTIF menyala.
        $saklar = $icAktif
            ? [
                'route' => 'ic.admin.ringkasan',
                'icon' => 'bi-people-fill',
                'label' => 'CMS Inner Circle',
                'judul' => 'Pindah ke CMS Inner Circle',
            ]
            : null;
    }

    $polaAktif = array_map(fn($l) => $l['pola'] ?? $l['route'], $navLinks);
    $isKontenActive = request()->routeIs($polaAktif);
    $rumahAktif = request()->routeIs($rumah['route']);
@endphp

{{-- ============== DESKTOP: dock bawah-tengah, tema gelap, beda dari background halaman ============== --}}
{{-- Pembungkus tetap di bawah-tengah. Lebarnya mengikuti dock, sehingga saklar mode (melayang sendiri di sisi
     kanan dock) selalu menempel di sebelah tombol Keluar, baik menu sedang terbuka maupun tertutup. --}}
<div class="hidden lg:block fixed bottom-5 left-1/2 -translate-x-1/2 z-50">
    <div
        class="cms-dock flex items-center
    rounded-2xl bg-neutral-900 border border-white/10 ring-1 ring-red-950/50
    p-1.5 shadow-2xl shadow-black/70">

        <a href="{{ route($rumah['route']) }}" aria-label="{{ $rumah['label'] }}" title="{{ $rumah['label'] }}"
            class="flex justify-center items-center h-12 w-12 rounded-xl shrink-0 text-xl transition-colors
        {{ $rumahAktif ? 'bg-white text-black' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <i class="bi bi-house-door-fill" aria-hidden="true"></i>
        </a>

        <button id="contentBtn" type="button" aria-label="Menu konten" aria-expanded="false" aria-controls="contentMenu"
            class="flex justify-center items-center h-12 w-12 rounded-xl shrink-0 text-xl transition-colors
        {{ $isKontenActive ? 'bg-white text-black' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <i class="bi bi-grid-fill" aria-hidden="true"></i>
        </button>

        {{-- Kategori: nyatu dalam dock yang sama, munculnya fade + scale dari sisi tombol menu --}}
        <div id="contentMenu"
            class="hidden items-center gap-1 pl-1 pr-1 opacity-0 scale-x-95 origin-left transition-all duration-200 ease-out">
            @foreach ($navLinks as $link)
                {{-- Saat menu Inner Circle ikut tampil, 8 menu tidak muat di layar 1024-1279px: label disembunyikan
                 di rentang itu (ikon + title saja) dan baru tampil penuh mulai 1280px. --}}
                <a href="{{ route($link['route']) }}"
                    @if ($icAktif) title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}" @endif
                    class="flex items-center gap-2 h-12 px-3.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors
                {{ request()->routeIs($link['pola'] ?? $link['route']) ? 'bg-white text-black' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
                    <i class="bi {{ $link['icon'] }} text-base" aria-hidden="true"></i>
                    <span class="{{ $icAktif ? 'hidden xl:inline' : '' }}">{{ $link['label'] }}</span>
                    @if (($link['lencana'] ?? 0) > 0)
                        <span
                            class="min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full bg-red-500 text-white text-[11px] font-bold">{{ $link['lencana'] }}</span>
                    @endif
                </a>
            @endforeach

            <div class="w-px h-8 bg-white/10 mx-1 shrink-0"></div>
        </div>

        <form action="{{ route('logout') }}" method="POST" class="contents">
            @csrf
            <button type="submit" aria-label="Logout"
                class="flex justify-center items-center h-12 w-12 rounded-xl shrink-0 text-xl text-red-500 hover:bg-red-500/10 transition-colors cursor-pointer">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            </button>
        </form>
    </div>

    {{-- Saklar mode: tombol melayang sendiri di kanan dock (di sebelah tombol Keluar). Berisi ikon saja agar tidak
         melebar keluar layar saat menu terbuka; nama CMS tujuan tampil di layar 1536px ke atas dan sebagai tooltip. --}}
    @if ($saklar)
        <a href="{{ route($saklar['route']) }}" title="{{ $saklar['judul'] }}" aria-label="{{ $saklar['judul'] }}"
            class="cms-dock absolute left-full top-0 ml-3 flex items-center gap-2 h-15.5 px-4 rounded-2xl
            bg-neutral-900 border border-white/10 ring-1 ring-red-950/50 shadow-2xl shadow-black/70
            text-sm font-semibold whitespace-nowrap text-white/80 hover:text-white hover:bg-neutral-800 transition-colors">
            <i class="bi bi-arrow-left-right text-xl" aria-hidden="true"></i>
            <span class="hidden 2xl:inline">{{ $saklar['label'] }}</span>
        </a>
    @endif
</div>

{{-- ============== MOBILE & TABLET: burger + bottom sheet setengah layar ============== --}}
<button id="dashOpenBtn" type="button" aria-label="Buka menu dashboard" aria-expanded="false"
    aria-controls="dashMobileMenu"
    class="cms-dock fixed z-50 bottom-5 right-5 lg:hidden flex justify-center items-center h-14 w-14 rounded-full bg-white text-black text-2xl shadow-lg shadow-black/40">
    <i class="bi bi-list" aria-hidden="true"></i>
</button>

{{-- Saklar mode di HP: tombol bulat melayang tepat di atas tombol burger. --}}
@if ($saklar)
    <a id="dashSwitchBtn" href="{{ route($saklar['route']) }}" title="{{ $saklar['judul'] }}"
        aria-label="{{ $saklar['judul'] }}"
        class="cms-dock fixed z-50 bottom-22 right-6 lg:hidden flex justify-center items-center h-12 w-12 rounded-full
        bg-neutral-900 border border-white/15 text-white text-xl shadow-lg shadow-black/40 transition-opacity">
        <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
    </a>
@endif

<div id="dashSheetOverlay" aria-hidden="true"
    class="cms-dock fixed inset-0 bg-black/60 z-40 opacity-0 invisible transition-opacity duration-300 lg:hidden"></div>

<nav id="dashMobileMenu" aria-label="Navigasi dashboard"
    class="cms-dock text-white fixed inset-x-0 bottom-0 z-50 lg:hidden bg-black border-t border-white/10 rounded-t-3xl
    h-3/5 overflow-y-auto translate-y-full transition-transform duration-300 pb-8">

    {{-- drag handle --}}
    <div class="flex justify-center pt-3 pb-1 sticky top-0 bg-black">
        <span class="w-10 h-1.5 rounded-full bg-white/20"></span>
    </div>

    <div class="flex items-center justify-between px-5 pt-2 pb-4">
        <h1 class="text-lg font-bold uppercase">{{ $judulMenu }}</h1>
        <button id="dashCloseBtn" type="button" aria-label="Tutup menu dashboard"
            class="flex justify-center items-center h-9 w-9 rounded-full bg-white/10 text-white text-xl">
            <i class="bi bi-x" aria-hidden="true"></i>
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5">
        <a href="{{ route($rumah['route']) }}"
            class="menu-link flex flex-col items-center justify-center gap-2 rounded-xl border border-white/10 py-4
            {{ $rumahAktif ? 'bg-white text-black' : 'text-white/70' }}">
            <i class="bi bi-house-door-fill text-2xl" aria-hidden="true"></i>
            <span class="text-xs font-semibold uppercase text-center">{{ $modeIc ? 'Ringkasan' : 'Dashboard' }}</span>
        </a>

        @foreach ($navLinks as $link)
            <a href="{{ route($link['route']) }}"
                class="menu-link flex flex-col items-center justify-center gap-2 rounded-xl border border-white/10 py-4
                {{ request()->routeIs($link['pola'] ?? $link['route']) ? 'bg-white text-black' : 'text-white/70' }}">
                <i class="bi {{ $link['icon'] }} text-2xl" aria-hidden="true"></i>
                <span class="text-xs font-semibold uppercase text-center">{{ $link['label'] }}
                    @if (($link['lencana'] ?? 0) > 0)
                        <span
                            class="ml-0.5 inline-flex min-w-5 h-5 px-1.5 items-center justify-center rounded-full bg-red-500 text-white text-[11px] font-bold align-middle">{{ $link['lencana'] }}</span>
                    @endif
                </span>
            </a>
        @endforeach

        <form action="{{ route('logout') }}" method="POST" class="contents">
            @csrf
            <button type="submit"
                class="menu-link w-full flex flex-col items-center justify-center gap-2 rounded-xl border border-red-500/20 py-4 text-red-500 cursor-pointer">
                <i class="bi bi-box-arrow-right text-2xl" aria-hidden="true"></i>
                <span class="text-xs font-semibold uppercase text-center">Logout</span>
            </button>
        </form>
    </div>
</nav>

<script>
    // ---- Desktop: dock + kategori fade/scale dari tombol menu ----
    const contentBtn = document.getElementById('contentBtn');
    const contentMenu = document.getElementById('contentMenu');
    let closeTimeout = null;

    function openContentMenu() {
        clearTimeout(closeTimeout);
        contentMenu.classList.remove('hidden');
        contentMenu.classList.add('flex');
        // paksa reflow dulu sebelum transisi, biar browser sempat render state awal (opacity-0)
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                contentMenu.classList.remove('opacity-0', 'scale-x-95');
                contentMenu.classList.add('opacity-100', 'scale-x-100');
            });
        });
        contentBtn.setAttribute('aria-expanded', 'true');
    }

    function closeContentMenu() {
        contentMenu.classList.remove('opacity-100', 'scale-x-100');
        contentMenu.classList.add('opacity-0', 'scale-x-95');
        contentBtn.setAttribute('aria-expanded', 'false');
        closeTimeout = setTimeout(() => {
            contentMenu.classList.add('hidden');
            contentMenu.classList.remove('flex');
        }, 200);
    }

    contentBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = contentBtn.getAttribute('aria-expanded') === 'true';
        isOpen ? closeContentMenu() : openContentMenu();
    });

    document.addEventListener('click', (e) => {
        if (contentMenu && !contentMenu.contains(e.target) && !contentBtn.contains(e.target)) {
            closeContentMenu();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeContentMenu();
    });

    // ---- Mobile: burger + bottom sheet ----
    const dashOpenBtn = document.getElementById('dashOpenBtn');
    const dashCloseBtn = document.getElementById('dashCloseBtn');
    const dashMobileMenu = document.getElementById('dashMobileMenu');
    const dashSheetOverlay = document.getElementById('dashSheetOverlay');
    const dashSwitchBtn = document.getElementById('dashSwitchBtn');

    function openDashSheet() {
        dashMobileMenu.classList.remove('translate-y-full');
        dashSheetOverlay.classList.remove('opacity-0', 'invisible');
        dashOpenBtn.setAttribute('aria-expanded', 'true');
        dashSwitchBtn?.classList.add('opacity-0', 'pointer-events-none');
        document.body.classList.add('overflow-hidden');
    }

    function closeDashSheet() {
        dashMobileMenu.classList.add('translate-y-full');
        dashSheetOverlay.classList.add('opacity-0', 'invisible');
        dashOpenBtn.setAttribute('aria-expanded', 'false');
        dashSwitchBtn?.classList.remove('opacity-0', 'pointer-events-none');
        document.body.classList.remove('overflow-hidden');
    }

    dashOpenBtn?.addEventListener('click', openDashSheet);
    dashCloseBtn?.addEventListener('click', closeDashSheet);
    dashSheetOverlay?.addEventListener('click', closeDashSheet);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDashSheet();
    });

    document.querySelectorAll('#dashMobileMenu .menu-link').forEach((link) => {
        link.addEventListener('click', closeDashSheet);
    });
</script>
