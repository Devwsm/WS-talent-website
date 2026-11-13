<?php

/*
 * Pengaturan keamanan tingkat aplikasi.
 *
 * Content-Security-Policy (CSP): daftar sumber yang boleh dimuat halaman. Mode:
 *   off      tidak mengirim header (bawaan di lokal/tes).
 *   report   mengirim Content-Security-Policy-Report-Only: tidak memblokir apa pun, hanya melaporkan pelanggaran ke
 *            /csp-report yang tampil di halaman Log dashboard sebagai "CSP:<aturan>" (bawaan di production).
 *   enforce  mengirim Content-Security-Policy yang benar-benar memblokir. Pakai setelah laporan bersih beberapa hari.
 * Ubah lewat CSP_MODE di .env (jalankan `php artisan optimize` lagi bila config sudah di-cache).
 *
 * Catatan jujur: halaman ini memakai skrip dan gaya inline di banyak tempat, jadi 'unsafe-inline' masih diperlukan.
 * Kebijakan ini sudah menutup sumber luar yang tidak dikenal, <object>, <base>, pengiriman form ke situs lain,
 * dan penyematan situs di situs lain, tapi BELUM menghentikan skrip inline yang disuntikkan. Langkah berikutnya
 * (tidak mendesak): beri nonce pada semua <script> lalu hapus 'unsafe-inline' dari script-src.
 */
return [
    'csp' => [
        // `?:` (bukan nilai bawaan env) supaya `CSP_MODE=` yang dibiarkan kosong di .env tetap memakai bawaan.
        'mode' => env('CSP_MODE') ?: (env('APP_ENV') === 'production' ? 'report' : 'off'),

        // Batas jumlah baris laporan CSP di tabel log_errors, supaya badai laporan tidak memenuhi database.
        'laporan_maks' => 300,

        'direktif' => [
            'default-src' => ["'self'"],
            // Widget Bandsintown dimuat dari widgetv3.bandsintown.com, lalu mengambil data acara lewat skrip dari
            // rest.bandsintown.com (terlihat di laporan CSP pertama di production), jadi seluruh subdomainnya diizinkan.
            'script-src' => ["'self'", "'unsafe-inline'", 'https://*.bandsintown.com'],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://*.bandsintown.com'],
            'font-src' => ["'self'", 'data:', 'https://fonts.gstatic.com', 'https://*.bandsintown.com'],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'media-src' => ["'self'", 'blob:'],
            'connect-src' => ["'self'", 'https://*.bandsintown.com'],
            'frame-src' => ["'self'", 'https://*.bandsintown.com'],
            'worker-src' => ["'self'", 'blob:'],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ],
    ],
];