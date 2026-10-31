
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title><?php echo e($judul); ?></title>
</head>

<body style="margin:0;padding:0;background-color:#f2f0ee;">
    
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#f2f0ee;"><?php echo e($pratinjau); ?></div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f2f0ee;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
                    <tr>
                        <td style="background-color:#5e0006;padding:22px 28px;">
                            <span
                                style="font-size:13px;font-weight:bold;letter-spacing:3px;color:#ffffff;text-transform:uppercase;"><?php echo e($merek ?? 'WS Inner Circle'); ?></span>
                            <br>
                            <span style="font-size:12px;color:#f3c9cc;">Whisnu Santika</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;color:#1a1a1a;font-size:16px;line-height:1.6;">
                            <?php if($tes): ?>
                                <p
                                    style="margin:0 0 18px;padding:10px 14px;background-color:#fff4d6;border-radius:8px;font-size:13px;color:#6b5200;">
                                    Ini email percobaan dari dashboard Inner Circle. Isinya contoh, bukan permintaan
                                    sungguhan.
                                </p>
                            <?php endif; ?>

                            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#1a1a1a;">
                                <?php echo e($judul); ?></h1>
                            <p style="margin:0 0 14px;"><?php echo e($sapaan); ?></p>

                            <?php if(isset($kodeBesar)): ?>
                                <p
                                    style="margin:0 0 18px;padding:16px;background-color:#faf8f6;border-radius:12px;text-align:center;font-size:34px;font-weight:bold;letter-spacing:10px;color:#5e0006;">
                                    <?php echo e($kodeBesar); ?></p>
                            <?php endif; ?>

                            <?php $__currentLoopData = $paragraf; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $baris): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <p style="margin:0 0 14px;"><?php echo e($baris); ?></p>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            <?php if($tombol): ?>
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                    style="margin:22px 0;">
                                    <tr>
                                        <td style="background-color:#5e0006;border-radius:999px;">
                                            <a href="<?php echo e($tombol['url']); ?>"
                                                style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;"><?php echo e($tombol['teks']); ?></a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:0 0 14px;font-size:13px;color:#666666;">
                                    Tombol tidak berfungsi? Salin tautan ini ke browser:<br>
                                    <span style="word-break:break-all;color:#5e0006;"><?php echo e($tombol['url']); ?></span>
                                </p>
                            <?php endif; ?>

                            <?php $__currentLoopData = $catatan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $baris): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <p style="margin:0 0 10px;font-size:13px;color:#666666;"><?php echo e($baris); ?></p>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="padding:18px 28px;background-color:#faf8f6;border-top:1px solid #eeeae6;font-size:12px;line-height:1.5;color:#888888;">
                            Email ini dikirim otomatis oleh <?php echo e($merek ?? 'WS Inner Circle'); ?>. Jangan membalas email
                            ini.<br>
                            <a href="<?php echo e(route('privasi')); ?>" style="color:#5e0006;">Kebijakan Privasi</a>
                            <?php if(filled(config('site.kontak_privasi'))): ?>
                                &middot; Pertanyaan: <a href="mailto:<?php echo e(config('site.kontak_privasi')); ?>"
                                    style="color:#5e0006;"><?php echo e(config('site.kontak_privasi')); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/emails/inner-circle/pesan.blade.php ENDPATH**/ ?>