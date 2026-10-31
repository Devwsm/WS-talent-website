<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['pengumuman']));

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

foreach (array_filter((['pengumuman']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<article class="ic-pengumuman">
    <div class="ic-pengumuman__ikon ic-warna-<?php echo e($pengumuman->warna); ?>">
        <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $pengumuman->ikon,'kelas' => 'ic-ikon--kecil']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pengumuman->ikon),'kelas' => 'ic-ikon--kecil']); ?>
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
    </div>
    <div>
        <p class="ic-pengumuman__meta">
            <?php echo e($pengumuman->tag ? $pengumuman->tag . ' · ' : ''); ?><?php echo e($pengumuman->terbit_pada->locale('id')->translatedFormat('j M Y')); ?>

        </p>
        <h3 class="ic-pengumuman__judul"><?php echo e($pengumuman->judul); ?></h3>
        <p class="ic-pengumuman__isi"><?php echo e($pengumuman->isi); ?></p>
        <a class="ic-tombol ic-tombol--kecil ic-tombol--krem"
            href="<?php echo e($pengumuman->cta_url ?? route($pengumuman->cta_tujuan)); ?>"><?php echo e($pengumuman->cta_label); ?>

            <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
</article>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/item-pengumuman.blade.php ENDPATH**/ ?>