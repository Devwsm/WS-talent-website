<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['id', 'judul', 'eyebrow' => null, 'terbuka' => false]));

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

foreach (array_filter((['id', 'judul', 'eyebrow' => null, 'terbuka' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="ic-modal" id="<?php echo e($id); ?>" hidden <?php if($terbuka): ?> data-ic-buka-awal <?php endif; ?>>
    <div class="ic-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo e($id); ?>-judul"
        tabindex="-1">
        <div class="ic-modal__kepala">
            <div>
                <?php if($eyebrow): ?>
                    <p class="ic-eyebrow"><?php echo e($eyebrow); ?></p>
                <?php endif; ?>
                <h2 class="ic-modal__judul" id="<?php echo e($id); ?>-judul"><?php echo e($judul); ?></h2>
            </div>
            <button type="button" class="ic-modal__tutup" data-ic-modal-tutup aria-label="Tutup">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/modal.blade.php ENDPATH**/ ?>