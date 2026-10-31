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
<div class="ic-kartu-anggota">
    <div>
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
        <p class="ic-sub">KARTU KEANGGOTAAN DIGITAL</p>
    </div>
    <div>
        <p class="ic-kartu-anggota__nama"><?php echo e($member->nama); ?></p>
        <small>ID ANGGOTA: <?php echo e($member->kode_member); ?></small>
    </div>
</div>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/kartu-anggota.blade.php ENDPATH**/ ?>