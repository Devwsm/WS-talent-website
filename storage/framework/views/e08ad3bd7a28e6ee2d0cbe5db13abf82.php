

<?php $__env->startSection('title', 'Daftar'); ?>

<?php $__env->startSection('content'); ?>
    <h1 class="ic-auth__judul">Gabung WS Inner Circle</h1>
    <p class="ic-auth__intro">Buat akun untuk mulai mengumpulkan stempel dan membuka hadiah.</p>

    <?php if (isset($component)) { $__componentOriginal26783ddc8c3dfdcfed5f578f56e9bdb1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal26783ddc8c3dfdcfed5f578f56e9bdb1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.tombol-google','data' => ['teks' => 'Daftar dengan Google']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.tombol-google'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['teks' => 'Daftar dengan Google']); ?>
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

    <form method="POST" action="<?php echo e(route('ic.daftar.proses')); ?>" novalidate>
        <?php echo csrf_field(); ?>

        <label class="ic-label" for="nama">Nama lengkap</label>
        <input class="ic-input" type="text" id="nama" name="nama" value="<?php echo e(old('nama')); ?>"
            placeholder="Nama lengkap" autocomplete="name" maxlength="100" required
            <?php $__errorArgs = ['nama'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" aria-describedby="nama-galat" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
        <?php $__errorArgs = ['nama'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="ic-galat" id="nama-galat" role="alert"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <label class="ic-label" for="email">Email</label>
        <input class="ic-input" type="email" id="email" name="email" value="<?php echo e(old('email')); ?>"
            placeholder="nama@email.com" autocomplete="email" required
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
        <input class="ic-input" type="password" id="sandi" name="sandi" placeholder="Minimal 8 karakter"
            autocomplete="new-password" minlength="8" required
            aria-describedby="sandi-bantuan<?php echo e($errors->has('sandi') ? ' sandi-galat' : ''); ?>"
            <?php $__errorArgs = ['sandi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
        <p class="ic-bantuan" id="sandi-bantuan">Minimal 8 karakter, memuat huruf besar, huruf kecil, dan angka. Simbol
            boleh, emoji tidak.</p>
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

        <label class="ic-label" for="sandi_confirmation">Ulangi kata sandi</label>
        <input class="ic-input" type="password" id="sandi_confirmation" name="sandi_confirmation"
            placeholder="Ulangi kata sandi" autocomplete="new-password" required>

        <label class="ic-centang">
            <input type="checkbox" data-ic-tampil-sandi="#sandi, #sandi_confirmation">
            Tampilkan kata sandi
        </label>

        <button type="submit" class="ic-tombol ic-tombol--penuh">Buat akun <i class="bi bi-arrow-right"
                aria-hidden="true"></i></button>
    </form>

    <p class="ic-auth__alih">Sudah punya akun? <a href="<?php echo e(route('ic.masuk')); ?>">Masuk</a></p>
    <p class="ic-bantuan ic-auth__privasi">Dengan mendaftar, kamu menyetujui <a class="ic-tautan"
            href="<?php echo e(route('privasi')); ?>" target="_blank" rel="noopener">Kebijakan Privasi</a> kami.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.innerCircleAuthLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/daftar.blade.php ENDPATH**/ ?>