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
<div class="ic-unggulan">
    <p class="ic-unggulan__label">● KABAR PILIHAN</p>
    <h3 class="ic-unggulan__judul"><?php echo e($pengumuman->judul); ?></h3>
    <p class="ic-unggulan__isi"><?php echo e($pengumuman->isi); ?></p>
    <a class="ic-tombol" href="<?php echo e($pengumuman->cta_url ?? route($pengumuman->cta_tujuan)); ?>"><?php echo e($pengumuman->cta_label); ?>

        <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    <span class="ic-unggulan__bintang" aria-hidden="true">✳</span>
</div>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/pengumuman-unggulan.blade.php ENDPATH**/ ?>