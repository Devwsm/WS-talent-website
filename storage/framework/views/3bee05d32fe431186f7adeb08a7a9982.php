
<?php $__env->startSection('content'); ?>
    <div class="min-h-screen w-full grid grid-cols-1 lg:grid-cols-2 bg-white">

        
        <div class="relative bg-black text-white flex flex-col justify-between overflow-hidden px-8 py-10 lg:px-16 lg:py-16">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[0.08]"
                style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;">
            </div>
            <div aria-hidden="true"
                class="pointer-events-none absolute -right-24 -top-24 w-72 h-72 rounded-full border border-white/10">
            </div>

            <a href="<?php echo e(route('login')); ?>"
                class="relative z-10 inline-flex items-center gap-2 text-sm text-white/60 hover:text-white transition-colors w-fit">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke login
            </a>

            <div class="relative z-10">
                <h1 class="text-4xl lg:text-6xl font-bold uppercase leading-[0.95] tracking-tight">
                    Whisnu<br>Santika
                </h1>
                <p class="mt-4 text-white/50 text-sm lg:text-base max-w-xs">
                    Satu langkah lagi: masukkan kode yang dikirim ke email admin.
                </p>
            </div>

            <p class="relative z-10 text-xs text-white/30">&copy; <?php echo e(date('Y')); ?> Whisnu Santika. All rights
                reserved.</p>
        </div>

        
        <div class="flex flex-col justify-center items-center px-6 py-12 lg:px-16 bg-white">
            <div class="w-full max-w-sm">
                <h2 class="text-2xl lg:text-3xl font-bold text-black">Kode verifikasi</h2>
                <p class="text-sm text-black/50 mt-1 mb-8">Kami mengirim kode 6 angka ke
                    <b class="text-black/70"><?php echo e($emailSamar); ?></b>. Kode berlaku <?php echo e(config('site.admin_otp_menit')); ?>

                    menit.
                </p>

                <?php echo $__env->make('components/errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('components/success', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <form action="<?php echo e(route('login.otp.proses')); ?>" method="POST" class="flex flex-col gap-5">
                    <?php echo csrf_field(); ?>

                    <div class="flex flex-col gap-1.5">
                        <label for="kode" class="text-sm font-semibold text-black">Kode 6 angka</label>
                        <input type="text" id="kode" name="kode" inputmode="numeric" pattern="[0-9 ]*"
                            maxlength="7" autocomplete="one-time-code" required autofocus placeholder="123456"
                            class="border border-black/20 focus:border-black outline-none p-3 rounded-lg text-black text-center text-2xl tracking-[0.5em] placeholder:text-black/20 transition-colors">
                    </div>

                    <button type="submit"
                        class="w-full text-white font-bold uppercase tracking-wide p-3 bg-black hover:bg-black/80 active:scale-[0.98] transition rounded-lg">
                        Verifikasi
                    </button>
                </form>

                <form action="<?php echo e(route('login.otp.ulang')); ?>" method="POST" class="mt-4 text-center">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-sm text-black/60 underline underline-offset-4 hover:text-black">
                        Kirim ulang kode
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template/dashboardLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/login-otp.blade.php ENDPATH**/ ?>