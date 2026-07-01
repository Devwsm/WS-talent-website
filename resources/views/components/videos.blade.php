<div id="header" class="videos-cover w-full h-full">
    <div class="swiper videosSwiper h-screen">
        <div class="swiper-wrapper">

            @foreach ($headers as $item)
                @php
                    $bgExtension = strtolower(pathinfo($item->header_background, PATHINFO_EXTENSION));
                    $isVideo = in_array($bgExtension, ['mp4', 'webm', 'mov']);
                @endphp

                <div class="swiper-slide relative flex flex-col">
                    @if ($isVideo)
                        <video muted loop playsinline data-video="{{ $loop->iteration }}" preload="auto"
                            class="bg-video absolute w-full h-full object-cover z-0">
                        </video>
                    @else
                        <img src="{{ Storage::url('header/background/' . $item->header_background) }}"
                            alt="{{ $item->header_name }}" loading="lazy" decoding="async"
                            class="absolute w-full h-full object-cover z-0">
                    @endif

                    <div class="content absolute inset-0 z-10 bg-black/50
                        flex flex-col justify-center items-center text-white text-center gap-4">
                        <span style="border-color: {{ $item->header_color }}99;"
                            class="inline-flex items-center gap-2 w-fit bg-white/15 backdrop-blur border rounded-full px-3 py-1 text-white text-xs">
                            <span style="background-color: {{ $item->header_color }}99;" 
                                class="w-2 h-2 rounded-full bg-[ {{ $item->header_color }} ]/60 animate-pulse"></span>
                            {{ $item->header_title }}
                        </span>
                        <img src="{{ Storage::url('header/img/' . $item->header_img) }}" loading="lazy" decoding="async"
                            alt="{{ $item->header_name }}" class="object-cover w-120 rounded-lg">
                        <div class="flex flex-col gap-2">
                            <h1 class="text-2xl font-semibold text-white">
                                {{ $item->header_name }}
                            </h1>
                            <h1 class="text-sm text-white/70">{{ $item->header_description }}</h1>
                            <div class="flex gap-3 justify-center">
                                <a href="{{ $item->link_header }}" target="_blank" style="background-color: {{ $item->header_color }}99;"
                                    class="px-5 py-2 shadow-sm font-semibold rounded-full text-sm">Watch
                                    Video</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-next text-white"></div>
        <div class="swiper-button-prev text-white"></div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const videoSources = {
            @foreach ($headers as $item)
                @php
                    $bgExtension = strtolower(pathinfo($item->header_background, PATHINFO_EXTENSION));
                    $isVideo = in_array($bgExtension, ['mp4', 'webm', 'mov']);
                @endphp
                @if ($isVideo)
                    {{ $loop->iteration }}: "{{ Storage::url('header/background/' . $item->header_background) }}",
                @endif
            @endforeach
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
            if (!activeVideo.dataset.loaded && videoSources[index]) {
                const source = document.createElement("source");
                source.src = videoSources[index];
                source.type = "video/mp4";
                activeVideo.appendChild(source);
                activeVideo.load();
                activeVideo.dataset.loaded = "true";
            }
            activeVideo.play().catch(() => {});
        }

        if (videos.length > 0) {
            playVideo(1);
        }

        const swiper = document.querySelector(".videosSwiper")?.swiper;
        if (!swiper) return;
        swiper.on("slideChange", function() {
            playVideo(swiper.realIndex + 1);
        });

    });
</script>
