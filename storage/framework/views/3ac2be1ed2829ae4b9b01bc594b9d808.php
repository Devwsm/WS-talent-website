<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['nama', 'kelas' => '']));

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

foreach (array_filter((['nama', 'kelas' => '']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $berkas = 'aset/inner-circle/ikon/' . $nama . '.webp';
?>
<?php if(file_exists(public_path($berkas))): ?>
    <img src="<?php echo e(asset($berkas)); ?>" alt="" width="256" height="256" loading="lazy" decoding="async"
        class="ic-ikon <?php echo e($kelas); ?>">
<?php else: ?>
    <span class="ic-ikon ic-ikon--emoji <?php echo e($kelas); ?>"
        aria-hidden="true"><?php echo e(config('inner_circle.ikon.' . $nama, '✦')); ?></span>
<?php endif; ?>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/ikon.blade.php ENDPATH**/ ?>