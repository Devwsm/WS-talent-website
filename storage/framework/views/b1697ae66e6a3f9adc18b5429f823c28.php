
<?php $__env->startSection('content'); ?>
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        <?php echo $__env->make('components/dashboard/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 z-0 opacity-5"
            style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 26px 26px;">
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-40 -right-32 w-96 h-96 rounded-full blur-3xl"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.5;"></div>
        <div aria-hidden="true" class="pointer-events-none absolute top-1/2 -left-40 w-80 h-80 rounded-full blur-3xl"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <?php
            $greeting = match (true) {
                now()->hour < 11 => 'Selamat pagi',
                now()->hour < 15 => 'Selamat siang',
                now()->hour < 18 => 'Selamat sore',
                default => 'Selamat malam',
            };
        ?>

        <div
            class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6
            px-4 sm:px-5 md:px-10 pt-6 md:pt-8 pb-28 text-white">

            
            <div
                class="relative rounded-3xl border border-white/10 bg-white/3 overflow-hidden px-5 py-5 md:px-8 md:py-6
                flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold uppercase wrap-anywhere">
                        <?php echo e($greeting); ?>, <span class="normal-case text-white/80"><?php echo e(session('user', 'Admin')); ?></span>
                        👋
                    </h1>
                    <p class="text-white/50 mt-1 text-sm md:text-base">
                        Ini preview konten yang lagi tayang di website Whisnu Santika.
                    </p>
                </div>
                <a href="<?php echo e(route('home')); ?>" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center justify-center gap-2 shrink-0 px-4 py-2 rounded-full border border-white/15
                    text-sm font-semibold text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Lihat website
                </a>
            </div>

            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">

                
                <div class="lg:col-span-2 min-w-0">
                    <?php $__env->startComponent('components.dashboard.card.card', [
                        'title' => 'Header (' . $header->count() . ')',
                        'href' => route('headers'),
                        'icon' => 'bi-card-image',
                        'flush' => true,
                    ]); ?>
                        <?php if($header->first()): ?>
                            <?php
                                $h = $header->first();
                                $hBgExt = strtolower(pathinfo($h->header_background, PATHINFO_EXTENSION));
                                $hIsVideo = in_array($hBgExt, ['mp4', 'webm', 'mov']);
                                $hBgUrl = Storage::url('header/background/' . $h->header_background);
                            ?>
                            <div class="relative flex-1 min-h-72 sm:min-h-80 overflow-hidden bg-black">
                                <?php if($hIsVideo): ?>
                                    
                                    <video muted playsinline preload="metadata" tabindex="-1" aria-hidden="true"
                                        src="<?php echo e($hBgUrl); ?>#t=0.5"
                                        class="absolute inset-0 w-full h-full object-cover"></video>
                                <?php else: ?>
                                    <img src="<?php echo e($hBgUrl); ?>" alt="" loading="lazy" decoding="async"
                                        class="absolute inset-0 w-full h-full object-cover">
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-black/55"></div>

                                <div
                                    class="relative z-10 h-full min-h-72 sm:min-h-80 flex flex-col justify-center items-center
                                    text-center gap-3 px-5 py-6">
                                    <span style="border-color: <?php echo e($h->header_color); ?>99;"
                                        class="inline-flex items-center gap-2 max-w-full bg-white/10 backdrop-blur border rounded-full px-3 py-1 text-xs">
                                        <span style="background-color: <?php echo e($h->header_color); ?>;" aria-hidden="true"
                                            class="w-2 h-2 rounded-full shrink-0"></span>
                                        <span class="truncate"><?php echo e($h->header_title); ?></span>
                                    </span>
                                    <img src="<?php echo e(Storage::url('header/img/' . $h->header_img)); ?>" alt="<?php echo e($h->header_name); ?>"
                                        loading="lazy" decoding="async" class="w-28 sm:w-36 lg:w-44 max-h-20 object-contain">
                                    <h3 class="text-lg md:text-xl lg:text-2xl font-semibold wrap-anywhere">
                                        <?php echo e($h->header_name); ?></h3>
                                    <p class="text-white/70 text-sm line-clamp-2 max-w-md"><?php echo e($h->header_description); ?></p>
                                    <?php if($header->count() > 1): ?>
                                        <p class="text-xs text-white/50">+<?php echo e($header->count() - 1); ?> header lainnya</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-6">
                                <?php echo $__env->make('components/dashboard/card/empty-state', [
                                    'message' => 'Belum ada header yang diupload.',
                                    'cta_href' => route('headers'),
                                    'cta_label' => 'Tambah header',
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                        <?php endif; ?>
                    <?php echo $__env->renderComponent(); ?>
                </div>

                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-6 min-w-0">
                    <?php $__env->startComponent('components.dashboard.card.card', [
                        'title' => 'Warna Web',
                        'href' => route('color_pages.index'),
                        'icon' => 'bi-palette-fill',
                    ]); ?>
                        <div class="h-full flex items-center gap-4">
                            <span class="w-14 h-14 rounded-2xl border border-white/20 shrink-0"
                                style="background-color: <?php echo e($color_pages->color ?? '#000000'); ?>;"></span>
                            <div class="min-w-0">
                                <p class="font-mono text-lg"><?php echo e($color_pages->color ?? '#000000'); ?></p>
                                <p class="text-xs text-white/40">Warna navbar &amp; footer website</p>
                            </div>
                        </div>
                    <?php echo $__env->renderComponent(); ?>

                    <?php $__env->startComponent('components.dashboard.card.card', [
                        'title' => 'Profil',
                        'href' => route('dashboard.profile'),
                        'icon' => 'bi-person-fill',
                    ]); ?>
                        <div class="h-full grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/4 p-4">
                                <p class="text-2xl font-bold"><?php echo e($highlightCount); ?></p>
                                <p class="text-xs text-white/50 mt-0.5">Highlight</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/4 p-4">
                                <p class="text-2xl font-bold"><?php echo e($statistikCount); ?></p>
                                <p class="text-xs text-white/50 mt-0.5">Statistik</p>
                            </div>
                        </div>
                    <?php echo $__env->renderComponent(); ?>
                </div>
            </div>

            
            <?php $__env->startComponent('components.dashboard.card.card', [
                'title' => 'Banner (' . $banner->count() . ')',
                'href' => route('banner'),
                'icon' => 'bi-images',
                'flush' => true,
            ]); ?>
                <?php if($banner->first()): ?>
                    <div class="bg-black">
                        <img src="<?php echo e(Storage::url('banner/' . $banner->first()->banner_cover)); ?>"
                            alt="<?php echo e($banner->first()->banner_name); ?>" loading="lazy" decoding="async"
                            class="w-full h-auto max-h-72 object-contain mx-auto">
                    </div>
                    <div class="flex items-center justify-between gap-3 px-6 py-3 border-t border-white/10">
                        <p class="font-semibold text-sm truncate"><?php echo e($banner->first()->banner_name); ?></p>
                        <?php if($banner->count() > 1): ?>
                            <p class="text-xs text-white/40 shrink-0">+<?php echo e($banner->count() - 1); ?> lainnya</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="p-6">
                        <?php echo $__env->make('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada banner.',
                            'cta_href' => route('banner'),
                            'cta_label' => 'Tambah banner',
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                <?php endif; ?>
            <?php echo $__env->renderComponent(); ?>

            
            <?php $__env->startComponent('components.dashboard.card.card', [
                'title' => 'Berita Terbaru (' . $news->count() . ')',
                'href' => route('news'),
                'icon' => 'bi-newspaper',
            ]); ?>
                <?php if($news->isEmpty()): ?>
                    <?php echo $__env->make('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada berita yang ditambahkan.',
                        'cta_href' => route('news'),
                        'cta_label' => 'Tambah berita',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php $__currentLoopData = $news->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="rounded-2xl border border-white/10 overflow-hidden bg-white/2 min-w-0">
                                <img src="<?php echo e(Storage::url('news/' . $item->news_cover)); ?>" alt="<?php echo e($item->news_title); ?>"
                                    loading="lazy" decoding="async" class="w-full aspect-video object-cover">
                                <div class="p-4">
                                    <h4 class="font-semibold text-sm line-clamp-2"><?php echo e($item->news_title); ?></h4>
                                    <p class="text-xs text-white/50 mt-2 truncate"><?php echo e($item->news_source); ?></p>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php if($news->count() > 3): ?>
                        <p class="text-xs text-white/40 mt-4">+<?php echo e($news->count() - 3); ?> berita lainnya</p>
                    <?php endif; ?>
                <?php endif; ?>
            <?php echo $__env->renderComponent(); ?>

            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php $__env->startComponent('components.dashboard.card.card', [
                    'title' => 'Album (' . $albums->count() . ')',
                    'href' => route('albums'),
                    'icon' => 'bi-disc-fill',
                ]); ?>
                    <?php if($albums->isEmpty()): ?>
                        <?php echo $__env->make('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada album yang ditambahkan.',
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php else: ?>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            <?php $__currentLoopData = $albums->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="group/item">
                                    <div class="rounded-xl aspect-square overflow-hidden">
                                        <img src="<?php echo e(Storage::url('albums/' . $item->albums_cover)); ?>"
                                            alt="<?php echo e($item->albums_name); ?>"
                                            class="w-full h-full object-cover transition-transform group-hover/item:scale-105">
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-center line-clamp-1"><?php echo e($item->albums_name); ?></p>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php if($albums->count() > 8): ?>
                            <p class="text-xs text-white/40 mt-4">+<?php echo e($albums->count() - 8); ?> album lainnya</p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php echo $__env->renderComponent(); ?>

                <?php $__env->startComponent('components.dashboard.card.card', [
                    'title' => 'Merchandise (' . $merchandise->count() . ')',
                    'href' => route('merchandise'),
                    'icon' => 'bi-basket-fill',
                ]); ?>
                    <?php if($merchandise->isEmpty()): ?>
                        <?php echo $__env->make('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada merchandise yang ditambahkan.',
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php else: ?>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            <?php $__currentLoopData = $merchandise->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="group/item">
                                    <div class="rounded-xl aspect-square overflow-hidden">
                                        <img src="<?php echo e(Storage::url('merchandise/' . $item->merchandise_cover)); ?>"
                                            alt="<?php echo e($item->merchandise_name); ?>"
                                            class="w-full h-full object-cover transition-transform group-hover/item:scale-105">
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-center line-clamp-1">
                                        <?php echo e($item->merchandise_name); ?></p>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php if($merchandise->count() > 8): ?>
                            <p class="text-xs text-white/40 mt-4">+<?php echo e($merchandise->count() - 8); ?> merchandise lainnya</p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php echo $__env->renderComponent(); ?>
            </div>

            
            <?php if(config('inner_circle.aktif')): ?>
                <?php $__env->startComponent('components.dashboard.card.card', [
                    'title' => 'WS Inner Circle',
                    'href' => route('ic.admin.ringkasan'),
                    'icon' => 'bi-people-fill',
                ]); ?>
                    <ul class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                        <?php $__currentLoopData = config('inner_circle.tab_admin'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <a href="<?php echo e(route($tab['route'])); ?>"
                                    class="flex flex-col items-center justify-center gap-2 min-h-24 rounded-2xl border border-white/10 bg-white/4 p-3
                                    text-white/70 hover:border-white/20 hover:bg-white/10 hover:text-white transition-colors">
                                    <i class="bi <?php echo e($tab['ikon']); ?> text-2xl" aria-hidden="true"></i>
                                    <span class="text-xs font-semibold text-center"><?php echo e($tab['label']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php echo $__env->renderComponent(); ?>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard.blade.php ENDPATH**/ ?>