<?php if($banner->isNotEmpty()): ?>
    
    <div id="banner-slider" class="flex flex-col pt-16 w-full z-30">
        <div class="swiper bannerSwiper w-full">
            <div class="swiper-wrapper">
                <?php $__currentLoopData = $banner; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="swiper-slide">
                        <a href="<?php echo e($item->link_banner); ?>" target="_blank" rel="noopener noreferrer" class="block">
                            <img src="<?php echo e(Storage::url('banner/' . $item->banner_cover)); ?>" alt="<?php echo e($item->banner_name); ?>"
                                loading="<?php echo e($loop->first ? 'eager' : 'lazy'); ?>" decoding="async"
                                <?php if($loop->first): ?> fetchpriority="high" <?php endif; ?>
                                class="object-cover w-full rounded-lg">
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/banner.blade.php ENDPATH**/ ?>