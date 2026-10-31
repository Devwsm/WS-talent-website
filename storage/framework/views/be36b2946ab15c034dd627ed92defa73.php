<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <?php
        // Default SEO values dipakai kalau halaman nggak nge-override lewat @section.
        // @section('x', '...') sudah meng-escape isinya (& → &amp;), lalu {{ }} meng-escape lagi
        // sehingga tab browser menampilkan "&amp;" mentah. Decode dulu, biar {{ }} yang escape sekali.
        $seoTitle =
            html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES) ?:
            'Whisnu Santika — DJ & Produser Musik Indonesia';
        $seoDescription =
            html_entity_decode(trim($__env->yieldContent('meta_description')), ENT_QUOTES) ?:
            'Whisnu Santika — DJ & produser asal Jakarta, pionir Indonesian Bounce. Info tur, rilisan musik, merchandise, dan berita terbaru.';
        $seoImage =
            html_entity_decode(trim($__env->yieldContent('og_image')), ENT_QUOTES) ?:
            asset('aset/logo/Whisnu-Santika_Logo-2025-White.png');
    ?>

    <title><?php echo e($seoTitle); ?></title>
    <meta name="description" content="<?php echo e($seoDescription); ?>">
    <link rel="canonical" href="<?php echo e(url()->current()); ?>">

    
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Whisnu Santika">
    <meta property="og:locale" content="id_ID">
    <meta property="og:url" content="<?php echo e(url()->current()); ?>">
    <meta property="og:title" content="<?php echo e($seoTitle); ?>">
    <meta property="og:description" content="<?php echo e($seoDescription); ?>">
    <meta property="og:image" content="<?php echo e($seoImage); ?>">

    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($seoTitle); ?>">
    <meta name="twitter:description" content="<?php echo e($seoDescription); ?>">
    <meta name="twitter:image" content="<?php echo e($seoImage); ?>">

    

    
    <style>
        :root {
            --theme-color: <?php echo e($color ?? '#000000'); ?>;
        }
    </style>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <link rel="icon" href="<?php echo e(asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png')); ?>" type="image/png">

    
    <?php echo $__env->yieldPushContent('schema'); ?>
</head>

<body class="sora bg-black">
    <div id="scrollProgress" class="scroll-progress" aria-hidden="true"></div>
    <main>
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <?php echo $__env->make('components.motion-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>

</html>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/template/layout.blade.php ENDPATH**/ ?>