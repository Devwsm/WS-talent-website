<?php
    // Ikon Bootstrap Icons per platform — dicocokkan dari nama platform yang diisi di dashboard.
    $iconMap = [
        'instagram' => 'bi-instagram',
        'tiktok' => 'bi-tiktok',
        'youtube' => 'bi-youtube',
        'spotify' => 'bi-spotify',
        'soundcloud' => 'bi-soundcloud',
        'apple' => 'bi-apple',
        'facebook' => 'bi-facebook',
        'twitter' => 'bi-twitter-x',
        'x.com' => 'bi-twitter-x',
        'whatsapp' => 'bi-whatsapp',
        'linkedin' => 'bi-linkedin',
    ];
    $iconFor = function (string $platform) use ($iconMap): string {
        $name = strtolower($platform);
        foreach ($iconMap as $key => $icon) {
            if (str_contains($name, $key)) {
                return $icon;
            }
        }
        return 'bi-link-45deg';
    };

    $socials = ($footerSosial ?? collect())->isNotEmpty()
        ? $footerSosial->map(fn($s) => ['platform' => $s->platform, 'url' => $s->url])->all()
        : [
            // Fallback kalau belum ada data media sosial di dashboard.
            ['platform' => 'Instagram', 'url' => 'https://www.instagram.com/whisnusantika'],
            ['platform' => 'TikTok', 'url' => 'https://www.tiktok.com/@whisnusantika'],
            ['platform' => 'YouTube', 'url' => 'https://youtube.com/@whisnusantika'],
            ['platform' => 'Spotify', 'url' => 'https://open.spotify.com/artist/6gvsmDZKW5wRvjKCPnbHDh'],
        ];
?>

<footer style="background-color: <?php echo e($color); ?>;"
    class="footer flex flex-col w-full text-white border-t border-white/10 px-6 py-10 md:px-12 md:py-14 gap-10">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">

        
        <?php if(($footerBooking ?? collect())->isNotEmpty()): ?>
            <section aria-labelledby="footer-booking">
                <h2 id="footer-booking" class="text-xs text-white/50 uppercase tracking-widest mb-4">Booking & Bisnis</h2>
                <ul class="flex flex-col gap-4">
                    <?php $__currentLoopData = $footerBooking; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex flex-col gap-0.5">
                            <span class="text-xs text-white/50"><?php echo e($item->label); ?></span>
                            <a href="mailto:<?php echo e($item->email); ?>"
                                class="text-sm text-white/80 hover:text-white break-all transition-colors"><?php echo e($item->email); ?></a>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </section>
        <?php endif; ?>

        
        <?php if(($footerGenre ?? collect())->isNotEmpty()): ?>
            <section aria-labelledby="footer-genre">
                <h2 id="footer-genre" class="text-xs text-white/50 uppercase tracking-widest mb-4">Genre</h2>
                <ul class="flex flex-wrap gap-2">
                    <?php $__currentLoopData = $footerGenre; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/70">
                            <?php echo e($item->nama_genre); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </section>
        <?php endif; ?>

        
        <?php if(($footerMedia ?? collect())->isNotEmpty()): ?>
            <section aria-labelledby="footer-media">
                <h2 id="footer-media" class="text-xs text-white/50 uppercase tracking-widest mb-4">Media Coverage</h2>
                <ul class="flex flex-wrap gap-2">
                    <?php $__currentLoopData = $footerMedia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="text-xs px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white/70">
                            <?php echo e($item->nama_media); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </section>
        <?php endif; ?>

        
        <section aria-labelledby="footer-ikuti">
            <h2 id="footer-ikuti" class="text-xs text-white/50 uppercase tracking-widest mb-4">Ikuti</h2>
            <nav aria-label="Media sosial" class="social-media flex flex-wrap gap-3">
                <?php $__currentLoopData = $socials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $social): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e($social['url']); ?>" target="_blank" rel="noopener noreferrer"
                        aria-label="<?php echo e($social['platform']); ?> Whisnu Santika" title="<?php echo e($social['platform']); ?>"
                        class="flex items-center justify-center w-11 h-11 text-lg border-2 border-white/30 rounded-full transition duration-300 hover:-translate-y-1 hover:bg-white/15 hover:border-white active:scale-90">
                        <i class="bi <?php echo e($iconFor($social['platform'])); ?>" aria-hidden="true"></i>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>
        </section>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-6 border-t border-white/10">
        <p class="text-white/50 text-sm">Copyright &copy; <?php echo e(date('Y')); ?> wahsudahmonday. All rights reserved.</p>
        <nav aria-label="Tautan footer" class="flex flex-wrap gap-x-5 gap-y-2">
            <a href="<?php echo e(route('privasi')); ?>" class="text-sm text-white/60 hover:text-white transition-colors">Kebijakan
                Privasi</a>
            <a href="<?php echo e(route('profile')); ?>" class="text-sm text-white/60 hover:text-white transition-colors">Profil
                lengkap →</a>
        </nav>
    </div>
</footer>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/footer.blade.php ENDPATH**/ ?>