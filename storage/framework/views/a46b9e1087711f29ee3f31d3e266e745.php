
<?php $__env->startSection('content'); ?>
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        <?php echo $__env->make('components/dashboard/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            <?php echo $__env->make('components.dashboard.inner-circle.tabs', [
                'aktif' => 'ringkasan',
                'judul' => 'Ringkasan',
                'deskripsi' =>
                    'Gambaran singkat komunitas Inner Circle: anggota, kehadiran, klaim hadiah, dan acara.',
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php
                $kartu = [
                    ['ikon' => 'bi-people-fill', 'angka' => $ringkasan->anggota_total, 'label' => 'Anggota terdaftar'],
                    [
                        'ikon' => 'bi-patch-check-fill',
                        'angka' => $ringkasan->kehadiran_terverifikasi,
                        'label' => 'Kehadiran terverifikasi',
                    ],
                    ['ikon' => 'bi-inbox-fill', 'angka' => $ringkasan->klaim_menunggu, 'label' => 'Klaim menunggu'],
                    ['ikon' => 'bi-broadcast', 'angka' => $ringkasan->acara_aktif, 'label' => 'Acara aktif hari ini'],
                ];
            ?>

            
            <ul class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <?php $__currentLoopData = $kartu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="rounded-3xl border border-white/10 bg-white/3 p-5 md:p-6 min-w-0">
                        <i class="bi <?php echo e($k['ikon']); ?> text-white/40 text-xl" aria-hidden="true"></i>
                        <p class="text-3xl md:text-4xl font-bold mt-3"><?php echo e(number_format($k['angka'], 0, ',', '.')); ?></p>
                        <p class="text-xs text-white/50 mt-1"><?php echo e($k['label']); ?></p>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                
                <?php $__env->startComponent('components.dashboard.card.card', [
                    'title' => 'Klaim menunggu (' . $ringkasan->klaim_menunggu . ')',
                    'href' => route('ic.admin.klaim'),
                    'icon' => 'bi-inbox-fill',
                ]); ?>
                    <?php if(empty($klaimMenunggu)): ?>
                        <?php echo $__env->make('components/dashboard/card/empty-state', [
                            'message' => 'Tidak ada klaim yang menunggu.',
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php else: ?>
                        <?php $statusKlaim = config('inner_circle.status_klaim'); ?>
                        <ul class="flex flex-col gap-3">
                            <?php $__currentLoopData = $klaimMenunggu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/2 p-3 min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-sm truncate"><?php echo e($k->nama_member); ?>

                                            <span class="text-white/40 font-normal">· <?php echo e($k->kode_member); ?></span>
                                        </p>
                                        <p class="text-xs text-white/50 truncate"><?php echo e($k->target_stempel); ?> stempel ·
                                            <?php echo e($k->judul); ?> ·
                                            <?php echo e($k->diajukan_pada->locale('id')->translatedFormat('j M Y')); ?></p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full border border-amber-300/30 bg-amber-300/10 px-2.5 py-1 text-[11px] font-semibold text-amber-200"><?php echo e($statusKlaim[$k->status]['label']); ?></span>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                <?php echo $__env->renderComponent(); ?>

                
                <?php $__env->startComponent('components.dashboard.card.card', [
                    'title' => 'Acara berikutnya',
                    'href' => route('ic.admin.acara'),
                    'icon' => 'bi-calendar-event-fill',
                ]); ?>
                    <?php if($ringkasan->acara_berikutnya): ?>
                        <?php $e = $ringkasan->acara_berikutnya; ?>
                        <div class="ic-app rounded-3xl p-4" inert>
                            <?php if (isset($component)) { $__componentOriginald96e93f0fbc95305b6c2d42d983aa137 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald96e93f0fbc95305b6c2d42d983aa137 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.kartu-event','data' => ['event' => $e,'href' => '#']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.kartu-event'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['event' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($e),'href' => '#']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald96e93f0fbc95305b6c2d42d983aa137)): ?>
<?php $attributes = $__attributesOriginald96e93f0fbc95305b6c2d42d983aa137; ?>
<?php unset($__attributesOriginald96e93f0fbc95305b6c2d42d983aa137); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald96e93f0fbc95305b6c2d42d983aa137)): ?>
<?php $component = $__componentOriginald96e93f0fbc95305b6c2d42d983aa137; ?>
<?php unset($__componentOriginald96e93f0fbc95305b6c2d42d983aa137); ?>
<?php endif; ?>
                        </div>
                        <p class="text-xs text-white/40 mt-3">
                            Jendela check-in:
                            <?php echo e($e->jendela_buka->locale('id')->translatedFormat('D, j M')); ?> pukul
                            <?php echo e($e->jendela_buka->format('H.i')); ?>

                            sampai
                            <?php echo e($e->jendela_tutup->locale('id')->translatedFormat('D, j M')); ?> pukul
                            <?php echo e($e->jendela_tutup->format('H.i')); ?> WIB.
                        </p>
                    <?php else: ?>
                        <?php echo $__env->make('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada acara yang akan datang.',
                            'cta_href' => route('ic.admin.acara'),
                            'cta_label' => 'Kelola acara',
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endif; ?>
                <?php echo $__env->renderComponent(); ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard-pages/inner-circle/ringkasan.blade.php ENDPATH**/ ?>