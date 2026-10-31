<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>QR check-in <?php echo e($event->venue); ?> — WS Inner Circle</title>
    
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "DM Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #111322;
            background: #fff;
        }

        .lembar {
            max-width: 720px;
            margin: 0 auto;
            text-align: center;
        }

        .merek {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #5b5870;
        }

        h1 {
            margin: 12px 0 4px;
            font-size: 40px;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .meta {
            margin: 0 0 24px;
            font-size: 18px;
            color: #4a475c;
        }

        .qr {
            width: min(100%, 460px);
            aspect-ratio: 1;
            margin: 0 auto;
        }

        .qr svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .langkah {
            margin: 28px auto 0;
            max-width: 460px;
            font-size: 18px;
            line-height: 1.5;
        }

        .kode-label {
            margin: 24px 0 6px;
            font-size: 14px;
            color: #5b5870;
        }

        .kode {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 12px;
            background: #111322;
            color: #fff;
            font-family: ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: .12em;
        }

        .tautan {
            margin: 20px 0 0;
            font-size: 12px;
            color: #6c6980;
            overflow-wrap: anywhere;
        }

        .alat {
            margin: 0 auto 24px;
            max-width: 720px;
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .alat button {
            min-height: 44px;
            padding: 0 20px;
            border: 0;
            border-radius: 10px;
            background: #111322;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        @media print {
            .alat {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="alat">
        <button type="button" onclick="window.print()">Cetak halaman ini</button>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    <main class="lembar">
        <p class="merek">WS Inner Circle · Tanda check-in</p>
        <h1><?php echo e($event->venue); ?></h1>
        <p class="meta">
            <?php echo e($event->kota ?? 'Kota belum diumumkan'); ?> ·
            <?php echo e($event->tanggal->locale('id')->translatedFormat('l, j F Y')); ?>

        </p>

        <div class="qr" role="img" aria-label="Kode QR check-in untuk <?php echo e($event->venue); ?>">
            <?php echo $qrSvg; ?>

        </div>

        <p class="langkah">Pindai QR ini dengan kamera HP, masuk ke akun Inner Circle, lalu ketuk
            <b>Verifikasi</b> untuk mendapat stempel.
        </p>

        <p class="kode-label">QR tidak bisa dipindai? Ketik kode ini di halaman Check-in:</p>
        <span class="kode"><?php echo e($event->token); ?></span>

        <p class="tautan"><?php echo e($tautanQr); ?></p>
    </main>
</body>

</html>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/pages/dashboard-pages/inner-circle/cetak-qr.blade.php ENDPATH**/ ?>