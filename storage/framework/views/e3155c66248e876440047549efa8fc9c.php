

<?php $__env->startSection('title', 'Check-in'); ?>

<?php
    $adaFilter = count($parameterFilter) > 0;
?>

<?php $__env->startSection('content'); ?>
    <p class="ic-eyebrow">Datang. Kumpulkan stempel.</p>
    <h1 class="ic-judul">Check-in<span>.</span></h1>
    <p class="ic-deskripsi">Jadilah bagian dari perjalanan ini. Temukan kegiatan Inner Circle dan kumpulkan stempel
        ketika hadir.</p>

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

    <section class="ic-filter" aria-labelledby="judul-jadwal">
        <div>
            <h2 class="ic-filter__judul" id="judul-jadwal">Jadwal Pertunjukan WS · Kuartal IV 2026</h2>
            <p class="ic-bantuan">Jadwal berdasarkan rencana WSM dan bersifat tentatif. Tidak semua acara otomatis
                memberi stempel.</p>
        </div>
        <form class="ic-filter__form" method="GET" action="<?php echo e(route('ic.checkin')); ?>" role="search">
            <label class="ic-sr-saja" for="filter-bulan">Bulan</label>
            <select class="ic-input" id="filter-bulan" name="bulan" data-ic-otomatis>
                <option value="all">Okt–Des 2026</option>
                <?php $__currentLoopData = config('inner_circle.bulan_event'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nomor => $nama): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($nomor); ?>" <?php if($filter['bulan'] === (string) $nomor): echo 'selected'; endif; ?>><?php echo e($nama); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <label class="ic-sr-saja" for="filter-tipe">Tipe acara</label>
            <select class="ic-input" id="filter-tipe" name="tipe" data-ic-otomatis>
                <option value="all">Semua tipe</option>
                <?php $__currentLoopData = config('inner_circle.tipe_event'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $tipe): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($kunci); ?>" <?php if($filter['tipe'] === $kunci): echo 'selected'; endif; ?>><?php echo e($tipe['label']); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <label class="ic-sr-saja" for="filter-q">Cari kota atau venue</label>
            <input class="ic-input" type="search" id="filter-q" name="q" value="<?php echo e($filter['q']); ?>"
                placeholder="Cari kota / venue…" maxlength="60">

            <button type="submit" class="ic-tombol ic-tombol--kecil">Terapkan</button>
        </form>
    </section>

    <p class="ic-jumlah" role="status">
        <?php echo e(count($events)); ?> dari <?php echo e($totalEvents); ?> jadwal ditampilkan
        <?php if($adaFilter): ?>
            · <a class="ic-tautan" href="<?php echo e(route('ic.checkin')); ?>">Atur ulang filter</a>
        <?php endif; ?>
    </p>

    <div class="ic-daftar">
        <?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php if (isset($component)) { $__componentOriginald96e93f0fbc95305b6c2d42d983aa137 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald96e93f0fbc95305b6c2d42d983aa137 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.kartu-event','data' => ['event' => $event,'href' => route('ic.checkin', $parameterFilter + ['acara' => $event->kode]) . '#kode-kehadiran','dipilih' => $eventDipilih && $eventDipilih->kode === $event->kode]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.kartu-event'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['event' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event),'href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('ic.checkin', $parameterFilter + ['acara' => $event->kode]) . '#kode-kehadiran'),'dipilih' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($eventDipilih && $eventDipilih->kode === $event->kode)]); ?>
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
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="ic-panel ic-kosong">
                <b>Belum ada jadwal yang cocok.</b>
                <p class="ic-bantuan">Coba ganti bulan, tipe, atau kata pencarian.</p>
            </div>
        <?php endif; ?>
    </div>

    <section class="ic-kotak" id="kode-kehadiran" aria-labelledby="judul-kode">
        <h2 class="ic-kotak__judul" id="judul-kode">✦ Kode kehadiran acara</h2>

        <?php if($eventDipilih): ?>
            <p class="ic-bantuan"><b><?php echo e($eventDipilih->venue); ?></b> · <?php echo e($eventDipilih->kota); ?> ·
                <?php echo e($eventDipilih->tanggal->locale('id')->translatedFormat('D, j M Y')); ?></p>
            <?php if($eventDipilih->sudah_checkin): ?>
                <p class="ic-bantuan"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Kamu sudah check-in di
                    acara
                    ini.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="ic-bantuan"><b>Belum memilih acara.</b> Pilih dari daftar di atas, atau langsung pindai QR di lokasi.
            </p>
        <?php endif; ?>

        <ol class="ic-langkah">
            <li>Pindai QR yang disediakan panitia di lokasi, atau ketik kodenya.</li>
            <li>Tekan Verifikasi. Stempel bertambah satu untuk setiap acara.</li>
            <li>Check-in hanya bisa dilakukan saat acara berlangsung.</li>
        </ol>
        <p class="ic-bantuan">Acara privat tidak tersedia untuk check-in.</p>

        <div class="ic-aksi-baris">
            <button type="button" class="ic-tombol ic-tombol--kecil" data-ic-pindai-buka><i class="bi bi-qr-code-scan"
                    aria-hidden="true"></i> Pindai QR dengan kamera</button>
            <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang" data-ic-pindai-berkas><i
                    class="bi bi-image" aria-hidden="true"></i> Unggah gambar QR</button>
            <input type="file" id="ic-qr-berkas" accept="image/*" hidden>
        </div>

        <form method="POST" action="<?php echo e(route('ic.checkin.proses')); ?>" novalidate>
            <?php echo csrf_field(); ?>
            <?php if($eventDipilih): ?>
                <input type="hidden" name="acara" value="<?php echo e($eventDipilih->kode); ?>">
            <?php endif; ?>
            <label class="ic-label" for="kode">Kode check-in acara</label>
            <input class="ic-input" id="kode" name="kode" value="<?php echo e(old('kode', $kodeIsian)); ?>"
                placeholder="Masukkan kode dari panitia atau hasil pindai QR" autocomplete="off" autocapitalize="characters"
                maxlength="300" required
                <?php if($errors->has('kode') || $galatKode): ?> aria-invalid="true" aria-describedby="kode-galat" <?php endif; ?>>
            <?php if($errors->has('kode')): ?>
                <p class="ic-galat" id="kode-galat" role="alert"><?php echo e($errors->first('kode')); ?></p>
            <?php elseif($galatKode): ?>
                <p class="ic-galat" id="kode-galat" role="alert"><?php echo e($galatKode); ?></p>
            <?php endif; ?>
            <div class="ic-aksi-baris">
                <button type="submit" class="ic-tombol">Verifikasi &amp; dapatkan stempel <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></button>
            </div>
        </form>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('modal'); ?>
    <?php if (isset($component)) { $__componentOriginal4299c0fc92bf0a34fa463efd75f94121 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4299c0fc92bf0a34fa463efd75f94121 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.modal','data' => ['id' => 'modal-pindai','judul' => 'Pindai QR check-in','eyebrow' => 'Arahkan kamera']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'modal-pindai','judul' => 'Pindai QR check-in','eyebrow' => 'Arahkan kamera']); ?>
        <div class="ic-pindai">
            <video id="ic-pindai-video" class="ic-pindai__video" playsinline muted></video>
            <p class="ic-bantuan ic-umpan" id="ic-pindai-umpan" role="status" aria-live="polite"></p>
        </div>
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

<?php echo $__env->make('template.innerCircleLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/checkin.blade.php ENDPATH**/ ?>