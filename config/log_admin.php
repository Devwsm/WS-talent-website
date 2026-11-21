<?php

/*
| Halaman Log di CMS Artis dan CMS Inner Circle: catatan error dan jejak aktivitas admin.
|
| Aktivitas yang dicatat:
| - Perubahan di CMS (tambah/ubah/hapus/simpan), lengkap dengan nama butir, waktu, dan perangkat. IP TIDAK disimpan.
| - Login gagal (kategori `akses`), satu-satunya yang menyimpan dan menampilkan IP karena menyangkut keamanan.
| Login berhasil, logout, dan membuka halaman TIDAK dicatat supaya log tidak penuh.
|
| Teks judul dan keterangan memakai bahasa sehari-hari. Penanda yang bisa dipakai di teks:
|   {objek}      nama jenis data (mis. "Album")        {nama}      nama butir yang diubah (mis. judul album)
|   {input.xxx}  isian form yang diizinkan (lihat `input_dicatat`)   {param.xxx}  bagian dari alamat (mis. daftar konten)
*/
return [

    /* Berapa lama catatan disimpan (hari). Pemangkasan berjalan sendiri sesekali saat ada catatan baru. */
    'simpan_error_hari' => 90,
    'simpan_aktivitas_hari' => 180,

    'per_halaman' => 20,

    /* Email peringatan error: satu email per jenis error per periode ini, dan paling banyak sekian per jam (semua jenis). */
    'email_per_error_menit' => 60,
    'email_maks_per_jam' => 10,

    /* Login gagal yang sama dari IP yang sama digabung jadi satu baris ("5x") selama jendela ini (menit). */
    'gabung_login_gagal_menit' => 60,

    /* Aksi yang persis sama dari admin yang sama dalam beberapa detik (klik ganda) hanya dicatat sekali. */
    'redam_ganda_detik' => 5,

    /* Dua area Log, masing-masing punya halaman Log sendiri. */
    'area' => [
        'artis' => 'CMS Artis',
        'inner-circle' => 'CMS Inner Circle',
    ],

    /* Label kategori aktivitas. Kunci disimpan di kolom `kategori`. */
    'kategori' => [
        'konten' => 'Perubahan konten',
        'inner-circle' => 'Perubahan Inner Circle',
        'akses' => 'Login gagal',
    ],

    /* Kategori aktivitas yang tampil di Log tiap area. Login gagal hanya di CMS Artis, supaya tidak muncul dua kali. */
    'kategori_area' => [
        'artis' => ['konten', 'akses'],
        'inner-circle' => ['inner-circle'],
    ],

    /* Kategori berisiko: hanya ini yang menyimpan dan menampilkan alamat IP. */
    'kategori_berisiko' => ['akses'],

    /* Route yang tidak dicatat sebagai aktivitas (mengelola Log itu sendiri, dan pencarian yang tidak mengubah data).
       Panel undian GL (`acara.gl.*`) mencatat sendiri, dengan keterangan dan alasan, lewat UndianService dan controller-nya. */
    'abaikan_rute' => ['log.*', 'ic.admin.log.*', 'ic.admin.acara.lokasi.cari', 'ic.admin.acara.gl.*'],

    /* Login gagal: teks tetap. Username yang diketik TIDAK dicatat (kadang yang terketik justru kata sandinya). */
    'akses' => [
        'login_gagal' => ['Gagal masuk ke dashboard', 'Username atau kata sandi salah.'],
        'otp_salah' => ['Kode verifikasi salah', 'Kode dari email salah dimasukkan.'],
        'otp_habis' => ['Kode verifikasi salah berkali-kali', 'Kode dibatalkan, admin harus masuk dari awal.'],
        'tanpa_2fa' => ['Masuk tanpa kode verifikasi', 'Email admin belum diisi, jadi kode verifikasi dilewati. Isi email admin agar login lebih aman.'],
    ],

    /* Isian form yang boleh ikut dicatat (nilai pendek dan tidak sensitif). Selain ini TIDAK pernah disimpan. */
    'input_dicatat' => ['aksi', 'aktif', 'jumlah', 'status'],

    /* Cara membaca nilai isian untuk kalimat. Bisa ditimpa per route lewat kunci `nilai`. */
    'nilai' => [
        'aktif' => ['1' => 'ditampilkan', '0' => 'disembunyikan'],
    ],

    /*
    | Jenis data di CMS Artis (bagian pertama nama route, mis. "albums" dari "albums.tambah").
    | model + kunci + nama dipakai untuk mencari nama butir yang diubah; nama adalah daftar pola, dipakai yang pertama terisi.
    */
    'modul' => [
        'albums' => ['objek' => 'Album', 'model' => \App\Models\albums::class, 'kunci' => 'id_albums', 'nama' => ['{albums_name}']],
        'banner' => ['objek' => 'Banner', 'model' => \App\Models\banner::class, 'kunci' => 'id_banner', 'nama' => ['{banner_name}']],
        'headers' => ['objek' => 'Header', 'model' => \App\Models\header::class, 'kunci' => 'id_header', 'nama' => ['{header_title}', '{header_name}']],
        'news' => ['objek' => 'Berita', 'model' => \App\Models\news::class, 'kunci' => 'id_news', 'nama' => ['{news_title}']],
        'merchandise' => ['objek' => 'Merchandise', 'model' => \App\Models\merchandise::class, 'kunci' => 'id_merchandise', 'nama' => ['{merchandise_name}']],
        'statistik' => ['objek' => 'Statistik', 'model' => \App\Models\statistik::class, 'kunci' => 'id_statistik', 'nama' => ['{platform}']],
        'highlight' => ['objek' => 'Highlight', 'model' => \App\Models\highlight::class, 'kunci' => 'id_highlight', 'nama' => ['{place}']],
        'genre' => ['objek' => 'Genre', 'model' => \App\Models\genre::class, 'kunci' => 'id_genre', 'nama' => ['{nama_genre}']],
        'collab' => ['objek' => 'Kolaborasi', 'model' => \App\Models\collab::class, 'kunci' => 'id_collab', 'nama' => ['{nama}']],
        'media_coverage' => ['objek' => 'Media coverage', 'model' => \App\Models\media_coverage::class, 'kunci' => 'id_media_coverage', 'nama' => ['{nama_media}']],
        'media_sosial' => ['objek' => 'Media sosial', 'model' => \App\Models\media_sosial::class, 'kunci' => 'id_media_sosial', 'nama' => ['{platform}']],
        'booking' => ['objek' => 'Kontak booking', 'model' => \App\Models\booking::class, 'kunci' => 'id_booking', 'nama' => ['{label}']],
    ],

    /* Kata kerja (bagian kedua nama route) untuk jenis data di atas. */
    'aksi' => [
        'tambah' => [
            'judul' => '{objek} ditambahkan',
            'ket' => '“{nama}” ditambahkan.',
            'ket_tanpa_nama' => '{objek} baru ditambahkan.',
            'gagal' => '{objek} gagal ditambahkan',
        ],
        'update' => [
            'judul' => '{objek} diubah',
            'ket' => 'Data “{nama}” diperbarui.',
            'ket_tanpa_nama' => 'Data {objek} diperbarui.',
            'gagal' => '{objek} gagal diubah',
        ],
        'urutan' => [
            'judul' => 'Urutan {objek} diubah',
            'ket' => '“{nama}” dipindahkan di halaman utama.',
            'ket_tanpa_nama' => 'Urutan {objek} di halaman utama diperbarui.',
            'gagal' => 'Urutan {objek} gagal diubah',
        ],
        'hapus' => [
            'judul' => '{objek} dihapus',
            'ket' => '“{nama}” dihapus dari website.',
            'ket_tanpa_nama' => 'Satu data {objek} dihapus dari website.',
            'gagal' => '{objek} gagal dihapus',
        ],
    ],

    /*
    | Route yang punya teks sendiri: bagian CMS Artis yang hanya punya satu isi, dan semua route CMS Inner Circle.
    | Kunci: judul, ket, ket_tanpa_nama (dipakai bila nama butir tidak ketemu), gagal, gagal_ket (teks tetap saat gagal;
    | kalau tidak ada, pesan penolakan dari sistem dipakai), nama (daftar pola), sumber (cara mencari nama di database),
    | nilai (cara membaca isian untuk kalimat).
    */
    'rute' => [
        // ---- CMS Artis: bagian dengan satu isi ----
        'hero.simpan' => [
            'judul' => 'Hero profil diperbarui',
            'ket' => 'Foto, nama, dan tagline di bagian atas halaman Profil diperbarui.',
            'gagal' => 'Hero profil gagal disimpan',
        ],
        'bio.simpan' => [
            'judul' => 'Bio diperbarui',
            'ket' => 'Teks bio artis diperbarui.',
            'gagal' => 'Bio gagal disimpan',
        ],
        'color_pages.update' => [
            'judul' => 'Warna web diubah',
            'ket' => 'Warna utama website diganti menjadi {nama}.',
            'ket_tanpa_nama' => 'Warna utama website diganti.',
            'gagal' => 'Warna web gagal diubah',
            'nama' => ['{color}'],
        ],

        // ---- CMS Inner Circle: anggota, acara, hadiah ----
        'ic.admin.anggota.poin' => [
            'judul' => 'Poin manual ditambahkan',
            'ket' => 'Menambah {input.jumlah} poin untuk {nama}.',
            'ket_tanpa_nama' => 'Poin manual ditambahkan untuk seorang anggota.',
            'gagal' => 'Poin manual gagal ditambahkan',
            'nama' => ['{nama}'],
            'sumber' => ['model' => \App\Models\member::class, 'param' => 'id', 'kolom' => 'id_member'],
        ],
        'ic.admin.anggota.kurangi' => [
            'judul' => 'Poin dikurangi',
            'ket' => 'Mengurangi {input.jumlah} poin dari {nama} (koreksi).',
            'ket_tanpa_nama' => 'Poin seorang anggota dikurangi (koreksi).',
            'gagal' => 'Poin gagal dikurangi',
            'nama' => ['{nama}'],
            'sumber' => ['model' => \App\Models\member::class, 'param' => 'id', 'kolom' => 'id_member'],
        ],
        'ic.admin.anggota.nonaktifkan' => [
            'judul' => 'Akun anggota dinonaktifkan',
            'ket' => 'Akun {nama} dinonaktifkan.',
            'ket_tanpa_nama' => 'Akun seorang anggota dinonaktifkan.',
            'gagal' => 'Akun anggota gagal dinonaktifkan',
            'nama' => ['{nama}'],
            'sumber' => ['model' => \App\Models\member::class, 'param' => 'id', 'kolom' => 'id_member'],
        ],
        'ic.admin.anggota.aktifkan' => [
            'judul' => 'Akun anggota diaktifkan kembali',
            'ket' => 'Akun {nama} diaktifkan kembali.',
            'ket_tanpa_nama' => 'Akun seorang anggota diaktifkan kembali.',
            'gagal' => 'Akun anggota gagal diaktifkan',
            'nama' => ['{nama}'],
            'sumber' => ['model' => \App\Models\member::class, 'param' => 'id', 'kolom' => 'id_member'],
        ],
        'ic.admin.acara.pemeriksaan.setujui' => [
            'judul' => 'Pengajuan pemeriksaan disetujui',
            'ket' => 'Pengajuan pemeriksaan kehadiran {nama} disetujui dan poin check-in diberikan.',
            'ket_tanpa_nama' => 'Sebuah pengajuan pemeriksaan kehadiran disetujui.',
            'gagal' => 'Pengajuan pemeriksaan gagal disetujui',
            'nama' => ['{anggota.nama} di {event.venue}', '{anggota.nama}'],
            'sumber' => ['model' => \App\Models\ic_pemeriksaan::class, 'param' => 'id', 'kolom' => 'id_pemeriksaan', 'relasi' => ['anggota', 'event']],
        ],
        'ic.admin.acara.pemeriksaan.tolak' => [
            'judul' => 'Pengajuan pemeriksaan ditolak',
            'ket' => 'Pengajuan pemeriksaan kehadiran {nama} ditolak.',
            'ket_tanpa_nama' => 'Sebuah pengajuan pemeriksaan kehadiran ditolak.',
            'gagal' => 'Pengajuan pemeriksaan gagal ditolak',
            'nama' => ['{anggota.nama} di {event.venue}', '{anggota.nama}'],
            'sumber' => ['model' => \App\Models\ic_pemeriksaan::class, 'param' => 'id', 'kolom' => 'id_pemeriksaan', 'relasi' => ['anggota', 'event']],
        ],
        'ic.admin.acara.penanda.ubah' => [
            'judul' => 'Penanda check-in ditinjau',
            'ket' => 'Penanda check-in {nama} diperbarui menjadi {input.status}.',
            'ket_tanpa_nama' => 'Status sebuah penanda check-in diperbarui.',
            'gagal' => 'Penanda check-in gagal diperbarui',
            'nama' => ['milik {anggota.nama}'],
            'sumber' => ['model' => \App\Models\ic_penanda::class, 'param' => 'id', 'kolom' => 'id_penanda', 'relasi' => ['anggota']],
            'nilai' => ['status' => ['baru' => 'dibuka kembali', 'aman' => 'aman', 'ditindak' => 'sudah ditindak']],
        ],
        'ic.admin.acara.simpan' => [
            'judul' => 'Acara ditambahkan',
            'ket' => 'Acara baru ditambahkan: {nama}.',
            'ket_tanpa_nama' => 'Acara baru ditambahkan.',
            'gagal' => 'Acara gagal ditambahkan',
            'nama' => ['{venue}, {kota}', '{venue}', '{kota}'],
        ],
        'ic.admin.acara.impor' => [
            'judul' => 'Acara diimpor dari file CSV',
            'ket' => 'Beberapa acara sekaligus ditambahkan lewat file CSV.',
            'gagal' => 'Impor acara gagal',
        ],
        'ic.admin.acara.bandsintown' => [
            'judul' => 'Jadwal Bandsintown disinkronkan',
            'ket' => 'Jadwal tur terbaru diambil dari Bandsintown.',
            'gagal' => 'Sinkron jadwal Bandsintown gagal',
        ],
        'ic.admin.acara.perbaiki' => [
            'judul' => 'Kode QR acara diperbaiki',
            'ket' => 'Kode QR yang tidak terbaca dibuat ulang.',
            'gagal' => 'Perbaikan kode QR gagal',
        ],
        'ic.admin.acara.ubah' => [
            'judul' => 'Acara diubah',
            'ket' => 'Data acara {nama} diperbarui.',
            'ket_tanpa_nama' => 'Data sebuah acara diperbarui.',
            'gagal' => 'Acara gagal diubah',
            'nama' => ['{venue}, {kota}', '{venue}', '{kota}', '{kode}'],
            'sumber' => ['model' => \App\Models\ic_event::class, 'param' => 'kode', 'kolom' => 'kode'],
        ],
        'ic.admin.acara.hapus' => [
            'judul' => 'Acara dihapus',
            'ket' => 'Acara {nama} dihapus.',
            'ket_tanpa_nama' => 'Sebuah acara dihapus.',
            'gagal' => 'Acara gagal dihapus',
            'nama' => ['{venue}, {kota}', '{venue}', '{kota}', '{kode}'],
            'sumber' => ['model' => \App\Models\ic_event::class, 'param' => 'kode', 'kolom' => 'kode'],
        ],
        'ic.admin.acara.token' => [
            'judul' => 'QR check-in dibuat baru',
            'ket' => 'QR check-in acara {nama} dibuat baru.',
            'ket_tanpa_nama' => 'QR check-in sebuah acara dibuat baru.',
            'gagal' => 'QR check-in gagal dibuat',
            'nama' => ['{venue}, {kota}', '{venue}', '{kota}', '{kode}'],
            'sumber' => ['model' => \App\Models\ic_event::class, 'param' => 'kode', 'kolom' => 'kode'],
        ],
        'ic.admin.acara.lokasi' => [
            'judul' => 'Lokasi check-in diatur',
            'ket' => 'Titik lokasi check-in acara {nama} disimpan.',
            'ket_tanpa_nama' => 'Titik lokasi check-in sebuah acara disimpan.',
            'gagal' => 'Lokasi check-in gagal disimpan',
            'nama' => ['{venue}, {kota}', '{venue}', '{kota}', '{kode}'],
            'sumber' => ['model' => \App\Models\ic_event::class, 'param' => 'kode', 'kolom' => 'kode'],
        ],
        'ic.admin.acara.lokasi.hapus' => [
            'judul' => 'Lokasi check-in dihapus',
            'ket' => 'Titik lokasi check-in acara {nama} dihapus.',
            'ket_tanpa_nama' => 'Titik lokasi check-in sebuah acara dihapus.',
            'gagal' => 'Lokasi check-in gagal dihapus',
            'nama' => ['{venue}, {kota}', '{venue}', '{kota}', '{kode}'],
            'sumber' => ['model' => \App\Models\ic_event::class, 'param' => 'kode', 'kolom' => 'kode'],
        ],
        'ic.admin.klaim.proses' => [
            'judul' => 'Klaim hadiah diproses',
            'ket' => 'Klaim {nama} {input.aksi}.',
            'ket_tanpa_nama' => 'Sebuah klaim hadiah diproses.',
            'gagal' => 'Klaim hadiah gagal diproses',
            'nama' => ['hadiah “{reward.judul}” milik {member.nama}'],
            'sumber' => ['model' => \App\Models\ic_claim::class, 'param' => 'id', 'kolom' => 'id_claim', 'relasi' => ['reward', 'member']],
            'nilai' => ['aksi' => ['lanjut' => 'dilanjutkan ke tahap berikutnya', 'tolak' => 'ditolak', 'catatan' => 'diberi catatan', 'buka_kembali' => 'dibuka kembali']],
        ],
        'ic.admin.hadiah.simpan' => [
            'judul' => 'Hadiah ditambahkan',
            'ket' => 'Hadiah “{nama}” ditambahkan ke katalog.',
            'ket_tanpa_nama' => 'Hadiah baru ditambahkan ke katalog.',
            'gagal' => 'Hadiah gagal ditambahkan',
            'nama' => ['{judul}'],
        ],
        'ic.admin.hadiah.ubah' => [
            'judul' => 'Hadiah diubah',
            'ket' => 'Hadiah “{nama}” diperbarui.',
            'ket_tanpa_nama' => 'Sebuah hadiah diperbarui.',
            'gagal' => 'Hadiah gagal diubah',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_reward::class, 'param' => 'id', 'kolom' => 'id_reward'],
        ],
        'ic.admin.hadiah.aktif' => [
            'judul' => 'Tampilan hadiah diubah',
            'ket' => 'Hadiah “{nama}” {input.aktif}.',
            'ket_tanpa_nama' => 'Tampilan sebuah hadiah diubah.',
            'gagal' => 'Tampilan hadiah gagal diubah',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_reward::class, 'param' => 'id', 'kolom' => 'id_reward'],
        ],
        'ic.admin.pengaduan.proses' => [
            'judul' => 'Pengaduan ditindaklanjuti',
            'ket' => 'Pengaduan {nama} {input.status}.',
            'ket_tanpa_nama' => 'Sebuah pengaduan ditindaklanjuti.',
            'gagal' => 'Pengaduan gagal ditindaklanjuti',
            'nama' => ['dari {member.nama}'],
            'sumber' => ['model' => \App\Models\ic_pengaduan::class, 'param' => 'id', 'kolom' => 'id_pengaduan', 'relasi' => ['member']],
            'nilai' => ['status' => ['baru' => 'dibuka kembali', 'diproses' => 'mulai diproses', 'selesai' => 'diselesaikan']],
        ],

        // ---- CMS Inner Circle: pengumuman, kenangan, email ----
        'ic.admin.pengumuman.simpan' => [
            'judul' => 'Pengumuman ditambahkan',
            'ket' => 'Pengumuman “{nama}” dibuat.',
            'ket_tanpa_nama' => 'Pengumuman baru dibuat.',
            'gagal' => 'Pengumuman gagal ditambahkan',
            'nama' => ['{judul}'],
        ],
        'ic.admin.pengumuman.ubah' => [
            'judul' => 'Pengumuman diubah',
            'ket' => 'Pengumuman “{nama}” diperbarui.',
            'ket_tanpa_nama' => 'Sebuah pengumuman diperbarui.',
            'gagal' => 'Pengumuman gagal diubah',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_broadcast::class, 'param' => 'id', 'kolom' => 'id_broadcast'],
        ],
        'ic.admin.pengumuman.aktif' => [
            'judul' => 'Tampilan pengumuman diubah',
            'ket' => 'Pengumuman “{nama}” {input.aktif}.',
            'ket_tanpa_nama' => 'Tampilan sebuah pengumuman diubah.',
            'gagal' => 'Tampilan pengumuman gagal diubah',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_broadcast::class, 'param' => 'id', 'kolom' => 'id_broadcast'],
        ],
        'ic.admin.pengumuman.hapus' => [
            'judul' => 'Pengumuman dihapus',
            'ket' => 'Pengumuman “{nama}” dihapus.',
            'ket_tanpa_nama' => 'Sebuah pengumuman dihapus.',
            'gagal' => 'Pengumuman gagal dihapus',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_broadcast::class, 'param' => 'id', 'kolom' => 'id_broadcast'],
        ],
        'ic.admin.kenangan.moderasi' => [
            'judul' => 'Kenangan dimoderasi',
            'ket' => 'Kenangan milik {nama} {input.aksi}.',
            'ket_tanpa_nama' => 'Sebuah kenangan dimoderasi.',
            'gagal' => 'Kenangan gagal dimoderasi',
            'nama' => ['{member.nama}'],
            'sumber' => ['model' => \App\Models\ic_memory::class, 'param' => 'id', 'kolom' => 'id_memory', 'relasi' => ['member']],
            'nilai' => ['aksi' => ['sembunyikan' => 'disembunyikan', 'tayangkan' => 'ditayangkan kembali']],
        ],
        'ic.admin.email.tes' => [
            'judul' => 'Email tes dikirim',
            'ket' => 'Email contoh dikirim untuk dicek tampilannya.',
            'gagal' => 'Email tes gagal dikirim',
            'gagal_ket' => 'Email tes tidak terkirim. Periksa pengaturan email di server.',
        ],
        'ic.admin.email.teks' => [
            'judul' => 'Teks email diubah',
            'ket' => 'Subjek atau kalimat pembuka email “{param.jenis}” diperbarui.',
            'gagal' => 'Teks email gagal diubah',
        ],
        'ic.admin.email.teks.bawaan' => [
            'judul' => 'Teks email dikembalikan ke awal',
            'ket' => 'Subjek dan kalimat pembuka email “{param.jenis}” kembali ke teks awal.',
            'gagal' => 'Teks email gagal dikembalikan',
        ],

        // ---- CMS Inner Circle: tab Konten ----
        'ic.admin.konten.simpan' => [
            'judul' => 'Teks halaman diubah',
            'ket' => 'Teks di bagian “{param.grup}” diperbarui.',
            'gagal' => 'Teks halaman gagal diubah',
        ],
        'ic.admin.konten.reset' => [
            'judul' => 'Teks dikembalikan ke awal',
            'ket' => 'Satu teks di bagian “{param.grup}” kembali ke teks awal.',
            'gagal' => 'Teks gagal dikembalikan',
        ],
        'ic.admin.konten.item.tambah' => [
            'judul' => 'Butir daftar ditambahkan',
            'ket' => 'Butir “{nama}” ditambahkan ke daftar {param.daftar}.',
            'ket_tanpa_nama' => 'Butir baru ditambahkan ke daftar {param.daftar}.',
            'gagal' => 'Butir daftar gagal ditambahkan',
            'nama' => ['{judul}'],
        ],
        'ic.admin.konten.item.bawaan' => [
            'judul' => 'Daftar dikembalikan ke awal',
            'ket' => 'Daftar {param.daftar} kembali ke isi awal.',
            'gagal' => 'Daftar gagal dikembalikan',
        ],
        'ic.admin.konten.item.ubah' => [
            'judul' => 'Butir daftar diubah',
            'ket' => 'Butir “{nama}” di daftar {param.daftar} diperbarui.',
            'ket_tanpa_nama' => 'Sebuah butir di daftar {param.daftar} diperbarui.',
            'gagal' => 'Butir daftar gagal diubah',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_item::class, 'param' => 'id', 'kolom' => 'id_item'],
        ],
        'ic.admin.konten.item.geser' => [
            'judul' => 'Urutan daftar diubah',
            'ket' => 'Urutan butir “{nama}” di daftar {param.daftar} digeser.',
            'ket_tanpa_nama' => 'Urutan sebuah butir di daftar {param.daftar} digeser.',
            'gagal' => 'Urutan daftar gagal diubah',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_item::class, 'param' => 'id', 'kolom' => 'id_item'],
        ],
        'ic.admin.konten.item.aktif' => [
            'judul' => 'Tampilan butir daftar diubah',
            'ket' => 'Butir “{nama}” {input.aktif}.',
            'ket_tanpa_nama' => 'Tampilan sebuah butir di daftar {param.daftar} diubah.',
            'gagal' => 'Tampilan butir daftar gagal diubah',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_item::class, 'param' => 'id', 'kolom' => 'id_item'],
        ],
        'ic.admin.konten.item.hapus' => [
            'judul' => 'Butir daftar dihapus',
            'ket' => 'Butir “{nama}” dihapus dari daftar {param.daftar}.',
            'ket_tanpa_nama' => 'Sebuah butir dihapus dari daftar {param.daftar}.',
            'gagal' => 'Butir daftar gagal dihapus',
            'nama' => ['{judul}'],
            'sumber' => ['model' => \App\Models\ic_item::class, 'param' => 'id', 'kolom' => 'id_item'],
        ],
        'ic.admin.konten.pilihan.tipe.ubah' => [
            'judul' => 'Tipe acara diubah',
            'ket' => 'Tipe acara “{nama}” diperbarui.',
            'ket_tanpa_nama' => 'Sebuah tipe acara diperbarui.',
            'gagal' => 'Tipe acara gagal diubah',
            'nama' => ['{label}'],
            'sumber' => ['model' => \App\Models\ic_pilihan::class, 'param' => 'kunci', 'kolom' => 'kunci', 'saring' => ['jenis' => 'tipe_event']],
        ],
        'ic.admin.konten.pilihan.tipe.bawaan' => [
            'judul' => 'Tipe acara dikembalikan ke awal',
            'ket' => 'Tipe acara kembali ke nama, ikon, dan warna awal.',
            'gagal' => 'Tipe acara gagal dikembalikan',
        ],
        'ic.admin.konten.pilihan.kategori.tambah' => [
            'judul' => 'Kategori kenangan ditambahkan',
            'ket' => 'Kategori kenangan “{nama}” ditambahkan.',
            'ket_tanpa_nama' => 'Kategori kenangan baru ditambahkan.',
            'gagal' => 'Kategori kenangan gagal ditambahkan',
            'nama' => ['{label}'],
        ],
        'ic.admin.konten.pilihan.kategori.ubah' => [
            'judul' => 'Kategori kenangan diubah',
            'ket' => 'Kategori kenangan “{nama}” diperbarui.',
            'ket_tanpa_nama' => 'Sebuah kategori kenangan diperbarui.',
            'gagal' => 'Kategori kenangan gagal diubah',
            'nama' => ['{label}'],
            'sumber' => ['model' => \App\Models\ic_pilihan::class, 'param' => 'kunci', 'kolom' => 'kunci', 'saring' => ['jenis' => 'kategori_kenangan']],
        ],
        'ic.admin.konten.pilihan.kategori.geser' => [
            'judul' => 'Urutan kategori diubah',
            'ket' => 'Urutan kategori kenangan “{nama}” digeser.',
            'ket_tanpa_nama' => 'Urutan sebuah kategori kenangan digeser.',
            'gagal' => 'Urutan kategori gagal diubah',
            'nama' => ['{label}'],
            'sumber' => ['model' => \App\Models\ic_pilihan::class, 'param' => 'kunci', 'kolom' => 'kunci', 'saring' => ['jenis' => 'kategori_kenangan']],
        ],
        'ic.admin.konten.pilihan.kategori.aktif' => [
            'judul' => 'Tampilan kategori diubah',
            'ket' => 'Kategori kenangan “{nama}” {input.aktif}.',
            'ket_tanpa_nama' => 'Tampilan sebuah kategori kenangan diubah.',
            'gagal' => 'Tampilan kategori gagal diubah',
            'nama' => ['{label}'],
            'sumber' => ['model' => \App\Models\ic_pilihan::class, 'param' => 'kunci', 'kolom' => 'kunci', 'saring' => ['jenis' => 'kategori_kenangan']],
        ],
        'ic.admin.konten.pilihan.kategori.bawaan' => [
            'judul' => 'Kategori kenangan dikembalikan ke awal',
            'ket' => 'Daftar kategori kenangan kembali ke isi awal.',
            'gagal' => 'Kategori kenangan gagal dikembalikan',
        ],
        'ic.admin.konten.pilihan.kategori.hapus' => [
            'judul' => 'Kategori kenangan dihapus',
            'ket' => 'Kategori kenangan “{nama}” dihapus.',
            'ket_tanpa_nama' => 'Sebuah kategori kenangan dihapus.',
            'gagal' => 'Kategori kenangan gagal dihapus',
            'nama' => ['{label}'],
            'sumber' => ['model' => \App\Models\ic_pilihan::class, 'param' => 'kunci', 'kolom' => 'kunci', 'saring' => ['jenis' => 'kategori_kenangan']],
        ],
        'ic.admin.konten.aturan.simpan' => [
            'judul' => 'Aturan check-in diubah',
            'ket' => 'Jam buka, radius, atau akurasi GPS bawaan check-in diperbarui.',
            'gagal' => 'Aturan check-in gagal diubah',
        ],
        'ic.admin.konten.aturan.bawaan' => [
            'judul' => 'Aturan check-in dikembalikan ke awal',
            'ket' => 'Aturan bawaan check-in kembali ke nilai awal.',
            'gagal' => 'Aturan check-in gagal dikembalikan',
        ],
        'ic.admin.konten.privasi.simpan' => [
            'judul' => 'Kebijakan privasi diubah',
            'ket' => 'Isi halaman Kebijakan Privasi diperbarui.',
            'gagal' => 'Kebijakan privasi gagal diubah',
        ],
        'ic.admin.konten.privasi.bawaan' => [
            'judul' => 'Kebijakan privasi dikembalikan ke awal',
            'ket' => 'Isi Kebijakan Privasi kembali ke teks awal.',
            'gagal' => 'Kebijakan privasi gagal dikembalikan',
        ],
        'ic.admin.konten.tampilan.simpan' => [
            'judul' => 'Tampilan dan merek diubah',
            'ket' => 'Nama program, warna, atau logo diperbarui.',
            'gagal' => 'Tampilan dan merek gagal diubah',
        ],
        'ic.admin.konten.tampilan.bawaan' => [
            'judul' => 'Tampilan dan merek dikembalikan ke awal',
            'ket' => 'Nama program, warna, dan logo kembali ke tampilan awal.',
            'gagal' => 'Tampilan dan merek gagal dikembalikan',
        ],
    ],

];