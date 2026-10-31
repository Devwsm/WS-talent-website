<?php

/*
| Halaman Log di dashboard: catatan error aplikasi dan jejak aktivitas admin.
| Label rute dipakai untuk menerjemahkan nama route jadi kalimat yang mudah dibaca.
*/
return [

    /* Berapa lama catatan disimpan (hari). Pemangkasan berjalan sendiri sesekali saat ada catatan baru. */
    'simpan_error_hari' => 90,
    'simpan_aktivitas_hari' => 180,

    'per_halaman' => 20,

    /* Email peringatan error: satu email per jenis error per periode ini, dan paling banyak sekian per jam (semua jenis). */
    'email_per_error_menit' => 60,
    'email_maks_per_jam' => 10,

    /* Nama modul CMS (bagian pertama nama route) dan kata kerjanya (bagian kedua). */
    'modul' => [
        'albums' => 'Album',
        'banner' => 'Banner',
        'bio' => 'Bio',
        'booking' => 'Booking',
        'collab' => 'Kolaborasi',
        'color_pages' => 'Warna web',
        'genre' => 'Genre',
        'headers' => 'Header',
        'hero' => 'Hero profil',
        'highlight' => 'Highlight',
        'media_coverage' => 'Media coverage',
        'media_sosial' => 'Media sosial',
        'merchandise' => 'Merchandise',
        'news' => 'Berita',
        'statistik' => 'Statistik',
    ],
    'aksi' => [
        'tambah' => 'Tambah',
        'update' => 'Ubah',
        'hapus' => 'Hapus',
        'simpan' => 'Simpan',
    ],

    /* Route Inner Circle di dashboard yang menulis data. */
    'rute' => [
        'ic.admin.anggota.stempel' => 'Beri stempel manual',
        'ic.admin.acara.simpan' => 'Tambah acara',
        'ic.admin.acara.impor' => 'Impor acara (CSV)',
        'ic.admin.acara.bandsintown' => 'Sinkron jadwal Bandsintown',
        'ic.admin.acara.perbaiki' => 'Perbaiki token QR',
        'ic.admin.acara.ubah' => 'Ubah acara',
        'ic.admin.acara.hapus' => 'Hapus acara',
        'ic.admin.acara.token' => 'Buat QR baru',
        'ic.admin.acara.lokasi' => 'Atur titik lokasi check-in',
        'ic.admin.acara.lokasi.hapus' => 'Hapus titik lokasi check-in',
        'ic.admin.acara.lokasi.cari' => 'Cari alamat lokasi acara',
        'ic.admin.klaim.proses' => 'Proses klaim hadiah',
        'ic.admin.hadiah.ubah' => 'Ubah tahap hadiah',
        'ic.admin.hadiah.aktif' => 'Aktif/nonaktifkan tahap hadiah',
        'ic.admin.pengumuman.simpan' => 'Tambah pengumuman',
        'ic.admin.pengumuman.ubah' => 'Ubah pengumuman',
        'ic.admin.pengumuman.hapus' => 'Hapus pengumuman',
        'ic.admin.pengumuman.aktif' => 'Aktif/nonaktifkan pengumuman',
        'ic.admin.kenangan.moderasi' => 'Moderasi kenangan',
        'ic.admin.email.tes' => 'Kirim email tes',
        'log.error.ubah' => 'Ubah status error',
        'log.error.bersihkan' => 'Hapus error yang selesai',
    ],

    /* Isi form yang boleh ikut dicatat (nilai pendek dan tidak sensitif). Selain ini TIDAK pernah disimpan. */
    'input_dicatat' => ['aksi', 'aktif', 'status', 'tahap', 'petugas'],

    'kategori' => [
        'akses' => 'Masuk & keluar',
        'konten' => 'Konten situs',
        'inner-circle' => 'Inner Circle',
    ],

];