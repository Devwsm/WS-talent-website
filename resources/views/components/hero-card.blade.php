<div class="hero-card relative flex flex-col w-full h-200 lg:h-238 p-36 lg:p-64 justify-center items-center overflow-hidden">

    <!-- Background Video -->
    <video autoplay muted loop playsinline 
        class="absolute top-0 left-0 w-full h-full object-cover z-0">
        <source src="{{ asset('aset/videos/Whisnu Santika Cartel DWP.mp4') }}" type="video/mp4">
    </video>

    <!-- Overlay (optional biar lebih readable) -->
    <div class="absolute top-0 left-0 w-full h-full bg-linear-to-b from-black/60 to-transparent z-0"></div>

    <!-- Content -->
    <div class="relative z-0 flex flex-col items-center">
        <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}"
            alt="whisnu-santika" 
            class="object-cover max-w-xs md:w-64 lg:w-100 rounded-lg">
    </div>
    <div class="social-media flex gap-2 lg:gap-4 absolute bottom-6 lg:bottom-7">
        <a href="https://www.instagram.com/whisnusantika" 
            class="instagram px-2 py-1 lg:p-2 bg-white text-xl lg:text-2xl font-bold rounded-lg">
            <i class="bi bi-instagram"></i>
        </a>
        <a href="#" 
            class="tiktok px-2 py-1 lg:p-2 bg-white text-xl lg:text-2xl font-bold rounded-lg">
            <i class="bi bi-tiktok"></i>
        </a>
        <a href="https://youtube.com/@whisnusantika" 
            class="youtube px-2 py-1 lg:p-2 bg-white text-xl lg:text-2xl font-bold rounded-lg">
            <i class="bi bi-youtube"></i>
        </a>
        <a href="https://open.spotify.com/artist/6gvsmDZKW5wRvjKCPnbHDh" 
            class="spotify px-2 py-1 lg:p-2 bg-white text-xl lg:text-2xl font-bold rounded-lg">
            <i class="bi bi-spotify"></i>
        </a>
    </div>
    <!-- Content end -->

</div>