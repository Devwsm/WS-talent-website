
<div class="flex flex-col items-center justify-center gap-2 py-8 text-center text-white/40">
    <i class="bi bi-inbox text-3xl" aria-hidden="true"></i>
    <p class="text-sm"><?php echo e($message ?? 'Belum ada data.'); ?></p>
    <?php if(isset($cta_href)): ?>
        <a href="<?php echo e($cta_href); ?>"
            class="mt-2 px-4 py-1.5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
            <?php echo e($cta_label ?? 'Tambah sekarang'); ?>

        </a>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/dashboard/card/empty-state.blade.php ENDPATH**/ ?>