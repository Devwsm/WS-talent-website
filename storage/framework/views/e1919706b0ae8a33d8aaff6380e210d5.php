

<?php $__env->startSection('title', 'WS Inner Circle, komunitas penggemar'); ?>
<?php $__env->startSection('deskripsi',
    'Komunitas penggemar resmi DJ Whisnu Santika. Datang ke acara, kumpulkan stempel lewat check-in
    QR, dan buka hadiah eksklusif di setiap tahap.'); ?>

    
<?php $__env->startSection('content'); ?>
    <section class="ic-hero ic-hero--publik" aria-labelledby="judul-hero">
        <p class="ic-eyebrow">Komunitas penggemar resmi</p>
        <h1 class="ic-judul" id="judul-hero">WS Inner Circle<span>.</span></h1>
        <p class="ic-deskripsi">Rumah bagi penggemar Whisnu Santika. Datang ke acaranya, kumpulkan stempel, dan buka
            hadiah eksklusif: dari merchandise sampai pengalaman personal.</p>

        <div class="ic-aksi-baris">
            <?php if($sudahMasuk): ?>
                <a href="<?php echo e(route('ic.beranda')); ?>" class="ic-tombol">Buka Inner Circle <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></a>
            <?php else: ?>
                <a href="<?php echo e(route('ic.daftar')); ?>" class="ic-tombol">Daftar sekarang <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></a>
                <a href="<?php echo e(route('ic.masuk')); ?>" class="ic-tombol ic-tombol--garis">Sudah punya akun? Masuk</a>
            <?php endif; ?>
        </div>
        <span class="ic-hero__bintang" aria-hidden="true">✳</span>
        <span class="ic-hero__cakram" aria-hidden="true"><span>WS</span></span>
    </section>

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">Cara kerjanya</h2>
    </div>
    <ol class="ic-langkah">
        <li>
            <h3>Daftar</h3>
            <p class="ic-bantuan">Buat akun dengan email dan kata sandi, atau langsung dengan akun Google.</p>
        </li>
        <li>
            <h3>Datang ke acara</h3>
            <p class="ic-bantuan">Pindai QR check-in di venue saat Whisnu Santika tampil.</p>
        </li>
        <li>
            <h3>Kumpulkan stempel</h3>
            <p class="ic-bantuan">Setiap acara yang kamu hadiri memberi satu stempel. Stempel tidak pernah berkurang.</p>
        </li>
        <li>
            <h3>Buka hadiah</h3>
            <p class="ic-bantuan">Di setiap tahap stempel, ajukan hadiahmu dan pantau statusnya sampai selesai.</p>
        </li>
    </ol>

    <?php if($hadiah->isNotEmpty()): ?>
        <div class="ic-bagian-kepala">
            <h2 class="ic-judul-bagian">Hadiah di tiap tahap</h2>
        </div>
        <div class="ic-grid ic-grid--2">
            <?php $__currentLoopData = $hadiah; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reward): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginale85949c1824580e84c1e582e710cf398 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale85949c1824580e84c1e582e710cf398 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.ubin-hadiah','data' => ['reward' => $reward,'lebar' => $loop->last && $hadiah->count() % 2 === 1,'href' => $sudahMasuk ? route('ic.hadiah') : route('ic.daftar'),'tampilTag' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.ubin-hadiah'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['reward' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reward),'lebar' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($loop->last && $hadiah->count() % 2 === 1),'href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sudahMasuk ? route('ic.hadiah') : route('ic.daftar')),'tampil-tag' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
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
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">Acara berikutnya</h2>
    </div>
    <?php if($acara->isNotEmpty()): ?>
        <div class="ic-daftar">
            <?php $__currentLoopData = $acara; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginald96e93f0fbc95305b6c2d42d983aa137 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald96e93f0fbc95305b6c2d42d983aa137 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.inner-circle.kartu-event','data' => ['event' => $event,'info' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('inner-circle.kartu-event'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['event' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($event),'info' => true]); ?>
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
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="ic-panel">
            <p class="ic-bantuan">Jadwal acara berikutnya akan diumumkan di sini dan di situs resmi.</p>
        </div>
    <?php endif; ?>

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">Pertanyaan umum</h2>
    </div>
    <div class="ic-faq">
        <details>
            <summary>Apa itu stempel?</summary>
            <p>Stempel adalah bukti kehadiranmu di sebuah acara. Setiap acara memberi satu stempel, dan jumlahnya tidak
                berkurang saat kamu mengajukan hadiah.</p>
        </details>
        <details>
            <summary>Bagaimana cara check-in di acara?</summary>
            <p>Di venue, pindai QR resmi acara dengan kamera HP, lalu masuk ke akunmu. Check-in dibuka pada hari acara dan
                hanya bisa dilakukan satu kali per acara.</p>
        </details>
        <details>
            <summary>Bagaimana cara mengajukan hadiah?</summary>
            <p>Saat stempelmu mencapai target sebuah tahap, buka halaman Hadiah lalu ajukan. Tim WSM akan meninjau dan
                memberi kabar lewat halaman Hadiah dan email.</p>
        </details>
        <details>
            <summary>Bagaimana data saya digunakan?</summary>
            <p>Kami hanya menyimpan data yang dibutuhkan untuk menjalankan komunitas ini. Penjelasan lengkapnya ada di <a
                    class="ic-tautan" href="<?php echo e(route('privasi')); ?>">Kebijakan Privasi</a>, dan kamu bisa menghapus
                akunmu kapan saja dari halaman Profil.</p>
        </details>
    </div>

    <?php if (! ($sudahMasuk)): ?>
        <section class="ic-cta-akhir" aria-labelledby="judul-cta">
            <h2 id="judul-cta">Siap bergabung?</h2>
            <p>Buat akunmu dan mulai kumpulkan stempel di acara berikutnya.</p>
            <div class="ic-aksi-baris">
                <a href="<?php echo e(route('ic.daftar')); ?>" class="ic-tombol ic-tombol--terang">Daftar sekarang</a>
                <a href="<?php echo e(route('ic.masuk')); ?>" class="ic-tombol ic-tombol--garis">Masuk</a>
            </div>
        </section>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.innerCirclePublikLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/inner-circle/tentang.blade.php ENDPATH**/ ?>