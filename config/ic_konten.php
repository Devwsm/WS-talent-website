<?php

/*
|--------------------------------------------------------------------------
| Daftar konten Inner Circle yang bisa diubah dari CMS (tab Konten)
|--------------------------------------------------------------------------
| Satu-satunya sumber kebenaran untuk teks yang bisa diubah. Tiap kunci punya:
| - grup        : bagian di tab Konten (kunci di `grup` di bawah)
| - label       : nama isian di form
| - bawaan      : teks bawaan, dipakai selama tidak ada yang mengubahnya (isinya persis seperti yang tampil sekarang)
| - maks        : batas panjang (karakter)
| - baris       : true bila isian beberapa baris (textarea)
| - placeholder : placeholder tambahan yang boleh dipakai, mis. ['nama']. `{program}` selalu boleh.
| - tautan      : nama tautan (lihat `tautan` di bawah) yang boleh disisipkan sebagai {nama_tautan}; teks dengan tautan
|                 ditampilkan lewat ic_html(), bukan ic_teks().
|
| Kunci hanya boleh ada di sini bila SUDAH dipakai view lewat ic_teks('kunci') atau ic_html('kunci'): ada tes yang menjaga
| agar tidak ada isian CMS yang tidak berpengaruh apa-apa. Tambah kunci baru = tambah di sini lalu pakai di view.
| Tanda < dan > tidak boleh dipakai (teks biasa saja, selalu di-escape saat ditampilkan).
|
| Tiap grup punya `pratinjau`: nama halaman tempat teks grup itu tampil (kunci halaman => label). Hanya dipakai sebagai
| keterangan di panel pratinjau replika (components/dashboard/inner-circle/replika.blade.php).
|
| `daftar` memuat daftar berurutan (langkah, FAQ, poin teaser) yang dikelola dari tab Konten. Isi awalnya (`bawaan`) ditanam
| oleh migrasi dan dipakai tombol "Kembalikan daftar bawaan"; sesudah itu sumbernya tabel `ic_item`.
*/

return [

    /* Nama program; dipakai lewat placeholder {program}. */
    'program' => 'WS Inner Circle',

    /*
    | Tampilan dan merek (tab Konten, bagian "Tampilan"; Fase 6): nama program, tiga warna, dan logo.
    | - kunci   : pengaturan yang disimpan di `ic_pengaturan` hanya bila BEDA dari bawaan (sama seperti teks lain).
    |             Pratinjau replika membacanya langsung dari form, tanpa permintaan ke server.
    | - warna   : `css` = variabel CSS yang ditimpa di area anggota (resources/css/inner-circle.css). Hanya nilai
    |             #RRGGBB yang lolos validasi, jadi tidak ada teks bebas yang masuk ke CSS.
    | - teks    : warna teks area anggota (bukan isian CMS). Dipakai untuk memeriksa kontras; tes menjaga agar nilainya
    |             sama dengan token di inner-circle.css.
    | - kontras : batas WCAG AA. Teks 4.5:1; aksen 3:1 (cincin fokus, garis, dan judul besar, bukan teks kecil).
    */
    'tampilan' => [
        'label' => 'Tampilan dan merek',
        'program_maks' => 30,
        'logo_folder' => 'inner-circle/logo-kustom',
        'logo_maks_kb' => 2048,
        'logo_sisi_maks' => 800,
        'kontras' => ['teks' => 4.5, 'aksen' => 3.0],

        'pratinjau' => [
            'beranda' => 'Beranda anggota',
            'masuk' => 'Masuk',
            'tentang' => 'Halaman Tentang',
        ],

        'warna' => [
            'aksen' => [
                'kunci' => 'tampilan.aksen',
                'label' => 'Warna aksen',
                'css' => '--ic-aksen',
                'bawaan' => '#1b68a0',
                'petunjuk' => 'Cincin fokus, garis pilihan, dan kata beraksen di judul. Warna lembut turunannya (lencana dan lingkaran bernomor) ikut menyesuaikan.'
            ],
            'latar' => [
                'kunci' => 'tampilan.latar',
                'label' => 'Warna latar',
                'css' => '--ic-latar',
                'bawaan' => '#d6f4ff',
                'petunjuk' => 'Bingkai di luar kartu utama pada layar lebar, dan latar halaman Masuk dan Daftar.'
            ],
            'kartu' => [
                'kunci' => 'tampilan.kartu',
                'label' => 'Warna kartu',
                'css' => '--ic-shell',
                'bawaan' => '#ffffff',
                'petunjuk' => 'Permukaan utama tempat teks tampil. Pilih warna terang supaya teks gelap tetap terbaca.'
            ],
        ],

        'teks' => [
            'tinta' => ['label' => 'teks utama', 'warna' => '#0b2a47'],
            'judul' => ['label' => 'judul', 'warna' => '#05467c'],
            'redup' => ['label' => 'teks pendamping', 'warna' => '#4a6178'],
            'eyebrow' => ['label' => 'teks label kecil', 'warna' => '#1e5688'],
        ],

        'kunci' => [
            'tampilan.program' => ['label' => 'Nama program', 'maks' => 30],
            'tampilan.aksen' => ['label' => 'Warna aksen', 'maks' => 7],
            'tampilan.latar' => ['label' => 'Warna latar', 'maks' => 7],
            'tampilan.kartu' => ['label' => 'Warna kartu', 'maks' => 7],
        ],
    ],

    /* Tautan yang boleh disisipkan di dalam teks: {nama} diganti tautan ber-label tetap (dibuat kode, bukan diketik admin). */
    'tautan' => [
        'privasi' => ['route' => 'privasi', 'label' => 'Kebijakan Privasi', 'baru' => false],
        'baca_privasi' => ['route' => 'privasi', 'label' => 'Baca Kebijakan Privasi', 'baru' => true],
        'hadiah' => ['route' => 'ic.hadiah', 'label' => 'Hadiah', 'baru' => false],
    ],

    /* Pilihan ikon (Bootstrap Icons) untuk butir daftar yang memakai ikon: nama ikon => label di form. */
    'ikon_daftar' => [
        'qr-code-scan' => 'Kode QR',
        'gift' => 'Hadiah',
        'images' => 'Galeri foto',
        'camera' => 'Kamera',
        'stars' => 'Bintang',
        'heart' => 'Hati',
        'music-note-beamed' => 'Musik',
        'calendar-event' => 'Kalender acara',
        'ticket-perforated' => 'Tiket',
        'people' => 'Komunitas',
        'trophy' => 'Piala',
        'megaphone' => 'Pengumuman',
        'geo-alt' => 'Lokasi',
        'patch-check' => 'Terverifikasi',
    ],

    /*
    | Teks email (tab Email; Fase 7): subjek dan paragraf pembuka tiap jenis email. Disimpan di `ic_pengaturan` dengan kunci
    | `email.<jenis>.<bagian>` hanya bila beda dari bawaan. Placeholder: `{program}` selalu boleh, ditambah `placeholder`
    | milik jenis itu. Tautan, token, dan tombol dibuat kode dan tidak bisa diubah dari CMS. `klaim.pembuka` yang kosong
    | berarti kalimat bawaan menurut status hadiah (diajukan, ditolak, selesai) tetap dipakai.
    */
    'email' => [
        'reset' => [
            'placeholder' => ['nama'],
            'subjek' => ['label' => 'Subjek', 'maks' => 90, 'bawaan' => 'Atur ulang kata sandi {program}'],
            'pembuka' => ['label' => 'Paragraf pembuka', 'maks' => 400, 'bawaan' => 'Kami menerima permintaan untuk mengatur ulang kata sandi akun {program} kamu. Tekan tombol di bawah untuk membuat kata sandi baru.'],
        ],
        'google' => [
            'placeholder' => ['nama'],
            'subjek' => ['label' => 'Subjek', 'maks' => 90, 'bawaan' => 'Akun {program} kamu masuk lewat Google'],
            'pembuka' => ['label' => 'Paragraf pembuka', 'maks' => 400, 'bawaan' => 'Kami menerima permintaan atur ulang kata sandi untuk akun ini. Akun ini dibuat dengan Google, jadi tidak ada kata sandi yang perlu diatur ulang.'],
        ],
        'selamat-datang' => [
            'placeholder' => ['nama', 'kode'],
            'subjek' => ['label' => 'Subjek', 'maks' => 90, 'bawaan' => 'Selamat datang di {program}'],
            'pembuka' => ['label' => 'Paragraf pembuka', 'maks' => 400, 'bawaan' => 'Akun {program} kamu sudah aktif. Kode anggotamu: {kode}.'],
        ],
        'verifikasi' => [
            'placeholder' => ['nama'],
            'subjek' => ['label' => 'Subjek', 'maks' => 90, 'bawaan' => 'Verifikasi email {program}'],
            'pembuka' => ['label' => 'Paragraf pembuka', 'maks' => 400, 'bawaan' => 'Terima kasih sudah bergabung di {program}. Tekan tombol di bawah untuk memastikan email ini milikmu. Email yang sudah terverifikasi dibutuhkan untuk mengajukan hadiah.'],
        ],
        'klaim' => [
            'placeholder' => ['nama', 'hadiah', 'status'],
            'subjek' => ['label' => 'Subjek', 'maks' => 120, 'bawaan' => 'Hadiah "{hadiah}": {status}'],
            'pembuka' => [
                'label' => 'Paragraf pembuka (semua status)',
                'maks' => 400,
                'bawaan' => '',
                'petunjuk' => 'Kosongkan untuk memakai kalimat bawaan menurut status (dibuka kembali, ditolak, selesai). Bila diisi, kalimat ini dipakai untuk semua status.'
            ],
        ],
        'pengaduan' => [
            'placeholder' => ['nama', 'status'],
            'subjek' => ['label' => 'Subjek', 'maks' => 120, 'bawaan' => 'Pengaduanmu: {status}'],
            'pembuka' => [
                'label' => 'Paragraf pembuka (semua status)',
                'maks' => 400,
                'bawaan' => '',
                'petunjuk' => 'Kosongkan untuk memakai kalimat bawaan menurut status (diproses, selesai). Bila diisi, kalimat ini dipakai untuk semua status.'
            ],
        ],
    ],

    /*
    | Kebijakan privasi (/privasi; Fase 7): isi teks kaya disimpan di `ic_pengaturan` (`privasi.isi`, sudah disaring
    | HtmlSanitizer) bersama tanggal `privasi.diperbarui` yang terisi otomatis saat disimpan. Tanpa baris, halaman memakai
    | teks bawaan di resources/views/pages/privasi-bawaan.blade.php dan tanggal dari config/site.php.
    | Batas: kolom nilai bertipe TEXT (65.535 byte), jadi ada batas byte selain batas karakter.
    */
    'privasi' => [
        'maks_karakter' => 30000,
        'maks_byte' => 60000,
    ],

    'grup' => [
        'tentang' => [
            'label' => 'Halaman Tentang (publik)',
            'ikon' => 'bi-megaphone',
            'petunjuk' => 'Halaman /innercircle/tentang yang dilihat calon anggota sebelum daftar: judul, penjelasan, langkah, dan tanya jawab.',
            'halaman' => 'ic.tentang',
            'pratinjau' => ['tentang' => 'Halaman Tentang'],
            'daftar' => ['langkah', 'faq'],
        ],
        'teaser' => [
            'label' => 'Teaser di beranda situs artis',
            'ikon' => 'bi-house-heart',
            'petunjuk' => 'Kotak Inner Circle di beranda situs Whisnu Santika (tema situs artis).',
            'halaman' => 'home',
            'jangkar' => 'inner-circle',
            'pratinjau' => ['teaser' => 'Teaser di beranda'],
            'daftar' => ['teaser_poin'],
        ],
        'beranda' => [
            'label' => 'Beranda anggota',
            'ikon' => 'bi-house-door',
            'petunjuk' => 'Sapaan, aktivitas terbaru, kartu anggota, kotak progres poin, dan ajakan main game.',
            'halaman' => 'ic.beranda',
            'pratinjau' => ['beranda' => 'Beranda anggota'],
        ],
        'checkin' => [
            'label' => 'Halaman Check-in',
            'ikon' => 'bi-qr-code-scan',
            'petunjuk' => 'Judul dan penjelasan di halaman Check-in anggota.',
            'halaman' => 'ic.checkin',
            'pratinjau' => ['checkin' => 'Check-in'],
        ],
        'hadiah' => [
            'label' => 'Halaman Hadiah',
            'ikon' => 'bi-gift',
            'petunjuk' => 'Judul, penjelasan, dan catatan cara kerja hadiah.',
            'halaman' => 'ic.hadiah',
            'pratinjau' => ['hadiah' => 'Rewards'],
        ],
        'komunitas' => [
            'label' => 'Halaman Komunitas',
            'ikon' => 'bi-people',
            'petunjuk' => 'Judul, banner, dan teks tab Pengumuman serta Galeri Kenangan. Teks tab Game ada di menu Game.',
            'halaman' => 'ic.komunitas',
            'pratinjau' => ['komunitas' => 'Pengumuman WS', 'galeri' => 'Galeri Kenangan'],
        ],
        'game' => [
            'label' => 'Game',
            'ikon' => 'bi-controller',
            'petunjuk' => 'Teks tab Game di Komunitas: pengantar, cara main, aturan, poin, dan papan skor. Aturan angka (jatah ronde, batas poin) ada di bagian atas halaman ini.',
            'pratinjau' => ['game' => 'Tab Game'],
        ],
        'profil' => [
            'label' => 'Halaman Profil anggota',
            'ikon' => 'bi-person-circle',
            'petunjuk' => 'Judul bagian dan teks pendamping di halaman Profil anggota.',
            'halaman' => 'ic.profil',
            'pratinjau' => ['profil' => 'Profil anggota'],
        ],
        'masuk' => [
            'label' => 'Halaman masuk dan daftar',
            'ikon' => 'bi-box-arrow-in-right',
            'petunjuk' => 'Judul dan kalimat pembuka halaman Masuk, Daftar, Lupa sandi, dan Atur ulang sandi.',
            'halaman' => 'ic.masuk',
            'pratinjau' => ['masuk' => 'Masuk', 'daftar' => 'Daftar', 'lupa' => 'Lupa sandi', 'atur' => 'Atur ulang sandi'],
        ],
    ],

    'kunci' => [
        'tentang.meta.judul' => [
            'grup' => 'tentang',
            'label' => 'Judul tab browser dan hasil pencarian',
            'bawaan' => '{program}, komunitas penggemar',
            'maks' => 70,
        ],
        'tentang.meta.deskripsi' => [
            'grup' => 'tentang',
            'label' => 'Deskripsi untuk mesin pencari',
            'bawaan' => 'Komunitas penggemar resmi DJ Whisnu Santika. Datang ke acara, kumpulkan poin lewat check-in, dan tukar dengan hadiah eksklusif.',
            'maks' => 200,
            'baris' => true,
        ],
        'tentang.hero.eyebrow' => [
            'grup' => 'tentang',
            'label' => 'Teks kecil di atas judul',
            'bawaan' => 'Komunitas penggemar resmi',
            'maks' => 60,
        ],
        'tentang.hero.judul' => [
            'grup' => 'tentang',
            'label' => 'Judul besar',
            'bawaan' => '{program}',
            'maks' => 30,
        ],
        'tentang.hero.deskripsi' => [
            'grup' => 'tentang',
            'label' => 'Penjelasan di bawah judul',
            'bawaan' => 'Rumah bagi penggemar Whisnu Santika. Datang ke acaranya, kumpulkan poin, dan buka hadiah eksklusif: dari merchandise sampai pengalaman personal.',
            'maks' => 260,
            'baris' => true,
        ],
        'tentang.tombol.daftar' => [
            'grup' => 'tentang',
            'label' => 'Tombol daftar (di atas dan di ajakan akhir)',
            'bawaan' => 'Daftar sekarang',
            'maks' => 30,
        ],
        'tentang.tombol.masuk' => [
            'grup' => 'tentang',
            'label' => 'Tombol masuk di bagian atas',
            'bawaan' => 'Sudah punya akun? Masuk',
            'maks' => 40,
        ],
        'tentang.tombol.buka' => [
            'grup' => 'tentang',
            'label' => 'Tombol untuk yang sudah masuk',
            'bawaan' => 'Buka Inner Circle',
            'maks' => 30,
        ],
        'tentang.langkah.judul' => [
            'grup' => 'tentang',
            'label' => 'Judul bagian langkah',
            'bawaan' => 'Cara kerjanya',
            'maks' => 60,
        ],
        'tentang.hadiah.judul' => [
            'grup' => 'tentang',
            'label' => 'Judul bagian hadiah (daftar hadiah diambil dari menu Hadiah)',
            'bawaan' => 'Hadiah di tiap tahap',
            'maks' => 60,
        ],
        'tentang.acara.judul' => [
            'grup' => 'tentang',
            'label' => 'Judul bagian acara (daftar acara diambil dari menu Acara & QR)',
            'bawaan' => 'Acara berikutnya',
            'maks' => 60,
        ],
        'tentang.acara.kosong' => [
            'grup' => 'tentang',
            'label' => 'Teks bila belum ada acara',
            'bawaan' => 'Jadwal acara berikutnya akan diumumkan di sini dan di situs resmi.',
            'maks' => 200,
            'baris' => true,
        ],
        'tentang.faq.judul' => [
            'grup' => 'tentang',
            'label' => 'Judul bagian tanya jawab',
            'bawaan' => 'Pertanyaan umum',
            'maks' => 60,
        ],
        'tentang.cta.judul' => [
            'grup' => 'tentang',
            'label' => 'Ajakan akhir: judul',
            'bawaan' => 'Siap bergabung?',
            'maks' => 60,
        ],
        'tentang.cta.deskripsi' => [
            'grup' => 'tentang',
            'label' => 'Ajakan akhir: penjelasan',
            'bawaan' => 'Buat akunmu dan mulai kumpulkan poin di acara berikutnya.',
            'maks' => 200,
            'baris' => true,
        ],
        'tentang.cta.masuk' => [
            'grup' => 'tentang',
            'label' => 'Ajakan akhir: tombol masuk',
            'bawaan' => 'Masuk',
            'maks' => 30,
        ],
        'teaser.eyebrow' => [
            'grup' => 'teaser',
            'label' => 'Teks kecil di atas judul',
            'bawaan' => 'Komunitas penggemar',
            'maks' => 60,
        ],
        'teaser.judul' => [
            'grup' => 'teaser',
            'label' => 'Judul',
            'bawaan' => '{program}',
            'maks' => 40,
        ],
        'teaser.deskripsi' => [
            'grup' => 'teaser',
            'label' => 'Penjelasan',
            'bawaan' => 'Kumpulkan poin setiap kali hadir di acara Whisnu Santika, buka hadiah di tiap tahap, dan bagikan kenanganmu bersama penggemar lain.',
            'maks' => 260,
            'baris' => true,
        ],
        'teaser.tombol.gabung' => [
            'grup' => 'teaser',
            'label' => 'Tombol gabung',
            'bawaan' => 'Gabung sekarang',
            'maks' => 30,
        ],
        'teaser.tombol.masuk' => [
            'grup' => 'teaser',
            'label' => 'Tombol masuk',
            'bawaan' => 'Sudah punya akun? Masuk',
            'maks' => 40,
        ],
        'teaser.tombol.buka' => [
            'grup' => 'teaser',
            'label' => 'Tombol untuk yang sudah masuk',
            'bawaan' => 'Buka Inner Circle',
            'maks' => 30,
        ],
        'teaser.tautan' => [
            'grup' => 'teaser',
            'label' => 'Tautan ke halaman Tentang',
            'bawaan' => 'Pelajari cara kerjanya',
            'maks' => 50,
        ],
        'beranda.eyebrow' => [
            'grup' => 'beranda',
            'label' => 'Teks kecil di atas judul',
            'bawaan' => 'Whisnu Santika Official Fanbase',
            'maks' => 60,
        ],
        'beranda.salam' => [
            'grup' => 'beranda',
            'label' => 'Sapaan (gunakan {nama} untuk nama depan anggota)',
            'bawaan' => 'Hai, {nama}!',
            'maks' => 60,
            'placeholder' => ['nama'],
        ],
        'beranda.terima_kasih' => [
            'grup' => 'beranda',
            'label' => 'Kalimat terima kasih',
            'bawaan' => 'Terima kasih sudah menjadi bagian dari keluarga {program}.',
            'maks' => 160,
            'baris' => true,
        ],
        'beranda.stiker' => [
            'grup' => 'beranda',
            'label' => 'Tombol stiker (mengarah ke Check-in)',
            'bawaan' => 'Check-in sekarang',
            'maks' => 40,
        ],
        'beranda.game.stiker' => [
            'grup' => 'beranda',
            'label' => 'Ajakan game: label kecil',
            'bawaan' => 'Mini game',
            'maks' => 30,
        ],
        'beranda.game.judul' => [
            'grup' => 'beranda',
            'label' => 'Ajakan game: judul',
            'bawaan' => 'Main game, dapat poin!',
            'maks' => 50,
        ],
        'beranda.game.deskripsi' => [
            'grup' => 'beranda',
            'label' => 'Ajakan game: penjelasan',
            'bawaan' => 'Tangkap nanas, kumpulkan sampai {poin_hari} poin sehari.',
            'maks' => 120,
            'baris' => true,
            'placeholder' => ['ronde', 'skor_per_poin', 'poin_ronde', 'poin_hari', 'menit'],
        ],
        'beranda.game.tombol' => [
            'grup' => 'beranda',
            'label' => 'Ajakan game: tombol',
            'bawaan' => 'Main',
            'maks' => 20,
        ],
        'beranda.aktivitas.judul' => [
            'grup' => 'beranda',
            'label' => 'Judul bagian aktivitas terbaru',
            'bawaan' => 'Aktivitas terbaru ✦',
            'maks' => 60,
        ],
        'beranda.aktivitas.kosong' => [
            'grup' => 'beranda',
            'label' => 'Teks bila belum ada aktivitas',
            'bawaan' => 'Belum ada aktivitas. Datang ke acara dan check-in untuk mendapat poin pertamamu.',
            'maks' => 200,
            'baris' => true,
        ],
        'beranda.kartu.label' => [
            'grup' => 'beranda',
            'label' => 'Tulisan di kartu anggota',
            'bawaan' => 'KARTU KEANGGOTAAN DIGITAL',
            'maks' => 40,
        ],
        'beranda.poin.sisa' => [
            'grup' => 'beranda',
            'label' => 'Kotak progres: teks setelah angka sisa poin',
            'bawaan' => 'poin lagi untuk membuka hadiah berikutnya.',
            'maks' => 120,
        ],
        'beranda.poin.selesai' => [
            'grup' => 'beranda',
            'label' => 'Kotak progres: bila semua hadiah terbuka',
            'bawaan' => 'Poinmu sudah cukup untuk semua hadiah.',
            'maks' => 100,
        ],
        'beranda.poin.catatan' => [
            'grup' => 'beranda',
            'label' => 'Kotak progres: catatan kecil',
            'bawaan' => 'Poin terus terkumpul dan tidak hangus',
            'maks' => 80,
        ],
        'checkin.eyebrow' => [
            'grup' => 'checkin',
            'label' => 'Teks kecil di atas judul',
            'bawaan' => 'Datang. Kumpulkan poin.',
            'maks' => 60,
        ],
        'checkin.deskripsi' => [
            'grup' => 'checkin',
            'label' => 'Penjelasan di bawah judul',
            'bawaan' => 'Jadilah bagian dari perjalanan ini. Temukan kegiatan Inner Circle dan kumpulkan poin ketika hadir.',
            'maks' => 220,
            'baris' => true,
        ],
        'checkin.jadwal.judul' => [
            'grup' => 'checkin',
            'label' => 'Judul kartu jadwal',
            'bawaan' => 'Jadwal Pertunjukan WS',
            'maks' => 80,
        ],
        'checkin.jadwal.deskripsi' => [
            'grup' => 'checkin',
            'label' => 'Penjelasan kartu jadwal',
            'bawaan' => 'Jadwal berdasarkan rencana WSM dan bersifat tentatif. Tidak semua acara otomatis memberi poin.',
            'maks' => 220,
            'baris' => true,
        ],
        'checkin.lokasi.judul' => [
            'grup' => 'checkin',
            'label' => 'Judul kotak "sudah di lokasi"',
            'bawaan' => 'Sudah di lokasi?',
            'maks' => 60,
        ],
        'checkin.lokasi.deskripsi' => [
            'grup' => 'checkin',
            'label' => 'Penjelasan kotak "sudah di lokasi"',
            'bawaan' => 'Pilih acara di atas lalu tekan Check-in; lokasi HP-mu dicek, tanpa QR. Pemenang GL gratis menunjukkan Kartu GL ke staf, lalu memindai QR acara.',
            'maks' => 220,
            'baris' => true,
        ],
        'hadiah.deskripsi' => [
            'grup' => 'hadiah',
            'label' => 'Penjelasan di bawah judul',
            'bawaan' => 'Setiap poin membuatmu lebih dekat dengan Whisnu Santika.',
            'maks' => 260,
            'baris' => true,
        ],
        'hadiah.klaim.judul' => [
            'grup' => 'hadiah',
            'label' => 'Judul bagian klaim',
            'bawaan' => 'Klaim hadiahmu ↗',
            'maks' => 60,
        ],
        'hadiah.cara.judul' => [
            'grup' => 'hadiah',
            'label' => 'Judul catatan cara kerja',
            'bawaan' => '✦ Cara kerja hadiah',
            'maks' => 60,
        ],
        'hadiah.cara.deskripsi' => [
            'grup' => 'hadiah',
            'label' => 'Isi catatan cara kerja (syarat hadiah)',
            'bawaan' => 'Poin ditahan saat kamu mengajukan penukaran, habis dipakai bila hadiah diterima, dan kembali bila penukaran dibatalkan, ditolak, atau stok habis. Hadiah boleh ditukar lagi selama poin cukup dan stok ada. Benefit pengalaman bergantung pada kapasitas, jadwal artis, dan izin promotor atau venue.',
            'maks' => 400,
            'baris' => true,
        ],
        'komunitas.deskripsi' => [
            'grup' => 'komunitas',
            'label' => 'Penjelasan di bawah judul',
            'bawaan' => 'We\'re DIVERSE, UNITE, EVOLVE',
            'maks' => 260,
            'baris' => true,
        ],
        'komunitas.banner.stiker' => [
            'grup' => 'komunitas',
            'label' => 'Banner: stiker kecil',
            'bawaan' => '✦ WS INNER CIRCLE ✦',
            'maks' => 40,
        ],
        'komunitas.banner.judul' => [
            'grup' => 'komunitas',
            'label' => 'Banner: judul (Enter = baris baru)',
            'bawaan' => 'Bagikan momen kamu ke sesama {program} yang lainnya.',
            'maks' => 90,
            'baris' => true,
        ],
        'komunitas.banner.deskripsi' => [
            'grup' => 'komunitas',
            'label' => 'Banner: penjelasan',
            'bawaan' => 'Lebih dekat. Lebih Akrab.',
            'maks' => 160,
            'baris' => true,
        ],
        'komunitas.banner.tombol' => [
            'grup' => 'komunitas',
            'label' => 'Banner: tombol bagikan kenangan',
            'bawaan' => '+ Bagikan kenangan',
            'maks' => 40,
        ],
        'komunitas.pengumuman.eyebrow' => [
            'grup' => 'komunitas',
            'label' => 'Pengumuman: teks kecil',
            'bawaan' => 'Langsung dari komunitas',
            'maks' => 60,
        ],
        'komunitas.pengumuman.judul' => [
            'grup' => 'komunitas',
            'label' => 'Pengumuman: judul bagian',
            'bawaan' => 'Kabar terbaru ✳',
            'maks' => 60,
        ],
        'komunitas.pengumuman.pil' => [
            'grup' => 'komunitas',
            'label' => 'Pengumuman: label di kanan judul',
            'bawaan' => '✦ Kabar resmi',
            'maks' => 40,
        ],
        'komunitas.pengumuman.kosong.judul' => [
            'grup' => 'komunitas',
            'label' => 'Pengumuman kosong: judul',
            'bawaan' => 'Belum ada pengumuman.',
            'maks' => 60,
        ],
        'komunitas.pengumuman.kosong.deskripsi' => [
            'grup' => 'komunitas',
            'label' => 'Pengumuman kosong: penjelasan',
            'bawaan' => 'Kabar resmi dari WSM akan muncul di sini.',
            'maks' => 160,
            'baris' => true,
        ],
        'komunitas.unggulan.label' => [
            'grup' => 'komunitas',
            'label' => 'Label kartu Kabar Pilihan',
            'bawaan' => '● KABAR PILIHAN',
            'maks' => 40,
        ],
        'komunitas.galeri.eyebrow' => [
            'grup' => 'komunitas',
            'label' => 'Galeri: teks kecil',
            'bawaan' => 'Penggemar, foto & cerita',
            'maks' => 60,
        ],
        'komunitas.galeri.judul' => [
            'grup' => 'komunitas',
            'label' => 'Galeri: judul bagian',
            'bawaan' => 'Galeri kenangan ♡',
            'maks' => 60,
        ],
        'komunitas.galeri.tombol' => [
            'grup' => 'komunitas',
            'label' => 'Galeri: tombol tambah',
            'bawaan' => '+ Tambah kenangan',
            'maks' => 40,
        ],
        'komunitas.galeri.intro_tebal' => [
            'grup' => 'komunitas',
            'label' => 'Galeri: kalimat pembuka (tebal)',
            'bawaan' => 'Simpan momen terbaikmu.',
            'maks' => 60,
        ],
        'komunitas.galeri.intro' => [
            'grup' => 'komunitas',
            'label' => 'Galeri: lanjutan kalimat pembuka',
            'bawaan' => 'Bagikan momen favorit, dukung sesama penggemar, dan kumpulkan kenangan seru.',
            'maks' => 200,
            'baris' => true,
        ],
        'komunitas.galeri.kosong.judul' => [
            'grup' => 'komunitas',
            'label' => 'Galeri kosong: judul',
            'bawaan' => 'Belum ada kenangan di kategori ini.',
            'maks' => 80,
        ],
        'komunitas.galeri.kosong.deskripsi' => [
            'grup' => 'komunitas',
            'label' => 'Galeri kosong: penjelasan',
            'bawaan' => 'Jadilah yang pertama membagikan momenmu.',
            'maks' => 160,
            'baris' => true,
        ],
        'komunitas.galeri.catatan' => [
            'grup' => 'komunitas',
            'label' => 'Catatan di bawah galeri',
            'bawaan' => 'Tim WSM dapat menyembunyikan kenangan yang melanggar aturan komunitas.',
            'maks' => 200,
            'baris' => true,
        ],
        'komunitas.kabar.stiker' => [
            'grup' => 'komunitas',
            'label' => 'Banner Pengumuman: stiker',
            'bawaan' => '✦ INFO TERBARU ✦',
            'maks' => 40,
        ],
        'komunitas.kabar.judul' => [
            'grup' => 'komunitas',
            'label' => 'Banner Pengumuman: judul',
            'bawaan' => 'Biar kamu nggak ketinggalan kabar WS.',
            'maks' => 90,
            'baris' => true,
        ],
        'komunitas.kabar.deskripsi' => [
            'grup' => 'komunitas',
            'label' => 'Banner Pengumuman: deskripsi',
            'bawaan' => 'Jadwal acara, info hadiah, sampai kejutan, semuanya mampir di sini dulu.',
            'maks' => 140,
            'baris' => true,
        ],
        'komunitas.kabar.tombol' => [
            'grup' => 'komunitas',
            'label' => 'Banner Pengumuman: tombol',
            'bawaan' => 'Lihat acara',
            'maks' => 30,
        ],
        'komunitas.game.papan.judul' => [
            'grup' => 'game',
            'label' => 'Papan skor: judul',
            'bawaan' => 'Papan skor',
            'maks' => 40,
        ],
        'komunitas.game.papan.pil' => [
            'grup' => 'game',
            'label' => 'Papan skor: label kecil',
            'bawaan' => '✦ Top 10',
            'maks' => 30,
        ],
        'komunitas.game.papan.deskripsi' => [
            'grup' => 'game',
            'label' => 'Papan skor: penjelasan',
            'bawaan' => 'Skor tertinggi dalam satu ronde. Siapa paling jago nangkep nanas?',
            'maks' => 120,
            'baris' => true,
        ],
        'komunitas.game.papan.kosong.judul' => [
            'grup' => 'game',
            'label' => 'Papan skor kosong: judul',
            'bawaan' => 'Papannya masih kosong nih',
            'maks' => 60,
        ],
        'komunitas.game.papan.kosong.deskripsi' => [
            'grup' => 'game',
            'label' => 'Papan skor kosong: ajakan',
            'bawaan' => 'Ayo jadi yang pertama! Main satu ronde aja, namamu langsung nangkring di puncak.',
            'maks' => 140,
            'baris' => true,
        ],
        'komunitas.game.papan.saya' => [
            'grup' => 'game',
            'label' => 'Papan skor: posisimu',
            'bawaan' => 'Kamu di peringkat {peringkat} dengan rekor {skor}. Gas terus!',
            'maks' => 120,
            'baris' => true,
            'placeholder' => ['peringkat', 'skor'],
        ],
        'komunitas.game.papan.belum' => [
            'grup' => 'game',
            'label' => 'Papan skor: kamu belum main',
            'bawaan' => 'Kamu belum masuk papan. Main satu ronde dulu yuk!',
            'maks' => 120,
            'baris' => true,
        ],
        'komunitas.game.eyebrow' => [
            'grup' => 'game',
            'label' => 'Game: teks kecil',
            'bawaan' => 'Mini game',
            'maks' => 60,
        ],
        'komunitas.game.judul' => [
            'grup' => 'game',
            'label' => 'Game: judul bagian',
            'bawaan' => 'Pineapple Catcher 🍍',
            'maks' => 60,
        ],
        'komunitas.game.pil' => [
            'grup' => 'game',
            'label' => 'Game: label di kanan judul',
            'bawaan' => '✦ Poin tambahan',
            'maks' => 40,
        ],
        'komunitas.game.intro' => [
            'grup' => 'game',
            'label' => 'Game: pengantar',
            'bawaan' => 'Tangkap nanas, hindari botol bir, lalu tukar skormu jadi poin {program}. Ada {ronde} ronde tiap hari, main santai aja!',
            'maks' => 320,
            'baris' => true,
            'placeholder' => ['ronde', 'skor_per_poin', 'poin_ronde', 'poin_hari', 'menit'],
        ],
        'komunitas.game.tombol' => [
            'grup' => 'game',
            'label' => 'Game: tombol mulai main',
            'bawaan' => 'Main sekarang',
            'maks' => 40,
        ],
        'komunitas.game.verifikasi' => [
            'grup' => 'game',
            'label' => 'Game: catatan bila email belum terverifikasi',
            'bawaan' => 'Verifikasi emailmu dulu supaya bisa main dan poinmu tercatat.',
            'maks' => 160,
            'baris' => true,
        ],
        'komunitas.game.habis' => [
            'grup' => 'game',
            'label' => 'Game: catatan bila jatah ronde habis',
            'bawaan' => 'Jatah ronde hari ini sudah habis. Terisi lagi besok!',
            'maks' => 160,
            'baris' => true,
        ],
        'komunitas.game.cara.judul' => [
            'grup' => 'game',
            'label' => 'Cara main: judul',
            'bawaan' => 'Cara main',
            'maks' => 60,
        ],
        'komunitas.game.cara.satu' => [
            'grup' => 'game',
            'label' => 'Cara main: langkah 1',
            'bawaan' => 'Tekan Main, tunggu hitung mundur 3-2-1.',
            'maks' => 200,
            'baris' => true,
        ],
        'komunitas.game.cara.dua' => [
            'grup' => 'game',
            'label' => 'Cara main: langkah 2',
            'bawaan' => 'Geser keranjang pakai mouse, jari, atau tombol panah.',
            'maks' => 200,
            'baris' => true,
        ],
        'komunitas.game.cara.tiga' => [
            'grup' => 'game',
            'label' => 'Cara main: langkah 3',
            'bawaan' => 'Tangkap nanas: skor +10. Biarkan botol bir lewat, kalau kena skor −10.',
            'maks' => 200,
            'baris' => true,
        ],
        'komunitas.game.cara.empat' => [
            'grup' => 'game',
            'label' => 'Cara main: langkah 4',
            'bawaan' => 'Ada 5 nyawa. Tiap nanas yang lolos mengurangi 1 nyawa. Habis, ronde selesai.',
            'maks' => 240,
            'baris' => true,
        ],
        'komunitas.game.aturan.judul' => [
            'grup' => 'game',
            'label' => 'Aturan main: judul',
            'bawaan' => 'Aturan main',
            'maks' => 60,
        ],
        'komunitas.game.aturan.satu' => [
            'grup' => 'game',
            'label' => 'Aturan main 1',
            'bawaan' => 'Jatahmu {ronde} ronde per hari, terisi lagi tiap 00.00 WIB.',
            'maks' => 200,
            'baris' => true,
            'placeholder' => ['ronde', 'skor_per_poin', 'poin_ronde', 'poin_hari', 'menit'],
        ],
        'komunitas.game.aturan.dua' => [
            'grup' => 'game',
            'label' => 'Aturan main 2',
            'bawaan' => 'Satu ronde paling lama {menit} menit.',
            'maks' => 200,
            'baris' => true,
            'placeholder' => ['ronde', 'skor_per_poin', 'poin_ronde', 'poin_hari', 'menit'],
        ],
        'komunitas.game.aturan.tiga' => [
            'grup' => 'game',
            'label' => 'Aturan main 3',
            'bawaan' => 'Halaman ditutup atau dimuat ulang saat main? Ronde hangus tanpa poin.',
            'maks' => 240,
            'baris' => true,
        ],
        'komunitas.game.aturan.empat' => [
            'grup' => 'game',
            'label' => 'Aturan main 4',
            'bawaan' => 'Pindah tab atau aplikasi? Game otomatis jeda.',
            'maks' => 200,
            'baris' => true,
        ],
        'komunitas.game.aturan.lima' => [
            'grup' => 'game',
            'label' => 'Aturan main 5',
            'bawaan' => 'Skor dicek server. Poin hasil curang bisa ditarik tim WSM.',
            'maks' => 240,
            'baris' => true,
        ],
        'komunitas.game.poin.judul' => [
            'grup' => 'game',
            'label' => 'Soal poin: judul',
            'bawaan' => 'Soal poin',
            'maks' => 60,
        ],
        'komunitas.game.poin.satu' => [
            'grup' => 'game',
            'label' => 'Soal poin 1',
            'bawaan' => '{skor_per_poin} skor = 1 poin {program}. Bisa ditukar hadiah.',
            'maks' => 200,
            'baris' => true,
            'placeholder' => ['ronde', 'skor_per_poin', 'poin_ronde', 'poin_hari', 'menit'],
        ],
        'komunitas.game.poin.dua' => [
            'grup' => 'game',
            'label' => 'Soal poin 2',
            'bawaan' => 'Satu ronde maksimal {poin_ronde} poin. Skor lebih tinggi tetap jadi rekormu.',
            'maks' => 240,
            'baris' => true,
            'placeholder' => ['ronde', 'skor_per_poin', 'poin_ronde', 'poin_hari', 'menit'],
        ],
        'komunitas.game.poin.tiga' => [
            'grup' => 'game',
            'label' => 'Soal poin 3',
            'bawaan' => 'Sehari maksimal {poin_hari} poin dari game. Sudah mentok? Tetap boleh main buat rekor.',
            'maks' => 240,
            'baris' => true,
            'placeholder' => ['ronde', 'skor_per_poin', 'poin_ronde', 'poin_hari', 'menit'],
        ],
        'komunitas.game.poin.empat' => [
            'grup' => 'game',
            'label' => 'Soal poin 4',
            'bawaan' => 'Koneksi putus saat ronde selesai? Tekan Kirim ulang, jangan tutup halaman.',
            'maks' => 240,
            'baris' => true,
        ],
        'profil.eyebrow' => [
            'grup' => 'profil',
            'label' => 'Teks kecil di atas judul',
            'bawaan' => 'Perjalanan Inner Circle-mu',
            'maks' => 60,
        ],
        'profil.foto.judul' => [
            'grup' => 'profil',
            'label' => 'Foto profil: judul',
            'bawaan' => 'Foto profilmu ✦',
            'maks' => 60,
        ],
        'profil.foto.deskripsi' => [
            'grup' => 'profil',
            'label' => 'Foto profil: penjelasan (gunakan {maks} untuk batas ukuran dalam MB)',
            'bawaan' => 'Ketuk foto untuk menggantinya. JPG, PNG, atau WebP (maksimal {maks} MB).',
            'maks' => 200,
            'baris' => true,
            'placeholder' => ['maks'],
        ],
        'profil.riwayat.judul' => [
            'grup' => 'profil',
            'label' => 'Riwayat aktivitas: judul bagian',
            'bawaan' => 'Riwayat aktivitas',
            'maks' => 60,
        ],
        'profil.riwayat.kosong' => [
            'grup' => 'profil',
            'label' => 'Riwayat aktivitas: teks bila kosong',
            'bawaan' => 'Belum ada aktivitas. Antri GL atau check-in di acara untuk mulai mengumpulkan poin.',
            'maks' => 200,
            'baris' => true,
        ],
        'profil.klaim.judul' => [
            'grup' => 'profil',
            'label' => 'Pengajuan hadiah: judul bagian',
            'bawaan' => 'Pengajuan hadiah',
            'maks' => 60,
        ],
        'profil.klaim.kosong' => [
            'grup' => 'profil',
            'label' => 'Pengajuan hadiah: teks bila kosong (gunakan {hadiah} untuk tautan ke halaman Hadiah)',
            'bawaan' => 'Belum ada pengajuan. Buka {hadiah} untuk menukar poinmu dengan hadiah.',
            'maks' => 200,
            'baris' => true,
            'tautan' => ['hadiah'],
        ],
        'profil.akun.judul' => [
            'grup' => 'profil',
            'label' => 'Akun dan privasi: judul bagian',
            'bawaan' => 'Akun dan privasi',
            'maks' => 60,
        ],
        'profil.akun.deskripsi' => [
            'grup' => 'profil',
            'label' => 'Akun dan privasi: penjelasan (gunakan {baca_privasi} untuk tautan kebijakan privasi)',
            'bawaan' => 'Datamu hanya dipakai untuk Inner Circle. {baca_privasi}.',
            'maks' => 200,
            'baris' => true,
            'tautan' => ['baca_privasi'],
        ],
        'masuk.judul' => [
            'grup' => 'masuk',
            'label' => 'Masuk: judul',
            'bawaan' => 'Selamat datang kembali',
            'maks' => 60,
        ],
        'masuk.intro' => [
            'grup' => 'masuk',
            'label' => 'Masuk: kalimat pembuka',
            'bawaan' => 'Masuk dengan akun yang sudah terdaftar.',
            'maks' => 160,
            'baris' => true,
        ],
        'daftar.judul' => [
            'grup' => 'masuk',
            'label' => 'Daftar: judul',
            'bawaan' => 'Gabung {program}',
            'maks' => 60,
        ],
        'daftar.intro' => [
            'grup' => 'masuk',
            'label' => 'Daftar: kalimat pembuka',
            'bawaan' => 'Buat akun untuk mulai mengumpulkan poin dan membuka hadiah.',
            'maks' => 160,
            'baris' => true,
        ],
        'lupa.judul' => [
            'grup' => 'masuk',
            'label' => 'Lupa sandi: judul',
            'bawaan' => 'Lupa kata sandi?',
            'maks' => 60,
        ],
        'lupa.intro' => [
            'grup' => 'masuk',
            'label' => 'Lupa sandi: kalimat pembuka',
            'bawaan' => 'Masukkan email akunmu. Kami kirim tautan untuk membuat kata sandi baru.',
            'maks' => 160,
            'baris' => true,
        ],
        'atur.judul' => [
            'grup' => 'masuk',
            'label' => 'Atur ulang sandi: judul',
            'bawaan' => 'Buat kata sandi baru',
            'maks' => 60,
        ],
        'atur.intro' => [
            'grup' => 'masuk',
            'label' => 'Atur ulang sandi: kalimat pembuka',
            'bawaan' => 'Pilih kata sandi baru untuk akun {program} kamu.',
            'maks' => 160,
            'baris' => true,
        ],
    ],

    /*
    | Daftar berurutan yang dikelola dari tab Konten (tabel ic_item).
    | - judul / isi : aturan isian (label, maks, baris); `isi` null = butir tanpa isi
    | - ikon        : true bila tiap butir memilih ikon dari `ikon_daftar`
    | - tautan      : tautan yang boleh disisipkan di isi
    | - maks_item   : batas jumlah butir (termasuk yang disembunyikan)
    | - bawaan      : isi awal (ditanam migrasi; dipakai tombol "Kembalikan daftar bawaan")
    */
    'daftar' => [
        'langkah' => [
            'label' => 'Langkah "Cara kerjanya"',
            'satuan' => 'langkah',
            'petunjuk' => 'Tampil sebagai daftar bernomor di halaman Tentang. Nomor mengikuti urutan.',
            'judul' => ['label' => 'Judul langkah', 'maks' => 50],
            'isi' => ['label' => 'Penjelasan singkat', 'maks' => 200, 'baris' => true],
            'ikon' => false,
            'maks_item' => 8,
            'bawaan' => [
                ['judul' => 'Daftar', 'isi' => 'Buat akun dengan email dan kata sandi, atau langsung dengan akun Google.'],
                ['judul' => 'Datang ke acara', 'isi' => 'Antri GL gratis dari halaman acara, lalu check-in di venue. Lokasi HP-mu dicek; pemenang GL menunjukkan Kartu GL ke staf.'],
                ['judul' => 'Kumpulkan poin', 'isi' => 'Setiap acara yang kamu hadiri memberi poin. Poin yang terkumpul bisa kamu tukar dengan hadiah.'],
                ['judul' => 'Buka hadiah', 'isi' => 'Pilih hadiah di katalog, ajukan penukarannya, lalu pantau statusnya sampai hadiah diterima.'],
            ],
        ],
        'faq' => [
            'label' => 'Pertanyaan umum (FAQ)',
            'satuan' => 'pertanyaan',
            'petunjuk' => 'Tampil sebagai tanya jawab yang bisa dibuka-tutup di halaman Tentang. Tulis {privasi} di jawaban untuk menyisipkan tautan Kebijakan Privasi.',
            'judul' => ['label' => 'Pertanyaan', 'maks' => 120],
            'isi' => ['label' => 'Jawaban', 'maks' => 400, 'baris' => true],
            'ikon' => false,
            'tautan' => ['privasi'],
            'maks_item' => 12,
            'bawaan' => [
                ['judul' => 'Apa itu poin?', 'isi' => 'Poin adalah bukti kehadiranmu di sebuah acara. Poin bisa ditukar dengan hadiah: poinnya ditahan saat kamu mengajukan, lalu dikembalikan bila penukaran dibatalkan atau ditolak.'],
                ['judul' => 'Bagaimana cara check-in di acara?', 'isi' => 'Buka halaman acara lalu tekan Check-in saat check-in dibuka, dan izinkan akses lokasi. Tidak perlu QR. Pemenang GL gratis menunjukkan Kartu GL ke staf, lalu memindai QR acara dari staf untuk mendapat poin. Satu kali per acara.'],
                ['judul' => 'Bagaimana cara mengajukan hadiah?', 'isi' => 'Saat poinmu cukup untuk sebuah hadiah, buka halaman Hadiah lalu ajukan. Tim WSM akan memeriksa dan memberi kabar lewat halaman Hadiah dan email.'],
                ['judul' => 'Bagaimana data saya digunakan?', 'isi' => 'Kami hanya menyimpan data yang dibutuhkan untuk menjalankan komunitas ini. Penjelasan lengkapnya ada di {privasi}, dan kamu bisa menghapus akunmu kapan saja dari halaman Profil.'],
            ],
        ],
        'teaser_poin' => [
            'label' => 'Poin keunggulan di teaser',
            'satuan' => 'poin',
            'petunjuk' => 'Tampil sebagai label bulat dengan ikon di bawah deskripsi teaser. Singkat saja supaya rapi di HP.',
            'judul' => ['label' => 'Teks poin', 'maks' => 40],
            'isi' => null,
            'ikon' => true,
            'maks_item' => 6,
            'bawaan' => [
                ['judul' => 'Check-in di acara', 'ikon' => 'geo-alt'],
                ['judul' => 'Hadiah di tiap tahap poin', 'ikon' => 'gift'],
                ['judul' => 'Galeri kenangan penggemar', 'ikon' => 'images'],
            ],
        ],
    ],

];