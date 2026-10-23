<?php

return [

    /*
    | Flag fitur WS Inner Circle.
    | Selama false, semua route /inner-circle mengembalikan 404 (lihat middleware IcAktif).
    | Nyalakan lewat INNER_CIRCLE_AKTIF=true di .env (lokal/preview dulu, produksi belakangan).
    */
    'aktif' => (bool) env('INNER_CIRCLE_AKTIF', false),

    /*
    | Isi jadwal contoh (database/data/ic_events_contoh.csv) ke database lewat `php artisan db:seed`.
    | Hanya untuk lokal/preview: di produksi biarkan false dan isi jadwal asli lewat CMS atau impor CSV.
    */
    'seed_contoh' => (bool) env('IC_SEED_CONTOH', false),

    /* Fase terakhir yang sudah tersambung ke database. Tab dashboard dengan `fase` lebih besar masih memakai data contoh. */
    'fase_tersambung' => 5,

    /* Batas percobaan check-in per anggota per menit (lihat RateLimiter `ic-checkin` di AppServiceProvider). */
    'batas_checkin_per_menit' => 8,

    /* Batas pengajuan hadiah per anggota per menit (RateLimiter `ic-klaim`). Pengaman klik ganda dan skrip. */
    'batas_klaim_per_menit' => 6,

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

    /*
    | Pilihan ikon untuk form admin (hadiah dan pengumuman): kunci = nama ikon di atas, nilai = label.
    | Ikon `lock`, `sunglasses`, dan `ok-hand` sengaja tidak ada: dipakai khusus oleh komponen lain.
    */
    'ikon_pilihan' => [
        'gift' => 'Kado',
        'cap' => 'Topi',
        'love-letter' => 'Surat cinta',
        'camera' => 'Kamera',
        'ticket' => 'Tiket',
        'circus' => 'Tenda festival',
        'headphone' => 'Headphone',
        'mirror-ball' => 'Bola disko',
        'fireworks' => 'Kembang api',
        'knobs' => 'Mixer',
        'sparkles' => 'Kilau',
        'cd' => 'Cakram CD',
        'megaphone' => 'Pengeras suara',
    ],

    /* Warna kartu yang boleh dipilih admin. Kunci = modifier .ic-warna-{kunci} di CSS anggota. */
    'warna_kartu' => [
        'koral' => 'Koral',
        'kuning' => 'Kuning',
        'biru' => 'Biru',
        'lilac' => 'Lilac',
        'pink' => 'Pink',
        'hijau' => 'Hijau',
        'merah' => 'Merah',
    ],

    /* Tujuan tombol (CTA) pengumuman: nama route anggota => label di form admin. */
    'cta_pengumuman' => [
        'ic.checkin' => 'Check-in',
        'ic.hadiah' => 'Hadiah',
        'ic.komunitas' => 'Komunitas',
    ],

    /*
    | Alur tombol antrean klaim di dashboard: status sekarang => status berikutnya + label tombol.
    | `ditolak` bisa dipilih dari tiga status yang masih menunggu; `selesai` dan `ditolak` adalah akhir alur.
    | Dipakai ClaimService sebagai satu-satunya aturan perpindahan status.
    */
    'alur_klaim' => [
        'diajukan' => ['berikutnya' => 'ditinjau', 'aksi' => 'Tinjau'],
        'ditinjau' => ['berikutnya' => 'disetujui', 'aksi' => 'Setujui'],
        'disetujui' => ['berikutnya' => 'selesai', 'aksi' => 'Selesaikan'],
    ],

    /*
    | Tab dashboard CMS Inner Circle (urutan = urutan tab). `fase` = fase yang menyambungkan tab
    | ini ke database; sebelum itu tab memakai data contoh dan tombol simpan hanya memberi tahu.
    */
    'tab_admin' => [
        'ringkasan' => ['label' => 'Ringkasan', 'ikon' => 'bi-speedometer2', 'route' => 'ic.admin.ringkasan', 'fase' => 4],
        'anggota' => ['label' => 'Anggota', 'ikon' => 'bi-people-fill', 'route' => 'ic.admin.anggota', 'fase' => 4],
        'acara' => ['label' => 'Acara & QR', 'ikon' => 'bi-qr-code', 'route' => 'ic.admin.acara', 'fase' => 4],
        'klaim' => ['label' => 'Klaim', 'ikon' => 'bi-inbox-fill', 'route' => 'ic.admin.klaim', 'fase' => 5],
        'hadiah' => ['label' => 'Hadiah', 'ikon' => 'bi-gift-fill', 'route' => 'ic.admin.hadiah', 'fase' => 5],
        'pengumuman' => ['label' => 'Pengumuman', 'ikon' => 'bi-megaphone-fill', 'route' => 'ic.admin.pengumuman', 'fase' => 6],
        'kenangan' => ['label' => 'Kenangan', 'ikon' => 'bi-images', 'route' => 'ic.admin.kenangan', 'fase' => 6],
    ],

];