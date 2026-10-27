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

    /*
    | Akun admin CMS pertama, dibaca oleh database/seeders/accountSeeder. Dipasang di sini (bukan env() di seeder)
    | supaya tetap terbaca bila config di-cache dan bisa diatur lewat config() di tes.
    */
    'admin_username' => env('ADMIN_USERNAME'),
    'admin_password' => env('ADMIN_PASSWORD'),
    'privasi_diperbarui' => '2026-10-26',

];