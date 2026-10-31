
<?php $__env->startSection('content'); ?>
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        <?php echo $__env->make('components/dashboard/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            
            <?php echo $__env->make('components.dashboard.inner-circle.tabs', [
                'aktif' => 'anggota',
                'judul' => 'Anggota',
                'deskripsi' => 'Cari anggota dan tambahkan stempel manual bila ada kehadiran yang perlu dicatat.',
                'pakaiCss' => false,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->make('components/errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('components/success', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            
            <form method="GET" action="<?php echo e(route('ic.admin.anggota')); ?>" role="search"
                class="flex flex-col sm:flex-row gap-3 rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5">
                <div class="flex flex-col gap-1.5 flex-1 min-w-0">
                    <label for="cari-anggota" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                        Cari anggota
                    </label>
                    <input type="search" id="cari-anggota" name="q" value="<?php echo e($q); ?>" maxlength="60"
                        placeholder="Nama, email, atau kode (mis. IC-00248)"
                        class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-12 px-6 text-white font-bold uppercase tracking-widest bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                        Cari
                    </button>
                    <?php if($q !== ''): ?>
                        <a href="<?php echo e(route('ic.admin.anggota')); ?>"
                            class="h-12 px-5 inline-flex items-center rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <div>
                <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                    <i class="bi bi-people-fill text-white/50" aria-hidden="true"></i>
                    <?php if($q !== ''): ?>
                        Hasil pencarian (<?php echo e($anggota->total()); ?> dari <?php echo e($totalAnggota); ?>)
                    <?php else: ?>
                        Anggota terdaftar (<?php echo e($totalAnggota); ?>)
                    <?php endif; ?>
                </h2>

                <?php if($anggota->isEmpty()): ?>
                    <?php echo $__env->make('components/dashboard/card/empty-state', [
                        'message' =>
                            $q !== ''
                                ? 'Tidak ada anggota yang cocok dengan pencarian ini.'
                                : 'Belum ada anggota yang mendaftar.',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
                    <ul class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                        <?php $__currentLoopData = $anggota; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li
                                class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-white/10 bg-white/3 p-4 transition-colors hover:border-white/20 min-w-0">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <span aria-hidden="true"
                                        class="w-12 h-12 shrink-0 rounded-full bg-white/10 border border-white/10 flex items-center justify-center text-2xl"><?php echo e($m->avatar_emoji); ?></span>
                                    <div class="min-w-0">
                                        <p class="font-semibold truncate"><?php echo e($m->nama); ?></p>
                                        <p class="text-xs text-white/50 truncate"><?php echo e($m->kode_member); ?> ·
                                            <?php echo e($m->email); ?></p>
                                        <p class="text-[11px] text-white/40 mt-0.5">
                                            Bergabung <?php echo e($m->bergabung_pada->locale('id')->translatedFormat('j M Y')); ?>

                                            ·
                                            <?php if($m->terakhir_checkin): ?>
                                                check-in terakhir
                                                <?php echo e($m->terakhir_checkin->locale('id')->translatedFormat('j M Y')); ?>

                                            <?php else: ?>
                                                belum pernah check-in
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                                    <p class="text-right">
                                        <span class="text-2xl font-bold"><?php echo e($m->stempel_total); ?></span>
                                        <span class="block text-[11px] text-white/40">stempel</span>
                                    </p>
                                    <button type="button" data-ic-stempel data-nama="<?php echo e($m->nama); ?>"
                                        data-kode="<?php echo e($m->kode_member); ?>"
                                        data-aksi="<?php echo e(route('ic.admin.anggota.stempel', $m->id_member)); ?>"
                                        class="h-11 px-4 inline-flex items-center gap-2 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
                                        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Tambah stempel
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                    <?php echo $__env->make('components.dashboard.pagination', ['paginator' => $anggota], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <dialog id="dialogStempel" aria-labelledby="dialogStempelJudul"
        class="fixed inset-0 m-0 w-screen h-screen max-w-none max-h-none bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
        <div class="min-h-screen w-full flex items-center justify-center p-4">
            <div
                class="w-full max-w-md max-h-[calc(100vh-2rem)] flex flex-col min-w-0 rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
                <div class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                    <h3 id="dialogStempelJudul" class="font-bold uppercase tracking-wide text-sm text-white">Tambah stempel
                        manual</h3>
                    <button type="button" onclick="document.getElementById('dialogStempel').close()" aria-label="Tutup"
                        class="text-white/40 hover:text-white transition">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <form method="POST" action="#" id="formStempel" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <?php echo csrf_field(); ?>
                    <div class="flex-1 min-h-0 p-6 flex flex-col gap-4 overflow-y-auto">
                        <div class="flex flex-col gap-1.5">
                            <label for="stempel-anggota"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Anggota</label>
                            <input type="text" id="stempel-anggota" readonly
                                class="bg-white/5 border border-white/15 text-white/80 p-3 rounded-lg" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="stempel-jumlah"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Jumlah stempel <span
                                    class="text-red-400">*</span></label>
                            <input type="number" id="stempel-jumlah" name="jumlah" min="1" max="10"
                                value="1" required
                                class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                            <p class="text-xs text-white/30">Satu sampai sepuluh stempel per penambahan.</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="stempel-catatan"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Alasan <span
                                    class="text-red-400">*</span></label>
                            <textarea id="stempel-catatan" name="catatan" rows="3" maxlength="160" required
                                placeholder="Mis. kehadiran di acara privat yang diverifikasi tim WSM"
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition"></textarea>
                            <p class="text-xs text-white/30">Tercatat di riwayat stempel anggota beserta nama admin.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/3 shrink-0">
                        <button type="button" onclick="document.getElementById('dialogStempel').close()"
                            class="h-11 px-5 rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="h-11 px-5 text-white font-bold uppercase tracking-widest text-sm bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Tambah
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </dialog>

    <?php $__env->startPush('scripts'); ?>
        <script>
            // Buka dialog tambah stempel dan isi nama anggota dari baris yang diklik.
            const dialogStempel = document.getElementById('dialogStempel');
            document.addEventListener('click', (e) => {
                const tombol = e.target.closest('[data-ic-stempel]');
                if (!tombol) return;
                document.getElementById('stempel-anggota').value = `${tombol.dataset.nama} (${tombol.dataset.kode})`;
                document.getElementById('formStempel').action = tombol.dataset.aksi;
                dialogStempel.showModal();
                document.getElementById('stempel-jumlah').focus();
            });
        </script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard-pages/inner-circle/anggota.blade.php ENDPATH**/ ?>