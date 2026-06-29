<div id="header" class="videos-cover w-full h-full">
    <div class="swiper videosSwiper h-screen">
        <div class="swiper-wrapper">

            {{-- Slide 1 --}}
            <div class="swiper-slide relative flex flex-col h-screen">
                <video muted loop playsinline data-video="1" preload="auto"
                    class="bg-video absolute w-full h-full object-cover z-0">
                </video>
                <div
                    class="content absolute inset-0 z-10 bg-black/50
                    flex flex-col justify-center items-center text-white text-center gap-4">
                    <div class="absolute flex flex-col bg-blue-500/60 top-0 left-0 p-6 w-full justify-center items-center">
                        <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-White.png') }}" loading="lazy"
                            decoding="async" alt="whisnu-santika" class="object-cover w-120 rounded-lg">
                    </div>
                    <span
                        class="inline-flex items-center gap-2 w-fit bg-white/15 backdrop-blur border border-blue-500/60 rounded-full px-3 py-1 text-white text-xs">
                        <span class="w-2 h-2 rounded-full bg-blue-500/60 animate-pulse"></span>
                        New Release
                    </span>
                    <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" loading="lazy"
                        decoding="async" alt="whisnu-santika" class="object-cover w-120 rounded-lg">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-2xl font-medium text-white"><span class="font-semibold">Aku Harus Pergi</span> —
                            New Release</h1>
                        <h1 class="text-sm text-white/70">Aku Harus Pergi · Indonesia 2026</h1>
                        <div class="flex gap-3 justify-center">
                            <a href="#"
                                class="px-5 py-2 bg-blue-500/60 shadow-sm font-semibold rounded-full text-sm">Watch
                                Video</a>
                            <a href="#tour"
                                class="px-5 py-2 border border-white/50 text-white rounded-full text-sm">Tour Dates</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Slide 2 --}}
            <div class="swiper-slide relative flex flex-col h-screen">
                <video muted loop playsinline data-video="2" preload="auto"
                    class="bg-video absolute w-full h-full object-cover z-0">
                </video>
                <div
                    class="content absolute inset-0 z-10 bg-black/50
                    flex flex-col justify-center items-center text-white text-center gap-4">
                    <div class="absolute flex flex-col bg-red-500/60 top-0 left-0 p-6 w-full justify-center items-center">
                        <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-White.png') }}" loading="lazy"
                            decoding="async" alt="whisnu-santika" class="object-cover w-120 rounded-lg">
                    </div>
                    <span
                        class="inline-flex items-center gap-2 w-fit bg-white/15 backdrop-blur border border-red-500/60 rounded-full px-3 py-1 text-white text-xs">
                        <span class="w-2 h-2 rounded-full bg-red-500/60 animate-pulse"></span>
                        Official Music Video
                    </span>
                    <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" loading="lazy"
                        decoding="async" alt="whisnu-santika" class="object-cover w-120 rounded-lg">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-2xl font-medium text-white"><span class="font-semibold">Cartel</span> — DWP 2024
                        </h1>
                        <h1 class="text-sm text-white/70">Official Music Experience · Jakarta</h1>
                        <div class="flex gap-3 justify-center">
                            <a href="#"
                                class="px-5 py-2 bg-red-500/60 shadow-sm font-semibold rounded-full text-sm">Watch
                                Video</a>
                            <a href="#tour"
                                class="px-5 py-2 border border-white/50 text-white rounded-full text-sm">Tour Dates</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Slide 3 --}}
            <div class="swiper-slide relative flex flex-col h-screen">
                <video muted loop playsinline data-video="3" preload="auto"
                    class="bg-video absolute w-full h-full object-cover z-0">
                </video>
                <div
                    class="content absolute inset-0 z-10 bg-black/50
                    flex flex-col justify-center items-center text-white text-center gap-4">
                    <div class="absolute flex flex-col bg-purple-500/60 top-0 left-0 p-6 w-full justify-center items-center">
                        <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-White.png') }}" loading="lazy"
                            decoding="async" alt="whisnu-santika" class="object-cover w-120 rounded-lg">
                    </div>
                    <span
                        class="inline-flex items-center gap-2 w-fit bg-white/15 backdrop-blur border border-purple-500/60 rounded-full px-3 py-1 text-white text-xs">
                        <span class="w-2 h-2 rounded-full bg-purple-500/60 animate-pulse"></span>
                        Live Performance
                    </span>
                    <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" loading="lazy"
                        decoding="async" alt="whisnu-santika" class="object-cover w-120 rounded-lg">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-2xl font-medium text-white"><span class="font-semibold">Afterdark</span> — World
                            Tour</h1>
                        <h1 class="text-sm text-white/70">Tour Recap · Singapore 2024</h1>
                        <div class="flex gap-3 justify-center">
                            <a href="#"
                                class="px-5 py-2 bg-purple-500/60 shadow-sm font-semibold rounded-full text-sm">Watch
                                Video</a>
                            <a href="#tour"
                                class="px-5 py-2 border border-white/50 text-white rounded-full text-sm">Tour Dates</a>
                        </div>
                    </div>
                </div>
            </div>


        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-next text-white"></div>
        <div class="swiper-button-prev text-white"></div>

    </div>
</div>



<script>
    document.addEventListener("DOMContentLoaded", () => {
        const videoSources = {
            1: "{{ asset('aset/videos/Whisnu Santika Cartel DWP.mp4') }}",
            2: "{{ asset('aset/videos/Whisnu Santika Cartel DWP.mp4') }}",
            3: "{{ asset('aset/videos/Whisnu Santika Cartel DWP.mp4') }}"
        };

        const videos = document.querySelectorAll(".bg-video");

        function playVideo(index) {
            videos.forEach(video => {
                video.pause();
                if (video.dataset.loaded === "true") {
                    video.currentTime = 0;
                }
            });

            const activeVideo = document.querySelector(`[data-video="${index}"]`);
            if (!activeVideo) return;
            if (!activeVideo.dataset.loaded) {
                const source = document.createElement("source");
                source.src = videoSources[index];
                source.type = "video/mp4";
                activeVideo.appendChild(source);
                activeVideo.load();
                activeVideo.dataset.loaded = "true";
            }
            activeVideo.play().catch(() => {});
        }
        playVideo(1);

        const swiper = document.querySelector(".videosSwiper")?.swiper;
        if (!swiper) return;
        swiper.on("slideChange", function() {
            playVideo(swiper.realIndex + 1);
        });

    });
</script>
