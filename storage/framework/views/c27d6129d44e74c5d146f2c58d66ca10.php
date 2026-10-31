<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['kenangan']));

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

foreach (array_filter((['kenangan']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $kategori = config('inner_circle.kategori_kenangan.' . $kenangan->kategori);
?>
<article class="ic-kenangan">
    <div class="ic-kenangan__sampul ic-warna-<?php echo e($kenangan->warna); ?>">
        <?php if($kenangan->foto): ?>
            <img src="<?php echo e(\Illuminate\Support\Facades\Storage::url($kenangan->foto)); ?>"
                alt="Foto kenangan dari <?php echo e($kenangan->nama_member); ?>" loading="lazy">
        <?php else: ?>
            <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $kenangan->ikon,'kelas' => 'ic-ikon--sampul']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kenangan->ikon),'kelas' => 'ic-ikon--sampul']); ?>
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
            <span class="ic-kenangan__bintang" aria-hidden="true">✳</span>
        <?php endif; ?>
    </div>
    <div class="ic-kenangan__isi">
        <div class="ic-kenangan__atas">
            <?php if (isset($component)) { $__componentOriginald3421edf8aa16853fbe25459ab4dc93d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald3421edf8aa16853fbe25459ab4dc93d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.avatar','data' => ['member' => $kenangan->member,'kelas' => 'ic-avatar--kecil']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['member' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kenangan->member),'kelas' => 'ic-avatar--kecil']); ?>
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
            <div>
                <b><?php echo e($kenangan->nama_member); ?></b>
                <small><?php echo e($kenangan->tempat ? $kenangan->tempat . ' · ' : ''); ?><?php echo e($kenangan->tanggal->locale('id')->translatedFormat('j M Y')); ?></small>
            </div>
        </div>
        <p class="ic-kenangan__caption"><?php echo e($kenangan->caption); ?></p>
        <div class="ic-kenangan__kaki">
            <span class="ic-kenangan__tag"><?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $kategori['ikon'],'kelas' => 'ic-ikon--mini']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kategori['ikon']),'kelas' => 'ic-ikon--mini']); ?>
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
                <?php echo e($kategori['label']); ?></span>
            <span class="ic-kenangan__aksi">
                <button type="button" class="ic-suka <?php echo e($kenangan->disukai_saya ? 'is-suka' : ''); ?>"
                    aria-pressed="<?php echo e($kenangan->disukai_saya ? 'true' : 'false'); ?>"
                    data-ic-suka="<?php echo e($kenangan->id_memory); ?>"
                    data-ic-suka-url="<?php echo e(route('ic.kenangan.suka', $kenangan->id_memory)); ?>">
                    <i class="bi <?php echo e($kenangan->disukai_saya ? 'bi-heart-fill' : 'bi-heart'); ?>" aria-hidden="true"></i>
                    <span><?php echo e($kenangan->jumlah_like); ?></span>
                </button>
                <?php if($kenangan->milik_saya): ?>
                    
                    <form method="POST" action="<?php echo e(route('ic.kenangan.hapus', $kenangan->id_memory)); ?>"
                        data-ic-konfirmasi data-ic-sekali data-ic-konfirmasi-judul="Hapus kenangan ini?"
                        data-ic-konfirmasi-tombol="Ya, hapus"
                        data-judul="Foto dan ceritanya akan dihapus dari galeri dan tidak bisa dikembalikan.">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="ic-hapus">Hapus</button>
                    </form>
                <?php endif; ?>
            </span>
        </div>
    </div>
</article>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/kartu-kenangan.blade.php ENDPATH**/ ?>