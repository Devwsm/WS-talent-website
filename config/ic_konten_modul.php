<?php

/*
|--------------------------------------------------------------------------
| Modul tab Konten di CMS Inner Circle
|--------------------------------------------------------------------------
| Sama seperti CMS Artis (halaman Profile: Hero, Genre, Bio, ...), tiap bagian tab Konten dipecah menjadi modul kecil.
| Satu modul = satu topik: formulir kecil di kiri, pratinjau replika topik itu di kanan.
|
| Kunci tingkat atas = kode grup di config/ic_konten.php (`grup`). Isi tiap modul:
| - label  : judul modul
| - ikon   : Bootstrap Icons
| - kunci  : kunci teks (config/ic_konten.php `kunci`) yang diedit di formulir modul ini. Kosong = modul tanpa formulir
|            (hanya daftar berurutan).
| - daftar : kode daftar berurutan (langkah, FAQ, poin teaser) yang dikelola di modul ini
| - fokus  : kunci teks yang ditampilkan di pratinjau bila modul tidak punya form; bawaannya kunci pertama modul.
|            Wajib diisi untuk modul tanpa kunci.
|
| Aturan yang dijaga tes (InnerCircleKontenBagianTest): setiap kunci teks ada di TEPAT SATU modul, setiap daftar grup
| terpasang di satu modul, dan setiap fokus adalah kunci yang nyata. Kunci baru di config/ic_konten.php = tambahkan juga
| ke satu modul di sini.
*/

return [

    'tentang' => [
        'meta' => [
            'label' => 'Tab browser dan pencarian',
            'ikon' => 'bi-search',
            'kunci' => ['tentang.meta.judul', 'tentang.meta.deskripsi'],
        ],
        'hero' => [
            'label' => 'Hero dan tombol',
            'ikon' => 'bi-stars',
            'kunci' => ['tentang.hero.eyebrow', 'tentang.hero.judul', 'tentang.hero.deskripsi', 'tentang.tombol.daftar', 'tentang.tombol.masuk', 'tentang.tombol.buka'],
        ],
        'langkah' => [
            'label' => 'Cara kerja',
            'ikon' => 'bi-list-ol',
            'kunci' => ['tentang.langkah.judul'],
            'daftar' => 'langkah',
        ],
        'hadiah' => [
            'label' => 'Judul bagian hadiah',
            'ikon' => 'bi-gift',
            'kunci' => ['tentang.hadiah.judul'],
        ],
        'acara' => [
            'label' => 'Acara berikutnya',
            'ikon' => 'bi-calendar-event',
            'kunci' => ['tentang.acara.judul', 'tentang.acara.kosong'],
        ],
        'faq' => [
            'label' => 'Tanya jawab',
            'ikon' => 'bi-question-circle',
            'kunci' => ['tentang.faq.judul'],
            'daftar' => 'faq',
        ],
        'cta' => [
            'label' => 'Ajakan akhir',
            'ikon' => 'bi-megaphone',
            'kunci' => ['tentang.cta.judul', 'tentang.cta.deskripsi', 'tentang.cta.masuk'],
        ],
    ],

    'teaser' => [
        'isi' => [
            'label' => 'Judul dan penjelasan',
            'ikon' => 'bi-card-text',
            'kunci' => ['teaser.eyebrow', 'teaser.judul', 'teaser.deskripsi'],
        ],
        'tombol' => [
            'label' => 'Tombol dan tautan',
            'ikon' => 'bi-cursor',
            'kunci' => ['teaser.tombol.gabung', 'teaser.tombol.masuk', 'teaser.tombol.buka', 'teaser.tautan'],
        ],
        'poin' => [
            'label' => 'Poin keunggulan',
            'ikon' => 'bi-list-check',
            'kunci' => [],
            'daftar' => 'teaser_poin',
            'fokus' => 'teaser.judul',
        ],
    ],

    'beranda' => [
        'sapaan' => [
            'label' => 'Sapaan',
            'ikon' => 'bi-chat-heart',
            'kunci' => ['beranda.eyebrow', 'beranda.salam', 'beranda.terima_kasih', 'beranda.stiker'],
        ],
        'aktivitas' => [
            'label' => 'Aktivitas terbaru',
            'ikon' => 'bi-activity',
            'kunci' => ['beranda.aktivitas.judul', 'beranda.aktivitas.kosong'],
        ],
        'kartu' => [
            'label' => 'Kartu anggota',
            'ikon' => 'bi-person-vcard',
            'kunci' => ['beranda.kartu.label'],
        ],
        'stempel' => [
            'label' => 'Progres stempel',
            'ikon' => 'bi-stars',
            'kunci' => ['beranda.stempel.sisa', 'beranda.stempel.selesai', 'beranda.stempel.catatan'],
        ],
    ],

    'checkin' => [
        'judul' => [
            'label' => 'Judul halaman',
            'ikon' => 'bi-type-h1',
            'kunci' => ['checkin.eyebrow', 'checkin.deskripsi'],
        ],
        'jadwal' => [
            'label' => 'Kartu jadwal',
            'ikon' => 'bi-calendar-event',
            'kunci' => ['checkin.jadwal.judul', 'checkin.jadwal.deskripsi'],
        ],
        'lokasi' => [
            'label' => 'Kotak sudah di lokasi',
            'ikon' => 'bi-geo-alt',
            'kunci' => ['checkin.lokasi.judul', 'checkin.lokasi.deskripsi'],
        ],
    ],

    'hadiah' => [
        'judul' => [
            'label' => 'Penjelasan halaman',
            'ikon' => 'bi-type-h1',
            'kunci' => ['hadiah.deskripsi'],
        ],
        'klaim' => [
            'label' => 'Bagian klaim',
            'ikon' => 'bi-inbox',
            'kunci' => ['hadiah.klaim.judul'],
        ],
        'cara' => [
            'label' => 'Cara kerja hadiah',
            'ikon' => 'bi-info-circle',
            'kunci' => ['hadiah.cara.judul', 'hadiah.cara.deskripsi'],
        ],
    ],

    'komunitas' => [
        'judul' => [
            'label' => 'Penjelasan halaman',
            'ikon' => 'bi-type-h1',
            'kunci' => ['komunitas.deskripsi'],
        ],
        'banner' => [
            'label' => 'Banner',
            'ikon' => 'bi-image',
            'kunci' => ['komunitas.banner.stiker', 'komunitas.banner.judul', 'komunitas.banner.deskripsi', 'komunitas.banner.tombol'],
        ],
        'pengumuman' => [
            'label' => 'Pengumuman',
            'ikon' => 'bi-megaphone',
            'kunci' => ['komunitas.pengumuman.eyebrow', 'komunitas.pengumuman.judul', 'komunitas.pengumuman.pil', 'komunitas.unggulan.label'],
        ],
        'pengumuman-kosong' => [
            'label' => 'Pengumuman kosong',
            'ikon' => 'bi-megaphone',
            'kunci' => ['komunitas.pengumuman.kosong.judul', 'komunitas.pengumuman.kosong.deskripsi'],
        ],
        'galeri' => [
            'label' => 'Galeri kenangan',
            'ikon' => 'bi-images',
            'kunci' => ['komunitas.galeri.eyebrow', 'komunitas.galeri.judul', 'komunitas.galeri.tombol', 'komunitas.galeri.intro_tebal', 'komunitas.galeri.intro', 'komunitas.galeri.catatan'],
        ],
        'galeri-kosong' => [
            'label' => 'Galeri kosong',
            'ikon' => 'bi-images',
            'kunci' => ['komunitas.galeri.kosong.judul', 'komunitas.galeri.kosong.deskripsi'],
        ],
    ],

    'profil' => [
        'judul' => [
            'label' => 'Judul halaman',
            'ikon' => 'bi-type-h1',
            'kunci' => ['profil.eyebrow'],
        ],
        'foto' => [
            'label' => 'Foto profil',
            'ikon' => 'bi-camera',
            'kunci' => ['profil.foto.judul', 'profil.foto.deskripsi'],
        ],
        'riwayat' => [
            'label' => 'Riwayat stempel',
            'ikon' => 'bi-clock-history',
            'kunci' => ['profil.riwayat.judul', 'profil.riwayat.kosong'],
        ],
        'klaim' => [
            'label' => 'Pengajuan hadiah',
            'ikon' => 'bi-inbox',
            'kunci' => ['profil.klaim.judul', 'profil.klaim.kosong'],
        ],
        'akun' => [
            'label' => 'Akun dan privasi',
            'ikon' => 'bi-shield-lock',
            'kunci' => ['profil.akun.judul', 'profil.akun.deskripsi'],
        ],
    ],

    'masuk' => [
        'masuk' => [
            'label' => 'Halaman Masuk',
            'ikon' => 'bi-box-arrow-in-right',
            'kunci' => ['masuk.judul', 'masuk.intro'],
        ],
        'daftar' => [
            'label' => 'Halaman Daftar',
            'ikon' => 'bi-person-plus',
            'kunci' => ['daftar.judul', 'daftar.intro'],
        ],
        'lupa' => [
            'label' => 'Halaman Lupa sandi',
            'ikon' => 'bi-question-circle',
            'kunci' => ['lupa.judul', 'lupa.intro'],
        ],
        'atur' => [
            'label' => 'Halaman Atur ulang sandi',
            'ikon' => 'bi-key',
            'kunci' => ['atur.judul', 'atur.intro'],
        ],
    ],

];