

<?php $__env->startSection('title', 'Whisnu Santika — DJ & Produser Musik Indonesia | Pionir Indonesian Bounce'); ?>
<?php $__env->startSection('meta_description',
    'Whisnu Santika — DJ & produser asal Jakarta, pionir Indonesian Bounce. Info tur, rilisan
    musik, merchandise, dan berita terbaru.'); ?>
<?php $__env->startSection('og_image', $hero->foto ?? null ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto) :
    asset('aset/logo/Whisnu-Santika_Logo-2025-White.png')); ?>

    <?php $__env->startPush('schema'); ?>
        <script type="application/ld+json">
        <?php echo json_encode([
            '<?php $__contextArgs = [];
if (context()->has($__contextArgs[0])) :
if (isset($value)) { $__contextPrevious[] = $value; }
$value = context()->get($__contextArgs[0]); ?>' => 'https://schema.org',
            '@type' => 'MusicGroup',
            'name' => 'Whisnu Santika',
            'url' => url('/'),
            'image' => ($hero->foto ?? null)
                ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto)
                : asset('aset/logo/Whisnu-Santika_Logo-2025-White.png'),
            'description' => isset($bio->konten)
                ? trim(strip_tags($bio->konten))
                : 'Whisnu Santika — DJ & produser asal Jakarta, pionir Indonesian Bounce.',
            'genre' => $genre->pluck('nama_genre')->values(),
            'sameAs' => [
                'https://www.instagram.com/whisnusantika',
                'https://www.tiktok.com/@whisnusantika',
                'https://youtube.com/@whisnusantika',
                'https://open.spotify.com/artist/6gvsmDZKW5wRvjKCPnbHDh',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>

    </script>
    <?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div>
        
        <h1 class="sr-only"><?php echo e($hero->nama ?? 'Whisnu Santika'); ?></h1>

        <div class="grid grid-cols-1 w-full justify-center items-center">
            <div class="main-section flex flex-col">
                <div class="relative">
                    <?php echo $__env->make('components/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('components/banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
                <div class="relative">
                    <?php echo $__env->make('components/videos', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
        <div id="profile" class="profile flex flex-col w-full">
            <?php echo $__env->make('components/profile/profile-teaser', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>

        
        <?php
            $marqueeWords = $genre->count()
                ? $genre->pluck('nama_genre')->all()
                : ['Indonesian Bounce', 'DJ & Producer'];
            // Cukup ±6 kata per grup — lebih panjang dari itu cuma bikin layer animasi raksasa & berat di HP.
            $reps = max(1, (int) ceil(6 / max(1, count($marqueeWords))));
        ?>
        <div class="marquee bg-black border-y border-white/10 py-5 md:py-8" aria-hidden="true">
            <div class="marquee-track">
                <?php for($g = 0; $g < 2; $g++): ?>
                    <div class="marquee-group">
                        <?php for($r = 0; $r < $reps; $r++): ?>
                            <?php $__currentLoopData = $marqueeWords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $word): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="marquee-word"><?php echo e($word); ?></span>
                                <span class="marquee-dot">✦</span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <?php if(config('inner_circle.aktif')): ?>
            <?php echo $__env->make('components/inner-circle-teaser', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
        
        <div id="tour" class="schedule flex flex-col w-full">
            <?php echo $__env->make('components/tour', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>

        <?php if($news->isNotEmpty()): ?>
            <div id="news" class="news flex flex-col w-full">
                <h2 class="sr-only">Berita Terbaru</h2>
                <?php echo $__env->make('components/news', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        <?php endif; ?>
        <div class="product flex flex-col w-full">
            <?php echo $__env->make('components/albums', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('components/merchandise', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <?php echo $__env->make('components/footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/home.blade.php ENDPATH**/ ?>