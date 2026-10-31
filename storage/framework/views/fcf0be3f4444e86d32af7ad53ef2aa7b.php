
<?php
    $tabs = config('inner_circle.tab_admin');
    $fase = $tabs[$aktif]['fase'];
    // Tab yang fasenya sudah tersambung ke database tidak lagi memakai catatan "data contoh".
    $dataContoh = $fase > config('inner_circle.fase_tersambung');
?>

<?php if($pakaiCss ?? true): ?>
    <?php $__env->startPush('head'); ?>
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">
        <?php echo app('Illuminate\Foundation\Vite')('resources/css/inner-circle.css'); ?>
    <?php $__env->stopPush(); ?>
<?php endif; ?>

<div>
    <p class="text-xs font-semibold uppercase tracking-widest text-white/40 mb-1">WS Inner Circle</p>
    <h1 class="text-2xl lg:text-3xl font-bold uppercase"><?php echo e($judul); ?></h1>
    <p class="text-white/50 mt-1"><?php echo e($deskripsi); ?></p>
</div>


<nav aria-label="Bagian Inner Circle" class="-mx-5 md:mx-0 overflow-x-auto md:overflow-visible">
    <ul class="flex md:flex-wrap gap-2 px-5 md:px-0 w-max md:w-auto">
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kunci => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $dipilih = $kunci === $aktif; ?>
            <li>
                <a href="<?php echo e(route($tab['route'])); ?>" <?php if($dipilih): ?> aria-current="page" <?php endif; ?>
                    class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                    <?php echo e($dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white'); ?>">
                    <i class="bi <?php echo e($tab['ikon']); ?>" aria-hidden="true"></i>
                    <?php echo e($tab['label']); ?>

                    <?php if($kunci === 'klaim' && ($jumlahKlaimMenunggu ?? 0) > 0): ?>
                        <span
                            class="min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[11px] font-bold
                            <?php echo e($dipilih ? 'bg-black text-white' : 'bg-red-950 text-white'); ?>"><?php echo e($jumlahKlaimMenunggu); ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</nav>

<?php if($dataContoh): ?>
    <div
        class="flex items-start gap-3 rounded-2xl border border-amber-300/20 bg-amber-300/5 px-4 py-3 text-sm text-amber-100/80">
        <i class="bi bi-info-circle mt-0.5 shrink-0" aria-hidden="true"></i>
        <p>Tampilan contoh: data di tab ini belum tersimpan dan baru tersambung ke database di <b>Fase
                <?php echo e($fase); ?></b>. Tombol simpan hanya menampilkan pemberitahuan.</p>
    </div>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
    <?php if (! $__env->hasRenderedOnce('673a4d83-62d8-40ab-8aa2-3764039fa10d')): $__env->markAsRenderedOnce('673a4d83-62d8-40ab-8aa2-3764039fa10d'); ?>
        <script>
            // Notifikasi untuk tombol yang belum tersambung ke server (atribut data-ic-segera="pesan").
            window.icSegera = (teks) => window.Toast?.fire({
                icon: 'info',
                title: teks || 'Fitur ini segera aktif.'
            });

            document.addEventListener('click', (e) => {
                const tombol = e.target.closest('[data-ic-segera]:not(form)');
                if (!tombol) return;
                e.preventDefault();
                window.icSegera(tombol.dataset.icSegera);
            });

            document.addEventListener('submit', (e) => {
                const form = e.target.closest('form[data-ic-segera]');
                if (!form) return;
                e.preventDefault();
                window.icSegera(form.dataset.icSegera);
                form.closest('dialog')?.close();
            });

            // Ikon untuk preview: gambar WebP kalau ada, kalau tidak emoji. Peta diisi tiap halaman
            // lewat window.icIkonPeta (dihitung di adminController, logikanya sama dengan komponen ikon anggota).
            window.icBuatIkon = (nama, kelas) => {
                const peta = (window.icIkonPeta || {})[nama] || {};
                if (peta.url) {
                    const img = document.createElement('img');
                    img.src = peta.url;
                    img.alt = '';
                    img.width = 256;
                    img.height = 256;
                    img.decoding = 'async';
                    img.className = 'ic-ikon ' + kelas;
                    return img;
                }
                const span = document.createElement('span');
                span.className = 'ic-ikon ic-ikon--emoji ' + kelas;
                span.setAttribute('aria-hidden', 'true');
                span.textContent = peta.emoji || '✦';
                return span;
            };

            // Tanggal pendek bahasa Indonesia, mis. "22 Okt 2026".
            window.icTanggal = (nilai) => {
                if (!nilai) return '—';
                const d = new Date(nilai + 'T00:00:00');
                return isNaN(d) ? '—' : d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            };
        </script>
    <?php endif; ?>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/dashboard/inner-circle/tabs.blade.php ENDPATH**/ ?>