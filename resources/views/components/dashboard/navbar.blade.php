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
@endphp

{{-- desktop --}}
<div class="nav z-50 fixed bottom-5 left-1/2 -translate-x-1/2 hidden md:flex gap-2">
    <a href="{{ route('dashboard') }}" aria-label="Dashboard"
        class="nav-links flex justify-center items-center p-6 rounded-lg border border-white/15 shrink-0 text-2xl
        {{ request()->routeIs('dashboard') ? 'bg-white text-black' : 'bg-white/5 text-white hover:bg-white/10' }} transition-colors">
        <i class="bi bi-house-door-fill" aria-hidden="true"></i>
    </a>

    <div class="relative shrink-0">
        <button id="contentBtn" type="button" aria-label="Menu konten" aria-expanded="false" aria-controls="contentMenu"
            class="nav-links flex justify-center items-center p-6 rounded-lg border border-white/15 bg-white/5 text-white hover:bg-white/10 text-2xl transition-colors">
            <i class="bi bi-grid-fill" aria-hidden="true"></i>
        </button>
        <div id="contentMenu"
            class="hidden absolute bottom-full mb-3 left-1/2 -translate-x-1/2 bg-black border border-white/15 rounded-lg p-3 flex-col gap-1 w-56 shadow-xl">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors
                    {{ request()->routeIs($link['route']) ? 'bg-white text-black font-semibold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="bi {{ $link['icon'] }} text-lg" aria-hidden="true"></i>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="nav-links flex justify-center items-center p-6 rounded-lg border border-white/15 bg-white/5 shrink-0">
        <a href="{{ route('logout') }}" aria-label="Logout"
            class="text-red-500 hover:text-red-400 text-2xl transition-colors">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</div>

{{-- Mobile trigger --}}
<button id="dashOpenBtn" type="button" aria-label="Buka menu dashboard" aria-expanded="false"
    aria-controls="dashMobileMenu"
    class="fixed z-50 bottom-5 right-5 md:hidden flex justify-center items-center h-14 w-14 rounded-full bg-white text-black text-2xl shadow-lg">
    <i class="bi bi-list" aria-hidden="true"></i>
</button>

{{-- Mobile Fullscreen --}}
<nav id="dashMobileMenu" aria-label="Navigasi dashboard"
    class="fixed inset-0 bg-black text-white z-60 flex flex-col items-center justify-center gap-5
    translate-x-full transition-transform duration-300 md:hidden overflow-y-auto py-10">

    <a href="{{ route('dashboard') }}"
        class="menu-link flex items-center gap-3
        {{ request()->routeIs('dashboard') ? 'text-white' : 'text-white/70' }}">
        <i class="bi bi-house-door-fill text-3xl" aria-hidden="true"></i>
        <h1 class="text-2xl font-bold uppercase">Dashboard</h1>
    </a>

    <span class="text-xs tracking-widest uppercase text-white/40 mt-2">Konten</span>

    @foreach ($navLinks as $link)
        <a href="{{ route($link['route']) }}"
            class="menu-link flex items-center gap-3
            {{ request()->routeIs($link['route']) ? 'text-white' : 'text-white/70' }}">
            <i class="bi {{ $link['icon'] }} text-3xl" aria-hidden="true"></i>
            <h1 class="text-2xl font-bold uppercase">{{ $link['label'] }}</h1>
        </a>
    @endforeach

    <a href="{{ route('logout') }}" class="menu-link flex items-center gap-3 mt-4 text-red-500">
        <i class="bi bi-box-arrow-right text-3xl" aria-hidden="true"></i>
        <h1 class="text-2xl font-bold uppercase">Logout</h1>
    </a>

    <button id="dashCloseBtn" type="button" aria-label="Tutup menu dashboard"
        class="fixed bottom-5 right-5 flex justify-center items-center h-14 w-14 rounded-full bg-white/10 border border-white/20 text-white text-3xl">
        <i class="bi bi-x" aria-hidden="true"></i>
    </button>
</nav>

<script>
    const dashOpenBtn = document.getElementById('dashOpenBtn');
    const dashCloseBtn = document.getElementById('dashCloseBtn');
    const dashMobileMenu = document.getElementById('dashMobileMenu');
    const contentBtn = document.getElementById('contentBtn');
    const contentMenu = document.getElementById('contentMenu');

    dashOpenBtn?.addEventListener('click', () => {
        dashMobileMenu.classList.remove('translate-x-full');
        dashOpenBtn.setAttribute('aria-expanded', 'true');
    });

    dashCloseBtn?.addEventListener('click', () => {
        dashMobileMenu.classList.add('translate-x-full');
        dashOpenBtn.setAttribute('aria-expanded', 'false');
    });

    contentBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = contentMenu.classList.toggle('hidden') === false;
        contentMenu.classList.toggle('flex', isOpen);
        contentBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
        if (contentMenu && !contentMenu.contains(e.target) && !contentBtn.contains(e.target)) {
            contentMenu.classList.add('hidden');
            contentMenu.classList.remove('flex');
            contentBtn.setAttribute('aria-expanded', 'false');
        }
    });
</script>
