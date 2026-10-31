<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'WS Inner Circle'); ?> — Whisnu Santika</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/inner-circle.css', 'resources/js/inner-circle.js']); ?>
    <link rel="icon" href="<?php echo e(asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png')); ?>" type="image/png">
    <?php echo $__env->yieldPushContent('head'); ?>
</head>

<body class="ic-app" data-ic-toast="<?php echo e(session('ic_toast')); ?>" data-ic-toast-galat="<?php echo e(session('ic_toast_galat')); ?>">
    <a class="ic-lewati" href="#konten">Lewati ke konten</a>

    <div class="ic-shell">
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
                
                <form method="POST" action="<?php echo e(route('ic.keluar')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="ic-tombol ic-tombol--garis">Keluar</button>
                </form>
                <a href="<?php echo e(route('ic.profil')); ?>" class="ic-bar__avatar" aria-label="Profil saya">
                    <?php if (isset($component)) { $__componentOriginald3421edf8aa16853fbe25459ab4dc93d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald3421edf8aa16853fbe25459ab4dc93d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.avatar','data' => ['member' => $member]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['member' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($member)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald3421edf8aa16853fbe25459ab4dc93d)): ?>
<?php $attributes = $__attributesOriginald3421edf8aa16853fbe25459ab4dc93d; ?>
<?php unset($__attributesOriginald3421edf8aa16853fbe25459ab4dc93d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald3421edf8aa16853fbe25459ab4dc93d)): ?>
<?php $component = $__componentOriginald3421edf8aa16853fbe25459ab4dc93d; ?>
<?php unset($__componentOriginald3421edf8aa16853fbe25459ab4dc93d); ?>
<?php endif; ?>
                </a>
            </div>
        </header>

        <main class="ic-konten" id="konten">
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>

    <?php if (isset($component)) { $__componentOriginal58e1ad5de4b379c6c7c38e6d977ad765 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal58e1ad5de4b379c6c7c38e6d977ad765 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.dock','data' => ['member' => $member]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['member' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($member)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal58e1ad5de4b379c6c7c38e6d977ad765)): ?>
<?php $attributes = $__attributesOriginal58e1ad5de4b379c6c7c38e6d977ad765; ?>
<?php unset($__attributesOriginal58e1ad5de4b379c6c7c38e6d977ad765); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal58e1ad5de4b379c6c7c38e6d977ad765)): ?>
<?php $component = $__componentOriginal58e1ad5de4b379c6c7c38e6d977ad765; ?>
<?php unset($__componentOriginal58e1ad5de4b379c6c7c38e6d977ad765); ?>
<?php endif; ?>

    <?php echo $__env->yieldPushContent('modal'); ?>
</body>

</html>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/template/innerCircleLayout.blade.php ENDPATH**/ ?>