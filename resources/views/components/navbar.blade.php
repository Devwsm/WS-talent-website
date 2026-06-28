<div class="navbar-cover">
    <div id="navbar" class="fixed top-0 left-0 w-full bg-[#5E0006] text-white z-50 transition-transform duration-300">

        <div class="flex items-center justify-between px-4 py-4">
            <!-- Mobile Left -->
            <div class="w-1/3 lg:hidden">
                <button id="menuBtn" class="text-3xl">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            <!-- Logo -->
            <div class="w-1/3 lg:w-auto flex justify-center lg:justify-start">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-White.png') }}" loading="lazy"
                        decoding="async" alt="whisnu-santika" class="object-cover w-32 md:w-40 lg:w-60 rounded-lg">
                </a>
            </div>
            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-8 ml-12">
                <a href="{{ route('home') }}#tour">
                    <h1 class="font-bold uppercase">Tour</h1>
                </a>
                <a href="{{ route('profile') }}">
                    <h1 class="font-bold uppercase">Profile</h1>
                </a>
                <a href="{{ route('home') }}#news">
                    <h1 class="font-bold uppercase">News</h1>
                </a>
                <a href="{{ route('home') }}#new-music">
                    <h1 class="font-bold uppercase">Albums</h1>
                </a>
                <a href="{{ route('home') }}#store">
                    <h1 class="font-bold uppercase">Merch</h1>
                </a>
                <a href="{{ route('login') }}" class="menu-link">
                    <i class="bi bi-person"></i>
                </a>
            </div>
            <!-- Mobile Right -->
            <div class="w-1/3 lg:hidden flex justify-end">
                <a href="{{ route('home') }}#store">
                    <h1 class="font-bold uppercase">Merch</h1>
                </a>
            </div>

        </div>
    </div>


    <!-- Mobile Fullscreen Menu -->
    <div id="mobileMenu"
        class="fixed inset-0 bg-[#5E0006] text-white z-60
        flex flex-col items-center justify-center gap-10
        -translate-x-full transition-transform duration-300">
        <button id="closeBtn" class="absolute top-5 left-5 text-4xl">
            <i class="bi bi-x"></i>
        </button>
        <a href="{{ route('home') }}#tour" class="menu-link">
            <h1 class="text-3xl font-bold uppercase">Tour</h1>
        </a>
        <a href="{{ route('profile') }}" class="menu-link">
            <h1 class="text-3xl font-bold uppercase">Profile</h1>
        </a>
        <a href="{{ route('home') }}#news" class="menu-link">
            <h1 class="text-3xl font-bold uppercase">News</h1>
        </a>
        <a href="{{ route('home') }}#new-music" class="menu-link">
            <h1 class="text-3xl font-bold uppercase">Albums</h1>
        </a>
        <a href="{{ route('home') }}#store" class="menu-link">
            <h1 class="text-3xl font-bold uppercase">Merch</h1>
        </a>
        <a href="{{ route('login') }}" class="menu-link">
            <i class="bi bi-person text-4xl"></i>
        </a>
    </div>
</div>

<script>
    const menuBtn = document.getElementById("menuBtn");
    const closeBtn = document.getElementById("closeBtn");
    const mobileMenu = document.getElementById("mobileMenu");
    const menuLinks = document.querySelectorAll(".menu-link");

    let scrollPosition = 0;

    function openMenu() {
        menuOpen = true;

        scrollPosition = window.pageYOffset;

        mobileMenu.classList.remove("-translate-x-full");

        document.body.style.position = "fixed";
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = "100%";
    }

    function closeMenu() {
        mobileMenu.classList.add("-translate-x-full");

        document.body.style.position = "";
        document.body.style.top = "";
        document.body.style.width = "";

        window.scrollTo({
            top: scrollPosition,
            behavior: "instant"
        });

        lastScrollY = scrollPosition;

        menuOpen = false;
    }

    menuBtn.addEventListener("click", openMenu);
    closeBtn.addEventListener("click", closeMenu);

    menuLinks.forEach(link => {
        link.addEventListener("click", closeMenu);
    });
</script>
<script>
    let lastScrollY = window.scrollY;
    const navbar = document.getElementById("navbar");

    window.addEventListener("scroll", () => {
        if (window.scrollY > lastScrollY) {
            navbar.style.transform = "translateY(-100%)";
        } else {
            navbar.style.transform = "translateY(0)";
        }

        lastScrollY = window.scrollY;
    });
</script>
