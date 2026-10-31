<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $__env->yieldContent('title', 'WS Inner Circle'); ?> — Whisnu Santika</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/inner-circle.css', 'resources/js/inner-circle.js']); ?>
    <link rel="icon" href="<?php echo e(asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png')); ?>" type="image/png">
    <?php echo $__env->yieldPushContent('head'); ?>
</head>



<body class="ic-app ic-app--auth" data-ic-toast="<?php echo e(session('ic_toast')); ?>">
    <a class="ic-lewati" href="#konten">Lewati ke konten</a>

    <main class="ic-auth" id="konten">
        <div class="ic-auth__kartu">
            <?php if (isset($component)) { $__componentOriginalc2ef2538e0fad04ea562c2cdaec1484f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc2ef2538e0fad04ea562c2cdaec1484f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.logo','data' => ['ukuran' => 'lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['ukuran' => 'lg']); ?>
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
            <p class="ic-sub">INNER CIRCLE</p>
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </main>
</body>

</html>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/template/innerCircleAuthLayout.blade.php ENDPATH**/ ?>