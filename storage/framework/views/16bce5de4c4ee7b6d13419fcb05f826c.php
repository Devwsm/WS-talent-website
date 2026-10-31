<div id="header" class="videos-cover w-full h-full">
    <div class="swiper videosSwiper h-dvh">
        <div class="swiper-wrapper">

            <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $bgExtension = strtolower(pathinfo($item->header_background, PATHINFO_EXTENSION));
                    $isVideo = in_array($bgExtension, ['mp4', 'webm', 'mov']);
                ?>

                <div class="swiper-slide relative flex flex-col overflow-hidden">
                    <?php if($isVideo): ?>
                        <video muted loop playsinline data-video="<?php echo e($loop->iteration); ?>" preload="none"
                            class="bg-video hero-bg absolute w-full h-full object-cover z-0 bg-black">
                        </video>
                    <?php else: ?>
                        <img src="<?php echo e(Storage::url('header/background/' . $item->header_background)); ?>"
                            alt="<?php echo e($item->header_name); ?>" loading="<?php echo e($loop->first ? 'eager' : 'lazy'); ?>"
                            <?php if($loop->first): ?> fetchpriority="high" <?php endif; ?> decoding="async"
                            class="hero-bg absolute w-full h-full object-cover z-0 bg-black">
                    <?php endif; ?>

                    <div
                        class="content absolute inset-0 z-10 bg-black/50
                        flex flex-col justify-center items-center text-white text-center gap-4 px-4 md:px-12">
                        <span style="border-color: <?php echo e($item->header_color); ?>99;"
                            class="inline-flex items-center gap-2 w-fit bg-white/15 backdrop-blur border rounded-full px-3 py-1 text-white text-xs">
                            <span style="background-color: <?php echo e($item->header_color); ?>99;" aria-hidden="true"
                                class="w-2 h-2 rounded-full animate-pulse"></span>
                            <span class="eq" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                            <?php echo e($item->header_title); ?>

                        </span>
                        <img src="<?php echo e(Storage::url('header/img/' . $item->header_img)); ?>"
                            loading="<?php echo e($loop->first ? 'eager' : 'lazy'); ?>"
                            <?php if($loop->first): ?> fetchpriority="high" <?php endif; ?> decoding="async"
                            alt="<?php echo e($item->header_name); ?>"
                            class="object-cover w-32 sm:w-48 md:w-64 lg:w-80 xl:w-96 rounded-lg">
                        <div class="flex flex-col gap-2">
                            <h2 class="text-2xl md:text-4xl lg:text-5xl font-semibold text-white tracking-tight">
                                <?php echo e($item->header_name); ?>

                            </h2>
                            <p class="text-sm md:text-base text-white/70 max-w-xl mx-auto">
                                <?php echo e($item->header_description); ?></p>
                            <div class="flex gap-3 justify-center">
                                <a href="<?php echo e($item->link_header); ?>" target="_blank" rel="noopener noreferrer"
                                    style="background-color: <?php echo e($item->header_color); ?>99;" data-magnetic
                                    class="px-5 py-2 md:px-7 md:py-3 shadow-sm font-semibold rounded-full text-sm md:text-base transition duration-300 hover:scale-105 active:scale-95">Watch
                                    Video</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-next text-white"></div>
        <div class="swiper-button-prev text-white"></div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const videosEl = document.querySelector(".videosSwiper");
        if (!videosEl) return;

        const videoSources = {
            <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $bgExtension = strtolower(pathinfo($item->header_background, PATHINFO_EXTENSION));
                    $isVideo = in_array($bgExtension, ['mp4', 'webm', 'mov']);
                ?>
                <?php if($isVideo): ?>
                    <?php echo e($loop->iteration); ?>: "<?php echo e(Storage::url('header/background/' . $item->header_background)); ?>",
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        };

        const videos = videosEl.querySelectorAll(".bg-video");

        function playVideo(index) {
            videos.forEach(video => {
                video.pause();
                if (video.dataset.loaded === "true") {
                    video.currentTime = 0;
                }
            });

            const activeVideo = videosEl.querySelector(`[data-video="${index}"]`);
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

        // Video latar berukuran besar (belasan MB): mulai diunduh SETELAH halaman selesai dimuat, supaya tidak
        // berebut bandwidth dengan gambar yang tampil pertama (LCP). Mode Hemat Data: video pertama tidak diputar.
        if (videos.length > 0) {
            const hematData = navigator.connection && navigator.connection.saveData;
            if (!hematData) {
                if (document.readyState === "complete") {
                    playVideo(1);
                } else {
                    window.addEventListener("load", () => playVideo(1), {
                        once: true
                    });
                }
            }
        }

        const swiper = videosEl.swiper;
        if (!swiper) return;
        swiper.on("slideChange", function() {
            playVideo(swiper.realIndex + 1);
        });

    });
</script>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/videos.blade.php ENDPATH**/ ?>