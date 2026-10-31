

<?php $__env->startSection('title', 'Kata sandi baru'); ?>

<?php $__env->startSection('content'); ?>
    <h1 class="ic-auth__judul">Buat kata sandi baru</h1>
    <p class="ic-auth__intro">Pilih kata sandi baru untuk akun WS Inner Circle kamu.</p>

    <form method="POST" action="<?php echo e(route('ic.sandi.atur.proses')); ?>" novalidate>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo e($token); ?>">

        <label class="ic-label" for="email">Email</label>
        <input class="ic-input" type="email" id="email" name="email" value="<?php echo e(old('email', $email)); ?>"
            autocomplete="username" required <?php $__errorArgs = ['email'];
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

        <label class="ic-label" for="sandi">Kata sandi baru</label>
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

        <button type="submit" class="ic-tombol ic-tombol--penuh">Simpan kata sandi <i class="bi bi-arrow-right"
                aria-hidden="true"></i></button>
    </form>

    <p class="ic-auth__alih">Tautan bermasalah? <a href="<?php echo e(route('ic.sandi.lupa')); ?>">Minta tautan baru</a></p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.innerCircleAuthLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/atur-ulang.blade.php ENDPATH**/ ?>