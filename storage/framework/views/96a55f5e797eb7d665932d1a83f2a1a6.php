<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['member']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['member']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $menu = [
        ['route' => 'ic.beranda', 'label' => 'Beranda', 'ikon' => 'bi-house-door', 'aktif' => 'bi-house-door-fill'],
        ['route' => 'ic.hadiah', 'label' => 'Hadiah', 'ikon' => 'bi-gift', 'aktif' => 'bi-gift-fill'],
        ['route' => 'ic.checkin', 'label' => 'Check-in', 'ikon' => 'bi-qr-code-scan', 'aktif' => 'bi-qr-code-scan'],
        ['route' => 'ic.komunitas', 'label' => 'Komunitas', 'ikon' => 'bi-heart', 'aktif' => 'bi-heart-fill'],
    ];
    $profilAktif = request()->routeIs('ic.profil');
?>

<nav class="ic-dock" aria-label="Navigasi Inner Circle">
    <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $aktif = request()->routeIs($m['route']); ?>
        <a href="<?php echo e(route($m['route'])); ?>" class="ic-dock__item <?php echo e($aktif ? 'is-aktif' : ''); ?>"
            <?php if($aktif): ?> aria-current="page" <?php endif; ?>>
            <i class="bi <?php echo e($aktif ? $m['aktif'] : $m['ikon']); ?>" aria-hidden="true"></i>
            <span><?php echo e($m['label']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(route('ic.profil')); ?>" class="ic-dock__item <?php echo e($profilAktif ? 'is-aktif' : ''); ?>"
        <?php if($profilAktif): ?> aria-current="page" <?php endif; ?>>
        <?php if (isset($component)) { $__componentOriginald3421edf8aa16853fbe25459ab4dc93d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald3421edf8aa16853fbe25459ab4dc93d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.avatar','data' => ['member' => $member,'kelas' => 'ic-avatar--dock']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['member' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($member),'kelas' => 'ic-avatar--dock']); ?>
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
        <span>Profil</span>
    </a>
</nav>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/dock.blade.php ENDPATH**/ ?>