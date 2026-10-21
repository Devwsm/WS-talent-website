<?php

return [

    /*
    | Flag fitur WS Inner Circle.
    | Selama false, semua route /inner-circle mengembalikan 404 (lihat middleware IcAktif).
    | Nyalakan lewat INNER_CIRCLE_AKTIF=true di .env (lokal/preview dulu, produksi belakangan).
    */
    'aktif' => (bool) env('INNER_CIRCLE_AKTIF', false),

    /*
    | Ikon gambar ada di public/aset/inner-circle/ikon/{nama}.webp (Fluent Emoji 3D, MIT).
    | Kalau berkasnya belum ada, komponen <x-inner-circle.ikon> jatuh ke emoji di bawah ini.
    */
    'ikon' => [
        'gift' => '🎁',
        'cap' => '🧢',
        'love-letter' => '💌',
        'camera' => '📸',
        'ticket' => '🎟️',
        'circus' => '🎪',
        'headphone' => '🎧',
        'mirror-ball' => '🪩',
        'fireworks' => '🎆',
        'knobs' => '🎛️',
        'sparkles' => '✨',
        'cd' => '💿',
        'megaphone' => '📣',
        'lock' => '🔒',
        'sunglasses' => '😎',
        'ok-hand' => '👌',
    ],

    /*
    | Tipe acara dan kategori kenangan: label, ikon, dan warna kartu ada di satu tempat
    | supaya tampilan anggota, preview CMS, dan filter memakai sumber yang sama.
    */
    'tipe_event' => [
        'festival' => ['label' => 'Festival', 'ikon' => 'circus', 'warna' => 'koral'],
        'club' => ['label' => 'Klub', 'ikon' => 'headphone', 'warna' => 'kuning'],
        'private' => ['label' => 'Acara Privat', 'ikon' => 'lock', 'warna' => 'pink'],
    ],

    'kategori_kenangan' => [
        'festival' => ['label' => 'Festival', 'ikon' => 'circus'],
        'club' => ['label' => 'Acara klub', 'ikon' => 'headphone'],
        'personal' => ['label' => 'Momen pribadi', 'ikon' => 'sparkles'],
    ],

    /*
    | Status pengajuan hadiah (satu set baku untuk anggota dan admin):
    | diajukan -> ditinjau -> disetujui -> selesai, atau ditolak.
    | `warna` dipakai sebagai modifier lencana: .ic-pil--{warna}.
    */
    'status_klaim' => [
        'diajukan' => ['label' => 'Diajukan', 'warna' => 'kuning'],
        'ditinjau' => ['label' => 'Ditinjau', 'warna' => 'kuning'],
        'disetujui' => ['label' => 'Disetujui', 'warna' => 'biru'],
        'selesai' => ['label' => 'Selesai', 'warna' => 'hijau'],
        'ditolak' => ['label' => 'Ditolak', 'warna' => 'merah'],
    ],

    /* Pilihan bulan di filter jadwal Check-in (Kuartal IV). Kunci = nomor bulan. */
    'bulan_event' => [
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ],

];