<?php

return [

    /*
    | Flag fitur WS Inner Circle.
    | Selama false, semua route /innercircle mengembalikan 404 (lihat middleware IcAktif).
    | Nyalakan lewat INNER_CIRCLE_AKTIF=true di .env (lokal/preview dulu, produksi belakangan).
    */
    'aktif' => (bool) env('INNER_CIRCLE_AKTIF', false),

    /*
    | Isi jadwal contoh (database/data/ic_events_contoh.csv) ke database lewat `php artisan db:seed`.
    | Hanya untuk lokal/preview: di produksi biarkan false dan isi jadwal asli lewat CMS atau impor CSV.
    */
    'seed_contoh' => (bool) env('IC_SEED_CONTOH', false),

    /*
    | Login dengan Google (Fase 8). Tombol dan route hanya hidup bila flag ini true DAN GOOGLE_CLIENT_ID serta
    | GOOGLE_CLIENT_SECRET terisi (lihat App\Services\InnerCircle\GoogleOAuth::tersedia()).
    */
    'google' => [
        'aktif' => (bool) env('INNER_CIRCLE_GOOGLE_AKTIF', false),
    ],

    /* Fase terakhir yang sudah tersambung ke database. Tab dashboard dengan `fase` lebih besar masih memakai data contoh. */
    'fase_tersambung' => 6,

    /* Batas percobaan check-in per anggota per menit (lihat RateLimiter `ic-checkin` di AppServiceProvider). */
    'batas_checkin_per_menit' => 8,

    /*
    | Check-in dengan lokasi. Acara yang punya titik lokasi (dashboard > Acara & QR) hanya bisa check-in bila HP
    | anggota berada di dalam radius titik itu; acara tanpa titik tetap bisa check-in seperti sebelumnya.
    | - radius_bawaan_m: radius bila petugas tidak mengisi (meter), selalu dijepit antara radius_min_m dan radius_maks_m.
    | - akurasi_maks_m: tolak bila HP melaporkan akurasi GPS lebih buruk dari ini (sinyal terlalu kasar untuk dipercaya).
    | - toleransi_akurasi_m: keringanan untuk GPS kasar di dalam gedung. Jarak dikurangi akurasi HP (paling banyak sebesar
    |   angka ini) sebelum dibandingkan dengan radius.
    | - geser_konfirmasi_m: titik yang sudah tersimpan hanya bisa dipindah lebih jauh dari ini bila petugas menegaskannya.
    */
    'lokasi' => [
        'radius_bawaan_m' => 150,
        'radius_min_m' => 30,
        'radius_maks_m' => 2000,
        'akurasi_maks_m' => 150,
        'toleransi_akurasi_m' => 50,
        'geser_konfirmasi_m' => 1000,
    ],

    /* Batas pengajuan hadiah per anggota per menit (RateLimiter `ic-klaim`). Pengaman klik ganda dan skrip. */
    'batas_klaim_per_menit' => 6,

    /*
    | Batas Komunitas (Fase 6), semua per ANGGOTA (RateLimiter di AppServiceProvider).
    | Unggah kenangan dibatasi per menit (salah klik) dan per hari (spam); suka dan foto profil per menit.
    */
    'batas_kenangan_per_menit' => 4,
    'batas_kenangan_per_hari' => 20,
    'batas_suka_per_menit' => 60,
    'batas_foto_per_menit' => 6,

    /*
    | Verifikasi email anggota yang daftar dengan kata sandi. Tautan berlaku `verifikasi_jam` jam.
    | Kirim ulang dibatasi per ANGGOTA (RateLimiter `ic-verifikasi`), dan seluruh situs digabung dibatasi per hari
    | (EmailService::kirimVerifikasi) supaya pendaftaran massal tidak menghabiskan kuota email hosting.
    */
    'verifikasi_jam' => 24,
    'batas_verifikasi_per_menit' => 2,
    'batas_verifikasi_per_hari' => 10,
    'batas_verifikasi_global_per_hari' => 200,

    /* Jumlah kenangan per halaman di galeri anggota dan tab Kenangan dashboard; maksimum daftar pengumuman anggota. */
    'kenangan_per_halaman' => 12,
    'pengumuman_maks_anggota' => 20,

    /*
    | Foto (Fase 6). Batas ukuran berkas dalam KB (dipakai validasi server dan batas di form), sisi terpanjang
    | hasil olahan dalam piksel. Semua foto disimpan ulang sebagai WebP (metadata EXIF, termasuk lokasi GPS, ikut terbuang).
    | Server juga perlu upload_max_filesize dan post_max_size minimal 6M (cPanel: MultiPHP INI Editor).
    */
    'foto_kenangan_maks_kb' => 4096,
    'foto_profil_maks_kb' => 5120,
    'foto_kenangan_sisi_maks' => 1600,
    'foto_profil_sisi_maks' => 512,

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

    /*
    | `ikon` dipakai di chip dan tag kategori; `sampul` dan `warna` dipakai kartu kenangan yang tidak punya foto
    | (warna: .ic-kenangan__sampul.ic-warna-{warna} di CSS anggota).
    */
    'kategori_kenangan' => [
        'festival' => ['label' => 'Festival', 'ikon' => 'circus', 'sampul' => 'fireworks', 'warna' => 'kuning'],
        'club' => ['label' => 'Acara klub', 'ikon' => 'headphone', 'sampul' => 'knobs', 'warna' => 'biru'],
        'personal' => ['label' => 'Momen pribadi', 'ikon' => 'sparkles', 'sampul' => 'ticket', 'warna' => 'pink'],
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
        'email' => ['label' => 'Email', 'ikon' => 'bi-envelope-fill', 'route' => 'ic.admin.email', 'fase' => 6],
    ],

];