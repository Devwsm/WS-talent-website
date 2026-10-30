# WS Talent Website

Website resmi DJ **Whisnu Santika**: halaman profil untuk publik, dashboard untuk mengelola isinya, dan komunitas penggemar **WS Inner Circle**.

## Daftar isi

- [Bagian 1: Panduan umum](#bagian-1-panduan-umum) (untuk semua orang di tim)
- [Bagian 2: Dokumentasi teknis](#bagian-2-dokumentasi-teknis) (untuk developer)
    - Pengaturan: [Stack](#stack-dan-hosting), [Lokal](#menjalankan-di-lokal), [`.env`](#konfigurasi-env), [Tes](#tes), [Peta kode](#peta-kode)
    - Inner Circle: [Arsitektur](#keputusan-arsitektur-inner-circle), [Aturan](#aturan-check-in-klaim-dan-konten), [Database](#skema-database), [Batas](#batas-unggahan-dan-pembatas-laju)
    - Operasional: [Rilis ke hosting](#panduan-rilis-ke-hosting), [Email](#email-gmail), [Bandsintown](#jadwal-otomatis-bandsintown)
    - [Keamanan](#keamanan) dan [Riwayat dan keputusan](#riwayat-dan-keputusan)

---

## Bagian 1: Panduan umum

Bagian ini untuk semua orang di tim, tanpa istilah teknis. Developer: lanjut ke [Bagian 2](#bagian-2-dokumentasi-teknis).

### Isi website

| Bagian                                       | Untuk siapa   | Fungsi                                                                                     | Status                                             |
| -------------------------------------------- | ------------- | ------------------------------------------------------------------------------------------ | -------------------------------------------------- |
| Situs publik (`/` dan `/profile`)            | Pengunjung    | Profil artis, jadwal tour, album, berita, merchandise                                      | **Sudah tayang**                                   |
| Dashboard (`/dashboard`)                     | Tim WSM       | Mengubah semua isi situs publik                                                            | **Sudah dipakai**                                  |
| Log (`/dashboard/log`)                       | Tim WSM       | Melihat error di website dan jejak aktivitas admin                                         | Siap, tayang di rilis berikutnya                   |
| WS Inner Circle (`/inner-circle`)            | Penggemar     | Daftar, check-in QR di acara, kumpulkan stempel, klaim hadiah, pengumuman, galeri kenangan | Selesai dan teruji, **belum dinyalakan di server** |
| Halaman penjelasan (`/inner-circle/tentang`) | Calon anggota | Menjelaskan program: cara kerja, hadiah, dan jadwal acara terdekat. Teksnya bisa diubah    | Sudah dibuat, tayang bersama Inner Circle          |
| Admin Inner Circle (di dashboard)            | Tim WSM       | Kelola anggota, acara, klaim, hadiah, pengumuman, kenangan, dan email                      | Sama seperti di atas                               |

### Cara kerja WS Inner Circle

1. Penggemar mendaftar dengan email dan kata sandi, atau dengan akun Google.
2. Di acara, penggemar memindai **QR** milik acara itu dan mendapat **1 stempel**.
3. Stempel yang terkumpul membuka hadiah di **10, 25, 50, 100, dan 250 stempel**. Di 50 stempel, anggota memilih: panggilan video atau ucapan video khusus.
4. Anggota mengajukan klaim. Tim WSM memprosesnya: _diajukan → ditinjau → disetujui → selesai_ (atau _ditolak_).
5. Anggota juga bisa membaca pengumuman, membagikan foto kenangan, dan memberi suka.

**Aturan yang tidak berubah**

- Stempel tidak berkurang saat klaim dan tidak hangus.
- Setiap hadiah hanya bisa diklaim satu kali per anggota. Klaim yang ditolak bisa dibuka kembali oleh tim WSM (kembali ke status Diajukan).
- Satu anggota hanya mendapat satu stempel per acara.
- Acara privat dan acara tanpa venue tidak bisa dipakai check-in.
- Check-in dibuka pada tanggal acara pukul 12.00 sampai esok harinya pukul 06.00 WIB (bisa diatur per acara).
- Membuat QR baru langsung membatalkan QR lama.

### Tugas rutin tim WSM

| Kapan         | Lakukan                                                                                                                                                       | Di mana (dashboard)                                          |
| ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| Kapan saja    | Ubah banner, header, profil, album, berita, merchandise, dan warna situs                                                                                      | Menu masing-masing                                           |
| Sebelum acara | Pastikan acara ada (impor CSV atau tombol **Sinkron sekarang** dari Bandsintown), lalu cetak QR-nya                                                           | Inner Circle, **Acara & QR**                                 |
| Setelah acara | Beri stempel manual bila perlu (mis. kegiatan resmi)                                                                                                          | Inner Circle, **Anggota**                                    |
| Setiap hari   | Proses klaim hadiah, isi kode voucher atau alasan penolakan, atau **Buka kembali** klaim yang ditolak                                                         | Inner Circle, **Klaim**                                      |
| Berkala       | Terbitkan atau sembunyikan pengumuman, sembunyikan kenangan yang tidak pantas, tampilkan atau sembunyikan tahap hadiah, uji kirim email, dan cek menu **Log** | **Pengumuman**, **Kenangan**, **Hadiah**, **Email**, **Log** |

**Batas ukuran gambar saat mengunggah**

| Gambar                                      | Maksimal    |
| ------------------------------------------- | ----------- |
| Banner dan foto hero profil                 | 2 MB        |
| Header, album, berita, merchandise          | 1 MB        |
| Video latar header                          | 25 MB       |
| Foto kenangan anggota / foto profil anggota | 4 MB / 5 MB |

Semua gambar sebaiknya di bawah 16 megapiksel (kira-kira 4000 x 4000 piksel). Video latar header hanya bisa diproses bila server punya program ffmpeg; di hosting bersama biasanya tidak ada, jadi gunakan gambar.

### Log, peringatan error, dan kode login

- **Menu Log** di dashboard punya dua tab. **Error** berisi masalah yang terjadi di website: error yang sama digabung dan dihitung, lalu bisa ditandai selesai. **Aktivitas admin** berisi siapa melakukan apa dan kapan (masuk, ubah konten, proses klaim). Isi form dan kata sandi tidak disimpan.
- **Peringatan lewat email:** bila alamat tujuan diisi (`LOG_ALERT_EMAIL`), error baru dikabari lewat email. Paling banyak satu email per jenis error per jam, dan sepuluh email per jam secara keseluruhan.
- **Kode login (2FA, opsional):** setelah memasukkan sandi, admin menerima kode 6 angka di email (berlaku 10 menit). Dinyalakan dengan `ADMIN_2FA_AKTIF=true`, **hanya setelah** email terbukti terkirim dan email admin terisi; kalau tidak, admin tidak bisa masuk. Bila terkunci, pengelola mengubahnya kembali ke `false`.

### Kondisi saat ini dan langkah menuju tayang

- Di server hanya halaman profil artis yang tayang. Inner Circle sudah ada di kode tetapi **sengaja dimatikan**.
- **Auto deploy lewat GitHub Actions + SSH**: setiap push ke `main` dites lalu dikirim ke hosting otomatis (lihat [Auto deploy](#auto-deploy-github-actions--ssh)).

Urutan menyalakan Inner Circle (langkah lengkap di [Panduan rilis ke hosting](#panduan-rilis-ke-hosting)):

1. Siapkan server (PHP 8.4+, HTTPS) dan cadangan database.
2. Isi pengaturan rahasia (`.env`) di server, termasuk sandi email.
3. Unggah kode terbaru (otomatis lewat auto deploy).
4. Buat tabel database baru dan **ganti sandi admin lama**.
5. Uji di alamat preview, lewat ponsel.
6. Siapkan login Google untuk publik.
7. Isi jadwal acara, hadiah, dan satu pengumuman, lalu nyalakan fitur.
8. Periksa daftar verifikasi pasca rilis.

**Mematikan darurat:** ubah `INNER_CIRCLE_AKTIF` menjadi `false` di `.env` server dan hapus cache. Semua halaman Inner Circle kembali 404, situs publik tidak terpengaruh.

### Yang tidak boleh terlewat

- **`.env` itu rahasia.** Jangan masuk git, jangan ikut dikirim di file zip, dan jangan pernah `APP_DEBUG=true` di server.
- **Kunci aplikasi (`APP_KEY`) dibuat sekali lalu tidak diubah.** Bila berubah, QR acara tidak bisa dibuka dan semua orang yang sedang login terkeluar. Simpan salinannya di pengelola kata sandi.
- **Sandi admin lama pernah tertulis di riwayat git**, anggap bocor dan ganti (langkahnya di [Ganti sandi admin](#ganti-sandi-admin)).
- **Jangan unggah `public/hot`** ke server; file itu membuat situs mencari tampilan ke komputer pengembang.
- Aktifkan **cadangan database terjadwal** di cPanel sebelum Inner Circle tayang, karena anggota mulai menyimpan data.

### Masalah yang sering muncul

| Gejala                                                 | Penyebab dan solusi                                                                                                                        |
| ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Tab Acara & QR menampilkan peringatan "MAC is invalid" | Kunci aplikasi berubah. Klik **Perbaiki token**, lalu cetak ulang QR acara itu                                                             |
| Email tidak terkirim                                   | Pengaturan email masih `log` atau sandi aplikasi Google belum diisi. Coba **Email tes** di tab Email                                       |
| Unggah video header gagal dengan pesan ffmpeg          | Server tidak punya ffmpeg. Pakai gambar JPG/PNG sebagai latar                                                                              |
| Unggah foto gagal dengan halaman "419 Page Expired"    | Batas unggah server terlalu kecil. Naikkan `upload_max_filesize` dan `post_max_size` ke minimal 6M di cPanel                               |
| Login Google menampilkan `redirect_uri_mismatch`       | Alamat callback di Google tidak sama persis dengan alamat situs                                                                            |
| Pesan "Terlalu banyak permintaan" saat lupa kata sandi | Batas kirim email tercapai. Tunggu beberapa saat; batas harian server 100 permintaan                                                       |
| Kode login tidak sampai ke email                       | Email Gmail belum disetel atau email admin belum diisi. Pengelola bisa mematikan kode login lewat `ADMIN_2FA_AKTIF=false` di `.env` server |
| Menu Log menulis "Email peringatan belum aktif"        | `LOG_ALERT_EMAIL` belum diisi di `.env` server                                                                                             |

---

## Bagian 2: Dokumentasi teknis

Bagian ini untuk developer.

### Stack dan hosting

| Bagian   | Isi                                                                                            |
| -------- | ---------------------------------------------------------------------------------------------- |
| Backend  | Laravel 13, **PHP 8.4+** (`composer.lock` memuat Symfony 8.1 yang butuh PHP >= 8.4.1), MySQL   |
| Frontend | Tailwind CSS 4, Vite, Swiper, SweetAlert2, Bootstrap Icons, Quill (CMS)                        |
| Hosting  | cPanel bersama (Rumahweb) dengan akses SSH; sesi, antrean, dan cache memakai driver `database` |
| Ekstensi | `gd` (dengan WebP), `exif`, `mbstring`, `pdo_mysql`                                            |
| Lainnya  | Zona waktu `Asia/Jakarta`; video header butuh `ffmpeg` (umumnya tidak ada di hosting bersama)  |

Route utama: situs publik `/` dan `/profile` (`robots.txt` dan `sitemap.xml` dibuat dinamis oleh `SeoController`), CMS `/dashboard` (login di `/login`), Inner Circle `/inner-circle/*` dan `/dashboard/inner-circle`, halaman `/privasi` (publik, selalu aktif).

### Menjalankan di lokal

Jalankan di terminal yang terbuka di folder project. Butuh PHP 8.4+, Composer, Node.js, MySQL.

```bash
composer install
cp .env.example .env                # Windows PowerShell: copy .env.example .env
php artisan key:generate
# isi DB_* di .env, lalu:
php artisan migrate --seed          # sandi admin acak dicetak sekali di terminal
php artisan storage:link
npm install
npm run dev                         # biarkan terbuka; di terminal kedua:
php artisan serve                   # http://127.0.0.1:8000
```

`npm run build` membuat berkas produksi di `public/build/`. Jangan unggah `public/hot`.

### Konfigurasi `.env`

`.env.example` berisi nilai yang aman untuk **server** (debug mati, cookie sesi aman, log harian, kredensial kosong, Inner Circle dan Google mati). Untuk lokal, salin ke `.env` lalu ubah `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL`, `DB_*`, dan `SESSION_SECURE_COOKIE=false` bila tidak memakai HTTPS. `.env` lokal jangan dipakai mentah-mentah di server.

| Variabel                                                                                                                        | Fungsi                                                                                                                    |
| ------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| `INNER_CIRCLE_AKTIF`                                                                                                            | Menyalakan seluruh Inner Circle (bawaan `false`)                                                                          |
| `INNER_CIRCLE_GOOGLE_AKTIF`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`                                  | Login Google. Tombol tampil hanya bila flag `true` dan ID + rahasia terisi                                                |
| `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | SMTP Gmail ([Email Gmail](#email-gmail)); `log` = tidak terkirim                                                          |
| `BANDSINTOWN_APP_ID`, `BANDSINTOWN_ARTIST`                                                                                      | Sinkron jadwal acara                                                                                                      |
| `KONTAK_PRIVASI_EMAIL`                                                                                                          | Email kontak di `/privasi` (kosong = mengarah ke Profil)                                                                  |
| `IC_SEED_CONTOH`                                                                                                                | Mengisi jadwal contoh saat `db:seed`. Hanya lokal/preview                                                                 |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD`                                                                                              | Akun admin pertama untuk seeder (sandi minimal 12 karakter). Wajib di produksi                                            |
| `ADMIN_EMAIL`, `ADMIN_2FA_AKTIF`                                                                                                | Email admin (tujuan kode OTP login; diisi ke akun hanya bila kolomnya masih kosong) dan saklar kode login. Bawaan `false` |
| `LOG_ALERT_EMAIL`                                                                                                               | Tujuan email peringatan error (halaman Log). Kosong = tanpa email                                                         |
| `TRUSTED_PROXIES`                                                                                                               | Isi bila di belakang proxy/CDN; kosong = tanpa proxy                                                                      |
| `MAVNUS_URL`                                                                                                                    | Alamat toko merchandise (bawaan `https://mavnus.com`)                                                                     |

`.env` tidak boleh masuk git atau zip. `.env` lokal (`APP_ENV=local`, `APP_DEBUG=true`, `IC_SEED_CONTOH=true`) jangan pernah terunggah ke server. `APP_KEY` dibuat sekali per server dan tidak diubah: token QR acara disimpan terenkripsi dengan kunci ini, dan sesi ikut terenkripsi.

### Tes

```bash
php artisan test
```

Terakhir **223 tes** (semua lulus; 15 tes gambar dilewati otomatis di mesin tanpa ekstensi `gd`). Database SQLite di memori; tes **tidak bergantung pada `.env`** (kunci aplikasi, kredensial, flag, dan cookie dipaku di `phpunit.xml`), jadi hasilnya sama di lokal dan di CI. Di CI: `cp .env.example .env` lalu `php artisan test`.

- `ImageProcessorTest` otomatis dilewati bila ekstensi `gd` dan `exif` tidak aktif; jalankan sekali di mesin yang memilikinya.
- SQLite tidak menguji kunci baris (`lockForUpdate`): coba klik ganda klaim dan suka secara manual di MySQL.
- Tes berkas memakai `Storage::fake()`; tes teks halaman memakai teks yang spasinya dilipat agar pemformat Blade tidak merusaknya.

### Peta kode

| Bagian                      | Lokasi                                                                                                                                                                                                                                                        |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CMS dan situs publik        | `dashboardController` (banner, header, album, berita, merchandise, warna), `profileController`, `homeController`, `loginController`                                                                                                                           |
| Login admin                 | Tabel `accounts` (kolom `email` untuk kode OTP), `session('login')`, middleware `cekLogin`, `AdminOtpService`, pembatas 5 percobaan/menit                                                                                                                     |
| Log                         | `logController` (halaman), `LogErrorService` (error; dipasang di `bootstrap/app.php`), `AktivitasService` + middleware `CatatAktivitas` (aktivitas admin), `config/log_admin.php`                                                                             |
| Halaman publik Inner Circle | `InnerCircle/tentangController`, layout `innerCirclePublikLayout`, view `pages/inner-circle/tentang` (teks draf, ubah langsung di berkas ini)                                                                                                                 |
| Inner Circle: controller    | `app/Http/Controllers/InnerCircle/` (auth, member, admin, kenangan, fotoProfil, akun, google, lupaSandi, emailAdmin)                                                                                                                                          |
| Inner Circle: logika        | `app/Services/InnerCircle/` (`CheckinService`, `ClaimService`, `StempelService`, `AcaraService`, `QrService`, `KenanganService`, `PengumumanService`, `AvatarService`, `GoogleOAuth`, `GoogleAkun`, `EmailService`, `HapusAkunService`, `BandsintownService`) |
| Validasi                    | `app/Http/Requests/InnerCircle/` (satu Form Request per aksi tulis, pesan Bahasa Indonesia)                                                                                                                                                                   |
| Gambar                      | `app/Support/ImageProcessor.php` (area anggota); `convertToWebP()` di dua controller CMS (JPEG/PNG, tanpa mengubah ukuran)                                                                                                                                    |
| Konfigurasi                 | `config/inner_circle.php` (flag, ikon, batas, status klaim), `config/site.php`                                                                                                                                                                                |
| Tampilan                    | Layout `innerCircleLayout`, `innerCircleAuthLayout`; komponen `components/inner-circle/`; `resources/css/inner-circle.css` dan `resources/js/inner-circle.js`                                                                                                 |
| Jadwal                      | `routes/console.php` (`ic:sinkron-bandsintown` tiap 04.00, hanya bila Inner Circle aktif dan App ID terisi)                                                                                                                                                   |

Konvensi: nama model dan tabel huruf kecil (`member`, `ic_event`), primary key `id_<nama>`, controller `lowerCamel`, controller tipis dengan logika di service. Nama model huruf kecil tidak ditemukan penemuan otomatis policy, jadi `MemoryPolicy` didaftarkan manual di `AppServiceProvider`.

### Keputusan arsitektur Inner Circle

- **Flag fitur:** semua route di belakang middleware `icAktif` (404 bila `INNER_CIRCLE_AKTIF` mati). Link di dashboard, navbar, dan teaser home hanya muncul bila flag menyala.
- **Tampilan terpisah:** layout dan CSS sendiri; semua class berprefix `ic-` di bawah `.ic-app` supaya tidak bocor ke halaman lain. CSS biasa (tanpa Tailwind), JavaScript vanilla, SweetAlert2 bertema lavender sendiri. Satu layar = satu route = satu Blade = satu `<h1>`.
- **Autentikasi anggota:** model `member`, guard `member`, provider `members`. Login admin tidak disentuh. Logout anggota memakai `Auth::guard('member')->logout()` dan `regenerateToken()`, **bukan** `session()->invalidate()`, agar sesi admin di browser yang sama tidak ikut terhapus.
- **Halaman penjelasan publik:** `/inner-circle/tentang` (`ic.tentang`), tanpa login. Satu-satunya halaman Inner Circle yang boleh diindeks: `robots.txt` memberi `Allow` sebelum `Disallow`, dan halaman ada di sitemap bila flag menyala. Tamu yang membuka `/inner-circle` dialihkan ke sini oleh `redirectGuestsTo`; area anggota lain tetap ke Masuk. Hanya tahap hadiah aktif dan acara mendatang yang tidak privat dan sudah punya venue yang ditampilkan.
- **Log error:** `Exceptions::report()` memanggil `LogErrorService`. Hanya error tak terduga yang sampai ke sana (Laravel tidak melaporkan validasi, 404, login, CSRF, dan `abort()`). Error digabung per sidik jari (kelas + berkas:baris) dengan penghitung; URL disimpan tanpa query string, pesan disamarkan dari pola sandi dan token, jejak tanpa argumen fungsi. Pencatat tidak pernah melempar galat. Email peringatan: satu per jenis error per jam dan paling banyak 10 per jam di seluruh situs (cache + `RateLimiter`). Pemangkasan berjalan sendiri sesekali tanpa cron: error 90 hari, aktivitas 180 hari.
- **Log aktivitas:** middleware `CatatAktivitas` di grup `/dashboard` mencatat request tulis (bukan GET). Label dari `config/log_admin.php`; yang disimpan hanya nama aksi, parameter rute, dan nilai pendek dari daftar putih (`aksi`, `aktif`, `status`, `tahap`). Gagal validasi tidak dicatat (dicek lewat `_flash.new` supaya sisa flash request sebelumnya tidak ikut). Username yang diketik saat login gagal sengaja tidak disimpan, karena kadang yang terketik di kolom itu justru kata sandi.
- **Kode login admin (OTP email):** sandi benar, lalu `AdminOtpService` mengirim kode 6 angka. Hanya hash HMAC (dengan `APP_KEY`) yang disimpan di cache, berlaku 10 menit, sekali pakai, 5 kali salah menghanguskannya, dan paling banyak 3 kali kirim per 10 menit per akun. Sesi baru dibuat di dua langkah (cegah session fixation) dan `login` baru diset setelah kode benar. **Gagal terbuka** bila email akun kosong (dicatat sebagai "Login tanpa 2FA"; supaya salah konfigurasi tidak mengunci pemilik), **gagal tertutup** bila email gagal terkirim (dashboard tidak terbuka, kesalahan masuk halaman Log). Darurat: `ADMIN_2FA_AKTIF=false`.
- **Pembatas bernama:** `throttle:N,M` bawaan memakai satu penghitung per IP untuk semua rute tanpa pengguna terautentikasi, jadi rute yang memakainya saling berbagi jatah. Rute baru (OTP, aksi kecil dashboard) memakai pembatas bernama di `AppServiceProvider`. Rute admin lama masih memakai `throttle:N,M`.
- **Status acara** (lewat, hari ini, akan datang) dihitung dari `now()` saat dibaca; tanpa cron.
- **Tanpa Socialite:** OAuth 2.0 (authorization code + PKCE, OpenID Connect) ditulis dengan HTTP client bawaan agar tidak ada paket baru untuk diunggah ke hosting tanpa terminal. Bila kelak pindah ke Socialite, hanya `GoogleOAuth` yang diganti.
- **Email tanpa antrean:** dikirim langsung setelah respons ke browser (`EmailService::tunda`), karena hosting tanpa worker.
- **Token QR terenkripsi** (`APP_KEY`), jadi tidak bisa dicari lewat SQL: pencarian dari token membandingkan hanya acara yang jendelanya sedang terbuka. Bila `APP_KEY` berubah, halaman menampilkan peringatan dengan tombol **Perbaiki token** (membuat token baru).

### Aturan check-in, klaim, dan konten

**Check-in** (`CheckinService`). QR berisi `/inner-circle/check-in?kode=WS-XXXXXXXXXXXX`; dipindai kamera bawaan HP pun sampai ke halaman yang benar setelah login. Server memeriksa: anggota login, acara bisa check-in (bukan privat, ada venue), di dalam jendela waktu (bawaan tanggal show 12.00 sampai H+1 06.00 WIB), token cocok, belum pernah check-in di acara itu. Acara yang sudah punya check-in tidak bisa dihapus. Impor CSV bersifat semua atau tidak sama sekali; kode yang sudah ada diperbarui tanpa mengganti token. Kamera pemindai butuh HTTPS.

**Klaim** (`ClaimService`). Hanya bila **email anggota sudah terverifikasi** dan `stempel_total >= target_stempel`, sekali per anggota per tahap; baris anggota dikunci dalam transaksi dan kunci unik `(member_id, reward_id)` jadi pengaman terakhir. Tahap bernilai pilihan (50 stempel) wajib memilih salah satu dari tepat 2 opsi. Alur: `diajukan` → `ditinjau` → `disetujui` → `selesai`, atau `ditolak` (alasan wajib). Perpindahan status membawa status yang dilihat admin, sehingga klik ganda tidak melompat dua langkah. Setiap perubahan masuk `ic_claim_logs`. Aksi **Catatan** mengubah `catatan_admin` (mis. kode voucher) tanpa mengubah status. Aksi **Buka kembali** hanya untuk `ditolak`: status kembali ke `diajukan` (alur mulai dari awal), catatan lama (alasan penolakan) hilang dari tampilan anggota tetapi tetap ada di `ic_claim_logs`, dan anggota diberi email. `selesai` adalah akhir alur.

**Verifikasi email** (`authController`, `EmailService::kirimVerifikasi`). Akun Google otomatis terverifikasi. Akun yang daftar dengan kata sandi menerima tautan bertanda tangan (berlaku 24 jam) dan **wajib membukanya saat sudah masuk**; tamu dialihkan ke Masuk lalu kembali ke tautan itu. Karena itu orang yang mendaftarkan email orang lain tidak bisa mengesahkannya: pemilik email tidak bisa masuk ke akun itu, dan penyerang tidak menerima emailnya. Tautan milik akun lain, kedaluwarsa, atau diubah ditolak. Kirim ulang lewat tombol di halaman Hadiah, dibatasi per anggota (2/menit, 10/hari) dan **200/hari untuk seluruh situs** agar pendaftaran massal tidak menghabiskan kuota email. Tanpa email yang berfungsi, anggota sandi tidak bisa mengajukan hadiah (anggota Google tidak terpengaruh). Pesan "Email ini sudah terdaftar" di form daftar sengaja dibiarkan karena pendaftaran langsung masuk; menyamarkannya butuh alur "cek email dulu".

**Kata sandi anggota** (`App\Rules\SandiAnggota`). Dipakai di daftar manual dan atur ulang: 8 sampai 72 karakter, wajib huruf besar, huruf kecil, dan angka; simbol boleh; hanya karakter keyboard biasa (emoji dan huruf non-ASCII ditolak). Login tidak memeriksa aturan ini sehingga akun lama tetap bisa masuk.

**Tampil/sembunyikan.** Tahap hadiah dan pengumuman punya tombol di CMS (kolom `aktif`). Tahap yang disembunyikan hilang dari halaman Hadiah anggota dan halaman penjelasan dan tidak bisa diklaim baru; pengajuan yang sudah masuk tetap diproses, dan minimal satu tahap harus aktif. Pengumuman yang disembunyikan tidak dihapus dan bisa ditampilkan lagi.

**Pengumuman.** Tampil bila `aktif` dan tanggal terbit sudah tiba (tanggal depan = terjadwal). Hanya satu yang boleh **unggulan**; menandai satu otomatis menurunkan yang lama (`PengumumanService`, dalam transaksi). Tujuan tombol hanya `ic.checkin`, `ic.hadiah`, atau `ic.komunitas`. Anggota melihat 20 pengumuman terbaru di luar unggulan. Menyimpan unggulan yang terjadwal jauh ke depan menurunkan unggulan lama sampai tanggalnya tiba.

**Kenangan.** Caption maks 240 karakter (wajib), tempat maks 65, kategori Festival / Acara klub / Momen pribadi, foto opsional. Langsung tayang; admin bisa menyembunyikan dan menayangkan lagi. Hanya pemilik yang bisa menghapus (`MemoryPolicy`). Nama di galeri disingkat ("Ancha M."). Suka lewat `POST /inner-circle/kenangan/{id}/suka` (JSON `{disukai, jumlah}`); kunci utama gabungan mencegah suka ganda. Galeri 12 per halaman.

**Foto anggota** (`ImageProcessor`). Format ditentukan dari isi berkas; JPG/PNG/WebP; EXIF diluruskan lalu disimpan ulang sebagai WebP dengan nama acak (metadata termasuk GPS terbuang); kenangan dikecilkan ke 1600 px dan profil 512 px; di atas 40 megapiksel atau tidak muat di `memory_limit` ditolak dengan pesan jelas. Foto avatar lama dihapus setelah yang baru tersimpan.

**Gambar CMS** (`convertToWebP`). Hanya JPG/PNG; ditolak di atas 16 megapiksel (pengaman bom dekompresi) sebagai pesan validasi, bukan error 500. Pada pembaruan, gambar baru diproses dulu dan gambar lama dihapus sesudahnya. Video header: dicek ada tidaknya ffmpeg di awal, hasilnya MP4 H.264 tanpa audio, 12 detik pertama, 1280 px lebar; bila ada langkah gagal, semua berkas baru dibersihkan (tanpa file yatim).

**Hapus akun** (`HapusAkunService`). Profil, **Akun dan privasi**, **Hapus akunku**: ketik `HAPUS` (dan kata sandi bila akunnya punya sandi). Terhapus permanen: akun, foto, stempel, klaim beserta lognya, kenangan beserta fotonya, dan tanda suka. **Ditahan** bila ada klaim berstatus `diajukan`, `ditinjau`, atau `disetujui`.

**`/privasi`.** Isi di `resources/views/pages/privasi.blade.php`; ubah `privasi_diperbarui` di `config/site.php` setiap isi berubah. Teksnya draf; tinjau sebelum rilis.

### Skema database

| Tabel             | Kolom utama                                                                                                                                                                                           |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `members`         | `id_member`, `kode_member` (unik, `IC-00001`), `nama`, `email` (unik), `password` (boleh kosong untuk akun Google), `avatar`, `stempel_total` (cache), `google_id`, `email_verified_at`, `sesi_versi` |
| `ic_events`       | `id_event`, `kode` (unik), `tanggal`, `kota`, `venue`, `tipe` (festival / club / private), `token` (terenkripsi), `token_dibuat_pada`, `jendela_mulai`, `jendela_selesai`                             |
| `ic_stempel`      | `id_stempel`, `member_id`, `jumlah`, `sumber` (checkin / manual), `event_id`, `judul`, `ikon`, `catatan`, `dicatat_oleh`. Unik `(member_id, event_id)`. Hanya ditambah                                |
| `ic_rewards`      | `id_reward`, `target_stempel` (unik), `judul`, `deskripsi`, `ikon`, `warna`, `opsi` (json), `urutan`, `aktif`                                                                                         |
| `ic_claims`       | `id_claim`, `member_id`, `reward_id`, `opsi_dipilih`, `status`, `catatan_admin`, `diproses_oleh`. Unik `(member_id, reward_id)`                                                                       |
| `ic_claim_logs`   | `id_log`, `claim_id`, `status`, `oleh`, `catatan`                                                                                                                                                     |
| `ic_broadcasts`   | `id_broadcast`, `judul`, `isi`, `tag`, `ikon`, `warna`, `cta_label`, `cta_tujuan`, `unggulan`, `terbit_pada`, `aktif`                                                                                 |
| `ic_memories`     | `id_memory`, `member_id`, `caption`, `tempat`, `kategori`, `foto`, `status` (tayang / disembunyikan)                                                                                                  |
| `ic_memory_likes` | `member_id`, `memory_id` (kunci utama gabungan, tanpa model)                                                                                                                                          |
| `log_errors`      | `id_log_error`, `fingerprint` (unik), `kelas`, `pesan`, `lokasi`, `jejak`, `url`, `metode`, `ip`, `pengguna`, `jumlah`, `selesai`, `pertama_pada`, `terakhir_pada`                                    |
| `log_aktivitas`   | `id_log_aktivitas`, `pelaku`, `kategori` (akses / konten / inner-circle), `aksi`, `keterangan`, `rute`, `ip`, `status` (berhasil / gagal), `created_at`                                               |

Tabel `accounts` (admin CMS) punya kolom `email` untuk kode OTP login. Check-in bukan tabel sendiri: ia baris `ic_stempel` dengan `sumber = checkin`. `members.stempel_total` hanya cache yang diperbarui di transaksi yang sama. Kolom `aktif` di `ic_rewards` dan `ic_broadcasts` dikendalikan tombol Sembunyikan/Aktifkan di CMS. Lima tahap hadiah (10, 25, 50, 100, 250) diisi lewat migration dengan `firstOrCreate`, jadi hasil edit di CMS tidak tertimpa.

### Batas unggahan dan pembatas laju

| Pembatas               | Batas                                           | Dihitung per                       |
| ---------------------- | ----------------------------------------------- | ---------------------------------- |
| Login admin            | 5/menit; 10/menit                               | username+IP; IP                    |
| Kode OTP admin         | 10/menit                                        | IP                                 |
| Kirim ulang kode OTP   | 3/menit; 3 kirim per 10 menit                   | IP; akun admin                     |
| Aksi kecil dashboard   | 30/menit (tampil/sembunyikan, status error)     | IP                                 |
| `ic-masuk`             | 5/menit; 60/menit                               | email+IP; IP                       |
| `ic-daftar`            | 30/menit                                        | IP                                 |
| `ic-lupa`              | 3/menit; 5/jam; 30/menit; **100/hari (global)** | email+IP; email; IP; seluruh situs |
| `ic-atur`              | 10/menit                                        | IP                                 |
| `ic-verifikasi`        | 2/menit; 10/hari; **200/hari (global)**         | anggota; anggota; seluruh situs    |
| `ic-google`            | 60/menit                                        | IP                                 |
| `ic-checkin`           | 8/menit                                         | anggota                            |
| `ic-klaim`             | 6/menit                                         | anggota                            |
| `ic-kenangan`          | 4/menit; 20/hari                                | anggota                            |
| `ic-suka`              | 60/menit                                        | anggota                            |
| `ic-foto`              | 6/menit                                         | anggota                            |
| `ic-akun` (hapus akun) | 5/menit                                         | anggota                            |

Pembatas anggota tidak memakai IP karena penonton satu venue sering berbagi IP. Batas anggota ada di `config/inner_circle.php`; sisanya di `AppServiceProvider`.

| Unggahan CMS                                                          | Batas                                                                                             |
| --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Banner, foto hero profil                                              | 2 MB                                                                                              |
| Album, berita, merchandise, gambar header, latar header berupa gambar | 1 MB                                                                                              |
| Latar header berupa video (sebelum dikompres)                         | 50 MB                                                                                             |
| Semua gambar CMS                                                      | maks 16 megapiksel                                                                                |
| Warna header                                                          | harus `#RRGGBB`                                                                                   |
| Foto kenangan / foto profil anggota                                   | 4 MB / 5 MB (maks 40 megapiksel)                                                                  |
| Kata sandi anggota                                                    | 8 sampai 72 karakter (batas bcrypt); wajib huruf besar + kecil + angka; simbol boleh, emoji tidak |

### File login Google

| File                                                              | Peran                                                                        |
| ----------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| `app/Services/InnerCircle/GoogleOAuth.php`                        | URL izin Google, tukar kode dengan token, periksa id_token                   |
| `app/Services/InnerCircle/GoogleAkun.php`                         | Mencocokkan identitas Google dengan anggota (masuk, buat baru, atau kaitkan) |
| `app/Http/Controllers/InnerCircle/googleController.php`           | Route `ic.google.masuk` dan `ic.google.callback`                             |
| `app/Http/Middleware/IcSesiVersi.php`                             | Mengeluarkan sesi anggota yang sudah dicabut                                 |
| `resources/views/components/inner-circle/tombol-google.blade.php` | Tombol sesuai pedoman merek Google                                           |

Aturan keamanannya ada di [aturan login Google](#aturan-login-google).

### Lupa kata sandi

Halaman `/inner-circle/lupa-sandi`; tautan berlaku 60 menit dan sekali pakai. Jawaban selalu sama untuk semua email (tidak membocorkan siapa yang punya akun) dan email dikirim setelah respons. Akun yang dibuat lewat Google menerima email yang mengarahkan **masuk dengan Google**. Memakai tautan memverifikasi email, mengganti token "ingat saya", dan mengeluarkan semua sesi lama.

### Panduan rilis ke hosting

Memasang situs (dengan Inner Circle) ke hosting cPanel **tanpa terminal**. Kerjakan berurutan.

| #   | Tahap                  | Hasil                                       |
| --- | ---------------------- | ------------------------------------------- |
| 1   | Server siap            | PHP 8.4+, ekstensi, HTTPS                   |
| 2   | `.env` server          | Konfigurasi aman                            |
| 3   | Build dan unggah       | Kode terbaru di server                      |
| 4   | Database               | Tabel Inner Circle ada, sandi admin diganti |
| 5   | Preview dan uji        | Teruji di HTTPS dan ponsel                  |
| 6   | Google untuk publik    | Login Google berjalan                       |
| 7   | Data awal dan nyalakan | Inner Circle tayang                         |
| 8   | Verifikasi             | Semua tercek                                |

#### Cek sebelum deploy production

Empat hal ini sering lolos tanpa disadari dan bisa membuat situs putih, bocor, atau email tidak terkirim. Selesaikan sebelum tahap 3.

1. **Cache lokal jangan ikut terunggah.** File di `bootstrap/cache/` (`packages.php`, `services.php`, `config.php`) dibuat di komputer lokal dan memuat paket dev (pail, tinker, collision). Di server yang memakai `composer install --no-dev`, paket itu tidak ada sehingga muncul galat "Class not found" dan halaman putih.
    - Kecualikan `bootstrap/cache/*` dari unggahan (folder dan `.gitignore`-nya tetap ada), dan hapus file sisa `bootstrap/cache/pac5194.tmp`.
    - Di server, hapus file cache yang terlanjur ada: cPanel > **File Manager** > folder `bootstrap/cache` > pilih semua file selain `.gitignore` > **Delete**. Setelah ada akses SSH: jalankan `php artisan optimize:clear` di folder Laravel server (perintah ini membersihkan semua cache Laravel).
2. **`.env` lokal jangan sampai ikut.** Isinya `APP_DEBUG=true`, DB user `root`, dan `IC_SEED_CONTOH=true`. Bila menimpa `.env` server, kode bisa bocor lewat halaman galat dan data contoh masuk produksi.
    - Kecualikan `.env` dari unggahan dan dari auto deploy.
    - Di server pakai isi `.env.example` dengan `APP_ENV=production`, `APP_DEBUG=false`, `IC_SEED_CONTOH=false`.
3. **Email: cek akun pengirim.** Sandi Aplikasi hanya bisa dibuat untuk akun Google sungguhan (Gmail atau Workspace), bukan email domain biasa seperti `noreply@whisnusantika.com` yang dipakai lewat `smtp.gmail.com`.
    - Pakai Gmail `it.wsm2026@gmail.com` (sesuai `.env.example`), atau SMTP Rumahweb (lihat [Keputusan yang masih terbuka](#keputusan-yang-masih-terbuka)). Tambahkan SPF, DKIM, dan DMARC di DNS domain supaya email tidak masuk spam.
    - Email juga dipakai verifikasi akun sandi: tanpa email yang berfungsi, anggota yang daftar dengan sandi tidak bisa mengajukan hadiah.
    - Selama `MAIL_PASSWORD` kosong, pakai `MAIL_MAILER=log` di lokal. Biarkan `ADMIN_2FA_AKTIF=false` sampai email tes terbukti terkirim.
4. **Login Google: status harus _In production_.** Bila masih _Testing_, hanya akun penguji yang bisa masuk (langkah 6 di [Google Cloud untuk publik](#google-cloud-untuk-publik)). Pastikan `GOOGLE_REDIRECT_URI` (bila diisi) sama persis dengan redirect URI yang didaftarkan di Google Cloud.

#### Server

- cPanel **MultiPHP Manager**: PHP **8.4 atau lebih**. **MultiPHP INI Editor**: `upload_max_filesize` dan `post_max_size` minimal `6M` (bila terlalu kecil, unggah foto gagal dengan "419 Page Expired"). Untuk mengunggah **video latar header** (batas 50 MB) naikkan keduanya ke `52M`; bila batas server lebih kecil dari 50 MB, unggahan video gagal sebelum sampai ke aplikasi.
- Ekstensi aktif: `gd` (dengan WebP), `exif`, `mbstring`, `pdo_mysql`.
- HTTPS aktif (AutoSSL) dan **Force HTTPS Redirect** menyala.
- Disarankan satu subdomain **preview** (mis. `preview.whisnusantika.com`) dengan database sendiri.
- Aktifkan cadangan database terjadwal (cPanel Backup atau JetBackup).

#### `.env` server

- **Server baru (mis. preview):** unggah `.env.example` ke folder utama Laravel, ganti namanya jadi `.env` (File Manager, klik kanan, **Rename**), lalu isi semua baris `GANTI`. `APP_KEY` dibuat di komputer lokal dengan `php artisan key:generate --show` (diawali `base64:`); pakai kunci berbeda untuk tiap server.
- **Server utama (sudah live):** jangan ditimpa. Tambahkan hanya variabel yang belum ada, di bagian bawah.

| Variabel                                          | Nilai produksi                                                                              |
| ------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| `APP_ENV`, `APP_DEBUG`                            | `production`, `false`                                                                       |
| `APP_URL`                                         | `https://domain-server` (wajib https)                                                       |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`       | Dari cPanel **MySQL Databases** (biasanya berawalan nama akun)                              |
| `SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT`        | `true` (hanya bila HTTPS sudah aktif)                                                       |
| `KONTAK_PRIVASI_EMAIL`                            | Email yang dipantau, tampil di `/privasi`                                                   |
| `MAIL_*`                                          | Lihat bagian [Email Gmail](#email-gmail)                                                    |
| `INNER_CIRCLE_AKTIF`, `INNER_CIRCLE_GOOGLE_AKTIF` | `false` sampai langkah 7                                                                    |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`        | Kosong sampai langkah 6                                                                     |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD`                | Hanya untuk seeder; kosongkan sandinya setelah akun admin ada                               |
| `ADMIN_EMAIL`, `ADMIN_2FA_AKTIF`                  | Email admin dan saklar kode login; `ADMIN_2FA_AKTIF=false` sampai bagian "Kode login admin" |
| `LOG_ALERT_EMAIL`                                 | Tujuan email peringatan error (kosong = tanpa email)                                        |
| `TRUSTED_PROXIES`                                 | Kosong, kecuali di belakang proxy/CDN                                                       |

Aturan penulisan: buang komentar `#` di baris yang diisi; beri tanda kutip pada nilai yang mengandung spasi, `#`, `$`, atau `"` (mis. `DB_PASSWORD="sandi#ku"`).

Cek setelah menyimpan: buka `https://domain/.env` di browser (harus 404 atau 403) dan hapus `bootstrap/cache/config.php` bila ada.

#### Build dan unggah

1. Di mesin build (lokal atau workflow) dengan PHP 8.4: `composer install --no-dev --optimize-autoloader`, lalu `npm ci` dan `npm run build`.
2. Unggah semua, **termasuk** `vendor/` dan `public/build/`, **kecuali**: `.env`, `.git`, `node_modules`, `public/hot`, `storage/logs/*`, `bootstrap/cache/*` (lihat [Cek sebelum deploy production](#cek-sebelum-deploy-production)), dan `tests`.
3. Pastikan `public/robots.txt` **tidak ada** di server (route dinamis yang dipakai) dan `public/storage` tertaut ke `storage/app/public` (foto muncul).
4. Auto deploy: sudah ada, lihat [Auto deploy](#auto-deploy-github-actions--ssh). `.env` dikecualikan dari unggahan dan rahasia disimpan di GitHub Secrets, bukan di repo.

#### Auto deploy (GitHub Actions + SSH)

Berkas: `.github/workflows/deploy.yml`. Setiap push ke `main` menjalankan dua tahap; bila tes gagal, deploy tidak jalan.

1. **Tes**: `vendor/bin/phpunit` (sqlite memori, tanpa `.env`).
2. **Deploy**: build di GitHub (`composer install --no-dev`, `npm ci`, `npm run build`), kirim lewat `rsync` ke server, lalu jalankan `migrate --force` dan `optimize:clear`.

Yang **tidak pernah** disentuh di server: `.env`, `storage/`, `bootstrap/cache/`, `.git` server, `node_modules`, `public/hot` (malah dihapus), serta `index.php` dan `.htaccess` di `public_html`.

Yang dikirim ke dua tempat: kode ke folder Laravel (`APP_PATH`), `public/aset` dan `public/build` ke `public_html` (`PUBLIC_PATH`). Folder `public/` di dalam folder Laravel juga ikut terisi karena `public_path()` (dan manifest Vite) membaca dari sana.

**Secrets** (GitHub repo > Settings > Secrets and variables > Actions):

| Nama              | Isi                                                         |
| ----------------- | ----------------------------------------------------------- |
| `SSH_HOST`        | Host SSH hosting                                            |
| `SSH_PORT`        | Port SSH hosting                                            |
| `SSH_USER`        | Username cPanel                                             |
| `SSH_PRIVATE_KEY` | Isi kunci privat khusus deploy (seluruh file)               |
| `APP_PATH`        | Path folder Laravel di server (berisi `artisan` dan `.env`) |
| `PUBLIC_PATH`     | Path folder web publik di server (berisi `index.php`)       |

Opsional, di tab **Variables**: `PHP_BIN` bila `php` di SSH bukan versi 8.4 (cek dengan `php -v` di server).

**Rollback:** revert commit di GitHub lalu push; deploy jalan lagi dengan kode lama. Migrasi tidak ikut mundur, jadi buat migration baru bila perlu.

#### Database

1. Buat tabel baru di database server: `members`, semua `ic_*`, `log_errors`, `log_aktivitas`, kolom baru `accounts.email`, plus `sessions`, `cache`, dan `jobs`.
2. Tanpa terminal, pilih salah satu:
    - **(a)** di lokal jalankan `php artisan migrate` pada database kosong, lalu di phpMyAdmin **Export > Custom**: struktur semua tabel, dan **struktur + data** untuk `migrations` dan `ic_rewards` (lima tahap hadiah terisi lewat migration). Impor hasilnya lewat **Import** di database server.
    - **(b)** lewat auto deploy: workflow menjalankan `php artisan migrate --force` di setiap deploy, jadi phpMyAdmin tidak diperlukan lagi.
3. Ganti sandi admin lama (lihat [Ganti sandi admin](#ganti-sandi-admin)).
4. Isi email admin (tujuan kode OTP login) di phpMyAdmin: `UPDATE accounts SET email = 'alamat@email.com' WHERE username = 'username-admin';` Atau isi `ADMIN_EMAIL` lalu jalankan `php artisan db:seed --class=accountSeeder` (email hanya diisi bila masih kosong).

#### Preview dan uji

Di preview (HTTPS), uji di ponsel: login Google dan email, kamera pindai QR, unggah foto dari galeri HP (termasuk HEIC iPhone), tampilan 375 px, klaim hadiah dua kali cepat (kunci MySQL), hapus akun, halaman `/privasi`, dan header keamanan (DevTools, tab Network).

#### Google Cloud untuk publik

Gratis: login dengan scope dasar (`openid`, `email`, `profile`) tidak ditagih dan tidak butuh akun penagihan. Abaikan tawaran **Try for free**; bila Console meminta kartu, berhenti dan periksa dulu. Nama menu bisa sedikit berbeda karena Google sering mengubah tampilan.

1. Buat **project terpisah** untuk produksi (mis. `WS Inner Circle Produksi`) dengan akun Google organisasi WSM.
2. **Google Auth Platform > Get started**: nama aplikasi, email dukungan, audiens **External**, email kontak.
3. **Branding:** beranda `https://whisnusantika.com`, kebijakan privasi `https://whisnusantika.com/privasi`, authorized domain `whisnusantika.com`. Kosongkan logo dulu (logo memicu verifikasi merek).
4. **Data Access:** hanya `openid`, `.../auth/userinfo.email`, `.../auth/userinfo.profile`.
5. **Clients > Create client**, **Web application**, redirect URI persis:
    - produksi: `https://whisnusantika.com/inner-circle/google/callback`
    - preview: `https://<domain-preview>/inner-circle/google/callback`
    - lokal: `http://127.0.0.1:8000/inner-circle/google/callback` (dan versi `localhost` bila kadang dibuka lewat itu)

    Pakai satu client untuk lokal/preview dan satu untuk produksi supaya rahasianya terpisah. Salin **Client ID** dan **Client secret** saat itu juga (secret hanya tampil sekali).

6. **Audience > Publish app** agar status berubah dari _Testing_ ke _In production_. Selama _Testing_, hanya akun di **Test users** yang bisa masuk dan izin kedaluwarsa tiap 7 hari. Bila diminta verifikasi domain, lakukan lewat Google Search Console.
7. Isi `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `.env` server. Rahasia hanya di `.env` server dan secret manager, bukan di repo atau chat.

| Galat dari Google       | Penyebab dan solusi                                                             |
| ----------------------- | ------------------------------------------------------------------------------- |
| `redirect_uri_mismatch` | Alamat callback tidak sama persis (cek `https`, garis miring, spasi)            |
| `access_denied`         | Status masih _Testing_ dan akun belum jadi penguji, atau pengguna menekan batal |
| `invalid_client`        | Client ID/secret salah atau belum terbaca (hapus cache konfigurasi)             |

Uji lokal: isi `.env` (`INNER_CIRCLE_AKTIF=true`, `INNER_CIRCLE_GOOGLE_AKTIF=true`, ID dan secret), jalankan `php artisan migrate` dan `php artisan config:clear`, lalu buka `http://127.0.0.1:8000/inner-circle/masuk` (alamat harus sama dengan yang terdaftar). Coba tiga skenario: email baru, email yang sudah terdaftar lewat sandi, dan tombol Batal di halaman Google.

#### Data awal dan menyalakan

1. Isi CMS: jadwal acara, hadiah, dan satu pengumuman. Pastikan `IC_SEED_CONTOH` tidak `true`.
2. Isi `KONTAK_PRIVASI_EMAIL` dan tinjau teks `/privasi` (draf; tinjau oleh pengelola dan penasihat hukum bila perlu).
3. Set `INNER_CIRCLE_GOOGLE_AKTIF=true`, lalu `INNER_CIRCLE_AKTIF=true`.
4. Hapus cache: kosongkan isi `storage/framework/views/` dan hapus `bootstrap/cache/config.php` bila ada.

**Mematikan darurat:** set `INNER_CIRCLE_AKTIF=false` lalu hapus cache seperti di atas.

#### Kode login admin dan peringatan error (opsional)

1. Pastikan email Gmail terbukti terkirim (Inner Circle, tab **Email**, **Email tes**) dan kolom `email` akun admin terisi (lihat Database, langkah 4).
2. Isi `LOG_ALERT_EMAIL` dengan alamat yang dipantau. Peringatan error baru dikirim ke sana.
3. Set `ADMIN_2FA_AKTIF=true` lalu hapus cache konfigurasi. **Jangan logout dulu:** buka jendela penyamaran, coba login, dan pastikan kodenya sampai. Baru setelah itu logout dari jendela lama.
4. Terkunci (kode tidak sampai)? Ubah `ADMIN_2FA_AKTIF=false` di `.env` server, hapus `bootstrap/cache/config.php`, lalu login seperti biasa dan periksa pengaturan email.

#### Verifikasi pasca rilis

- [ ] `/robots.txt` memuat `Disallow: /inner-circle`; `/sitemap.xml` memuat `/privasi`.
- [ ] `https://domain/.env` tidak terbuka; header `X-Frame-Options` dan `Strict-Transport-Security` ada.
- [ ] Login Google dengan akun yang **bukan** penguji berhasil, dari ponsel.
- [ ] Alur QR: tamu memindai, masuk lewat Google, kembali ke check-in.
- [ ] `/privasi` terbuka tanpa login dan email kontak benar.
- [ ] Hapus akun uji berhasil (foto ikut hilang).
- [ ] `/inner-circle/tentang` terbuka tanpa login, dan `/inner-circle` untuk tamu mengarah ke sana.
- [ ] Menu **Log** terbuka. Bila 2FA dinyalakan: kode sampai ke email dan login berhasil di jendela penyamaran.
- [ ] Email tes dari tab Email terkirim.
- [ ] `composer audit` dan `npm audit` dari lokal bersih.

### Email Gmail

Email (lupa kata sandi, akun Google, selamat datang, verifikasi email, status hadiah) dikirim lewat **SMTP Gmail** dengan **Sandi Aplikasi**, langsung setelah respons ke browser (tanpa antrean). Selama `MAIL_MAILER=log`, email tidak terkirim.

1. Pilih akun Google pengirim. Di <https://myaccount.google.com/security> aktifkan **Verifikasi 2 Langkah**.
2. Di <https://myaccount.google.com/apppasswords> buat sandi aplikasi (mis. `WS Inner Circle`) dan salin 16 hurufnya (hanya tampil sekali). Bila menu tidak ada: akun Workspace dibatasi admin, atau Verifikasi 2 Langkah belum aktif.
3. Isi `.env` server:

    ```
    MAIL_MAILER=smtp
    MAIL_SCHEME=smtps
    MAIL_HOST=smtp.gmail.com
    MAIL_PORT=465
    MAIL_USERNAME=alamat-gmail-pengirim@gmail.com
    MAIL_PASSWORD=sandiaplikasi16huruf
    MAIL_FROM_ADDRESS=alamat-gmail-pengirim@gmail.com
    MAIL_FROM_NAME="WS Inner Circle"
    ```

    Tulis sandi aplikasi tanpa spasi. Bila port 465 gagal, coba `MAIL_PORT=587` dan `MAIL_SCHEME=smtp`.

4. Hapus `bootstrap/cache/config.php` bila ada, lalu kirim **Email tes** dari dashboard, tab **Email**.

Batasan:

- `MAIL_FROM_ADDRESS` harus sama dengan `MAIL_USERNAME` (atau alias yang terdaftar di Gmail, Setelan > Akun > Kirim email sebagai); Gmail menimpa alamat pengirim lain.
- Kuota sekitar 500 penerima per hari (Gmail biasa) atau 2.000 (Workspace): cukup untuk lupa sandi dan notifikasi, tidak untuk surat massal.
- Sebagian hosting bersama memblokir sambungan keluar ke port 465/587. Bila tes gagal dengan `Connection could not be established`, minta Rumahweb membuka sambungan keluar ke `smtp.gmail.com`.
- Sengaja memakai Sandi Aplikasi, bukan OAuth: kirim email lewat OAuth butuh scope Gmail yang dibatasi Google dan token yang harus diperbarui. Client OAuth untuk login anggota tetap terpisah.

### Jadwal otomatis Bandsintown

1. Isi `BANDSINTOWN_APP_ID` (butuh persetujuan Bandsintown; lihat _API installation_ di Bandsintown for Artists). `BANDSINTOWN_ARTIST` bawaannya `id_6784724`. App ID widget Tour belum tentu diterima API ini.
2. Manual: dashboard, tab **Acara & QR**, tombol **Sinkron sekarang**. Acara baru dibuat dengan QR sendiri; acara yang ada tidak ditimpa dan **tidak pernah dihapus otomatis** (yang hilang dari Bandsintown hanya dilaporkan). Tipe acara ditebak dari nama (memuat festival/fest/open air = festival, selain itu club); ubah bila perlu.
3. Otomatis pukul 04.00 WIB: di cPanel, **Cron Jobs**, tiap menit:
   `* * * * * /usr/local/bin/php /home/USERNAME/laravel/artisan schedule:run >> /dev/null 2>&1`
   (sesuaikan folder Laravel dan path PHP). Tanpa cron, pakai tombol di dashboard.

### Keamanan

Audit awal dilakukan pada commit `b18c7ea` (Fase 7), lalu dilanjutkan dengan perbaikan bertahap. Audit ini mengurangi risiko, bukan menjamin situs bebas celah.

**Cakupan:** konfigurasi dan `.env`, login admin dan anggota, middleware, validasi dan unggahan, semua keluaran tanpa escape (`{!! !!}`), SQL mentah, redirect, mass assignment, `HtmlSanitizer` (diuji dengan payload XSS), `npm audit`, dan pencocokan `composer.lock` dengan database advisory (snapshot 20 Oktober 2026: 110 paket, 0 cocok).
**Di luar cakupan:** uji penetrasi, server dan hosting sungguhan, konfigurasi cPanel, dan `composer audit` resmi (jalankan sendiri).

#### Ganti sandi admin

Sandi admin lama pernah tertulis di `accountSeeder` (ada di riwayat git), jadi anggap bocor. Kodenya sudah diperbaiki (kredensial dari `.env`), tetapi **sandi di setiap database** (lokal, preview, produksi) yang dibuat dari seeder lama harus diganti:

1. Di komputer, jalankan (ganti sandinya, minimal 12 karakter):
    ```bash
    php -r "echo password_hash('SANDI-BARU-YANG-PANJANG', PASSWORD_BCRYPT), PHP_EOL;"
    ```
2. cPanel, **phpMyAdmin**, database situs, tabel `accounts`, **Edit** baris admin, tempel hash ke kolom `password`, lalu simpan. Ubah `username` bila perlu.
3. Coba login di `/login`. Simpan sandi di pengelola kata sandi, bukan di chat atau repo.

#### Temuan audit dan status

| Tingkat | Temuan                                                                                            | Status                                                                                                |
| ------- | ------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Tinggi  | Username dan sandi admin tertulis di seeder                                                       | Kode diperbaiki; **sandi lama harus diganti** (di atas)                                               |
| Sedang  | `HtmlSanitizer` meloloskan `<svg><script>` dan atribut di tag tak dikenal                         | Diperbaiki dan dites; teks dibersihkan lagi saat ditampilkan                                          |
| Sedang  | `public/robots.txt` statis menimpa route dinamis, sehingga `Disallow: /inner-circle` tidak tayang | Berkas statis dihapus                                                                                 |
| Sedang  | `npm audit`: 10 kerentanan (semua devDependencies)                                                | `npm audit fix`, tersisa 0                                                                            |
| Sedang  | `composer.json` menulis PHP `^8.3` padahal lock butuh 8.4.1                                       | Disamakan `^8.4`                                                                                      |
| Sedang  | Tidak ada header keamanan                                                                         | Middleware `SecurityHeaders` (nosniff, frame, referrer, permissions, HSTS di produksi)                |
| Sedang  | Login anggota hanya dibatasi per IP                                                               | Pembatas per email+IP dan per IP (lihat [batas dan pembatas laju](#batas-unggahan-dan-pembatas-laju)) |
| Rendah  | Waktu respons login admin membedakan username yang ada                                            | Hash selalu dicek (hash pengganti)                                                                    |
| Rendah  | `.env.example` berisi `APP_DEBUG=true`, tanpa contoh `.env` server                                | `.env.example` kini berisi nilai aman untuk server                                                    |
| Info    | Zip kerja memuat `.env` dan `public/hot`                                                          | Jangan unggah atau kirim keduanya                                                                     |
| Rendah  | Kata sandi di form daftar/masuk ikut tersimpan di sesi saat validasi gagal                        | `dontFlash` untuk `sandi`, dites                                                                      |
| Sedang  | Kolom link CMS menerima `javascript:`                                                             | Wajib URL `http(s)://`                                                                                |

**Perbaikan terbaru** (sudah di-push, 184 tes lulus):

- Batas **harian global** untuk lupa kata sandi (100/hari) agar kuota email hosting tidak habis oleh serangan dengan banyak email dan IP. Efek samping yang disadari: penyerang bisa menghabiskan kuota itu dan membuat fitur tidak tersedia sampai esok hari.
- Unggah video header tidak lagi error 500 dan tidak meninggalkan file yatim; berkas lama baru dihapus setelah data tersimpan.
- Warna header wajib `#RRGGBB`; banner dan foto hero maks 2 MB; semua gambar CMS maks 16 megapiksel.
- Kata sandi anggota maksimal 72 karakter. bcrypt hanya membaca 72 **byte**, jadi kata sandi dengan banyak karakter non-Latin atau emoji tetap bisa melewati batas dan sisanya diabaikan; ini tidak merusak login.

**Perbaikan Batch B** (hasil 223 tes):

- Login admin dengan **kode OTP lewat email** (opsional, mati secara bawaan). Lihat [aturannya di Bagian 2](#keputusan-arsitektur-inner-circle).
- Halaman **Log**: error aplikasi (digabung, disamarkan) dan **jejak aktivitas admin**, plus email peringatan error yang dibatasi (satu per jenis per jam, maksimal 10 per jam).
- Pembatas bernama untuk rute baru, karena `throttle:N,M` bawaan berbagi satu penghitung per IP.

**Perbaikan Batch C** (hasil 240 tes):

- **Verifikasi email** untuk akun sandi sebelum klaim hadiah (aturannya di Bagian 2, bagian Aturan check-in, klaim, dan konten).
- **Login admin** memakai pembatas bernama `admin-login`: 5/menit per username+IP dan 10/menit per IP, tidak lagi berbagi penghitung dengan rute `throttle:N,M` lain. `ADMIN_PASSWORD` minimal 12 karakter sudah dicek seeder. Nyalakan `ADMIN_2FA_AKTIF=true` setelah email terbukti terkirim.
- **Kata sandi anggota** wajib huruf besar + kecil + angka, tanpa emoji (daftar manual dan atur ulang).
- **Batas video latar header** dinaikkan dari 25 MB ke 50 MB (dicek di controller; batas upload server harus ikut naik).
- Catatan temuan, **belum diperbaiki**: `ic-kenangan` memakai dua batas (per menit dan per hari) dengan kunci yang sama, sehingga keduanya berbagi satu penghitung dan batas harian tidak pernah berlaku. Pembatas baru `ic-verifikasi` memakai kunci berbeda untuk tiap batas.

#### Aturan login Google

Semuanya punya tes otomatis.

1. **`state`, `nonce`, dan PKCE** per permintaan, disimpan di sesi dan dibuang setelah dipakai; kedaluwarsa 10 menit.
2. **id_token diperiksa:** penerbit (`iss`), penerima (`aud`), kedaluwarsa (`exp`), dan `nonce`. Tanda tangan tidak diverifikasi ulang karena token diterima langsung dari Google lewat HTTPS dengan client secret.
3. **Email harus terverifikasi oleh Google.** Pencocokan memakai `google_id` (`sub`) lebih dulu, baru email.
4. **Cegah pengambilalihan akun.** Pendaftaran sandi tidak memverifikasi email, jadi orang lain bisa mendaftarkan email korban lebih dulu. Saat korban masuk lewat Google, akun dikaitkan, **sandi lama dihapus**, token "ingat saya" diganti, dan `sesi_versi` naik sehingga sesi penyerang berakhir. Cara yang sama berlaku saat email diverifikasi lewat tautan atur ulang sandi.
5. Akun yang sudah tertaut ke akun Google lain ditolak. Akun hanya-Google tidak bisa masuk dengan sandi (pesannya sama dengan "sandi salah").
6. **Token Google tidak disimpan**; hanya `sub`, email, dan nama. Foto profil Google tidak dipakai.
7. Galat (batal, sesi habis, state salah, Google menolak atau tidak terjangkau) kembali ke halaman masuk dengan pesan jelas; log hanya memuat status dan jenis galat.
8. Login admin CMS sengaja tidak memakai Google.

#### Belum dikerjakan (urut prioritas)

1. **Cadangan database terjadwal** di cPanel, serta **HTTPS paksa** dan AutoSSL aktif. Wajib sebelum Inner Circle tayang.
2. **Content-Security-Policy.** Belum dipasang karena widget Bandsintown, Google Fonts, dan embed video bisa mati bila terlalu ketat. Pasang dulu sebagai `Content-Security-Policy-Report-Only` di preview, perbaiki pelanggaran di konsol browser, baru ditegakkan.
3. ~~Verifikasi email untuk akun sandi~~ Selesai (Batch C).
4. **Login admin:** masih satu bendera sesi tanpa peran. Log aktivitas dan kode OTP email sudah ada; peran dengan hak berbeda baru perlu bila tim bertambah.
5. **`composer audit` dan `npm audit` rutin** (tiap bulan atau di workflow deploy).
6. **Pembatas lama `throttle:N,M`** di rute admin (hadiah, pengumuman, kenangan, dsb.) berbagi satu penghitung per IP. Dampaknya kecil (admin sendiri), tetapi sebaiknya diganti pembatas bernama sekalian dibersihkan.

### Riwayat dan keputusan

Catatan bagaimana WS Inner Circle dibangun. Detail per file ada di riwayat git; yang dicatat di sini hanya hal yang masih berguna untuk memahami keputusan. Acuan awal: `WS_Inner_Circle.html` (prototype satu file, data di `localStorage`) dan 19 screenshot di `inner-circle-ui-prototype.zip`. Rencana dibuat 21 Oktober 2026.

#### Fase pengerjaan

Urutan kerja: **tampilan dulu dengan data contoh, backend menyusul per fitur.** Setelah fase 3, tiap fase hanya mengganti sumber data, bukan tampilannya.

| Fase | Isi                                                                                                                                              | Status  |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------ | ------- |
| 1    | Fondasi UI: layout, CSS, komponen, flag fitur, 15 ikon WebP                                                                                      | Selesai |
| 2    | UI anggota (masuk, daftar, beranda, hadiah, check-in, komunitas, profil) dengan data contoh                                                      | Selesai |
| 3    | UI admin di dashboard (7 tab) dengan data contoh                                                                                                 | Selesai |
| 4    | Anggota, check-in QR, dan stempel sungguhan di database                                                                                          | Selesai |
| 5    | Hadiah dan klaim di database                                                                                                                     | Selesai |
| 6    | Komunitas (pengumuman, kenangan, suka) dan foto profil                                                                                           | Selesai |
| 7    | Teaser di home, link navbar, `robots.txt`, penghapusan data contoh, QA aksesibilitas                                                             | Selesai |
| 8    | Login dengan Google, halaman `/privasi`, hapus akun                                                                                              | Selesai |
| 9    | Perbaikan token QR, sinkron Bandsintown, lupa kata sandi, email Gmail, tab Email                                                                 | Selesai |
| 10   | Halaman penjelasan program, buka kembali klaim, tampil/sembunyikan hadiah dan pengumuman, halaman Log dan peringatan error, kode OTP login admin | Selesai |

Yang tersisa bukan kode, melainkan [rilis ke hosting](#panduan-rilis-ke-hosting): uji perangkat nyata, setup Google dan email, auto deploy.

**Uji perangkat nyata sebelum rilis** (tidak bisa dicek tanpa perangkat): kamera pindai QR di Safari iOS dan Chrome Android (hanya jalan lewat HTTPS), unggah foto langsung dari HP (miring, besar, HEIC), tampilan 375 px untuk semua layar anggota, dan klik ganda klaim dan suka di MySQL.

#### Catatan per fase

- **Fase 4.** Token QR menentukan acara; kode acara di halaman hanya petunjuk, jadi anggota bisa langsung memindai tanpa memilih acara. Kolom `ic_stempel.catatan` menyimpan alasan stempel manual. `qr-scanner` dipakai karena `BarcodeDetector` tidak ada di Safari iOS dan Firefox. Dependensi baru: `bacon/bacon-qr-code` dan `qr-scanner`.
- **Fase 5.** Lima tahap hadiah diisi lewat migration (bukan hanya seeder) karena deploy manual hanya menjalankan `migrate`. Mengubah opsi tahap tidak mengubah pengajuan yang sudah masuk. Anggota baru sempat membawa `stempel_total` null di memori; sekarang bernilai bawaan 0.
- **Fase 6.** Nama di galeri disingkat agar nama lengkap tidak terlihat sesama anggota. Fitur "laporan kenangan" dibuang karena tidak ada di rencana; moderasi lewat tombol Sembunyikan. Pengumuman diedit lewat `?ubah=ID` (bisa dibagikan, jalan tanpa JS). Paginasi galeri memakai tautan buatan sendiri karena tampilan bawaan Laravel butuh kelas Tailwind yang tidak dimuat di area anggota.
- **Fase 7.** Audit statis CSS: target sentuh minimal 44 px, `prefers-reduced-motion` mematikan animasi di seluruh `.ic-app`, pasangan warna teks utama lolos 4.5:1. Satu pengecualian disengaja: kata beraksen ungu di judul besar berkontras 3.3:1, memenuhi syarat teks besar (3:1).
- **Fase 8.** Login Google berjalan berdampingan dengan email + sandi.
- **Fase 9.** Galat "The MAC is invalid" terjadi bila `APP_KEY` berbeda dari saat token dibuat (mis. `key:generate` dijalankan lagi, `.env` diganti, atau database dipindah). Sekarang halaman menampilkan peringatan dengan tombol **Perbaiki token**; token lama tidak bisa dipulihkan.

#### Penyesuaian dari prototype

Prototype dibuat untuk didemokan, bukan dijalankan. Yang diubah atau dibuang:

- **Struktur:** SPA satu halaman jadi halaman terpisah; gerbang login bersama untuk anggota dan admin dipisah (admin tetap lewat `/login`); sidebar, toggle Anggota/Admin, tombol simulasi, dan semua teks "demo/dummy" dibuang; navigasi hanya dock bawah. Filter jadi parameter GET yang dirender server.
- **Tampilan:** empat lapis CSS prototype dirapikan jadi satu berkas (tema Soft Lavender); font DM Sans dan Space Grotesk; emoji diganti ikon gambar tetap agar identik di semua perangkat; glyph dock diganti Bootstrap Icons.
- **Bug prototype yang diperbaiki:** tombol "undefined" di antrean klaim (status disatukan jadi `diajukan`, `ditinjau`, `disetujui`, `selesai`, `ditolak`); bahasa campur diseragamkan ke Indonesia; pilihan hadiah 50 stempel tidak lagi memakai `prompt()` tetapi modal dua kartu; tanggal acak `2026-10-13` yang ditanam diganti perhitungan dari `now()`; kata sandi minimal naik dari 6 ke 8 untuk anggota baru.
- **Pindah dari browser ke server:** akun, stempel, hadiah, klaim, acara, pengumuman, dan kenangan semuanya jadi tabel database; QR dibuat dan diverifikasi di server; foto diunggah ke disk dan dikonversi WebP.

#### Keputusan yang masih terbuka

Bawaan di bawah dipakai sampai ada keputusan lain.

- **Moderasi kenangan:** langsung tayang, admin bisa menyembunyikan. Alternatif: disetujui admin dulu.
- **Avatar bawaan:** emoji sebelum anggota unggah foto. Alternatif: inisial nama.
- **Jendela check-in:** tanggal show 12.00 sampai H+1 06.00 WIB. Sesuaikan bila jam show WSM berbeda.
- **Halaman `/inner-circle` untuk tamu:** dialihkan ke halaman penjelasan `/inner-circle/tentang`. Teksnya draf; sesuaikan di `resources/views/pages/inner-circle/tentang.blade.php`.
- **Klaim ditolak:** bisa dibuka kembali oleh admin (kembali ke Diajukan, alasan lama tetap di riwayat).
- **Email admin untuk OTP:** kolom `email` terpisah dari `username`, jadi login tetap memakai username.
- **Ikon:** memakai Fluent Emoji 3D (lisensi MIT, cek ulang sebelum dipakai) atau ilustrasi buatan tim WSM.
- **SMTP lewat domain sendiri (`mail.whisnusantika.com`):** belum dikerjakan, dicatat untuk nanti. Perlu dipelajari cara membuat akun email di cPanel Rumahweb, lalu mengisi `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, dan `MAIL_PASSWORD` dari akun itu. Sampai diputuskan, pakai SMTP Gmail di [Email Gmail](#email-gmail).
- **Data acara:** 50 baris di prototype adalah contoh tentatif; jadwal asli diisi tim WSM lewat CMS, impor CSV, atau Bandsintown.

#### Di luar cakupan

- QR yang berputar otomatis dan verifikasi lokasi (GPS). Mitigasi sekarang: jendela waktu, token per acara, pembatas per anggota.
- Notifikasi WhatsApp dan Instagram, serta pesan antar anggota.
- Integrasi akun atau voucher dengan Mavnus; hadiah merchandise dipenuhi manual lewat antrean klaim.
- Login Google untuk **admin** CMS.
- Perubahan pada login admin dan tema situs publik di luar perbaikan keamanan.
