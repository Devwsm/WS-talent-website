<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['reward', 'lebar' => false, 'href' => '#klaim', 'tampilTag' => true]));

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

foreach (array_filter((['reward', 'lebar' => false, 'href' => '#klaim', 'tampilTag' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<a href="<?php echo e($href); ?>" class="ic-ubin ic-warna-<?php echo e($reward->warna); ?> <?php echo e($lebar ? 'ic-ubin--lebar' : ''); ?>">
    <?php if($tampilTag): ?>
        <span class="ic-ubin__tag"><?php echo e($reward->status_member === 'terkunci' ? 'TERKUNCI' : 'TERBUKA'); ?></span>
    <?php endif; ?>
    <span class="ic-ubin__angka"><?php echo e($reward->target_stempel); ?><small>Stempel</small></span>
    <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $reward->ikon,'kelas' => 'ic-ubin__ikon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reward->ikon),'kelas' => 'ic-ubin__ikon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $attributes = $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $component = $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?>
    <span class="ic-ubin__judul"><?php echo e($reward->judul); ?></span>
    <span class="ic-ubin__deskripsi"><?php echo e($reward->deskripsi); ?></span>
    <span class="ic-ubin__panah" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
</a>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/ubin-hadiah.blade.php ENDPATH**/ ?>