<div class="flex flex-col w-full bg-black p-6 md:p-12 gap-4">

    <?php
        $heroFoto =
            $hero->foto ?? null
                ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto)
                : asset('aset/logo/whisnuSantika.jpg');
    ?>

    
    <div class="head relative hidden lg:flex w-full justify-start items-center gap-4" data-motion="blur-up">
        <img src="<?php echo e($heroFoto); ?>" loading="lazy" decoding="async" alt="<?php echo e($hero->nama ?? 'whisnu-santika'); ?>"
            class="object-cover object-center w-42 aspect-square rounded-full">

        <div class="flex flex-col">
            <p class="text-sm text-white/60"><?php echo e($hero->judul_singkat ?? 'DJ & Producer'); ?></p>
            <p class="text-2xl md:text-4xl lg:text-2xl font-medium text-white"><?php echo e($hero->nama ?? 'Whisnu Santika'); ?></p>
            <p class="text-sm text-white/60 leading-relaxed"><?php echo e($hero->tagline ?? 'Pionir Indonesian Bounce — '); ?></p>
        </div>
    </div>
    
    <div class="head relative flex lg:hidden w-full justify-center" data-motion="blur-up">
        <img src="<?php echo e($heroFoto); ?>" loading="lazy" decoding="async" alt="<?php echo e($hero->nama ?? 'whisnu-santika'); ?>"
            class="object-cover object-center w-full aspect-4/5 md:aspect-16/10 rounded-lg">

        <div class="absolute inset-0 rounded-lg bg-linear-to-t from-black/75 via-black/25 to-transparent"></div>
        <div class="absolute bottom-4 left-4 md:bottom-8 md:left-8 text-left">
            <p class="text-sm text-white/60"><?php echo e($hero->judul_singkat ?? 'DJ & Producer'); ?></p>
            <p class="text-2xl md:text-4xl lg:text-2xl font-medium text-white"><?php echo e($hero->nama ?? 'Whisnu Santika'); ?></p>
            <p class="text-sm text-white/60 leading-relaxed"><?php echo e($hero->tagline ?? 'Pionir Indonesian Bounce — '); ?></p>
        </div>
    </div>

    
    <div class="flex flex-col gap-4">

        
        <div class="flex flex-wrap gap-2 px-1" data-motion-stagger="0.06" data-motion-children="pop">
            <?php $__empty_1 = true; $__currentLoopData = $genre; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <span
                    class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60"><?php echo e($item->nama_genre); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Indonesian
                    Bounce</span>
            <?php endif; ?>
        </div>

        
        <p class="text-sm md:text-base text-white/70 leading-relaxed px-1" data-motion="fade-up">
            <?php echo e($bio->konten ?? null ? \Illuminate\Support\Str::limit(strip_tags($bio->konten), 220) : 'Pionir Indonesian Bounce — memadukan EDM, dancehall, hip hop, dan afrobeat dalam satu identitas bunyi yang khas. Tampil di Tomorrowland Belgium 2024 dan Djakarta Warehouse Project, menjadikan Whisnu Santika salah satu DJ Indonesia dengan jangkauan global.'); ?>

        </p>

        
        <div class="grid grid-cols-3 gap-3" data-motion-stagger="0.1" data-motion-children="pop">
            <?php $__currentLoopData = $statistik; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="spotlight-card flex flex-col gap-1 p-3 md:p-5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-lg md:text-3xl font-medium text-white" data-count><?php echo e($item->total); ?></span>
                    <span class="text-xs text-white/50"><?php echo e($item->platform); ?></span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <a href="<?php echo e(route('profile')); ?>"
            class="flex items-center justify-between w-full px-4 py-3 rounded-lg border border-white/20 text-white/80 hover:bg-white/5 transition-colors group">
            <span class="text-sm font-medium">Lihat profil lengkap</span>
            <span class="text-white/40 group-hover:text-white/70 transition-colors">→</span>
        </a>

    </div>
</div>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/profile/profile-teaser.blade.php ENDPATH**/ ?>