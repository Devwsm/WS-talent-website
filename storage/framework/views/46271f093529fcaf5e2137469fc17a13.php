<?php if(session('success')): ?>
    <script>
        window.__swalSuccess = <?php echo json_encode(session('success'), 15, 512) ?>;
    </script>
<?php endif; ?>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/success.blade.php ENDPATH**/ ?>