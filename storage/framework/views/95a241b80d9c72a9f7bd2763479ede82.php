
<?php if($paginator->hasPages()): ?>
    <nav aria-label="Navigasi halaman" class="flex items-center justify-between gap-3 mt-4 flex-wrap">
        <p class="text-xs text-white/40">
            Halaman <?php echo e($paginator->currentPage()); ?> dari <?php echo e($paginator->lastPage()); ?>

            (<?php echo e($paginator->total()); ?> data)
        </p>

        <div class="flex items-center gap-2">
            <?php if($paginator->onFirstPage()): ?>
                <span class="px-3 py-1.5 text-xs rounded-xl border border-white/10 text-white/30 cursor-not-allowed">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i> Sebelumnya
                </span>
            <?php else: ?>
                <a href="<?php echo e($paginator->previousPageUrl()); ?>"
                    class="px-3 py-1.5 text-xs rounded-xl border border-white/10 text-white hover:border-white/30 transition-colors">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i> Sebelumnya
                </a>
            <?php endif; ?>

            <?php if($paginator->hasMorePages()): ?>
                <a href="<?php echo e($paginator->nextPageUrl()); ?>"
                    class="px-3 py-1.5 text-xs rounded-xl border border-white/10 text-white hover:border-white/30 transition-colors">
                    Selanjutnya <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            <?php else: ?>
                <span class="px-3 py-1.5 text-xs rounded-xl border border-white/10 text-white/30 cursor-not-allowed">
                    Selanjutnya <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </span>
            <?php endif; ?>
        </div>
    </nav>
<?php endif; ?>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/dashboard/pagination.blade.php ENDPATH**/ ?>