
<?php $__env->startSection('content'); ?>
    <?php
        // Placeholder preview saat isian masih kosong (mirip form News). `$ubah` = pengumuman yang sedang diubah, atau null.
        $kosong = (object) [
            'tag' => 'KATEGORI • PENGUMUMAN',
            'judul' => 'Judul pengumuman',
            'isi' => 'Isi pengumuman tampil di sini.',
            'cta_label' => 'Lihat detail',
            'cta_tujuan' => 'ic.checkin',
            'ikon' => 'sparkles',
            'warna' => 'kuning',
            'terbit_pada' => \Carbon\Carbon::today(),
            'unggulan' => false,
        ];

        // Isi awal form: dari pengumuman yang diubah, kalau tidak nilai bawaan. old() menang setelah gagal validasi.
        $awal = [
            'tag' => $ubah?->tag ?? '',
            'terbit_pada' => ($ubah?->terbit_pada ?? $kosong->terbit_pada)->format('Y-m-d'),
            'judul' => $ubah?->judul ?? '',
            'isi' => $ubah?->isi ?? '',
            'ikon' => $ubah?->ikon ?? $kosong->ikon,
            'warna' => $ubah?->warna ?? $kosong->warna,
            'cta_label' => $ubah?->cta_label ?? '',
            'cta_tujuan' => $ubah?->cta_tujuan ?? $kosong->cta_tujuan,
        ];
        $nilai = fn(string $kunci) => old($kunci, $awal[$kunci]);
        // Kotak centang tidak terkirim bila tidak dicentang, jadi old() kosong tidak boleh jatuh ke nilai awal.
        $unggulanDicentang = session()->hasOldInput() ? (bool) old('unggulan') : (bool) $ubah?->unggulan;

        $kelasInput =
            'bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
        $labelKelas = 'text-sm font-semibold uppercase tracking-widest text-white/60';
        $ctaPengumuman = config('inner_circle.cta_pengumuman');
    ?>

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        <?php echo $__env->make('components/dashboard/navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            <?php echo $__env->make('components.dashboard.inner-circle.tabs', [
                'aktif' => 'pengumuman',
                'judul' => 'Pengumuman',
                'deskripsi' => 'Tulis kabar resmi untuk anggota. Satu pengumuman bisa dijadikan Kabar Pilihan.',
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->make('components/errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('components/success', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start" id="form-pengumuman-bagian">

                
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 scroll-mt-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi <?php echo e($ubah ? 'bi-pencil-square' : 'bi-plus-circle'); ?> text-white/50"
                            aria-hidden="true"></i>
                        <span><?php echo e($ubah ? 'Ubah pengumuman' : 'Tambah pengumuman'); ?></span>
                    </h2>

                    <form id="form-pengumuman" method="POST"
                        action="<?php echo e($ubah ? route('ic.admin.pengumuman.ubah', $ubah->id_broadcast) : route('ic.admin.pengumuman.simpan')); ?>"
                        class="flex flex-col gap-4">
                        <?php echo csrf_field(); ?>
                        <?php if($ubah): ?>
                            <?php echo method_field('PUT'); ?>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-tag" class="<?php echo e($labelKelas); ?>">Kategori</label>
                                <input type="text" id="p-tag" name="tag" maxlength="40"
                                    value="<?php echo e($nilai('tag')); ?>" placeholder="Contoh: MUSIK • PENGUMUMAN"
                                    class="<?php echo e($kelasInput); ?>" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-terbit" class="<?php echo e($labelKelas); ?>">Tanggal terbit <span
                                        class="text-red-400">*</span></label>
                                <input type="date" id="p-terbit" name="terbit_pada" required
                                    value="<?php echo e($nilai('terbit_pada')); ?>" class="<?php echo e($kelasInput); ?> scheme-dark" />
                                <p class="text-xs text-white/30">Tanggal di masa depan = terjadwal, belum tampil ke
                                    anggota.</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="p-judul" class="<?php echo e($labelKelas); ?>">Judul <span
                                    class="text-red-400">*</span></label>
                            <input type="text" id="p-judul" name="judul" maxlength="80" required
                                value="<?php echo e($nilai('judul')); ?>" placeholder="Contoh: Sampai jumpa di pertunjukan berikutnya"
                                class="<?php echo e($kelasInput); ?>" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="p-isi" class="<?php echo e($labelKelas); ?>">Isi <span
                                    class="text-red-400">*</span></label>
                            <textarea id="p-isi" name="isi" rows="4" maxlength="220" required
                                placeholder="Tulis kabar singkat untuk anggota..." class="<?php echo e($kelasInput); ?>"><?php echo e($nilai('isi')); ?></textarea>
                            <p class="text-xs text-white/30"><span id="p-isi-hitung">0</span> / 220 karakter</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-ikon" class="<?php echo e($labelKelas); ?>">Ikon</label>
                                <select id="p-ikon" name="ikon" class="<?php echo e($kelasInput); ?> scheme-dark">
                                    <?php $__currentLoopData = config('inner_circle.ikon_pilihan'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option class="bg-neutral-900" value="<?php echo e($kunci); ?>"
                                            <?php if($kunci === $nilai('ikon')): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-warna" class="<?php echo e($labelKelas); ?>">Warna</label>
                                <select id="p-warna" name="warna" class="<?php echo e($kelasInput); ?> scheme-dark">
                                    <?php $__currentLoopData = config('inner_circle.warna_kartu'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option class="bg-neutral-900" value="<?php echo e($kunci); ?>"
                                            <?php if($kunci === $nilai('warna')): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-cta-label" class="<?php echo e($labelKelas); ?>">Teks tombol <span
                                        class="text-red-400">*</span></label>
                                <input type="text" id="p-cta-label" name="cta_label" maxlength="30" required
                                    value="<?php echo e($nilai('cta_label')); ?>" placeholder="Contoh: Lihat jadwal acara"
                                    class="<?php echo e($kelasInput); ?>" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-cta-tujuan" class="<?php echo e($labelKelas); ?>">Tombol menuju</label>
                                <select id="p-cta-tujuan" name="cta_tujuan" class="<?php echo e($kelasInput); ?> scheme-dark">
                                    <?php $__currentLoopData = $ctaPengumuman; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option class="bg-neutral-900" value="<?php echo e($route); ?>"
                                            <?php if($route === $nilai('cta_tujuan')): echo 'selected'; endif; ?>><?php echo e($label); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>

                        <label
                            class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/2 p-4 cursor-pointer">
                            <input type="checkbox" id="p-unggulan" name="unggulan" value="1"
                                <?php if($unggulanDicentang): echo 'checked'; endif; ?> class="mt-1 w-5 h-5 shrink-0 accent-red-900" />
                            <span>
                                <span class="block text-sm font-semibold">Jadikan Kabar Pilihan</span>
                                <span class="block text-xs text-white/40 mt-0.5">Tampil sebagai kartu besar di atas daftar.
                                    Hanya satu yang bisa jadi Kabar Pilihan; yang lama otomatis menjadi pengumuman
                                    biasa.</span>
                            </span>
                        </label>

                        <div class="flex flex-col sm:flex-row gap-2 mt-2">
                            <button type="submit" id="p-kirim"
                                class="flex-1 text-white font-bold uppercase tracking-widest p-3 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                <?php echo e($ubah ? 'Simpan perubahan' : 'Terbitkan'); ?>

                            </button>
                            <?php if($ubah): ?>
                                <a href="<?php echo e(route('ic.admin.pengumuman')); ?>"
                                    class="h-12 px-5 inline-flex items-center justify-center rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                    Batal ubah
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                
                <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                        <i class="bi bi-eye text-white/40" aria-hidden="true"></i>
                        <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di Komunitas › Pengumuman
                            WS</span>
                    </div>
                    <div class="ic-app p-5" inert>
                        <div id="preview-unggulan" hidden>
                            <?php if (isset($component)) { $__componentOriginal6f6d33b5cdaf6441023f2a08b8c18204 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6f6d33b5cdaf6441023f2a08b8c18204 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.pengumuman-unggulan','data' => ['pengumuman' => $kosong]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.pengumuman-unggulan'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['pengumuman' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kosong)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6f6d33b5cdaf6441023f2a08b8c18204)): ?>
<?php $attributes = $__attributesOriginal6f6d33b5cdaf6441023f2a08b8c18204; ?>
<?php unset($__attributesOriginal6f6d33b5cdaf6441023f2a08b8c18204); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6f6d33b5cdaf6441023f2a08b8c18204)): ?>
<?php $component = $__componentOriginal6f6d33b5cdaf6441023f2a08b8c18204; ?>
<?php unset($__componentOriginal6f6d33b5cdaf6441023f2a08b8c18204); ?>
<?php endif; ?>
                        </div>
                        <div id="preview-item">
                            <?php if (isset($component)) { $__componentOriginal2a50be00b6dee3cb05f88a98cfdefcaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a50be00b6dee3cb05f88a98cfdefcaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.item-pengumuman','data' => ['pengumuman' => $kosong]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.item-pengumuman'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['pengumuman' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kosong)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a50be00b6dee3cb05f88a98cfdefcaf)): ?>
<?php $attributes = $__attributesOriginal2a50be00b6dee3cb05f88a98cfdefcaf; ?>
<?php unset($__attributesOriginal2a50be00b6dee3cb05f88a98cfdefcaf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a50be00b6dee3cb05f88a98cfdefcaf)): ?>
<?php $component = $__componentOriginal2a50be00b6dee3cb05f88a98cfdefcaf; ?>
<?php unset($__componentOriginal2a50be00b6dee3cb05f88a98cfdefcaf); ?>
<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <section aria-labelledby="judul-daftar-pengumuman" class="flex flex-col gap-4">
                <h2 id="judul-daftar-pengumuman"
                    class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-megaphone-fill text-white/50" aria-hidden="true"></i>
                    Pengumuman tersimpan (<?php echo e($daftar->total()); ?>)
                </h2>

                <?php if($daftar->isEmpty()): ?>
                    <?php echo $__env->make('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada pengumuman yang ditambahkan.',
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
                    <ul class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                        <?php $__currentLoopData = $daftar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li
                                class="flex items-center gap-3 rounded-2xl border p-3 transition-colors hover:border-white/20 min-w-0 <?php echo e($ubah && $ubah->id_broadcast === $p->id_broadcast ? 'border-white/40 bg-white/6' : 'border-white/10 bg-white/3'); ?>">
                                <span class="ic-app ic-klaim__bulat ic-warna-<?php echo e($p->warna); ?> shrink-0">
                                    <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $p->ikon,'kelas' => 'ic-ikon--kecil']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p->ikon),'kelas' => 'ic-ikon--kecil']); ?>
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
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] mb-0.5">
                                        <?php if($p->tag): ?>
                                            <span class="text-white/50 truncate"><?php echo e($p->tag); ?></span>
                                        <?php endif; ?>
                                        <span
                                            class="text-red-400 shrink-0"><?php echo e($p->terbit_pada->locale('id')->translatedFormat('j M Y')); ?></span>
                                        <?php if($p->terjadwal): ?>
                                            <span
                                                class="shrink-0 rounded-full border border-sky-300/30 bg-sky-300/10 px-2 py-0.5 font-semibold text-sky-200">Terjadwal</span>
                                        <?php endif; ?>
                                        <?php if($p->unggulan): ?>
                                            <span
                                                class="shrink-0 rounded-full border border-amber-300/30 bg-amber-300/10 px-2 py-0.5 font-semibold text-amber-200">Kabar
                                                Pilihan</span>
                                        <?php endif; ?>
                                        <?php if (! ($p->aktif)): ?>
                                            <span
                                                class="shrink-0 rounded-full border border-white/20 bg-white/5 px-2 py-0.5 font-semibold text-white/60">Disembunyikan</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="font-semibold text-sm line-clamp-1"><?php echo e($p->judul); ?></p>
                                    <p class="text-xs text-white/40 line-clamp-1 mt-0.5"><?php echo e($p->isi); ?></p>
                                </div>
                                <div class="flex items-center shrink-0">
                                    <form method="POST"
                                        action="<?php echo e(route('ic.admin.pengumuman.aktif', $p->id_broadcast)); ?>"
                                        class="inline-flex">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input type="hidden" name="aktif" value="<?php echo e($p->aktif ? 0 : 1); ?>">
                                        <button type="submit"
                                            aria-label="<?php echo e($p->aktif ? 'Sembunyikan' : 'Tampilkan'); ?> pengumuman <?php echo e($p->judul); ?>"
                                            title="<?php echo e($p->aktif ? 'Sembunyikan dari anggota' : 'Tampilkan ke anggota'); ?>"
                                            class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-white transition">
                                            <i class="bi <?php echo e($p->aktif ? 'bi-eye-fill' : 'bi-eye-slash-fill'); ?>"
                                                aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <a href="<?php echo e(route('ic.admin.pengumuman', ['ubah' => $p->id_broadcast, 'page' => $daftar->currentPage() > 1 ? $daftar->currentPage() : null])); ?>#form-pengumuman-bagian"
                                        aria-label="Ubah pengumuman <?php echo e($p->judul); ?>"
                                        class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-white transition">
                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                    </a>
                                    <form method="POST"
                                        action="<?php echo e(route('ic.admin.pengumuman.hapus', $p->id_broadcast)); ?>"
                                        class="inline-flex" data-swal-confirm
                                        data-confirm-message="Pengumuman “<?php echo e($p->judul); ?>” akan dihapus dan hilang dari Komunitas anggota.">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" aria-label="Hapus pengumuman <?php echo e($p->judul); ?>"
                                            class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-red-400 transition">
                                            <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                    <?php echo $__env->make('components.dashboard.pagination', ['paginator' => $daftar], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
        <script>
            window.icIkonPeta = <?php echo json_encode($ikonPeta, 15, 512) ?>;

            const KOSONG = <?php echo json_encode($kosong, 15, 512) ?>;
            const $ = (id) => document.getElementById(id);
            const form = $('form-pengumuman');

            const unggulan = $('preview-unggulan');
            const item = $('preview-item');

            // Ganti teks tombol (CTA) tanpa menghapus ikon panahnya.
            function setTombol(a, teks) {
                a.textContent = teks + ' ';
                a.insertAdjacentHTML('beforeend', '<i class="bi bi-arrow-up-right" aria-hidden="true"></i>');
            }

            function perbaruiPreview() {
                const isi = (id, pengganti) => $(id).value.trim() || pengganti;
                const tag = $('p-tag').value.trim();
                const judul = isi('p-judul', KOSONG.judul);
                const teks = isi('p-isi', KOSONG.isi);
                const label = isi('p-cta-label', KOSONG.cta_label);
                const ikon = $('p-ikon').value;
                const warna = $('p-warna').value;
                const besar = $('p-unggulan').checked;
                // Kategori boleh kosong: preview mengikuti tampilan anggota (tanpa tag), placeholder hanya bila form masih benar-benar baru.
                const awalan = tag ? `${tag} · ` : '';

                unggulan.hidden = !besar;
                item.hidden = besar;

                unggulan.querySelector('.ic-unggulan__judul').textContent = judul;
                unggulan.querySelector('.ic-unggulan__isi').textContent = teks;
                setTombol(unggulan.querySelector('.ic-tombol'), label);

                item.querySelector('.ic-pengumuman__meta').textContent = `${awalan}${window.icTanggal($('p-terbit').value)}`;
                item.querySelector('.ic-pengumuman__judul').textContent = judul;
                item.querySelector('.ic-pengumuman__isi').textContent = teks;
                setTombol(item.querySelector('.ic-tombol'), label);

                const bulat = item.querySelector('.ic-pengumuman__ikon');
                bulat.className = bulat.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${warna}`;
                bulat.replaceChildren(window.icBuatIkon(ikon, 'ic-ikon--kecil'));

                $('p-isi-hitung').textContent = $('p-isi').value.length;
            }

            form.addEventListener('input', perbaruiPreview);
            form.addEventListener('change', perbaruiPreview);

            perbaruiPreview();
        </script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard-pages/inner-circle/pengumuman.blade.php ENDPATH**/ ?>