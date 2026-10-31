

<?php $__env->startSection('title', 'Hadiah'); ?>

<?php
    $semua = collect($rewards);
    $statusKlaim = config('inner_circle.status_klaim');
    // Hadiah hanya bisa diajukan setelah email terverifikasi (ClaimService menolaknya di server).
    $terverifikasi = $member->email_verified_at !== null;
?>

<?php $__env->startSection('content'); ?>
    <p class="ic-eyebrow">Kumpulkan • Buka • Rasakan</p>
    <h1 class="ic-judul">Hadiah<span>.</span></h1>
    <p class="ic-deskripsi">Setiap stempel membuatmu lebih dekat dengan Whisnu Santika. Buka merchandise eksklusif,
        pengalaman personal, dan akses spesial.</p>

    <?php if (! ($terverifikasi)): ?>
        <section class="ic-panel ic-jarak-atas" aria-labelledby="verifikasi-judul">
            <h2 class="ic-panel__judul" id="verifikasi-judul">✉ Verifikasi emailmu dulu</h2>
            <p class="ic-bantuan">Hadiah baru bisa diajukan setelah emailmu terverifikasi. Kami mengirim tautan ke
                <b><?php echo e($member->email); ?></b>; buka tautannya saat kamu sudah masuk. Tidak ada email? Cek folder spam, atau
                kirim ulang.
            </p>
            <form method="POST" action="<?php echo e(route('ic.verifikasi.kirim')); ?>" data-ic-sekali>
                <?php echo csrf_field(); ?>
                <button type="submit" class="ic-tombol ic-tombol--kecil">Kirim ulang email verifikasi</button>
            </form>
        </section>
    <?php endif; ?>

    <div class="ic-grid ic-grid--2 ic-jarak-atas">
        <?php $__currentLoopData = $rewards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reward): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginale85949c1824580e84c1e582e710cf398 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale85949c1824580e84c1e582e710cf398 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ubin-hadiah','data' => ['reward' => $reward,'lebar' => $loop->last,'href' => '#klaim']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ubin-hadiah'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['reward' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reward),'lebar' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($loop->last),'href' => '#klaim']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale85949c1824580e84c1e582e710cf398)): ?>
<?php $attributes = $__attributesOriginale85949c1824580e84c1e582e710cf398; ?>
<?php unset($__attributesOriginale85949c1824580e84c1e582e710cf398); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale85949c1824580e84c1e582e710cf398)): ?>
<?php $component = $__componentOriginale85949c1824580e84c1e582e710cf398; ?>
<?php unset($__componentOriginale85949c1824580e84c1e582e710cf398); ?>
<?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="ic-bagian-kepala" id="klaim">
        <h2 class="ic-judul-bagian">Klaim hadiahmu ↗</h2>
        <span class="ic-pil"><?php echo e($member->stempel_total); ?> stempel terkumpul</span>
    </div>
    <div class="ic-panel ic-klaim-daftar">
        <?php $__currentLoopData = $rewards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reward): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="ic-klaim">
                <span class="ic-klaim__bulat ic-warna-<?php echo e($reward->warna); ?>">
                    <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $reward->ikon,'kelas' => 'ic-ikon--kecil']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reward->ikon),'kelas' => 'ic-ikon--kecil']); ?>
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
                </span>
                <div>
                    <h3><?php echo e($reward->target_stempel); ?> stempel · <?php echo e($reward->judul); ?></h3>
                    <p class="ic-bantuan"><?php echo e($reward->deskripsi); ?></p>
                    <?php if($reward->catatan_admin): ?>
                        <p class="ic-bantuan"><b>Catatan WSM:</b> <?php echo e($reward->catatan_admin); ?></p>
                    <?php endif; ?>
                </div>

                <?php if($reward->status_member === 'terbuka' && !$terverifikasi): ?>
                    <span class="ic-pil ic-pil--redup"><i class="bi bi-envelope-fill" aria-hidden="true"></i> Verifikasi
                        email dulu</span>
                <?php elseif($reward->status_member === 'terbuka'): ?>
                    <?php if($reward->opsi): ?>
                        
                        <button type="button" class="ic-tombol ic-tombol--kecil"
                            data-ic-modal-buka="modal-opsi-<?php echo e($reward->target_stempel); ?>">Klaim <i class="bi bi-arrow-right"
                                aria-hidden="true"></i></button>
                    <?php else: ?>
                        
                        <form method="POST" action="<?php echo e(route('ic.hadiah.klaim', $reward->target_stempel)); ?>"
                            data-ic-konfirmasi data-ic-sekali
                            data-judul="<?php echo e($reward->target_stempel); ?> stempel · <?php echo e($reward->judul); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="ic-tombol ic-tombol--kecil">Klaim <i class="bi bi-arrow-right"
                                    aria-hidden="true"></i></button>
                        </form>
                    <?php endif; ?>
                <?php elseif($reward->status_member === 'terkunci'): ?>
                    <span class="ic-pil ic-pil--redup"><i class="bi bi-lock-fill" aria-hidden="true"></i> Terkunci</span>
                <?php else: ?>
                    <?php $status = $statusKlaim[$reward->status_member]; ?>
                    <span class="ic-pil ic-pil--<?php echo e($status['warna']); ?>"><?php echo e($status['label']); ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <section class="ic-panel ic-jarak-atas" aria-labelledby="cara-kerja">
        <h2 class="ic-panel__judul" id="cara-kerja">✦ Cara kerja hadiah</h2>
        <p class="ic-bantuan">Stempel bersifat kumulatif dan tidak berkurang setelah klaim. Setiap tahapan hanya dapat
            diklaim satu kali. Benefit pengalaman bergantung pada kapasitas, jadwal artis, dan izin promotor atau
            venue.</p>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('modal'); ?>
    <?php $__currentLoopData = $semua->whereNotNull('opsi'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reward): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if (isset($component)) { $__componentOriginal4299c0fc92bf0a34fa463efd75f94121 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4299c0fc92bf0a34fa463efd75f94121 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.modal','data' => ['id' => 'modal-opsi-' . $reward->target_stempel,'judul' => $reward->judul,'eyebrow' => $reward->target_stempel . ' stempel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('modal-opsi-' . $reward->target_stempel),'judul' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reward->judul),'eyebrow' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reward->target_stempel . ' stempel')]); ?>
            <form method="POST" action="<?php echo e(route('ic.hadiah.klaim', $reward->target_stempel)); ?>" data-ic-sekali>
                <?php echo csrf_field(); ?>
                <fieldset class="ic-pilihan-daftar">
                    <legend class="ic-bantuan">Pilih salah satu hadiah:</legend>
                    <?php $__currentLoopData = $reward->opsi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opsi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="ic-pilihan">
                            <input type="radio" name="opsi" value="<?php echo e($opsi); ?>" required>
                            <span class="ic-pilihan__isi">
                                <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $loop->first ? 'camera' : 'love-letter','kelas' => 'ic-ikon--event']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($loop->first ? 'camera' : 'love-letter'),'kelas' => 'ic-ikon--event']); ?>
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
                                <?php echo e($opsi); ?>

                            </span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </fieldset>
                <div class="ic-aksi-form">
                    <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Batal</button>
                    <button type="submit" class="ic-tombol">Ajukan hadiah <i class="bi bi-arrow-right"
                            aria-hidden="true"></i></button>
                </div>
            </form>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4299c0fc92bf0a34fa463efd75f94121)): ?>
<?php $attributes = $__attributesOriginal4299c0fc92bf0a34fa463efd75f94121; ?>
<?php unset($__attributesOriginal4299c0fc92bf0a34fa463efd75f94121); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4299c0fc92bf0a34fa463efd75f94121)): ?>
<?php $component = $__componentOriginal4299c0fc92bf0a34fa463efd75f94121; ?>
<?php unset($__componentOriginal4299c0fc92bf0a34fa463efd75f94121); ?>
<?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('template.innerCircleLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/hadiah.blade.php ENDPATH**/ ?>