<div class="navbar-cover">
    <header id="navbar" style="background-color: {{ $color }};"
        class="fixed top-0 left-0 w-full text-white z-50 transition-transform duration-300">
        <div class="flex items-center justify-between px-4 py-4">
            <!-- Mobile Left: hamburger -->
            <div class="w-1/3 lg:hidden">
                <button id="menuBtn" type="button" aria-label="Buka menu navigasi" aria-expanded="false"
                    aria-controls="mobileMenu" class="text-3xl leading-none">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
            </div>
            <!-- Logo -->
            <div class="w-1/3 lg:w-auto flex justify-center lg:justify-start">
                <a href="{{ route('home') }}" aria-label="Whisnu Santika — beranda">
                    <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-White.png') }}" loading="lazy"
                        decoding="async" width="240" height="80" alt="Logo Whisnu Santika"
                        class="object-cover w-32 md:w-40 lg:w-60 rounded-lg">
                </a>
            </div>
            <!-- Desktop Menu -->
            <nav aria-label="Navigasi utama" class="hidden lg:flex items-center gap-8 ml-12">
                <a href="{{ route('profile') }}" class="font-bold uppercase">Profile</a>
                <a href="{{ route('home') }}#news" class="font-bold uppercase">News</a>
                <a href="{{ route('home') }}#new-music" class="font-bold uppercase">Albums</a>
                <a href="{{ route('home') }}#store" class="font-bold uppercase">Merch</a>
                <a href="{{ route('dashboard') }}" aria-label="Dashboard admin" class="menu-link text-xl">
                    <i class="bi bi-person" aria-hidden="true"></i>
                </a>
            </nav>
            <!-- Mobile Right -->
            <div class="w-1/3 lg:hidden flex justify-end">
                <a href="{{ route('home') }}#store" class="font-bold uppercase">Merch</a>
            </div>
        </div>
    </header>

    <!-- Backdrop, tap luar drawer buat nutup -->
    <div id="menuOverlay" aria-hidden="true"
        class="fixed inset-0 bg-black/60 z-40 opacity-0 invisible transition-opacity duration-300 lg:hidden"></div>

    <!-- Drawer: 3/4 layar di mobile, 1/2 di tablet, disembunyikan total di desktop -->
    <nav id="mobileMenu" aria-label="Navigasi mobile" style="background-color: {{ $color }};"
        class="fixed inset-y-0 left-0 w-3/4 md:w-1/2 max-w-sm text-white z-50
        flex flex-col items-start justify-center gap-8 px-10
        -translate-x-full transition-transform duration-300 lg:hidden">
        <button id="closeBtn" type="button" aria-label="Tutup menu navigasi"
            class="absolute top-5 right-5 text-3xl leading-none">
            <i class="bi bi-x" aria-hidden="true"></i>
        </button>
        <a href="{{ route('profile') }}" class="menu-link text-2xl font-bold uppercase">Profile</a>
        <a href="{{ route('home') }}#news" class="menu-link text-2xl font-bold uppercase">News</a>
        <a href="{{ route('home') }}#new-music" class="menu-link text-2xl font-bold uppercase">Albums</a>
        <a href="{{ route('home') }}#store" class="menu-link text-2xl font-bold uppercase">Merch</a>
        <a href="{{ route('dashboard') }}" aria-label="Dashboard admin"
            class="menu-link flex items-center gap-2 text-2xl font-bold uppercase">
            <i class="bi bi-person text-2xl" aria-hidden="true"></i> Dashboard
        </a>
    </nav>
</div>

<script>
    const menuBtn = document.getElementById("menuBtn");
    const closeBtn = document.getElementById("closeBtn");
    const mobileMenu = document.getElementById("mobileMenu");
    const menuOverlay = document.getElementById("menuOverlay");
    const menuLinks = document.querySelectorAll(".menu-link");

    let scrollPosition = 0;

    function openMenu() {
        scrollPosition = window.pageYOffset;

        mobileMenu.classList.remove("-translate-x-full");
        menuOverlay.classList.remove("opacity-0", "invisible");
        menuBtn.setAttribute("aria-expanded", "true");

        document.body.style.position = "fixed";
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = "100%";
    }

    function closeMenu() {
        mobileMenu.classList.add("-translate-x-full");
        menuOverlay.classList.add("opacity-0", "invisible");
        menuBtn.setAttribute("aria-expanded", "false");

        document.body.style.position = "";
        document.body.style.top = "";
        document.body.style.width = "";

        window.scrollTo({
            top: scrollPosition,
            behavior: "instant"
        });
    }

    menuBtn?.addEventListener("click", openMenu);
    closeBtn?.addEventListener("click", closeMenu);
    menuOverlay?.addEventListener("click", closeMenu);

    menuLinks.forEach(link => {
        link.addEventListener("click", closeMenu);
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeMenu();
    });
</script>
