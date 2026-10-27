<?php

return [

    /*
    | Semua link merch di website diarahkan ke toko resmi (Mavnus).
    | Ganti lewat MAVNUS_URL di .env kalau domainnya berubah — default sudah benar.
    */
    'mavnus_url' => env('MAVNUS_URL', 'https://mavnus.com'),

    /*
    | Halaman /privasi. Email kontak untuk pertanyaan dan permintaan data; kosong = halaman mengarahkan ke Profil.
    | Isi KONTAK_PRIVASI_EMAIL di .env sebelum rilis. `privasi_diperbarui` (YYYY-MM-DD): ubah tiap isi kebijakan berubah.
    */
    'kontak_privasi' => env('KONTAK_PRIVASI_EMAIL'),
    'privasi_diperbarui' => '2026-10-26',

];