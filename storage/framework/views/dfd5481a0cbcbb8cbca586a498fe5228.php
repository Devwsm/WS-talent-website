<?php
    $anggotaVerif = auth('member')->user();
?>
<?php if($anggotaVerif && $anggotaVerif->email_verified_at === null): ?>
    <section class="ic-panel ic-jarak-atas" aria-labelledby="verifikasi-judul">
        <h2 class="ic-panel__judul" id="verifikasi-judul">✉ Verifikasi emailmu dulu</h2>
        <p class="ic-bantuan">Check-in, unggah kenangan, suka, dan hadiah baru bisa dipakai setelah emailmu
            terverifikasi.
            Kami mengirim tautan ke <b><?php echo e($anggotaVerif->email); ?></b>; buka tautannya saat kamu sudah masuk. Tidak ada
            email? Cek folder spam, atau kirim ulang.
        </p>
        <form method="POST" action="<?php echo e(route('ic.verifikasi.kirim')); ?>" data-ic-sekali>
            <?php echo csrf_field(); ?>
            <button type="submit" class="ic-tombol ic-tombol--kecil">Kirim ulang email verifikasi</button>
        </form>
    </section>
<?php endif; ?>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/inner-circle/panel-verifikasi.blade.php ENDPATH**/ ?>