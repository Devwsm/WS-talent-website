<div id="new-music" class="relative bg-white overflow-hidden">
    <div class="absolute inset-0
        bg-black transform z-0">
    </div>
    <div class="albums relative
        w-full py-24 px-6 md:px-16 lg:px-52 gap-6">
        <div class="swiper musicSwiper" data-motion="fade-up">
            <div class="swiper-wrapper">
                <?php $__currentLoopData = $albums; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="swiper-slide">
                        <a href="<?php echo e($item->link_spotify); ?>" target="_blank" rel="noopener noreferrer" class="block"
                            data-tilt="6">
                            <img src="<?php echo e(Storage::url('albums/' . $item->albums_cover)); ?>"
                                alt="<?php echo e($item->albums_name); ?>" loading="lazy" decoding="async"
                                class="object-cover aspect-square w-full hover:scale-105 transition duration-300 rounded-lg">
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next text-white"></div>
            <div class="swiper-button-prev text-white"></div>

        </div>
    </div>
</div>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/albums.blade.php ENDPATH**/ ?>