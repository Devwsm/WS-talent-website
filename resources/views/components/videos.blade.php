<div id="header" class="videos-cover w-full h-full">
    <div class="swiper videosSwiper h-screen">
        <div class="swiper-wrapper">

            {{-- Slide 1 --}}
            <div class="swiper-slide relative flex flex-col">
                <video muted loop playsinline data-video="1" preload="auto"
                    class="bg-video absolute w-full h-full object-cover z-0">
                </video>
                <div
                    class="content absolute inset-0 z-10 bg-black/50
                    flex flex-col justify-center items-center text-white text-center gap-4">
                    <span
                        class="inline-flex items-center gap-2 w-fit bg-white/15 backdrop-blur border border-blue-500/60 rounded-full px-3 py-1 text-white text-xs">
                        <span class="w-2 h-2 rounded-full bg-blue-500/60 animate-pulse"></span>
                        New Release
                    </span>
                    <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" loading="lazy"
                        decoding="async" alt="whisnu-santika" class="object-cover w-120 rounded-lg">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-2xl font-medium text-white"><span class="font-semibold">Aku Harus Pergi</span>
                            (Official Lyric Video)</h1>
                        <h1 class="text-sm text-white/70">Whisnu Santika, Ari Lesmana</h1>
                        <div class="flex gap-3 justify-center">
                            <a href="https://www.youtube.com/watch?v=PqTRMsd8uRQ&list=RDPqTRMsd8uRQ&start_radio=1" target="_blank"
                                class="px-5 py-2 bg-blue-500/60 shadow-sm font-semibold rounded-full text-sm">Watch
                                Video</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Slide 2 --}}
            <div class="swiper-slide relative flex flex-col">
                <video muted loop playsinline data-video="2" preload="auto"
                    class="bg-video absolute w-full h-full object-cover z-0">
                </video>
                <div
                    class="content absolute inset-0 z-10 bg-black/50
                    flex flex-col justify-center items-center text-white text-center gap-4">
                    <span
                        class="inline-flex items-center gap-2 w-fit bg-white/15 backdrop-blur border border-blue-500/60 rounded-full px-3 py-1 text-white text-xs">
                        <span class="w-2 h-2 rounded-full bg-blue-500/60 animate-pulse"></span>
                        New Release
                    </span>
                    <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" loading="lazy"
                        decoding="async" alt="whisnu-santika" class="object-cover w-120 rounded-lg">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-2xl font-medium text-white"><span class="font-semibold">YEAR MIX</span> 2025
                        </h1>
                        <h1 class="text-sm text-white/70">LIVE AT DWP25 - BALI</h1>
                        <div class="flex gap-3 justify-center">
                            <a href="https://www.youtube.com/watch?v=aTXY-jlYXfo" targer="_blank"
                                class="px-5 py-2 bg-red-500/60 shadow-sm font-semibold rounded-full text-sm">Watch
                                Video</a>
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
