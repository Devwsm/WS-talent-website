@php
    $navLinks = [
        ['route' => 'banner', 'icon' => 'bi-images', 'label' => 'Banner'],
        ['route' => 'headers', 'icon' => 'bi-card-image', 'label' => 'Headers'],
        ['route' => 'dashboard.profile', 'icon' => 'bi-person-fill', 'label' => 'Profile'],
        ['route' => 'color_pages.index', 'icon' => 'bi-palette-fill', 'label' => 'Warna Web'],
        ['route' => 'albums', 'icon' => 'bi-disc-fill', 'label' => 'Albums'],
        ['route' => 'news', 'icon' => 'bi-newspaper', 'label' => 'News'],
        ['route' => 'merchandise', 'icon' => 'bi-basket-fill', 'label' => 'Merchandise'],
    ];
    $isKontenActive = request()->routeIs([
        'banner',
        'headers',
        'dashboard.profile',
        'color_pages.index',
        'albums',
        'news',
        'merchandise',
    ]);
@endphp

{{-- ============== DESKTOP: dock bawah-tengah, tema gelap, beda dari background halaman ============== --}}
<div
    class="hidden lg:flex fixed bottom-5 left-1/2 -translate-x-1/2 z-50 items-center
    rounded-2xl bg-neutral-900 border border-white/10 ring-1 ring-red-950/50
    p-1.5 shadow-2xl shadow-black/70">

    <a href="{{ route('dashboard') }}" aria-label="Dashboard"
        class="flex justify-center items-center h-12 w-12 rounded-xl shrink-0 text-xl transition-colors
        {{ request()->routeIs('dashboard') ? 'bg-white text-black' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
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
            <a href="{{ route($link['route']) }}"
                class="flex items-center gap-2 h-12 px-3.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors
                {{ request()->routeIs($link['route']) ? 'bg-white text-black' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
                <i class="bi {{ $link['icon'] }} text-base" aria-hidden="true"></i>
                {{ $link['label'] }}
            </a>
        @endforeach

        <div class="w-px h-8 bg-white/10 mx-1 shrink-0"></div>
    </div>

    <a href="{{ route('logout') }}" aria-label="Logout"
        class="flex justify-center items-center h-12 w-12 rounded-xl shrink-0 text-xl text-red-500 hover:bg-red-500/10 transition-colors">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
    </a>
</div>

{{-- ============== MOBILE & TABLET: burger + bottom sheet setengah layar ============== --}}
<button id="dashOpenBtn" type="button" aria-label="Buka menu dashboard" aria-expanded="false"
    aria-controls="dashMobileMenu"
    class="fixed z-50 bottom-5 right-5 lg:hidden flex justify-center items-center h-14 w-14 rounded-full bg-white text-black text-2xl shadow-lg shadow-black/40">
    <i class="bi bi-list" aria-hidden="true"></i>
</button>

<div id="dashSheetOverlay" aria-hidden="true"
    class="fixed inset-0 bg-black/60 z-40 opacity-0 invisible transition-opacity duration-300 lg:hidden"></div>

<nav id="dashMobileMenu" aria-label="Navigasi dashboard"
    class="fixed inset-x-0 bottom-0 z-50 lg:hidden bg-black border-t border-white/10 rounded-t-3xl
    h-3/5 overflow-y-auto translate-y-full transition-transform duration-300 pb-8">

    {{-- drag handle --}}
    <div class="flex justify-center pt-3 pb-1 sticky top-0 bg-black">
        <span class="w-10 h-1.5 rounded-full bg-white/20"></span>
    </div>

    <div class="flex items-center justify-between px-5 pt-2 pb-4">
        <h1 class="text-lg font-bold uppercase">Menu Dashboard</h1>
        <button id="dashCloseBtn" type="button" aria-label="Tutup menu dashboard"
            class="flex justify-center items-center h-9 w-9 rounded-full bg-white/10 text-white text-xl">
            <i class="bi bi-x" aria-hidden="true"></i>
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5">
        <a href="{{ route('dashboard') }}"
            class="menu-link flex flex-col items-center justify-center gap-2 rounded-xl border border-white/10 py-4
            {{ request()->routeIs('dashboard') ? 'bg-white text-black' : 'text-white/70' }}">
            <i class="bi bi-house-door-fill text-2xl" aria-hidden="true"></i>
            <span class="text-xs font-semibold uppercase text-center">Dashboard</span>
        </a>

        @foreach ($navLinks as $link)
            <a href="{{ route($link['route']) }}"
                class="menu-link flex flex-col items-center justify-center gap-2 rounded-xl border border-white/10 py-4
                {{ request()->routeIs($link['route']) ? 'bg-white text-black' : 'text-white/70' }}">
                <i class="bi {{ $link['icon'] }} text-2xl" aria-hidden="true"></i>
                <span class="text-xs font-semibold uppercase text-center">{{ $link['label'] }}</span>
            </a>
        @endforeach

        <a href="{{ route('logout') }}"
            class="menu-link flex flex-col items-center justify-center gap-2 rounded-xl border border-red-500/20 py-4 text-red-500">
            <i class="bi bi-box-arrow-right text-2xl" aria-hidden="true"></i>
            <span class="text-xs font-semibold uppercase text-center">Logout</span>
        </a>
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

    function openDashSheet() {
        dashMobileMenu.classList.remove('translate-y-full');
        dashSheetOverlay.classList.remove('opacity-0', 'invisible');
        dashOpenBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('overflow-hidden');
    }

    function closeDashSheet() {
        dashMobileMenu.classList.add('translate-y-full');
        dashSheetOverlay.classList.add('opacity-0', 'invisible');
        dashOpenBtn.setAttribute('aria-expanded', 'false');
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
