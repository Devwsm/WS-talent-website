
<?php $__env->startSection('content'); ?>
    <?php
        // Data awal untuk form dan preview JavaScript: satu entri per tahapan, kunci = target stempel.
        $dataHadiah = collect($rewards)
            ->mapWithKeys(
                fn($r) => [
                    $r->target_stempel => [
                        'judul' => $r->judul,
                        'deskripsi' => $r->deskripsi,
                        'ikon' => $r->ikon,
                        'warna' => $r->warna,
                        'opsi' => $r->opsi ?? [],
                    ],
                ],
            )
            ->all();

        // Alamat simpan per tahapan (form dipakai bergantian untuk kelima tahap).
        $urlUbah = collect($rewards)
            ->mapWithKeys(fn($r) => [$r->target_stempel => route('ic.admin.hadiah.ubah', $r->target_stempel)])
            ->all();

        // Isian yang gagal divalidasi tampil lagi di tahap yang sedang dibuka.
        if (!empty($rewards) && (old('judul') !== null || old('deskripsi') !== null)) {
            $opsiLama = old('opsi', []);
            $dataHadiah[$tahapAwal] = array_merge($dataHadiah[$tahapAwal], [
                'judul' => (string) old('judul', $dataHadiah[$tahapAwal]['judul']),
                'deskripsi' => (string) old('deskripsi', $dataHadiah[$tahapAwal]['deskripsi']),
                'ikon' => (string) old('ikon', $dataHadiah[$tahapAwal]['ikon']),
                'warna' => (string) old('warna', $dataHadiah[$tahapAwal]['warna']),
                'opsi' => is_array($opsiLama) ? array_values(array_map('strval', $opsiLama)) : [],
            ]);
        }

        // Preview selalu tampil sebagai "terbuka" supaya label dan tombolnya terlihat penuh.
        $awal = empty($rewards)
            ? null
            : (object) array_merge($dataHadiah[$tahapAwal], [
                'target_stempel' => $tahapAwal,
                'status_member' => 'terbuka',
            ]);
        $kelasInput =
            'bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
    ?>

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        <?php echo $__env->make('components/dashboard/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            <?php echo $__env->make('components.dashboard.inner-circle.tabs', [
                'aktif' => 'hadiah',
                'judul' => 'Hadiah',
                'deskripsi' =>
                    'Atur lima tahapan hadiah: judul, deskripsi, ikon, warna, pilihan hadiah, dan tampil/sembunyikan.',
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->make('components/errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('components/success', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php if(empty($rewards)): ?>
                <?php echo $__env->make('components/dashboard/card/empty-state', [
                    'message' => 'Belum ada tahapan hadiah. Jalankan php artisan db:seed --class=icRewardSeeder.',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                    
                    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                        <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                            <i class="bi bi-pencil-square text-white/50" aria-hidden="true"></i> Ubah tahapan
                        </h2>

                        
                        <div role="group" aria-label="Pilih tahapan hadiah" class="flex flex-wrap gap-2 mb-6">
                            <?php $__currentLoopData = $rewards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $dipilih = $r->target_stempel === $tahapAwal; ?>
                                <button type="button" data-tahap="<?php echo e($r->target_stempel); ?>"
                                    aria-pressed="<?php echo e($dipilih ? 'true' : 'false'); ?>"
                                    class="h-11 min-w-16 px-4 rounded-full border text-sm font-semibold transition-colors
                                <?php echo e($dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white'); ?> <?php echo e($r->aktif ? '' : 'opacity-60 border-dashed'); ?>"
                                    <?php if (! ($r->aktif)): ?> title="Disembunyikan dari anggota" <?php endif; ?>>
                                    <?php echo e($r->target_stempel); ?>

                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <form id="form-hadiah" method="POST" action="<?php echo e($urlUbah[$tahapAwal]); ?>"
                            class="flex flex-col gap-4">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <div class="flex flex-col gap-1.5">
                                <span class="text-sm font-semibold uppercase tracking-widest text-white/60">Target
                                    stempel</span>
                                <p class="text-sm text-white/70"><b id="h-target"><?php echo e($awal->target_stempel); ?></b> stempel
                                    <span class="text-white/30">· tetap, tidak bisa diubah</span>
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="h-judul"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Judul <span
                                        class="text-red-400">*</span></label>
                                <input type="text" id="h-judul" name="judul" maxlength="40" required
                                    value="<?php echo e($awal->judul); ?>" class="<?php echo e($kelasInput); ?>" />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="h-deskripsi"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Deskripsi <span
                                        class="text-red-400">*</span></label>
                                <textarea id="h-deskripsi" name="deskripsi" rows="3" maxlength="140" required class="<?php echo e($kelasInput); ?>"><?php echo e($awal->deskripsi); ?></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="h-ikon"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Ikon</label>
                                    <select id="h-ikon" name="ikon" class="<?php echo e($kelasInput); ?> scheme-dark">
                                        <?php $__currentLoopData = config('inner_circle.ikon_pilihan'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option class="bg-neutral-900" value="<?php echo e($kunci); ?>"
                                                <?php if($kunci === $awal->ikon): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="h-warna"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Warna</label>
                                    <select id="h-warna" name="warna" class="<?php echo e($kelasInput); ?> scheme-dark">
                                        <?php $__currentLoopData = config('inner_circle.warna_kartu'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option class="bg-neutral-900" value="<?php echo e($kunci); ?>"
                                                <?php if($kunci === $awal->warna): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="h-opsi"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Pilihan
                                    hadiah</label>
                                <textarea id="h-opsi" name="opsi" rows="2" placeholder="Kosongkan bila hadiah ini tidak punya pilihan"
                                    class="<?php echo e($kelasInput); ?>"><?php echo e(implode("\n", $awal->opsi ?? [])); ?></textarea>
                                <p class="text-xs text-white/30">Satu pilihan per baris: isi tepat 2, atau kosongkan. Bila
                                    terisi, anggota memilih salah satunya saat mengajukan klaim. Pengajuan yang sudah masuk
                                    tidak berubah.</p>
                            </div>

                            <button type="submit"
                                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                Simpan tahapan
                            </button>
                            <p class="text-xs text-white/30">Simpan dilakukan per tahapan: hanya tahapan yang sedang dibuka
                                yang tersimpan.</p>
                        </form>

                        
                        <div class="mt-8 pt-6 border-t border-white/10">
                            <h3 class="font-bold uppercase tracking-wide text-sm mb-1 flex items-center gap-2">
                                <i class="bi bi-eye text-white/50" aria-hidden="true"></i> Tampil ke anggota
                            </h3>
                            <p class="text-xs text-white/40 mb-4">Tahap yang disembunyikan tidak tampil di halaman Hadiah
                                dan tidak bisa diklaim baru. Pengajuan yang sudah masuk tetap diproses. Minimal satu tahap
                                harus aktif.</p>
                            <ul class="flex flex-col gap-2">
                                <?php $__currentLoopData = $rewards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li
                                        class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/3 px-4 py-2.5">
                                        <p class="min-w-0 flex-1 text-sm">
                                            <b><?php echo e($r->target_stempel); ?></b> stempel
                                            <span class="text-white/50">· <?php echo e($r->judul); ?></span>
                                        </p>
                                        <span
                                            class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?php echo e($r->aktif ? 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200' : 'border-white/15 text-white/50'); ?>"><?php echo e($r->aktif ? 'Aktif' : 'Disembunyikan'); ?></span>
                                        <form method="POST"
                                            action="<?php echo e(route('ic.admin.hadiah.aktif', $r->target_stempel)); ?>"
                                            class="shrink-0">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <input type="hidden" name="aktif" value="<?php echo e($r->aktif ? 0 : 1); ?>">
                                            <button type="submit"
                                                class="h-11 px-4 rounded-full border border-white/15 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                                <?php echo e($r->aktif ? 'Sembunyikan' : 'Aktifkan'); ?>

                                            </button>
                                        </form>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>

                    
                    <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                            <i class="bi bi-eye text-white/40" aria-hidden="true"></i>
                            <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di halaman Hadiah</span>
                        </div>

                        <div class="ic-app p-5 flex flex-col gap-5" inert>
                            <div class="max-w-xs w-full mx-auto" id="preview-ubin">
                                <?php if (isset($component)) { $__componentOriginale85949c1824580e84c1e582e710cf398 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale85949c1824580e84c1e582e710cf398 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ubin-hadiah','data' => ['reward' => $awal,'href' => '#']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ubin-hadiah'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['reward' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($awal),'href' => '#']); ?>
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
                            </div>

                            <div class="ic-panel ic-klaim-daftar">
                                <div class="ic-klaim" id="preview-klaim">
                                    <span class="ic-klaim__bulat ic-warna-<?php echo e($awal->warna); ?>">
                                        <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $awal->ikon,'kelas' => 'ic-ikon--kecil']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($awal->ikon),'kelas' => 'ic-ikon--kecil']); ?>
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
                                        <h3><?php echo e($awal->target_stempel); ?> stempel · <?php echo e($awal->judul); ?></h3>
                                        <p class="ic-bantuan"><?php echo e($awal->deskripsi); ?></p>
                                    </div>
                                    <button type="button" class="ic-tombol ic-tombol--kecil">Klaim <i
                                            class="bi bi-arrow-right" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-4 border-t border-white/10 bg-white/3">
                            <p class="text-xs uppercase text-white/40">Pilihan saat klaim</p>
                            <p id="preview-opsi" class="text-sm text-white/70 mt-1">Tanpa pilihan</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!empty($rewards)): ?>
        <?php $__env->startPush('scripts'); ?>
            <script>
                window.icIkonPeta = <?php echo json_encode($ikonPeta, 15, 512) ?>;

                const DATA = <?php echo json_encode($dataHadiah, 15, 512) ?>;
                const URL_UBAH = <?php echo json_encode($urlUbah, 15, 512) ?>;
                const $ = (id) => document.getElementById(id);
                let aktif = String(<?php echo json_encode($awal->target_stempel, 15, 512) ?>);

                const ubin = document.querySelector('#preview-ubin .ic-ubin');
                const klaim = $('preview-klaim');

                // Gambar preview dari data tahapan yang sedang dipilih.
                function perbaruiPreview() {
                    const d = DATA[aktif];

                    ubin.className = ubin.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${d.warna}`;
                    ubin.classList.toggle('ic-ubin--lebar', aktif === '250');
                    ubin.querySelector('.ic-ubin__angka').firstChild.nodeValue = aktif;
                    ubin.querySelector('.ic-ubin__judul').textContent = d.judul;
                    ubin.querySelector('.ic-ubin__deskripsi').textContent = d.deskripsi;
                    ubin.querySelector('.ic-ubin__ikon').replaceWith(window.icBuatIkon(d.ikon, 'ic-ubin__ikon'));

                    const bulat = klaim.querySelector('.ic-klaim__bulat');
                    bulat.className = bulat.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${d.warna}`;
                    bulat.replaceChildren(window.icBuatIkon(d.ikon, 'ic-ikon--kecil'));
                    klaim.querySelector('h3').textContent = `${aktif} stempel · ${d.judul}`;
                    klaim.querySelector('p').textContent = d.deskripsi;

                    $('preview-opsi').textContent = d.opsi.length ? d.opsi.join(' · ') : 'Tanpa pilihan';
                }

                // Isi form dari data tahapan yang dipilih.
                function muatForm() {
                    const d = DATA[aktif];
                    $('h-target').textContent = aktif;
                    $('h-judul').value = d.judul;
                    $('h-deskripsi').value = d.deskripsi;
                    $('h-ikon').value = d.ikon;
                    $('h-warna').value = d.warna;
                    $('h-opsi').value = d.opsi.join('\n');
                }

                document.querySelectorAll('[data-tahap]').forEach((tombol) => {
                    tombol.addEventListener('click', () => {
                        aktif = tombol.dataset.tahap;
                        document.querySelectorAll('[data-tahap]').forEach((t) => {
                            const pilih = t === tombol;
                            t.setAttribute('aria-pressed', String(pilih));
                            t.classList.toggle('bg-white', pilih);
                            t.classList.toggle('text-black', pilih);
                            t.classList.toggle('border-white', pilih);
                            t.classList.toggle('border-white/10', !pilih);
                            t.classList.toggle('text-white/60', !pilih);
                        });
                        muatForm();
                        perbaruiPreview();
                        $('form-hadiah').action = URL_UBAH[aktif];
                    });
                });

                const sinkron = {
                    'h-judul': (v, d) => (d.judul = v),
                    'h-deskripsi': (v, d) => (d.deskripsi = v),
                    'h-ikon': (v, d) => (d.ikon = v),
                    'h-warna': (v, d) => (d.warna = v),
                    'h-opsi': (v, d) => (d.opsi = v.split('\n').map((x) => x.trim()).filter(Boolean).slice(0, 2)),
                };
                Object.entries(sinkron).forEach(([id, simpan]) => {
                    $(id).addEventListener('input', (e) => {
                        simpan(e.target.value, DATA[aktif]);
                        perbaruiPreview();
                    });
                });

                perbaruiPreview();
            </script>
        <?php $__env->stopPush(); ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard-pages/inner-circle/hadiah.blade.php ENDPATH**/ ?>