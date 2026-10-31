
<?php $__env->startSection('content'); ?>
    <?php
        $kategoriLabel = config('log_admin.kategori');
        $kelasKategori = [
            'akses' => 'border-sky-300/30 bg-sky-300/10 text-sky-200',
            'konten' => 'border-violet-300/30 bg-violet-300/10 text-violet-200',
            'inner-circle' => 'border-pink-300/30 bg-pink-300/10 text-pink-200',
        ];
        $filterError = ['terbuka' => 'Terbuka', 'selesai' => 'Selesai', 'semua' => 'Semua'];
    ?>

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        <?php echo $__env->make('components/dashboard/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-white/40 mb-1">Dashboard</p>
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">Log</h1>
                <p class="text-white/50 mt-1">Error yang terjadi di website, dan jejak aktivitas admin di dashboard.</p>
            </div>

            
            <nav aria-label="Bagian Log">
                <ul class="flex flex-wrap gap-2">
                    <?php $__currentLoopData = ['error' => ['Error', 'bi-bug-fill'], 'aktivitas' => ['Aktivitas admin', 'bi-clock-history']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => [$label, $ikon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $dipilih = $kunci === $tab; ?>
                        <li>
                            <a href="<?php echo e(route('log.index', ['tab' => $kunci])); ?>"
                                <?php if($dipilih): ?> aria-current="page" <?php endif; ?>
                                class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                                <?php echo e($dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white'); ?>">
                                <i class="bi <?php echo e($ikon); ?>" aria-hidden="true"></i>
                                <?php echo e($label); ?>

                                <?php if($kunci === 'error' && $jumlahError['terbuka'] > 0): ?>
                                    <span
                                        class="min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[11px] font-bold <?php echo e($dipilih ? 'bg-black text-white' : 'bg-red-950 text-white'); ?>"><?php echo e($jumlahError['terbuka']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </nav>

            <?php echo $__env->make('components/errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('components/success', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php if($tab === 'error'): ?>
                
                <?php if($emailPeringatan): ?>
                    <p class="text-xs text-white/40 flex items-start gap-2">
                        <i class="bi bi-envelope-check mt-0.5" aria-hidden="true"></i>
                        <span>Error baru dikabari lewat email ke <b class="text-white/60"><?php echo e($emailPeringatan); ?></b>
                            (satu email per jenis error per jam).</span>
                    </p>
                <?php else: ?>
                    <div
                        class="flex items-start gap-3 rounded-2xl border border-amber-300/20 bg-amber-300/5 px-4 py-3 text-sm text-amber-100/80">
                        <i class="bi bi-info-circle mt-0.5 shrink-0" aria-hidden="true"></i>
                        <p>Email peringatan belum aktif. Isi <code>LOG_ALERT_EMAIL</code> di <code>.env</code> server agar
                            error baru dikabari lewat email.</p>
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <nav aria-label="Saring error">
                        <ul class="flex flex-wrap gap-2">
                            <?php $__currentLoopData = $filterError; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $dipilih = $kunci === $filterStatus; ?>
                                <li>
                                    <a href="<?php echo e(route('log.index', ['tab' => 'error', 'status' => $kunci])); ?>"
                                        <?php if($dipilih): ?> aria-current="page" <?php endif; ?>
                                        class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                                        <?php echo e($dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white'); ?>">
                                        <?php echo e($label); ?>

                                        <span
                                            class="text-xs <?php echo e($dipilih ? 'text-black/60' : 'text-white/40'); ?>"><?php echo e($jumlahError[$kunci]); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </nav>

                    <?php if($jumlahError['selesai'] > 0): ?>
                        <form method="POST" action="<?php echo e(route('log.error.bersihkan')); ?>" data-swal-confirm
                            data-confirm-message="<?php echo e($jumlahError['selesai']); ?> catatan error yang sudah selesai akan dihapus permanen.">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit"
                                class="h-11 px-4 rounded-full border border-white/15 text-white/60 text-sm font-semibold hover:bg-white/10 hover:text-white transition-colors">
                                <i class="bi bi-trash3" aria-hidden="true"></i> Hapus yang selesai
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if($daftar->isEmpty()): ?>
                    <?php echo $__env->make('components/dashboard/card/empty-state', [
                        'message' =>
                            $filterStatus === 'terbuka'
                                ? 'Tidak ada error yang terbuka. 🎉'
                                : 'Belum ada catatan error.',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
                    <ul class="flex flex-col gap-3">
                        <?php $__currentLoopData = $daftar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li
                                class="rounded-3xl border p-4 md:p-5 <?php echo e($e->selesai ? 'border-white/10 bg-white/2' : 'border-red-400/30 bg-red-400/5'); ?>">
                                <div class="flex flex-col md:flex-row md:items-start gap-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <p class="font-semibold"><?php echo e(class_basename($e->kelas)); ?></p>
                                            <span
                                                class="rounded-full border border-white/15 px-2 py-0.5 text-[11px] font-semibold text-white/70"><?php echo e(number_format($e->jumlah, 0, ',', '.')); ?>x</span>
                                            <?php if($e->selesai): ?>
                                                <span
                                                    class="rounded-full border border-emerald-300/30 bg-emerald-300/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-200">Selesai</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-white/70 wrap-break-word">
                                            <?php echo e(\Illuminate\Support\Str::limit($e->pesan, 300)); ?></p>
                                        <p class="text-xs text-white/40 mt-1 break-all"><?php echo e($e->lokasi); ?></p>
                                        <p class="text-xs text-white/40 mt-0.5">Terakhir
                                            <?php echo e($e->terakhir_pada->locale('id')->translatedFormat('j M Y, H.i')); ?>

                                            · pertama
                                            <?php echo e($e->pertama_pada?->locale('id')->translatedFormat('j M Y') ?? '—'); ?></p>
                                    </div>

                                    <form method="POST" action="<?php echo e(route('log.error.ubah', $e->id_log_error)); ?>"
                                        class="shrink-0">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input type="hidden" name="aksi"
                                            value="<?php echo e($e->selesai ? 'buka' : 'selesai'); ?>">
                                        <button type="submit"
                                            class="h-11 px-5 rounded-full text-xs font-semibold transition-colors <?php echo e($e->selesai ? 'border border-white/15 text-white/70 hover:bg-white/10' : 'bg-white text-black hover:bg-white/80'); ?>">
                                            <?php echo e($e->selesai ? 'Buka lagi' : 'Tandai selesai'); ?>

                                        </button>
                                    </form>
                                </div>

                                <details class="mt-3 text-xs text-white/50">
                                    <summary class="cursor-pointer select-none py-2 hover:text-white/80">Detail</summary>
                                    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 pb-2">
                                        <dt class="text-white/40">Jenis</dt>
                                        <dd class="break-all"><?php echo e($e->kelas); ?></dd>
                                        <dt class="text-white/40">Alamat</dt>
                                        <dd class="break-all"><?php echo e($e->metode); ?> <?php echo e($e->url ?? '—'); ?></dd>
                                        <dt class="text-white/40">Pengguna</dt>
                                        <dd><?php echo e($e->pengguna ?? 'tamu'); ?></dd>
                                        <dt class="text-white/40">IP</dt>
                                        <dd><?php echo e($e->ip ?? '—'); ?></dd>
                                    </dl>
                                    <?php if($e->jejak): ?>
                                        <pre class="overflow-x-auto rounded-xl bg-black/40 p-3 text-[11px] leading-relaxed text-white/60"><?php echo e($e->jejak); ?></pre>
                                    <?php endif; ?>
                                    <p class="mt-2 text-white/30">Pesan lengkap dan jejak penuh ada di storage/logs di
                                        server.</p>
                                </details>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                    <?php echo $__env->make('components.dashboard.pagination', ['paginator' => $daftar], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="flex flex-wrap items-center gap-2">
                    <?php $__currentLoopData = ['semua' => 'Semua'] + $kategoriLabel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $dipilih = $kunci === $filterKategori; ?>
                        <a href="<?php echo e(route('log.index', array_filter(['tab' => 'aktivitas', 'kategori' => $kunci === 'semua' ? null : $kunci, 'status' => $hanyaGagal ? 'gagal' : null]))); ?>"
                            <?php if($dipilih): ?> aria-current="page" <?php endif; ?>
                            class="inline-flex items-center h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                            <?php echo e($dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white'); ?>"><?php echo e($label); ?></a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('log.index', array_filter(['tab' => 'aktivitas', 'kategori' => $filterKategori === 'semua' ? null : $filterKategori, 'status' => $hanyaGagal ? null : 'gagal']))); ?>"
                        <?php if($hanyaGagal): ?> aria-current="true" <?php endif; ?>
                        class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                        <?php echo e($hanyaGagal ? 'bg-red-400 text-black border-red-400' : 'border-red-400/30 text-red-300 hover:bg-red-400/10'); ?>">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Hanya yang gagal
                    </a>
                </div>

                <p class="text-xs text-white/40 -mt-3">Yang dicatat: siapa, kapan, dan aksi apa. Isi form, kata sandi, dan
                    file tidak disimpan. Catatan lebih dari <?php echo e(config('log_admin.simpan_aktivitas_hari')); ?> hari dihapus
                    otomatis.</p>

                <?php if($daftar->isEmpty()): ?>
                    <?php echo $__env->make('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada aktivitas yang cocok.',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
                    <ul class="flex flex-col gap-2">
                        <?php $__currentLoopData = $daftar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li
                                class="rounded-2xl border border-white/10 bg-white/3 px-4 py-3 flex flex-col md:flex-row md:items-center gap-1 md:gap-4">
                                <p class="text-xs text-white/40 md:w-40 shrink-0">
                                    <?php echo e($a->created_at->locale('id')->translatedFormat('j M Y, H.i')); ?></p>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold"><?php echo e($a->aksi); ?>

                                        <?php if($a->status === 'gagal'): ?>
                                            <span
                                                class="ml-1 rounded-full border border-red-400/30 bg-red-400/10 px-2 py-0.5 text-[11px] font-semibold text-red-300">Gagal</span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if($a->keterangan): ?>
                                        <p class="text-xs text-white/50 wrap-break-word"><?php echo e($a->keterangan); ?></p>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-white/60 md:w-28 shrink-0"><?php echo e($a->pelaku ?? 'tidak dikenal'); ?></p>
                                <p class="text-xs text-white/30 md:w-32 shrink-0"><?php echo e($a->ip); ?></p>
                                <span
                                    class="shrink-0 w-fit rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?php echo e($kelasKategori[$a->kategori] ?? 'border-white/15 text-white/60'); ?>"><?php echo e($kategoriLabel[$a->kategori] ?? $a->kategori); ?></span>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                    <?php echo $__env->make('components.dashboard.pagination', ['paginator' => $daftar], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard-pages/log.blade.php ENDPATH**/ ?>