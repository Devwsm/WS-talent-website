
<?php $__env->startSection('content'); ?>
    <?php
        $kategoriKenangan = config('inner_circle.kategori_kenangan');
        $chipAktif = 'bg-white text-black border-white';
        $chipBiasa = 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white';
    ?>

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        <?php echo $__env->make('components/dashboard/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            <?php echo $__env->make('components.dashboard.inner-circle.tabs', [
                'aktif' => 'kenangan',
                'judul' => 'Kenangan',
                'deskripsi' =>
                    'Moderasi galeri kenangan anggota. Kenangan yang disembunyikan tidak tampil di galeri.',
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->make('components/errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('components/success', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            
            <div class="flex flex-col gap-3 rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5">
                <nav aria-label="Filter kategori kenangan" class="flex flex-wrap gap-2">
                    <a href="<?php echo e(route('ic.admin.kenangan', array_filter(['status' => $status !== 'all' ? $status : null]))); ?>"
                        <?php if($kategori === 'all'): ?> aria-current="true" <?php endif; ?>
                        class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold transition-colors <?php echo e($kategori === 'all' ? $chipAktif : $chipBiasa); ?>">
                        Semua kategori
                    </a>
                    <?php $__currentLoopData = $kategoriKenangan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('ic.admin.kenangan', array_filter(['kategori' => $kunci, 'status' => $status !== 'all' ? $status : null]))); ?>"
                            <?php if($kategori === $kunci): ?> aria-current="true" <?php endif; ?>
                            class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold transition-colors <?php echo e($kategori === $kunci ? $chipAktif : $chipBiasa); ?>">
                            <?php echo e($k['label']); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </nav>

                <nav aria-label="Filter status kenangan" class="flex flex-wrap gap-2">
                    <?php $__currentLoopData = ['all' => 'Semua status', 'tayang' => 'Tayang', 'disembunyikan' => 'Disembunyikan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('ic.admin.kenangan', array_filter(['kategori' => $kategori !== 'all' ? $kategori : null, 'status' => $kunci !== 'all' ? $kunci : null]))); ?>"
                            <?php if($status === $kunci): ?> aria-current="true" <?php endif; ?>
                            class="inline-flex items-center h-11 px-4 rounded-full border text-sm font-semibold transition-colors <?php echo e($status === $kunci ? $chipAktif : $chipBiasa); ?>">
                            <?php echo e($label); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </nav>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-images text-white/50" aria-hidden="true"></i>
                    Kenangan (<?php echo e($kenangan->total()); ?> dari <?php echo e($jumlahSemua); ?>)
                </h2>
                <p class="text-xs text-white/50">
                    Disembunyikan: <b class="text-white"><?php echo e($jumlahDisembunyikan); ?></b>
                </p>
            </div>

            <?php if($kenangan->isEmpty()): ?>
                <?php echo $__env->make('components/dashboard/card/empty-state', [
                    'message' => 'Tidak ada kenangan yang cocok dengan filter ini.',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
                <ul class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php $__currentLoopData = $kenangan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex flex-col rounded-3xl border border-white/10 bg-white/3 overflow-hidden min-w-0">

                            
                            <div class="ic-app p-3 transition-opacity <?php echo e($k->disembunyikan ? 'opacity-40' : ''); ?>" inert>
                                <?php if (isset($component)) { $__componentOriginal690eeacf3d9cb68a821b1a7b200fae97 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal690eeacf3d9cb68a821b1a7b200fae97 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.kartu-kenangan','data' => ['kenangan' => $k]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.kartu-kenangan'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['kenangan' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($k)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal690eeacf3d9cb68a821b1a7b200fae97)): ?>
<?php $attributes = $__attributesOriginal690eeacf3d9cb68a821b1a7b200fae97; ?>
<?php unset($__attributesOriginal690eeacf3d9cb68a821b1a7b200fae97); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal690eeacf3d9cb68a821b1a7b200fae97)): ?>
<?php $component = $__componentOriginal690eeacf3d9cb68a821b1a7b200fae97; ?>
<?php unset($__componentOriginal690eeacf3d9cb68a821b1a7b200fae97); ?>
<?php endif; ?>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 px-4 py-3 border-t border-white/10">
                                <span
                                    class="rounded-full border px-3 py-1.5 text-xs font-semibold <?php echo e($k->disembunyikan ? 'border-red-400/30 bg-red-400/10 text-red-300' : 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200'); ?>"><?php echo e($k->disembunyikan ? 'Disembunyikan' : 'Tayang'); ?></span>
                                
                                <span class="min-w-0 truncate text-xs text-white/50"><?php echo e($k->member->nama); ?> ·
                                    <?php echo e($k->member->kode_member); ?></span>
                                <form method="POST" action="<?php echo e(route('ic.admin.kenangan.moderasi', $k->id_memory)); ?>"
                                    class="ml-auto"
                                    <?php if(!$k->disembunyikan): ?> data-konfirmasi-sembunyi
                                        data-ringkas="<?php echo e($k->member->nama); ?> · <?php echo e($k->tempat ?: 'tanpa tempat'); ?>" <?php endif; ?>>
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="aksi"
                                        value="<?php echo e($k->disembunyikan ? 'tayangkan' : 'sembunyikan'); ?>">
                                    <button type="submit"
                                        class="h-11 px-4 rounded-full border border-white/15 text-xs font-semibold text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                                        <?php echo e($k->disembunyikan ? 'Tayangkan' : 'Sembunyikan'); ?>

                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>

                <?php echo $__env->make('components.dashboard.pagination', ['paginator' => $kenangan], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
        <script>
            // Menyembunyikan butuh konfirmasi; menayangkan lagi langsung terkirim. Tanpa JS form tetap terkirim.
            document.addEventListener('submit', async (e) => {
                const form = e.target.closest('form[data-konfirmasi-sembunyi]');
                if (!form || form.dataset.sudahKonfirmasi === '1') return;
                e.preventDefault();

                const hasil = await window.Swal.fire({
                    title: 'Sembunyikan kenangan ini?',
                    text: `${form.dataset.ringkas}. Kenangan tidak akan tampil di galeri anggota.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, sembunyikan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                });
                if (!hasil.isConfirmed) return;

                form.dataset.sudahKonfirmasi = '1';
                form.requestSubmit();
            });
        </script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard-pages/inner-circle/kenangan.blade.php ENDPATH**/ ?>