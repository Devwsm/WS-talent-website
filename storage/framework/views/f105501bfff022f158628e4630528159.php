

<?php $__env->startSection('title', 'Profil'); ?>

<?php $__env->startSection('content'); ?>
    <p class="ic-eyebrow">Perjalanan Inner Circle-mu</p>
    <h1 class="ic-judul">Profil Saya<span>.</span></h1>
    <p class="ic-deskripsi"><?php echo e($member->nama); ?> · <?php echo e($member->kode_member); ?> · <?php echo e($member->email); ?></p>

    <section class="ic-profil-editor" aria-labelledby="judul-foto">
        <span class="ic-profil-foto" id="ic-foto-pratinjau"
            data-ic-foto-awal="<?php echo e($member->avatar ? \Illuminate\Support\Facades\Storage::url($member->avatar) : ''); ?>"
            data-ic-emoji="<?php echo e($member->avatar_emoji); ?>">
            <?php if($member->avatar): ?>
                <img src="<?php echo e(\Illuminate\Support\Facades\Storage::url($member->avatar)); ?>" alt="Foto profilmu saat ini">
            <?php else: ?>
                <span aria-hidden="true"><?php echo e($member->avatar_emoji); ?></span>
            <?php endif; ?>
        </span>
        <div class="ic-profil-editor__isi">
            <h2 class="ic-panel__judul" id="judul-foto">Foto profilmu ✦</h2>
            <p class="ic-bantuan">Sesuaikan akun Inner Circle-mu. Pilih foto JPG, PNG, atau WebP (maksimal
                <?php echo e($maksFotoMb); ?> MB).</p>

            
            <form method="POST" action="<?php echo e(route('ic.profil.foto')); ?>" enctype="multipart/form-data" id="ic-foto-form"
                data-ic-sekali>
                <?php echo csrf_field(); ?>
                <input type="file" id="ic-foto-input" name="foto" accept="image/jpeg,image/png,image/webp"
                    data-ic-maks-mb="<?php echo e($maksFotoMb); ?>" hidden>
            </form>
            <?php if($member->avatar): ?>
                <form method="POST" action="<?php echo e(route('ic.profil.foto.hapus')); ?>" id="ic-foto-hapus-form"
                    data-ic-konfirmasi data-ic-sekali data-ic-konfirmasi-judul="Hapus foto profil?"
                    data-ic-konfirmasi-tombol="Ya, hapus" data-judul="Kamu bisa mengunggah foto baru kapan saja.">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                </form>
            <?php endif; ?>

            <div class="ic-aksi-baris ic-aksi-baris--rapat">
                <button type="button" class="ic-tombol ic-tombol--kecil" id="ic-foto-pilih"><i class="bi bi-camera-fill"
                        aria-hidden="true"></i> Pilih foto</button>
                <button type="submit" form="ic-foto-form" class="ic-tombol ic-tombol--kecil" id="ic-foto-simpan"
                    disabled>Simpan foto</button>
                <button type="submit" form="ic-foto-hapus-form" class="ic-tombol ic-tombol--kecil ic-tombol--terang"
                    <?php if(!$member->avatar): echo 'disabled'; endif; ?>>Hapus</button>
            </div>
            <p class="ic-bantuan ic-umpan" id="ic-foto-umpan" role="status" aria-live="polite"></p>
            <?php $__errorArgs = ['foto'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="ic-galat" role="alert"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </section>

    <div class="ic-metrik-grid">
        <div class="ic-metrik">
            <strong><?php echo e($metrik->total_stempel); ?></strong>
            <small>Total stempel</small>
        </div>
        <div class="ic-metrik">
            <strong><?php echo e($metrik->tahap_terbuka); ?></strong>
            <small>Tahapan hadiah terbuka</small>
        </div>
        <div class="ic-metrik">
            <strong><?php echo e($metrik->jumlah_klaim); ?></strong>
            <small>Pengajuan hadiah</small>
        </div>
    </div>

    <h2 class="ic-judul-bagian" id="riwayat">Riwayat stempel</h2>
    <div class="ic-panel ic-daftar ic-daftar--rapat">
        <?php $__empty_1 = true; $__currentLoopData = $riwayat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php if (isset($component)) { $__componentOriginal8f8d093d6c36a8943ef3d6da21e2e865 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8f8d093d6c36a8943ef3d6da21e2e865 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.baris-aktivitas','data' => ['aktivitas' => $a]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.baris-aktivitas'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['aktivitas' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($a)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8f8d093d6c36a8943ef3d6da21e2e865)): ?>
<?php $attributes = $__attributesOriginal8f8d093d6c36a8943ef3d6da21e2e865; ?>
<?php unset($__attributesOriginal8f8d093d6c36a8943ef3d6da21e2e865); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8f8d093d6c36a8943ef3d6da21e2e865)): ?>
<?php $component = $__componentOriginal8f8d093d6c36a8943ef3d6da21e2e865; ?>
<?php unset($__componentOriginal8f8d093d6c36a8943ef3d6da21e2e865); ?>
<?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="ic-bantuan">Belum ada stempel. Datang ke acara dan check-in untuk mulai mengumpulkan.</p>
        <?php endif; ?>
    </div>

    <h2 class="ic-judul-bagian">Pengajuan hadiah</h2>
    <div class="ic-panel ic-klaim-daftar">
        <?php $__empty_1 = true; $__currentLoopData = $klaim; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $status = config('inner_circle.status_klaim.' . $k->status); ?>
            <div class="ic-klaim-baris">
                <div>
                    <h3><?php echo e($k->target_stempel); ?> stempel · <?php echo e($k->judul); ?></h3>
                    <p class="ic-bantuan"><?php echo e($k->opsi ?? 'Hadiah tahapan'); ?> ·
                        <?php echo e($k->diajukan_pada->locale('id')->translatedFormat('j M Y')); ?></p>
                    <?php if($k->catatan_admin): ?>
                        <p class="ic-bantuan"><b>Catatan WSM:</b> <?php echo e($k->catatan_admin); ?></p>
                    <?php endif; ?>
                </div>
                <span class="ic-pil ic-pil--<?php echo e($status['warna']); ?>"><?php echo e($status['label']); ?></span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="ic-bantuan">Belum ada pengajuan. Buka <a class="ic-tautan" href="<?php echo e(route('ic.hadiah')); ?>">Hadiah</a>
                untuk mengajukan tahapan yang sudah terbuka.</p>
        <?php endif; ?>
    </div>

    <?php
        $punyaSandi = $member->password !== null;
        $klaimMenunggu = app(\App\Services\InnerCircle\HapusAkunService::class)->adaKlaimMenunggu($member);
    ?>

    <h2 class="ic-judul-bagian">Akun dan privasi</h2>
    <div class="ic-panel ic-akun">
        <p class="ic-bantuan">Datamu hanya dipakai untuk Inner Circle.
            <a class="ic-tautan" href="<?php echo e(route('privasi')); ?>" target="_blank" rel="noopener">Baca Kebijakan Privasi</a>.
        </p>
        <?php $__errorArgs = ['akun'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="ic-galat ic-galat--blok" role="alert"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <button type="button" class="ic-tombol ic-tombol--bahaya" data-ic-modal-buka="modal-hapus-akun">Hapus
            akunku</button>
    </div>

    <?php if (isset($component)) { $__componentOriginal4299c0fc92bf0a34fa463efd75f94121 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4299c0fc92bf0a34fa463efd75f94121 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.modal','data' => ['id' => 'modal-hapus-akun','judul' => 'Hapus akunmu?','eyebrow' => 'Tidak bisa dibatalkan','terbuka' => $errors->hasAny(['konfirmasi', 'sandi'])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'modal-hapus-akun','judul' => 'Hapus akunmu?','eyebrow' => 'Tidak bisa dibatalkan','terbuka' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->hasAny(['konfirmasi', 'sandi']))]); ?>
        <?php if($klaimMenunggu): ?>
            <p class="ic-bantuan">Kamu masih punya pengajuan hadiah yang belum selesai. Akun bisa dihapus setelah
                pengajuannya selesai atau ditolak, supaya hadiahmu tidak hilang di tengah proses.</p>
            <div class="ic-aksi-form">
                <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Mengerti</button>
            </div>
        <?php else: ?>
            <form method="POST" action="<?php echo e(route('ic.akun.hapus')); ?>" data-ic-sekali novalidate>
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <p class="ic-bantuan">Yang ikut terhapus permanen: akun dan foto profilmu, riwayat check-in dan stempel,
                    pengajuan hadiah, serta kenangan beserta fotonya dan tanda suka.</p>

                <?php if($punyaSandi): ?>
                    <label class="ic-label" for="hapus-sandi">Kata sandi saat ini</label>
                    <input class="ic-input" type="password" id="hapus-sandi" name="sandi" autocomplete="current-password"
                        required <?php $__errorArgs = ['sandi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                    <?php $__errorArgs = ['sandi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="ic-galat" role="alert"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php endif; ?>

                <label class="ic-label" for="hapus-konfirmasi">Ketik <b>HAPUS</b> untuk melanjutkan</label>
                <input class="ic-input" type="text" id="hapus-konfirmasi" name="konfirmasi" autocomplete="off"
                    autocapitalize="characters" required <?php $__errorArgs = ['konfirmasi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                <?php $__errorArgs = ['konfirmasi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="ic-galat" role="alert"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                <div class="ic-aksi-form">
                    <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Batal</button>
                    <button type="submit" class="ic-tombol ic-tombol--bahaya-penuh">Hapus akun permanen</button>
                </div>
            </form>
        <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.innerCircleLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/profil.blade.php ENDPATH**/ ?>