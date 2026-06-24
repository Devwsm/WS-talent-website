<div class="navbar-cover">
    <div id="navbar"
        class="navbar fixed flex bg-[#5E0006] text-white w-full p-4 z-20 
        gap-6 items-center transition-transform duration-100">
        <a href="#tour">
            <h1 class="text-xs lg:text-lg font-bold uppercase">tour</h1>
        </a>
        <a href="#news">
            <h1 class="text-xs lg:text-lg font-bold uppercase">news</h1>
        </a>
        <a href="#new-music">
            <h1 class="text-xs lg:text-lg font-bold uppercase">albums</h1>
        </a>
        <a href="#store">
            <h1 class="text-xs lg:text-lg font-bold uppercase">merch</h1>
        </a>
        <a href="{{ route('login') }}">
            <i class="bi bi-person"></i>
        </a>
    </div>
</div>
<script>
    const navbar = document.getElementById("navbar");

    let lastScrollY = window.scrollY;
    let currentTranslate = 0;

    window.addEventListener("scroll", () => {
        const currentScroll = window.scrollY;

        // beda scroll (positif = turun, negatif = naik)
        const diff = currentScroll - lastScrollY;

        // update posisi navbar (semakin scroll turun → naik)
        currentTranslate -= diff;

        // batasi (biar tidak terlalu jauh)
        if (currentTranslate < -100) currentTranslate = -100;
        if (currentTranslate > 0) currentTranslate = 0;

        navbar.style.transform = `translateY(${currentTranslate}px)`;

        lastScrollY = currentScroll;
    });
</script>
