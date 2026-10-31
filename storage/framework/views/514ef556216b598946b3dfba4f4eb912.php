<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Whisnu Santika</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js']); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>

<body class="dash-theme bg-black text-white flex flex-col w-full sora">
    <div class="flex flex-col justify-center items-center">
        <?php echo $__env->yieldContent('content'); ?>
    </div>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/template/dashboardLayout.blade.php ENDPATH**/ ?>