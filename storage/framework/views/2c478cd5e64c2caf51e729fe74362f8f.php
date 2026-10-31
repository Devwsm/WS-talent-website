

<?php $__env->startSection('title', 'Beranda'); ?>

<?php $__env->startSection('content'); ?>
    <div class="ic-beranda">
        <section class="ic-hero" aria-labelledby="judul-beranda">
            <p class="ic-eyebrow">Musik seru. Teman baru.</p>
            <h1 class="ic-judul" id="judul-beranda">Beranda<span>.</span></h1>
            <h2 class="ic-hero__salam">Hai, <?php echo e(\Illuminate\Support\Str::of($member->nama)->before(' ')); ?>!</h2>
            <div class="ic-hero__tangan" aria-hidden="true">
                <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => 'ok-hand','kelas' => 'ic-ikon--tangan']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => 'ok-hand','kelas' => 'ic-ikon--tangan']); ?>
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
                <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => 'ok-hand','kelas' => 'ic-ikon--tangan']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => 'ok-hand','kelas' => 'ic-ikon--tangan']); ?>
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
            <p class="ic-hero__teks">Terima kasih sudah menjadi bagian dari keluarga WS Inner Circle.</p>
            <a class="ic-hero__stiker" href="<?php echo e(route('ic.komunitas')); ?>">Setiap momen berarti</a>
            <span class="ic-hero__bintang" aria-hidden="true">✳</span>
            <span class="ic-hero__cakram" aria-hidden="true"><span>WS</span></span>
        </section>

        <div class="ic-beranda__sisi">
            <?php if (isset($component)) { $__componentOriginal22acec488312d140e283db886d95f370 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal22acec488312d140e283db886d95f370 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.kartu-anggota','data' => ['member' => $member]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.kartu-anggota'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['member' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($member)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal22acec488312d140e283db886d95f370)): ?>
<?php $attributes = $__attributesOriginal22acec488312d140e283db886d95f370; ?>
<?php unset($__attributesOriginal22acec488312d140e283db886d95f370); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal22acec488312d140e283db886d95f370)): ?>
<?php $component = $__componentOriginal22acec488312d140e283db886d95f370; ?>
<?php unset($__componentOriginal22acec488312d140e283db886d95f370); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginala3e01992bbfbcaea7bed609199f62c24 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala3e01992bbfbcaea7bed609199f62c24 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.bar-stempel','data' => ['member' => $member,'berikutnya' => $berikutnya]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.bar-stempel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['member' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($member),'berikutnya' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($berikutnya)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala3e01992bbfbcaea7bed609199f62c24)): ?>
<?php $attributes = $__attributesOriginala3e01992bbfbcaea7bed609199f62c24; ?>
<?php unset($__attributesOriginala3e01992bbfbcaea7bed609199f62c24); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala3e01992bbfbcaea7bed609199f62c24)): ?>
<?php $component = $__componentOriginala3e01992bbfbcaea7bed609199f62c24; ?>
<?php unset($__componentOriginala3e01992bbfbcaea7bed609199f62c24); ?>
<?php endif; ?>
        </div>
    </div>

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">Aktivitas terbaru ✦</h2>
        <a class="ic-tautan" href="<?php echo e(route('ic.profil')); ?>#riwayat">Lihat semua →</a>
    </div>
    <div class="ic-panel ic-daftar ic-daftar--rapat">
        <?php $__empty_1 = true; $__currentLoopData = $aktivitas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php if (isset($component)) { $__componentOriginal8f8d093d6c36a8943ef3d6da21e2e865 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8f8d093d6c36a8943ef3d6da21e2e865 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.baris-aktivitas','data' => ['aktivitas' => $a]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.baris-aktivitas'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['aktivitas' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($a)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8f8d093d6c36a8943ef3d6da21e2e865)): ?>
<?php $attributes = $__attributesOriginal8f8d093d6c36a8943ef3d6da21e2e865; ?>
<?php unset($__attributesOriginal8f8d093d6c36a8943ef3d6da21e2e865); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8f8d093d6c36a8943ef3d6da21e2e865)): ?>
<?php $component = $__componentOriginal8f8d093d6c36a8943ef3d6da21e2e865; ?>
<?php unset($__componentOriginal8f8d093d6c36a8943ef3d6da21e2e865); ?>
<?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="ic-bantuan">Belum ada aktivitas. Datang ke acara dan check-in untuk mendapat stempel pertamamu.</p>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.innerCircleLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/beranda.blade.php ENDPATH**/ ?>