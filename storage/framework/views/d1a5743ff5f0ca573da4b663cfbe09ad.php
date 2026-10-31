
<?php $__env->startSection('content'); ?>
    <?php
        $statusKlaim = config('inner_circle.status_klaim');
        $alurKlaim = config('inner_circle.alur_klaim');

        // Warna lencana status untuk tema gelap dashboard (kunci = `warna` di config status_klaim).
        $kelasLencana = [
            'kuning' => 'border-amber-300/30 bg-amber-300/10 text-amber-200',
            'biru' => 'border-sky-300/30 bg-sky-300/10 text-sky-200',
            'hijau' => 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200',
            'merah' => 'border-red-400/30 bg-red-400/10 text-red-300',
        ];

        $pilihanFilter = [
            'menunggu' => 'Menunggu',
            'semua' => 'Semua',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];
        $pesanKosong = [
            'menunggu' => 'Tidak ada pengajuan yang menunggu tindakan.',
            'semua' => 'Belum ada pengajuan hadiah.',
            'selesai' => 'Belum ada pengajuan yang selesai.',
            'ditolak' => 'Belum ada pengajuan yang ditolak.',
        ];
    ?>

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        <?php echo $__env->make('components/dashboard/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            <?php echo $__env->make('components.dashboard.inner-circle.tabs', [
                'aktif' => 'klaim',
                'judul' => 'Klaim hadiah',
                'deskripsi' => 'Tinjau pengajuan hadiah anggota dan gerakkan statusnya sampai selesai.',
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->make('components/errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('components/success', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-inbox-fill text-white/50" aria-hidden="true"></i>
                    Antrean pengajuan
                </h2>

                
                <nav aria-label="Saring pengajuan" class="-mx-5 md:mx-0 overflow-x-auto md:overflow-visible">
                    <ul class="flex gap-2 px-5 md:px-0 w-max md:w-auto">
                        <?php $__currentLoopData = $pilihanFilter; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $dipilih = $kunci === $filterStatus; ?>
                            <li>
                                <a href="<?php echo e(route('ic.admin.klaim', ['status' => $kunci])); ?>"
                                    <?php if($dipilih): ?> aria-current="page" <?php endif; ?>
                                    class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                                    <?php echo e($dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white'); ?>">
                                    <?php echo e($label); ?>

                                    <span
                                        class="text-xs <?php echo e($dipilih ? 'text-black/60' : 'text-white/40'); ?>"><?php echo e($hitung[$kunci]); ?></span>
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </nav>
            </div>

            <p class="text-xs text-white/40 -mt-3">
                Alur status: Diajukan → Ditinjau → Disetujui → Selesai. Pengajuan bisa ditolak selama belum selesai
                (alasan wajib dan tampil di profil anggota). Yang ditolak bisa <b>dibuka kembali</b> dan kembali ke
                Diajukan. Catatan, mis. kode voucher, juga tampil di profil anggota.
            </p>

            <?php if($klaim->isEmpty()): ?>
                <?php echo $__env->make('components/dashboard/card/empty-state', [
                    'message' => $pesanKosong[$filterStatus],
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
                <ul class="flex flex-col gap-3" id="antrean-klaim">
                    <?php $__currentLoopData = $klaim; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $status = $statusKlaim[$k->status];
                            $aksi = $alurKlaim[$k->status] ?? null;
                        ?>
                        <li class="rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5 transition-colors hover:border-white/20"
                            data-klaim data-status="<?php echo e($k->status); ?>"
                            data-judul="<?php echo e($k->nama_member); ?> · <?php echo e($k->judul); ?>"
                            data-catatan-awal="<?php echo e($k->catatan_admin); ?>">
                            <div class="flex flex-col md:flex-row md:items-center gap-4">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    
                                    <span class="ic-app ic-klaim__bulat ic-warna-<?php echo e($k->warna); ?> shrink-0">
                                        <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $k->ikon,'kelas' => 'ic-ikon--kecil']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($k->ikon),'kelas' => 'ic-ikon--kecil']); ?>
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
                                    <div class="min-w-0">
                                        <p class="font-semibold">
                                            <?php echo e($k->nama_member); ?>

                                            <span class="text-white/40 font-normal">· <?php echo e($k->kode_member); ?></span>
                                        </p>
                                        <p class="text-sm text-white/70"><?php echo e($k->target_stempel); ?> stempel ·
                                            <?php echo e($k->judul); ?>

                                            <?php if($k->opsi_dipilih): ?>
                                                <span class="text-white/40">· pilihan: <?php echo e($k->opsi_dipilih); ?></span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-white/40 mt-0.5">Diajukan
                                            <?php echo e($k->diajukan_pada->locale('id')->translatedFormat('j M Y')); ?></p>
                                        <?php if($k->catatan_admin): ?>
                                            <p class="text-xs text-white/60 mt-2">
                                                <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                                                <?php echo e($k->catatan_admin); ?>

                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 md:justify-end shrink-0">
                                    <span
                                        class="rounded-full border px-3 py-1.5 text-xs font-semibold <?php echo e($kelasLencana[$status['warna']]); ?>"><?php echo e($status['label']); ?></span>

                                    <?php if($aksi): ?>
                                        <button type="button" data-aksi="lanjut" data-label="<?php echo e($aksi['aksi']); ?>"
                                            class="h-11 px-5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
                                            <?php echo e($aksi['aksi']); ?>

                                        </button>
                                        <button type="button" data-aksi="tolak"
                                            class="h-11 px-4 rounded-full border border-red-400/30 text-red-300 text-xs font-semibold hover:bg-red-400/10 transition-colors">
                                            Tolak
                                        </button>
                                    <?php endif; ?>
                                    <?php if($k->status === 'ditolak'): ?>
                                        <button type="button" data-aksi="buka_kembali"
                                            class="h-11 px-5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
                                            Buka kembali
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" data-aksi="catatan"
                                        class="h-11 px-4 rounded-full border border-white/15 text-white/70 text-xs font-semibold hover:bg-white/10 hover:text-white transition-colors">
                                        Catatan
                                    </button>
                                </div>
                            </div>

                            
                            <details class="mt-3 text-xs text-white/50">
                                <summary class="cursor-pointer select-none py-2 hover:text-white/80">
                                    Riwayat (<?php echo e($k->logs->count()); ?>)
                                </summary>
                                <ol class="flex flex-col gap-1.5 pb-1 pl-1">
                                    <?php $__currentLoopData = $k->logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li>
                                            <b class="text-white/70"><?php echo e($statusKlaim[$log->status]['label']); ?></b>
                                            · <?php echo e($log->created_at->locale('id')->translatedFormat('j M Y, H.i')); ?>

                                            · <?php echo e($log->oleh ?? ($log->status === 'diajukan' ? 'anggota' : 'admin')); ?>

                                            <?php if($log->catatan): ?>
                                                <span class="text-white/40">— <?php echo e($log->catatan); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ol>
                            </details>

                            
                            <form method="POST" action="<?php echo e(route('ic.admin.klaim.proses', $k->id_claim)); ?>"
                                data-form-klaim hidden>
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <input type="hidden" name="aksi" value="">
                                <input type="hidden" name="dari" value="<?php echo e($k->status); ?>">
                                <input type="hidden" name="catatan" value="">
                            </form>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>

                <?php echo $__env->make('components.dashboard.pagination', ['paginator' => $klaim], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
        <script>
            document.addEventListener('click', async (e) => {
                const tombol = e.target.closest('[data-aksi]');
                if (!tombol) return;
                const baris = tombol.closest('[data-klaim]');
                const form = baris.querySelector('form[data-form-klaim]');
                const aksi = tombol.dataset.aksi;
                const catatanAwal = baris.dataset.catatanAwal || '';

                const dialog = {
                    lanjut: {
                        title: `${tombol.dataset.label} pengajuan ini?`,
                        inputLabel: 'Catatan untuk anggota (opsional, mis. kode voucher)',
                        inputValue: catatanAwal,
                        confirmButtonText: tombol.dataset.label,
                    },
                    tolak: {
                        title: 'Tolak pengajuan ini?',
                        inputLabel: 'Alasan (tampil di profil anggota)',
                        inputValidator: (nilai) => (nilai.trim() ? undefined : 'Alasan wajib diisi.'),
                        confirmButtonText: 'Ya, tolak',
                    },
                    buka_kembali: {
                        title: 'Buka kembali pengajuan ini?',
                        inputLabel: 'Catatan untuk anggota (opsional). Alasan penolakan sebelumnya hilang dari profil anggota, tapi tetap ada di riwayat.',
                        inputValue: '',
                        confirmButtonText: 'Buka kembali',
                    },
                    catatan: {
                        title: 'Catatan pengajuan',
                        inputLabel: 'Tampil di profil anggota. Kosongkan untuk menghapus catatan.',
                        inputValue: catatanAwal,
                        confirmButtonText: 'Simpan catatan',
                    },
                } [aksi];
                if (!dialog) return;

                const hasil = await window.Swal.fire({
                    ...dialog,
                    text: baris.dataset.judul,
                    input: 'textarea',
                    inputAttributes: {
                        maxlength: 160
                    },
                    showCancelButton: true,
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                });
                if (!hasil.isConfirmed) return;

                form.elements.aksi.value = aksi;
                form.elements.catatan.value = (hasil.value || '').trim();
                // Kunci semua tombol baris ini supaya klik ganda tidak mengirim dua permintaan.
                baris.querySelectorAll('[data-aksi]').forEach((b) => (b.disabled = true));
                form.submit();
            });
        </script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard-pages/inner-circle/klaim.blade.php ENDPATH**/ ?>