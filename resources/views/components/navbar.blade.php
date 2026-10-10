<div class="navbar-cover">
    <header id="navbar" style="background-color: {{ $color }};"
        class="fixed top-0 left-0 w-full text-white z-50 border-b border-white/10 transition-transform duration-300">
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
                <a href="{{ route('home') }}#tour" class="font-bold uppercase">Tour</a>
                <a href="{{ route('home') }}#news" class="font-bold uppercase">News</a>
                <a href="{{ route('home') }}#new-music" class="font-bold uppercase">Albums</a>
                <a href="{{ config('site.mavnus_url') }}" target="_blank" rel="noopener noreferrer"
                    class="font-bold uppercase">Merch</a>
                {{-- Link admin cuma muncul kalau sudah login — pengunjung biasa nggak lihat sama sekali --}}
                @if (session('login'))
                    <a href="{{ route('dashboard') }}" aria-label="Dashboard admin" class="text-xl">
                        <i class="bi bi-person" aria-hidden="true"></i>
                    </a>
                @endif
            </nav>
            <!-- Mobile Right -->
            <div class="w-1/3 lg:hidden flex justify-end">
                <a href="{{ config('site.mavnus_url') }}" target="_blank" rel="noopener noreferrer"
                    class="font-bold uppercase">Merch</a>
            </div>
        </div>
    </header>

    <!-- Backdrop, tap luar drawer buat nutup -->
    <div id="menuOverlay" aria-hidden="true"
        class="fixed inset-0 bg-black/60 z-40 opacity-0 invisible transition-opacity duration-300 lg:hidden"></div>

    <!-- Drawer: 3/4 layar di mobile, 1/2 di tablet, disembunyikan total di desktop -->
    <nav id="mobileMenu" aria-label="Navigasi mobile" inert style="background-color: {{ $color }};"
        class="fixed inset-y-0 left-0 w-3/4 md:w-1/2 max-w-sm text-white z-50 border-r border-white/15 shadow-[8px_0_40px_rgba(0,0,0,0.6)]
        flex flex-col items-start justify-center gap-8 px-10
        -translate-x-full transition-transform duration-300 lg:hidden">
        <button id="closeBtn" type="button" aria-label="Tutup menu navigasi"
            class="absolute top-5 right-5 text-3xl leading-none">
            <i class="bi bi-x" aria-hidden="true"></i>
        </button>
        <a href="{{ route('profile') }}" class="menu-link text-2xl font-bold uppercase">Profile</a>
        <a href="{{ route('home') }}#tour" class="menu-link text-2xl font-bold uppercase">Tour</a>
        <a href="{{ route('home') }}#news" class="menu-link text-2xl font-bold uppercase">News</a>
        <a href="{{ route('home') }}#new-music" class="menu-link text-2xl font-bold uppercase">Albums</a>
        <a href="{{ config('site.mavnus_url') }}" target="_blank" rel="noopener noreferrer"
            class="menu-link text-2xl font-bold uppercase">Merch</a>
        @if (session('login'))
            <a href="{{ route('dashboard') }}" aria-label="Dashboard admin"
                class="menu-link flex items-center gap-2 text-2xl font-bold uppercase">
                <i class="bi bi-person text-2xl" aria-hidden="true"></i> Dashboard
            </a>
        @endif
    </nav>
</div>

<script>
    (function() {
        const menuBtn = document.getElementById("menuBtn");
        const closeBtn = document.getElementById("closeBtn");
        const mobileMenu = document.getElementById("mobileMenu");
        const menuOverlay = document.getElementById("menuOverlay");
        const menuLinks = document.querySelectorAll("#mobileMenu .menu-link");

        if (!menuBtn || !mobileMenu) return;

        let scrollPosition = 0;
        let menuOpen = false;

        function openMenu() {
            if (menuOpen) return;
            menuOpen = true;
            scrollPosition = window.pageYOffset;

            // drawer baru boleh difokus keyboard saat terbuka
            mobileMenu.removeAttribute("inert");
            mobileMenu.classList.remove("-translate-x-full");
            menuOverlay.classList.remove("opacity-0", "invisible");
            menuBtn.setAttribute("aria-expanded", "true");

            document.body.style.position = "fixed";
            document.body.style.top = `-${scrollPosition}px`;
            document.body.style.width = "100%";

            closeBtn.focus({
                preventScroll: true
            });
        }

        function closeMenu(returnFocus = true) {
            // Kalau menu memang lagi tertutup, jangan ngapa-ngapain.
            // (Dulu Escape / klik link selalu scrollTo(0) walau menu nggak dibuka.)
            if (!menuOpen) return;
            menuOpen = false;

            mobileMenu.classList.add("-translate-x-full");
            menuOverlay.classList.add("opacity-0", "invisible");
            menuBtn.setAttribute("aria-expanded", "false");
            // drawer tertutup = nggak bisa dimasuki Tab / screen reader
            mobileMenu.setAttribute("inert", "");

            document.body.style.position = "";
            document.body.style.top = "";
            document.body.style.width = "";

            window.scrollTo({
                top: scrollPosition,
                behavior: "instant"
            });

            if (returnFocus) menuBtn.focus({
                preventScroll: true
            });
        }

        menuBtn.addEventListener("click", openMenu);
        closeBtn?.addEventListener("click", () => closeMenu());
        menuOverlay?.addEventListener("click", () => closeMenu());

        // klik link: tutup tanpa narik fokus balik ke tombol (biar fokus ikut ke tujuan link)
        menuLinks.forEach(link => {
            link.addEventListener("click", () => closeMenu(false));
        });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") closeMenu();
        });

        // Layar dilebarkan sampai mode desktop (mis. HP diputar) saat menu terbuka:
        // tutup, supaya halaman nggak "ngunci" dalam posisi fixed.
        window.matchMedia("(min-width: 1024px)").addEventListener("change", (e) => {
            if (e.matches) closeMenu(false);
        });
    })();
</script>
