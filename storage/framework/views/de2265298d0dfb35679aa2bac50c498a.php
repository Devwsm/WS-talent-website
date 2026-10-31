

<?php $__env->startSection('title', 'Masuk'); ?>

<?php $__env->startSection('content'); ?>
    <h1 class="ic-auth__judul">Selamat datang kembali</h1>
    <p class="ic-auth__intro">Masuk dengan akun yang sudah terdaftar.</p>

    <?php if (isset($component)) { $__componentOriginal26783ddc8c3dfdcfed5f578f56e9bdb1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal26783ddc8c3dfdcfed5f578f56e9bdb1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.tombol-google','data' => ['teks' => 'Lanjutkan dengan Google']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.tombol-google'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['teks' => 'Lanjutkan dengan Google']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal26783ddc8c3dfdcfed5f578f56e9bdb1)): ?>
<?php $attributes = $__attributesOriginal26783ddc8c3dfdcfed5f578f56e9bdb1; ?>
<?php unset($__attributesOriginal26783ddc8c3dfdcfed5f578f56e9bdb1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal26783ddc8c3dfdcfed5f578f56e9bdb1)): ?>
<?php $component = $__componentOriginal26783ddc8c3dfdcfed5f578f56e9bdb1; ?>
<?php unset($__componentOriginal26783ddc8c3dfdcfed5f578f56e9bdb1); ?>
<?php endif; ?>

    <form method="POST" action="<?php echo e(route('ic.masuk.proses')); ?>" novalidate>
        <?php echo csrf_field(); ?>

        <label class="ic-label" for="email">Email</label>
        <input class="ic-input" type="email" id="email" name="email" value="<?php echo e(old('email')); ?>"
            placeholder="nama@email.com" autocomplete="username" required
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" aria-describedby="email-galat" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="ic-galat" id="email-galat" role="alert"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <label class="ic-label" for="sandi">Kata sandi</label>
        <input class="ic-input" type="password" id="sandi" name="sandi" placeholder="Masukkan kata sandi"
            autocomplete="current-password" required
            <?php $__errorArgs = ['sandi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" aria-describedby="sandi-galat" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
        <?php $__errorArgs = ['sandi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="ic-galat" id="sandi-galat" role="alert"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <label class="ic-centang">
            <input type="checkbox" data-ic-tampil-sandi="#sandi">
            Tampilkan kata sandi
        </label>

        <p class="ic-bantuan"><a class="ic-tautan" href="<?php echo e(route('ic.sandi.lupa')); ?>">Lupa kata sandi?</a></p>

        <button type="submit" class="ic-tombol ic-tombol--penuh">Masuk <i class="bi bi-arrow-right"
                aria-hidden="true"></i></button>
    </form>

    <p class="ic-auth__alih">Belum punya akun? <a href="<?php echo e(route('ic.daftar')); ?>">Daftar sekarang</a></p>
    <p class="ic-bantuan ic-auth__privasi">Dengan masuk, kamu menyetujui <a class="ic-tautan" href="<?php echo e(route('privasi')); ?>"
            target="_blank" rel="noopener">Kebijakan Privasi</a> kami.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.innerCircleAuthLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/masuk.blade.php ENDPATH**/ ?>