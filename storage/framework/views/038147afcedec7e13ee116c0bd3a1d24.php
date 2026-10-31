
<div
    class="group relative h-full flex flex-col rounded-3xl border border-white/10 bg-white/3 overflow-hidden
    transition-colors hover:border-white/20">
    <div class="flex justify-between items-center gap-4 px-6 py-5 border-b border-white/10 shrink-0">
        <h2 class="flex items-center gap-2 font-bold uppercase tracking-wide text-sm">
            <?php if(isset($icon)): ?>
                <i class="bi <?php echo e($icon); ?> text-white/50" aria-hidden="true"></i>
            <?php endif; ?>
            <?php echo e($title); ?>

        </h2>
        <?php if(isset($href)): ?>
            <a href="<?php echo e($href); ?>"
                class="px-4 py-1.5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors shrink-0">
                Kelola
            </a>
        <?php endif; ?>
    </div>
    <div class="flex-1 min-w-0 <?php echo e($flush ?? false ? 'flex flex-col' : 'p-6'); ?>">
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/dashboard/card/card.blade.php ENDPATH**/ ?>