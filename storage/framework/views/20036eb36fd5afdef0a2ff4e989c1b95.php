

<?php $__env->startSection('title', ($hero->nama ?? 'Whisnu Santika') . ' — Profile | Whisnu Santika'); ?>
<?php $__env->startSection('meta_description',
    $hero->tagline ?? null ?:
    'Profile lengkap Whisnu Santika — genre, highlight, kolaborasi,
    media coverage, dan kontak booking.'); ?>
<?php $__env->startSection('og_image', $hero->foto ?? null ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto) :
    asset('aset/logo/whisnuSantika.jpg')); ?>

    <?php $__env->startPush('schema'); ?>
        <script type="application/ld+json">
        <?php echo json_encode([
            '<?php $__contextArgs = [];
if (context()->has($__contextArgs[0])) :
if (isset($value)) { $__contextPrevious[] = $value; }
$value = context()->get($__contextArgs[0]); ?>' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $hero->nama ?? 'Whisnu Santika',
            'url' => url('/profile'),
            'image' => ($hero->foto ?? null)
                ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto)
                : asset('aset/logo/whisnuSantika.jpg'),
            'jobTitle' => 'DJ & Produser Musik',
            'description' => isset($bio->konten) ? trim(strip_tags($bio->konten)) : null,
            'sameAs' => $mediaSosial->pluck('url')->values(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>

    </script>
    <?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="flex flex-col w-full bg-black p-6 md:p-12 gap-8">
        <?php
            $color = $color_pages->color ?? '#5E0006';
            $heroFoto =
                $hero->foto ?? null
                    ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto)
                    : asset('aset/logo/whisnuSantika.jpg');
        ?>
        <div class="relative">
            <?php echo $__env->make('components/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>

        
        <div class="head relative flex w-full justify-center">
            <img src="<?php echo e($heroFoto); ?>" loading="eager" fetchpriority="high" decoding="async"
                alt="<?php echo e($hero->nama ?? 'whisnu-santika'); ?>"
                class="object-cover object-center w-full aspect-4/5 sm:aspect-video rounded-lg">

            <div class="absolute inset-0 rounded-lg bg-linear-to-t from-black/80 via-black/30 to-transparent"></div>
            <div class="hero-in absolute bottom-4 left-4 md:bottom-8 md:left-8 text-left max-w-lg">
                <p class="text-xs text-white/60 uppercase tracking-widest mb-1">
                    <?php echo e($hero->judul_singkat ?? 'DJ & Producer'); ?></p>
                <h1 class="text-3xl md:text-5xl font-medium text-white mb-1"><?php echo e($hero->nama ?? 'Whisnu Santika'); ?></h1>
                <p class="text-sm text-white/60 leading-relaxed"><?php echo e($hero->tagline ?? 'Pionir Indonesian Bounce — '); ?></p>
            </div>
        </div>

        
        <div class="flex flex-wrap gap-2" data-motion-stagger="0.06" data-motion-children="pop">
            <?php $__empty_1 = true; $__currentLoopData = $genre; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <span
                    class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60"><?php echo e($item->nama_genre); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Indonesian Bounce</span>
            <?php endif; ?>
        </div>

        
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Bio</h2>
            <div class="text-sm md:text-base text-white/70 leading-relaxed flex flex-col gap-3" data-motion="fade-up">
                <?php echo \App\Support\HtmlSanitizer::clean($bio->konten ?? '') ?:
                    '<p class="text-sm text-white/70 leading-relaxed">Whisnu Santika adalah DJ dan produser rekaman asal Jakarta yang dikenal sebagai pionir <span class="text-white font-medium">Indonesian Bounce</span> — sebuah pendekatan genre yang ia ciptakan sendiri dengan memadukan EDM, dancehall, moombahton, hip hop, bass, dan afrobeat.</p>'; ?>

            </div>
        </div>

        
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Pencapaian</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3" data-motion-stagger="0.08" data-motion-children="pop">
                <?php $__currentLoopData = $statistik; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="spotlight-card flex flex-col gap-1 p-3 md:p-5 rounded-lg bg-white/5 border border-white/10">
                        <span class="text-lg md:text-3xl font-medium text-white" data-count><?php echo e($item->total); ?></span>
                        <span class="text-xs text-white/50"><?php echo e($item->platform); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Highlight Penampilan</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3" data-motion-stagger="0.1" data-motion-children="fade-up">
                <?php $__currentLoopData = $highlight; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div
                        class="spotlight-card flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/10">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-sm font-medium text-white"><?php echo e($item->place); ?></span>
                            <span class="text-xs text-white/60"><?php echo e($item->description); ?></span>
                        </div>
                        <span class="text-xs text-white/20"><?php echo e($item->year); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Kolaborasi</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3" data-motion-stagger="0.08" data-motion-children="pop">
                <?php $__empty_1 = true; $__currentLoopData = $collab; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="spotlight-card flex flex-col gap-0.5 p-4 rounded-lg bg-white/5 border border-white/10">
                        <span class="text-sm font-medium text-white"><?php echo e($item->nama); ?></span>
                        <span class="text-xs text-white/60"><?php echo e($item->role); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <span class="text-xs text-white/30">Belum ada data kolaborasi.</span>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Media Coverage</h2>
            <div class="flex flex-wrap gap-2" data-motion-stagger="0.06" data-motion-children="pop">
                <?php $__empty_1 = true; $__currentLoopData = $mediaCoverage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <span
                        class="text-xs px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/50"><?php echo e($item->nama_media); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <span class="text-xs text-white/30">Belum ada data media coverage.</span>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Booking & Bisnis</h2>
            <div class="flex flex-col gap-3 p-5 rounded-lg bg-white/5 border border-white/10" data-motion="fade-up">
                <p class="text-xs text-white/60 leading-relaxed">
                    Untuk keperluan booking show, brand partnership, atau kolaborasi musik,
                    hubungi tim manajemen <?php echo e($hero->nama ?? 'Whisnu Santika'); ?>.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1" data-motion-stagger="0.1"
                    data-motion-children="fade-up">
                    <?php $__empty_1 = true; $__currentLoopData = $booking; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-white/50 uppercase tracking-widest"><?php echo e($item->label); ?></span>
                            <a href="mailto:<?php echo e($item->email); ?>"
                                class="text-sm text-white/70 hover:text-white transition-colors">
                                <?php echo e($item->email); ?>

                            </a>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <span class="text-xs text-white/30">Belum ada data booking & kontak.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <div id="ikuti" class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Ikuti</h2>
            <div class="flex flex-wrap gap-2" data-motion-stagger="0.06" data-motion-children="pop">
                <?php $__empty_1 = true; $__currentLoopData = $mediaSosial; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e($item->url); ?>" target="_blank" rel="noopener"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/60 hover:text-white hover:bg-white/10 transition-colors text-xs">
                        <?php echo e($item->platform); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <span class="text-xs text-white/30">Belum ada tautan media sosial.</span>
                <?php endif; ?>
            </div>
        </div>

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/profile/profile-full.blade.php ENDPATH**/ ?>