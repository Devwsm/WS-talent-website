

<?php $__env->startSection('title', 'Komunitas'); ?>

<?php $__env->startSection('content'); ?>
    <p class="ic-eyebrow">Lebih banyak musik • Teman yang sama</p>
    <h1 class="ic-judul">Komunitas kita<span>.</span></h1>
    <p class="ic-deskripsi">Semua cerita dimulai dari hadir. Update langsung dari WSM, lalu simpan momen yang layak
        diingat.</p>

    <?php if (isset($component)) { $__componentOriginalf877d07c4fe1a01e26f53be775598596 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf877d07c4fe1a01e26f53be775598596 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.panel-verifikasi','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.panel-verifikasi'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf877d07c4fe1a01e26f53be775598596)): ?>
<?php $attributes = $__attributesOriginalf877d07c4fe1a01e26f53be775598596; ?>
<?php unset($__attributesOriginalf877d07c4fe1a01e26f53be775598596); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf877d07c4fe1a01e26f53be775598596)): ?>
<?php $component = $__componentOriginalf877d07c4fe1a01e26f53be775598596; ?>
<?php unset($__componentOriginalf877d07c4fe1a01e26f53be775598596); ?>
<?php endif; ?>

    <section class="ic-banner" aria-labelledby="judul-banner">
        <div>
            <span class="ic-banner__stiker">✦ WS INNER CIRCLE ✦</span>
            <h2 id="judul-banner">Lebih banyak musik.<br>Teman yang sama.</h2>
            <p>Lebih dekat dengan musik. Lebih akrab satu sama lain.</p>
            <button type="button" class="ic-tombol" data-ic-modal-buka="modal-kenangan" <?php if($member->email_verified_at === null): echo 'disabled'; endif; ?>>+ Bagikan
                kenangan <i class="bi bi-arrow-up-right" aria-hidden="true"></i></button>
        </div>
        <div class="ic-banner__orbit" aria-hidden="true">
            <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => 'mirror-ball','kelas' => 'ic-ikon--sampul']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => 'mirror-ball','kelas' => 'ic-ikon--sampul']); ?>
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
    </section>

    <nav class="ic-tab-daftar" aria-label="Bagian komunitas">
        <a class="ic-tab <?php echo e($tab === 'pengumuman' ? 'is-aktif' : ''); ?>"
            href="<?php echo e(route('ic.komunitas', ['tab' => 'pengumuman'])); ?>"
            <?php if($tab === 'pengumuman'): ?> aria-current="page" <?php endif; ?>>
            <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => 'megaphone','kelas' => 'ic-ikon--mini']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => 'megaphone','kelas' => 'ic-ikon--mini']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $attributes = $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $component = $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?> Pengumuman WS
            <span class="ic-tab__lencana"><?php echo e($jumlahPengumuman); ?></span>
        </a>
        <a class="ic-tab <?php echo e($tab === 'galeri' ? 'is-aktif' : ''); ?>" href="<?php echo e(route('ic.komunitas', ['tab' => 'galeri'])); ?>"
            <?php if($tab === 'galeri'): ?> aria-current="page" <?php endif; ?>>
            <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => 'camera','kelas' => 'ic-ikon--mini']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => 'camera','kelas' => 'ic-ikon--mini']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $attributes = $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $component = $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?> Galeri Kenangan
            <span class="ic-tab__lencana"><?php echo e($jumlahKenangan); ?></span>
        </a>
    </nav>

    <?php if($tab === 'pengumuman'): ?>
        <div class="ic-bagian-kepala">
            <div>
                <p class="ic-eyebrow">Langsung dari komunitas</p>
                <h2 class="ic-judul-bagian">Kabar terbaru ✳</h2>
            </div>
            <span class="ic-pil">✦ Kabar resmi</span>
        </div>
        <?php if($unggulan): ?>
            <?php if (isset($component)) { $__componentOriginal6f6d33b5cdaf6441023f2a08b8c18204 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6f6d33b5cdaf6441023f2a08b8c18204 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.pengumuman-unggulan','data' => ['pengumuman' => $unggulan]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.pengumuman-unggulan'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['pengumuman' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($unggulan)]); ?>
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
        <?php endif; ?>
        <div class="ic-daftar ic-jarak-atas">
            <?php $__currentLoopData = $pengumuman; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginal2a50be00b6dee3cb05f88a98cfdefcaf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a50be00b6dee3cb05f88a98cfdefcaf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.item-pengumuman','data' => ['pengumuman' => $p]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.item-pengumuman'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['pengumuman' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p)]); ?>
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
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php if(!$unggulan && $pengumuman->isEmpty()): ?>
            <div class="ic-panel ic-kosong">
                <b>Belum ada pengumuman.</b>
                <p class="ic-bantuan">Kabar resmi dari WSM akan muncul di sini.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="ic-bagian-kepala">
            <div>
                <p class="ic-eyebrow">Penggemar, foto &amp; cerita</p>
                <h2 class="ic-judul-bagian">Galeri kenangan ♡</h2>
            </div>
            <button type="button" class="ic-tombol" data-ic-modal-buka="modal-kenangan" <?php if($member->email_verified_at === null): echo 'disabled'; endif; ?>>+
                Tambah kenangan</button>
        </div>

        <p class="ic-intro-kenangan"><b>Simpan momen terbaikmu.</b> Bagikan momen favorit, dukung sesama penggemar,
            dan kumpulkan kenangan seru.</p>

        <nav class="ic-chip-daftar" aria-label="Filter kategori kenangan">
            <a class="ic-chip <?php echo e($kategori === 'all' ? 'is-aktif' : ''); ?>"
                href="<?php echo e(route('ic.komunitas', ['tab' => 'galeri'])); ?>"
                <?php if($kategori === 'all'): ?> aria-current="true" <?php endif; ?>>Semua kenangan</a>
            <?php $__currentLoopData = config('inner_circle.kategori_kenangan'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="ic-chip <?php echo e($kategori === $kunci ? 'is-aktif' : ''); ?>"
                    href="<?php echo e(route('ic.komunitas', ['tab' => 'galeri', 'kategori' => $kunci])); ?>"
                    <?php if($kategori === $kunci): ?> aria-current="true" <?php endif; ?>>
                    <?php if (isset($component)) { $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ikon','data' => ['nama' => $item['ikon'],'kelas' => 'ic-ikon--mini']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ikon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['ikon']),'kelas' => 'ic-ikon--mini']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $attributes = $__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__attributesOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b)): ?>
<?php $component = $__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b; ?>
<?php unset($__componentOriginalcfa42609bd5a05d39e3b6fd21eebb83b); ?>
<?php endif; ?> <?php echo e($item['label']); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>

        <div class="ic-grid ic-grid--2 ic-jarak-atas">
            <?php $__empty_1 = true; $__currentLoopData = $kenangan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="ic-panel ic-kosong ic-grid__penuh">
                    <b>Belum ada kenangan di kategori ini.</b>
                    <p class="ic-bantuan">Jadilah yang pertama membagikan momenmu.</p>
                </div>
            <?php endif; ?>
        </div>

        
        <?php if($kenangan->hasPages()): ?>
            <nav class="ic-aksi-baris ic-jarak-atas" aria-label="Halaman galeri">
                <?php if($kenangan->onFirstPage()): ?>
                    <span class="ic-tombol ic-tombol--kecil ic-tombol--terang" aria-disabled="true">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Lebih baru</span>
                <?php else: ?>
                    <a class="ic-tombol ic-tombol--kecil ic-tombol--terang" href="<?php echo e($kenangan->previousPageUrl()); ?>"
                        rel="prev"><i class="bi bi-arrow-left" aria-hidden="true"></i> Lebih baru</a>
                <?php endif; ?>
                <span class="ic-bantuan">Halaman <?php echo e($kenangan->currentPage()); ?> dari <?php echo e($kenangan->lastPage()); ?></span>
                <?php if($kenangan->hasMorePages()): ?>
                    <a class="ic-tombol ic-tombol--kecil" href="<?php echo e($kenangan->nextPageUrl()); ?>" rel="next">Lebih lama
                        <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <?php else: ?>
                    <span class="ic-tombol ic-tombol--kecil ic-tombol--terang" aria-disabled="true">Lebih lama <i
                            class="bi bi-arrow-right" aria-hidden="true"></i></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <p class="ic-catatan">Tim WSM dapat menyembunyikan kenangan yang melanggar aturan komunitas.</p>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('modal'); ?>
    
    <?php if (isset($component)) { $__componentOriginal4299c0fc92bf0a34fa463efd75f94121 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4299c0fc92bf0a34fa463efd75f94121 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.modal','data' => ['id' => 'modal-kenangan','judul' => 'Unggah kenangan ✦','eyebrow' => 'Momenmu, ceritamu','terbuka' => $errors->any()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'modal-kenangan','judul' => 'Unggah kenangan ✦','eyebrow' => 'Momenmu, ceritamu','terbuka' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->any())]); ?>
        <form method="POST" action="<?php echo e(route('ic.kenangan.simpan')); ?>" enctype="multipart/form-data" data-ic-sekali>
            <?php echo csrf_field(); ?>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $galat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <p class="ic-galat" role="alert"><?php echo e($galat); ?></p>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <label class="ic-label" for="kenangan-caption">Apa ceritamu?</label>
            <textarea class="ic-input ic-input--area" id="kenangan-caption" name="caption" maxlength="240" rows="3"
                placeholder="Cerita singkat tentang momen paling berkesan…" required><?php echo e(old('caption')); ?></textarea>

            <div class="ic-baris-2">
                <div>
                    <label class="ic-label" for="kenangan-tempat">Acara / tempat</label>
                    <input class="ic-input" id="kenangan-tempat" name="tempat" maxlength="65" value="<?php echo e(old('tempat')); ?>"
                        placeholder="Contoh: WS Showcase">
                </div>
                <div>
                    <label class="ic-label" for="kenangan-kategori">Kategori</label>
                    <select class="ic-input" id="kenangan-kategori" name="kategori">
                        <?php $__currentLoopData = config('inner_circle.kategori_kenangan'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($kunci); ?>" <?php if(old('kategori') === $kunci): echo 'selected'; endif; ?>><?php echo e($item['label']); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>

            <label class="ic-label" for="kenangan-foto">Tambahkan foto (opsional)</label>
            <input class="ic-input" type="file" id="kenangan-foto" name="foto"
                accept="image/jpeg,image/png,image/webp" data-ic-maks-mb="<?php echo e($maksFotoMb); ?>">
            <p class="ic-bantuan">JPG, PNG, atau WebP. Maksimal <?php echo e($maksFotoMb); ?> MB.</p>

            <div class="ic-aksi-form">
                <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Batal</button>
                <button type="submit" class="ic-tombol">Terbitkan kenangan <i class="bi bi-arrow-right"
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('template.innerCircleLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/komunitas.blade.php ENDPATH**/ ?>