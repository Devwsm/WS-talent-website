<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['event', 'dipilih' => false, 'href' => null, 'info' => false]));

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

foreach (array_filter((['event', 'dipilih' => false, 'href' => null, 'info' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $tipe = config('inner_circle.tipe_event.' . $event->tipe);
    $statusWaktu =
        ['lewat' => 'Sudah lewat', 'hari_ini' => 'Hari ini', 'akan_datang' => 'Akan datang'][$event->status_waktu] ??
        '';
    $catatan =
        $event->tipe === 'private' ? 'Tidak untuk umum' : (!$event->venue ? 'Venue belum diumumkan' : $statusWaktu);
    $bisaDipilih = $event->bisa_checkin && $href;
    $tag = $bisaDipilih ? 'a' : 'div';
?>

<<?php echo e($tag); ?>

    <?php if($bisaDipilih): ?> href="<?php echo e($href); ?>" <?php elseif(!$info): ?> aria-disabled="true" <?php endif; ?>
    class="ic-event ic-warna-<?php echo e($tipe['warna']); ?> <?php echo e($dipilih ? 'is-dipilih' : ''); ?> <?php echo e($bisaDipilih || $info ? '' : 'is-nonaktif'); ?>"
    <?php if($dipilih): ?> aria-current="true" <?php endif; ?>>
    <span class="ic-event__seni"><?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $tipe['ikon'],'kelas' => 'ic-ikon--event']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tipe['ikon']),'kelas' => 'ic-ikon--event']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $attributes = $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $component = $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?></span>
    <span class="ic-event__isi">
        <span class="ic-event__tanggal"><?php echo e($event->tanggal->locale('id')->translatedFormat('D, j M Y')); ?></span>
        <b class="ic-event__venue"><?php echo e($event->venue ?? 'Venue belum diumumkan'); ?></b>
        <span class="ic-event__kota"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
            <?php echo e($event->kota ?? 'Kota belum diumumkan'); ?></span>
        <span class="ic-event__lencana"><?php echo e($tipe['label']); ?> · <?php echo e($catatan); ?></span>
        <?php if($event->sudah_checkin): ?>
            <span class="ic-event__lencana ic-event__lencana--ok"><i class="bi bi-check-circle-fill"
                    aria-hidden="true"></i> Sudah check-in</span>
        <?php endif; ?>
    </span>
    <?php if (! ($info)): ?>
        <span class="ic-event__panah" aria-hidden="true"><i
                class="bi <?php echo e($bisaDipilih ? 'bi-chevron-right' : 'bi-dash'); ?>"></i></span>
    <?php endif; ?>
    </<?php echo e($tag); ?>>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/kartu-event.blade.php ENDPATH**/ ?>