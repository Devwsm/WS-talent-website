
<?php if($tes): ?>
    [Ini email percobaan dari dashboard Inner Circle]
<?php endif; ?>
<?php echo $judul; ?>


<?php echo $sapaan; ?>

<?php if(isset($kodeBesar)): ?>
    <?php echo $kodeBesar; ?>

<?php endif; ?>

<?php $__currentLoopData = $paragraf; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $baris): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php echo $baris; ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php if($tombol): ?>
    <?php echo $tombol['teks']; ?>: <?php echo $tombol['url']; ?>

<?php endif; ?>
<?php $__currentLoopData = $catatan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $baris): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php echo $baris; ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

--
<?php echo e($merek ?? 'WS Inner Circle'); ?> - Whisnu Santika
Kebijakan Privasi: <?php echo route('privasi'); ?>

<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/emails/inner-circle/pesan-teks.blade.php ENDPATH**/ ?>