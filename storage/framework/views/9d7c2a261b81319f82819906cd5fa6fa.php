<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="robots" content="index, follow">
    <title><?php echo $__env->yieldContent('title', 'WS Inner Circle'); ?> — Whisnu Santika</title>
    <meta name="description" content="<?php echo $__env->yieldContent('deskripsi'); ?>">
    <link rel="canonical" href="<?php echo e(url()->current()); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $__env->yieldContent('title', 'WS Inner Circle'); ?> — Whisnu Santika">
    <meta property="og:description" content="<?php echo $__env->yieldContent('deskripsi'); ?>">
    <meta property="og:url" content="<?php echo e(url()->current()); ?>">
    <meta property="og:image" content="<?php echo e(asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png')); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/inner-circle.css', 'resources/js/inner-circle.js']); ?>
    <link rel="icon" href="<?php echo e(asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png')); ?>" type="image/png">
    <?php echo $__env->yieldPushContent('head'); ?>
</head>

<body class="ic-app">
    <a class="ic-lewati" href="#konten">Lewati ke konten</a>

    <div class="ic-shell ic-shell--publik">
        <header class="ic-bar">
            <div>
                <?php if (isset($component)) { $__componentOriginalc2ef2538e0fad04ea562c2cdaec1484f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc2ef2538e0fad04ea562c2cdaec1484f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.logo','data' => ['ukuran' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['ukuran' => 'md']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc2ef2538e0fad04ea562c2cdaec1484f)): ?>
<?php $attributes = $__attributesOriginalc2ef2538e0fad04ea562c2cdaec1484f; ?>
<?php unset($__attributesOriginalc2ef2538e0fad04ea562c2cdaec1484f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc2ef2538e0fad04ea562c2cdaec1484f)): ?>
<?php $component = $__componentOriginalc2ef2538e0fad04ea562c2cdaec1484f; ?>
<?php unset($__componentOriginalc2ef2538e0fad04ea562c2cdaec1484f); ?>
<?php endif; ?>
                <p class="ic-bar__sub">INNER CIRCLE</p>
            </div>
            <div class="ic-bar__aksi">
                <?php if($sudahMasuk): ?>
                    <a href="<?php echo e(route('ic.beranda')); ?>" class="ic-tombol">Buka Inner Circle</a>
                <?php else: ?>
                    <a href="<?php echo e(route('ic.masuk')); ?>" class="ic-tombol ic-tombol--garis">Masuk</a>
                    <a href="<?php echo e(route('ic.daftar')); ?>" class="ic-tombol">Daftar</a>
                <?php endif; ?>
            </div>
        </header>

        <main class="ic-konten ic-konten--publik" id="konten">
            <?php echo $__env->yieldContent('content'); ?>
        </main>

        <footer class="ic-footer-publik">
            <p>&copy; <?php echo e(date('Y')); ?> Whisnu Santika ·
                <a href="<?php echo e(route('home')); ?>">Situs resmi</a> ·
                <a href="<?php echo e(route('privasi')); ?>">Kebijakan Privasi</a>
            </p>
        </footer>
    </div>
</body>

</html>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/template/innerCirclePublikLayout.blade.php ENDPATH**/ ?>