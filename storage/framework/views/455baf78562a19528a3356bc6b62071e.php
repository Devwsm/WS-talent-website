<?php if($errors->any()): ?>
    <script>
        window.__swalErrors = <?php echo json_encode($errors->all(), 15, 512) ?>;
    </script>
<?php endif; ?>

<?php if(session('error')): ?>
    <script>
        window.__swalError = <?php echo json_encode(session('error'), 15, 512) ?>;
    </script>
<?php endif; ?>

<?php if(session('warning')): ?>
    <script>
        window.__swalWarning = <?php echo json_encode(session('warning'), 15, 512) ?>;
    </script>
<?php endif; ?>

<?php if(session('info')): ?>
    <script>
        window.__swalInfo = <?php echo json_encode(session('info'), 15, 512) ?>;
    </script>
<?php endif; ?>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/errors.blade.php ENDPATH**/ ?>