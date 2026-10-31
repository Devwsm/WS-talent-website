
<?php $__env->startSection('content'); ?>
    <div class="min-h-screen flex flex-col uppercase text-white items-center justify-center text-center px-4">
        <h1 class="text-8xl font-bold">404</h1>
        <p class="text-2xl font-semibold mt-4">Halaman tidak ditemukan</p>
        <p class="mt-2">Halaman yang kamu cari tidak ada atau sudah dipindahkan.</p>
        <a href="<?php echo e(route('home')); ?>"
            class="mt-6 px-4 py-2 bg-white text-black rounded-lg font-semibold hover:bg-gray-100 transition">
            Kembali ke Beranda
        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/errors/404.blade.php ENDPATH**/ ?>