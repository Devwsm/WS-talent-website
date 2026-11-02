# WS Talent Website

Situs resmi DJ **Whisnu Santika** (`whisnusantika.com`) beserta komunitas penggemarnya, **WS Inner Circle**. Satu project Laravel yang berisi dua produk: situs artis dengan CMS-nya, dan komunitas Inner Circle dengan CMS-nya.

Status: tayang di production dengan auto deploy (GitHub Actions + SSH). Terakhir **285 tes lulus**.

## Daftar isi

1. [WS Artis](#1-ws-artis)
2. [WS Inner Circle](#2-ws-inner-circle)
3. [Halaman dan fitur](#3-halaman-dan-fitur)
4. [Arsitektur file](#4-arsitektur-file)
5. [Auto deploy](#5-auto-deploy)
6. [Rencana: pisah CMS artis dan CMS Inner Circle](#6-rencana-pisah-cms-artis-dan-cms-inner-circle-sementara)

---

## 1. WS Artis

Situs profil dan promosi untuk DJ Whisnu Santika, dikelola timnya lewat CMS tanpa perlu menyentuh kode.

**Untuk siapa:** pengunjung umum (penggemar, penyelenggara acara, media) dan tim WSM yang mengisi konten.

**Isinya:**

- **Beranda (`/`)**: banner, header video/gambar, album, berita, carousel merchandise, jadwal tur (widget Bandsintown), cuplikan profil, dan teaser Inner Circle.
- **Profil (`/profile`)**: hero, genre, bio, kolaborasi, liputan media, kontak booking, media sosial, statistik, dan highlight.
- **Merchandise** tidak dijual di sini: semua tautan mengarah ke toko `mavnus.com`.
- **SEO**: `robots.txt` dan `sitemap.xml` dibuat dinamis oleh `SeoController`.

**CMS Artis (`/dashboard`)** mengelola semua isi di atas. Login lewat `/login` (kode OTP email bersifat opsional). Tema CMS gelap dengan aksen merah tua; warna universal situs publik diatur dari menu **Warna Web**.

Aturan penting CMS Artis: gambar JPG/PNG otomatis dikonversi ke WebP (maks 16 megapiksel), video header dikompres bila `ffmpeg` ada, teks kaya (Quill) dibersihkan oleh `HtmlSanitizer`, dan semua aksi tulis admin tercatat di menu **Log**.

---

## 2. WS Inner Circle

Komunitas penggemar resmi. Penggemar **hadir di acara, mengumpulkan stempel, dan membuka hadiah**. Tujuannya mengubah kehadiran di konser menjadi hubungan jangka panjang dengan penggemar.

**Perjalanan anggota:**

1. **Daftar** dengan email + kata sandi, atau dengan akun Google.
2. **Datang ke acara** dan **check-in** dengan memindai QR panitia. Untuk acara yang punya titik lokasi, HP harus berada di dalam radius venue.
3. **Kumpulkan stempel.** Satu acara = satu stempel, jumlahnya tidak pernah berkurang.
4. **Ajukan hadiah** di tiap tahap (5 tahap, dari 10 sampai 250 stempel). Admin memproses: diajukan, ditinjau, disetujui, selesai, atau ditolak. Anggota dikabari lewat halaman Hadiah dan email.
5. **Komunitas**: membaca pengumuman resmi WSM, membagikan kenangan (foto + cerita), dan menyukai kenangan anggota lain.

**Aturan yang perlu diketahui:**

- Check-in, unggah kenangan, suka, dan klaim hadiah butuh **email terverifikasi**. Akun Google otomatis terverifikasi.
- Token QR milik **acara** dan terenkripsi dengan `APP_KEY`. Jendela check-in bawaan: tanggal show 12.00 sampai H+1 06.00 WIB (bisa diubah per acara).
- **Lokasi check-in:** titik dan radius milik acara (bukan milik akun CMS), jadi banyak petugas boleh memakai satu akun. Koordinat anggota tidak disimpan, hanya jarak dan akurasinya. Detail di [4.9](#49-aturan-bisnis-penting).
- Anggota bisa **menghapus akunnya sendiri** (kecuali ada klaim yang sedang diproses). Kebijakan ada di `/privasi`.
- Inner Circle dikendalikan **flag** `INNER_CIRCLE_AKTIF`. Bila mati, semua route-nya 404 dan menu hilang dari dashboard.

**Tampilan terpisah** dari situs artis: tema lavender sendiri, CSS dan JS sendiri (semua class berprefix `ic-` di bawah `.ic-app`), dengan navigasi dock di bawah.

---

## 3. Halaman dan fitur

### 3.1 Situs publik

| Halaman              | Alamat                        | Isi                                                                                            |
| -------------------- | ----------------------------- | ---------------------------------------------------------------------------------------------- |
| Beranda              | `/`                           | Banner, header, album, berita, merchandise, tur, profil singkat, teaser Inner Circle           |
| Profil               | `/profile`                    | Profil lengkap artis                                                                           |
| Kebijakan privasi    | `/privasi`                    | Selalu aktif; kontak dari `KONTAK_PRIVASI_EMAIL`                                               |
| Tentang Inner Circle | `/innercircle/tentang`        | Penjelasan program, hadiah, jadwal, FAQ. Satu-satunya halaman Inner Circle yang boleh diindeks |
| SEO                  | `/robots.txt`, `/sitemap.xml` | Dinamis                                                                                        |
| Alamat lama          | `/inner-circle/...`           | Dialihkan permanen (301) ke `/innercircle/...` supaya QR cetakan lama tetap jalan              |

### 3.2 Area anggota Inner Circle (`/innercircle/*`)

| Halaman          | Alamat                          | Fitur                                                                                                |
| ---------------- | ------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Masuk / Daftar   | `/innercircle/masuk`, `/daftar` | Email + sandi atau Google; sandi wajib huruf besar, kecil, angka                                     |
| Lupa sandi       | `/innercircle/lupa-sandi`       | Tautan email berlaku 60 menit, sekali pakai                                                          |
| Beranda          | `/innercircle`                  | Kartu anggota, progres stempel, aktivitas terbaru                                                    |
| Check-in         | `/innercircle/check-in`         | Filter jadwal, lembar check-in dari bawah, pindai QR (kamera atau unggah gambar), pemeriksaan lokasi |
| Hadiah           | `/innercircle/hadiah`           | Tahap hadiah, ajukan klaim, pilih opsi (tahap 50 stempel), status                                    |
| Komunitas        | `/innercircle/komunitas`        | Pengumuman, galeri kenangan, suka, unggah kenangan                                                   |
| Profil           | `/innercircle/profil`           | Foto profil, riwayat stempel, pengajuan hadiah, hapus akun                                           |
| Verifikasi email | `/innercircle/verifikasi/...`   | Tautan bertanda tangan, berlaku 24 jam                                                               |

### 3.3 CMS Artis (`/dashboard`)

CMS dibagi dua **mode** dengan navbar bawah yang identik (dock di desktop, bottom sheet di HP); yang beda hanya menu dan tema. **Saklar mode** ada di sebelah tombol Keluar: di CMS Artis tombolnya "CMS Inner Circle", di CMS Inner Circle tombolnya "CMS Artis". Saklar hanya muncul bila `INNER_CIRCLE_AKTIF` menyala. Mode ditentukan dari alamat (route `ic.admin.*` = CMS Inner Circle), bukan dari sesi. Satu akun admin untuk kedua mode.

| Menu                      | Fungsi                                                                                       |
| ------------------------- | -------------------------------------------------------------------------------------------- |
| Dashboard                 | Ringkasan                                                                                    |
| Banner                    | Banner beranda (gambar, maks 2 MB)                                                           |
| Headers                   | Header beranda (gambar atau video latar)                                                     |
| Profile                   | 9 bagian: hero, genre, bio, kolaborasi, liputan, booking, media sosial, statistik, highlight |
| Warna Web                 | Warna universal situs publik                                                                 |
| Albums, News, Merchandise | CRUD konten beranda                                                                          |
| Log                       | Error aplikasi (digabung, disamarkan) dan jejak aktivitas admin                              |

### 3.4 CMS Inner Circle (`/dashboard/inner-circle`)

Tema lavender seperti area anggota, tetapi dibuat lembut di mata (latar lavender muda, teks ungu-tua, bukan putih murni). Menu CMS ini ada di dock: Anggota, Acara & QR, Klaim (dengan lencana jumlah yang menunggu), Hadiah, Pengumuman, Kenangan, Konten, Email; tombol rumah membuka Ringkasan.

| Tab        | Fungsi                                                                                                                                                         |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Ringkasan  | Angka ringkas komunitas                                                                                                                                        |
| Anggota    | Daftar anggota, tambah stempel manual (untuk anggota yang gagal check-in)                                                                                      |
| Acara & QR | Tambah/ubah/hapus acara, impor CSV, sinkron Bandsintown, QR cetak, perbaiki token, **titik lokasi check-in**                                                   |
| Klaim      | Proses pengajuan hadiah per status                                                                                                                             |
| Hadiah     | Ubah tahap hadiah, tampil/sembunyikan                                                                                                                          |
| Pengumuman | CRUD, unggulan (hanya satu), terjadwal, tampil/sembunyikan                                                                                                     |
| Kenangan   | Moderasi (sembunyikan/tayangkan)                                                                                                                               |
| Konten     | Ubah teks halaman anggota (judul, penjelasan, dst.) tanpa menyentuh kode; kosongkan = teks bawaan. Saat ini baru halaman Check-in, grup lain menyusul bertahap |
| Email      | Pratinjau format email, cek pengaturan kirim, email tes                                                                                                        |

### 3.5 Fitur lintas

- **Login admin** dengan pembatas percobaan dan **kode OTP email** (opsional, `ADMIN_2FA_AKTIF`).
- **Log**: error tak terduga dan jejak aksi admin, plus email peringatan error yang dibatasi.
- **Email** (lupa sandi, verifikasi, selamat datang, status klaim) lewat SMTP Gmail, dikirim setelah respons tanpa antrean.
- **Sinkron Bandsintown**: tombol manual dan otomatis pukul 04.00 WIB (butuh cron).
- **Header keamanan** untuk semua halaman (`SecurityHeaders`).

---

## 4. Arsitektur file

### 4.1 Stack dan menjalankan di lokal

| Bagian       | Isi                                                                                                                                                             |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Backend      | Laravel 13, **PHP 8.4+** (`composer.lock` memuat Symfony 8.1), MySQL                                                                                            |
| Frontend     | Tailwind CSS 4 + Vite (CMS dan situs artis), CSS biasa + JS vanilla (Inner Circle), Swiper, SweetAlert2, Bootstrap Icons, Quill (editor teks CMS), `qr-scanner` |
| Hosting      | cPanel bersama (Rumahweb) dengan SSH; sesi, antrean, dan cache memakai driver `database`                                                                        |
| Ekstensi PHP | `gd` (dengan WebP), `exif`, `mbstring`, `pdo_mysql`                                                                                                             |
| Lainnya      | Zona waktu `Asia/Jakarta`; video header butuh `ffmpeg` (umumnya tidak ada di hosting bersama)                                                                   |

Jalankan di terminal yang terbuka di folder project (VS Code: Terminal, New Terminal). Butuh PHP 8.4+, Composer, Node.js, MySQL.

```bash
composer install
cp .env.example .env                # Windows PowerShell: copy .env.example .env
php artisan key:generate
# isi DB_* di .env, lalu:
php artisan migrate --seed          # sandi admin dari ADMIN_PASSWORD (min. 12 karakter)
php artisan storage:link
npm install
npm run dev                         # biarkan terbuka; di terminal kedua:
php artisan serve                   # http://127.0.0.1:8000
```

`npm run build` membuat berkas produksi di `public/build/`. `public/hot` hanya ada saat `npm run dev` jalan dan **tidak boleh** ikut ke server.

Konvensi: nama model dan tabel huruf kecil (`member`, `ic_event`), primary key `id_<nama>`, controller `lowerCamel`, controller tipis dengan logika di service, satu Form Request per aksi tulis (pesan Bahasa Indonesia). Tailwind v4: `@apply` tidak boleh merangkai class kustom ke class kustom, tulis daftar utility penuh.

### 4.2 Struktur folder

```
app/
  Http/Controllers/      controller CMS Artis, situs publik, dan InnerCircle/
  Http/Middleware/       gerbang login, flag, header keamanan, pencatat aktivitas
  Http/Requests/InnerCircle/   satu Form Request per aksi tulis
  Services/              logika bisnis (Services/InnerCircle/ untuk Inner Circle)
  Models/                model Eloquent (huruf kecil)
  Mail/                  email (Mail/InnerCircle/, Mail/Admin/)
  Policies/ Rules/ Support/    hak akses, aturan validasi, pengolah gambar & HTML
  Console/Commands/      perintah artisan kustom
  Providers/             AppServiceProvider (pembatas laju bernama, policy)
bootstrap/app.php        middleware, pelaporan error ke Log
config/                  inner_circle.php, ic_konten.php (teks yang bisa diubah dari CMS), site.php, log_admin.php, dst.
database/migrations/     36 migrasi;  database/seeders/  data awal
public/                  index.php, aset/ (logo, ikon Inner Circle), build/ (hasil Vite)
resources/css/           app.css (Tailwind), inner-circle.css (tema lavender)
resources/js/            app.js, dashboard.js, sweetalert.js, inner-circle.js
resources/views/         template/, pages/, components/, emails/, errors/
routes/                  web.php, console.php (jadwal)
tests/                   Feature/ (15 berkas), Unit/ (2 berkas)
.github/workflows/       deploy.yml (tes + auto deploy)
```

### 4.3 Backend: penjelasan per file

**Controller: situs dan CMS Artis** (`app/Http/Controllers/`)

| File                  | Peran                                                                           |
| --------------------- | ------------------------------------------------------------------------------- |
| `homeController`      | Beranda dan halaman profil publik                                               |
| `dashboardController` | CMS Artis: banner, headers, albums, news, merchandise, warna web; konversi WebP |
| `profileController`   | CMS Artis: 9 bagian profil                                                      |
| `loginController`     | Login admin, kode OTP, logout                                                   |
| `logController`       | Halaman Log (error dan aktivitas)                                               |
| `SeoController`       | `robots.txt` dan `sitemap.xml` dinamis                                          |

**Controller: Inner Circle** (`app/Http/Controllers/InnerCircle/`)

| File                   | Peran                                                                                 |
| ---------------------- | ------------------------------------------------------------------------------------- |
| `authController`       | Masuk, daftar, keluar anggota                                                         |
| `googleController`     | Masuk dengan Google (`ic.google.masuk`, `ic.google.callback`)                         |
| `lupaSandiController`  | Lupa dan atur ulang kata sandi                                                        |
| `memberController`     | Layar anggota: beranda, check-in, hadiah, komunitas, profil                           |
| `kenanganController`   | Unggah, hapus, dan suka kenangan                                                      |
| `fotoProfilController` | Ganti dan hapus foto profil                                                           |
| `akunController`       | Hapus akun anggota                                                                    |
| `tentangController`    | Halaman publik penjelasan program                                                     |
| `adminController`      | CMS Inner Circle: 8 tab (anggota, acara, lokasi, klaim, hadiah, pengumuman, kenangan) |
| `emailAdminController` | Tab Email: pratinjau, cek pengiriman, email tes                                       |
| `kontenController`     | Tab Konten: tampil, simpan per grup, kembalikan bawaan                                |

**Middleware** (`app/Http/Middleware/`): `CekLogin` (gerbang admin), `IcAktif` (flag fitur, 404 bila mati), `IcSesiVersi` (mengeluarkan sesi anggota yang dicabut), `IcTerverifikasi` (aksi anggota butuh email terverifikasi), `CatatAktivitas` (mencatat aksi tulis admin), `SecurityHeaders` (nosniff, frame, referrer, `Permissions-Policy` dengan `geolocation=(self)`, HSTS di produksi).

**Service** (`app/Services/`)

| File                                               | Peran                                                                                                                                                                                                     |
| -------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `AdminOtpService`                                  | Kode OTP 6 digit login admin (hash HMAC, 10 menit, sekali pakai)                                                                                                                                          |
| `AktivitasService`, `LogErrorService`              | Jejak aksi admin dan penyimpanan error (digabung per sidik jari, disamarkan)                                                                                                                              |
| `InnerCircle/CheckinService`                       | Semua aturan check-in (anggota, jendela waktu, token, lokasi, satu kali per acara)                                                                                                                        |
| `InnerCircle/LokasiService`                        | Jarak haversine dan penilaian radius + akurasi GPS                                                                                                                                                        |
| `InnerCircle/GeocodeService`                       | Cari koordinat dari alamat venue (Nominatim/OpenStreetMap)                                                                                                                                                |
| `InnerCircle/StempelService`                       | Satu-satunya tempat stempel ditambah (buku besar `ic_stempel`)                                                                                                                                            |
| `InnerCircle/ClaimService`                         | Aturan klaim hadiah dan alur status                                                                                                                                                                       |
| `InnerCircle/AcaraService`                         | Buat acara dan impor CSV                                                                                                                                                                                  |
| `InnerCircle/QrService`                            | QR check-in sebagai SVG (tanpa GD)                                                                                                                                                                        |
| `InnerCircle/BandsintownService`                   | Tarik jadwal tur ke `ic_events` (tidak pernah menghapus)                                                                                                                                                  |
| `InnerCircle/KenanganService`, `PengumumanService` | Galeri kenangan (terbit, suka, moderasi) dan pengumuman (satu unggulan)                                                                                                                                   |
| `InnerCircle/AvatarService`, `HapusAkunService`    | Foto profil dan penghapusan akun                                                                                                                                                                          |
| `InnerCircle/GoogleOAuth`, `GoogleAkun`            | OAuth Google (code + PKCE, OIDC) dan pencocokan akun                                                                                                                                                      |
| `InnerCircle/EmailService`                         | Semua email Inner Circle (kapan, ke siapa, contoh isi)                                                                                                                                                    |
| `InnerCircle/IcKonten`                             | Konten yang bisa diubah dari CMS: `teks(kunci)` = isi `ic_pengaturan` bila ada, kalau tidak teks bawaan dari `config/ic_konten.php`; `daftar(grup)`; cache dibuang tiap simpan; aman bila tabel belum ada |
| `InnerCircle/*Gagal`                               | Pengecualian berpesan aman untuk ditampilkan ke pengguna                                                                                                                                                  |

**Model** (`app/Models/`): situs artis (`banner`, `header`, `albums`, `news`, `merchandise`, `color_pages`, `statistik`, `highlight`, dan 7 model profil: `profile_hero`, `genre`, `bio`, `collab`, `media_coverage`, `booking`, `media_sosial`); admin dan log (`account`, `log_error`, `log_aktivitas`); Inner Circle (`member`, `ic_event`, `ic_stempel`, `ic_reward`, `ic_claim`, `ic_claim_log`, `ic_broadcast`, `ic_memory`, `ic_pengaturan`, `ic_item`).

**Lainnya:** `Mail/InnerCircle/*` (reset sandi, akun Google, selamat datang, status klaim, verifikasi; dasar bersama `PesanIc`) dan `Mail/Admin/*` (kode login, peringatan error); `Policies/MemoryPolicy` (hak atas kenangan, didaftarkan manual di `AppServiceProvider`); `Rules/SandiAnggota`; `Support/ImageProcessor` (foto anggota: EXIF diluruskan, WebP, metadata dibuang), `Support/HtmlSanitizer`, `Support/GambarGagal`, `Support/helpers.php` (fungsi view `ic_teks()` dan `ic_daftar()`, dimuat oleh `AppServiceProvider`, tidak perlu `composer dump-autoload`); `Console/Commands/SinkronBandsintown` dan `OptimalkanGambar`.

> `ic_event` juga memuat `pilihanBulan()` dan `scopeDiBulan()`: pilihan filter bulan di area anggota dan dashboard diambil dari tanggal acara yang ada, bukan daftar tetap.

### 4.4 Frontend: penjelasan per folder

| Lokasi                                                                           | Isi                                                                                                                                                                                                                                                                                               |
| -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `template/dashboardLayout`                                                       | Kerangka CMS Artis (gelap, Tailwind)                                                                                                                                                                                                                                                              |
| `template/icDashboardLayout`                                                     | Kerangka CMS Inner Circle: sama dengan `dashboardLayout`, bedanya `<body class="cms-ic">` yang mengganti tema warna                                                                                                                                                                               |
| `template/innerCircleLayout`, `innerCircleAuthLayout`, `innerCirclePublikLayout` | Kerangka area anggota, halaman masuk/daftar, dan halaman publik Inner Circle                                                                                                                                                                                                                      |
| `template/layout`                                                                | Kerangka situs publik artis                                                                                                                                                                                                                                                                       |
| `pages/`                                                                         | Halaman: `home`, `login`, `login-otp`, `privasi`, `dashboard-pages/` (CMS), `inner-circle/` (area anggota)                                                                                                                                                                                        |
| `pages/dashboard-pages/inner-circle/`                                            | 10 berkas CMS Inner Circle (ringkasan, anggota, acara, cetak-qr, klaim, hadiah, pengumuman, kenangan, konten, email). Semua memakai `icDashboardLayout` kecuali cetak QR yang berdiri sendiri                                                                                                     |
| `components/dashboard/`                                                          | `navbar` (dock + bottom sheet, **sadar mode**: menu Artis atau Inner Circle plus saklar mode), modal edit/hapus tiap modul, `inner-circle/kepala` (judul halaman CMS Inner Circle dan skrip bersama)                                                                                              |
| `components/inner-circle/`                                                       | Komponen area anggota: dock, kartu event, ubin hadiah, kartu kenangan, modal (varian lembar bawah), bar stempel, dst.                                                                                                                                                                             |
| `components/inner-circle-teaser`                                                 | Teaser Inner Circle di beranda artis (tema situs artis, bukan lavender)                                                                                                                                                                                                                           |
| `components/profile/`                                                            | Profil publik dan bagian-bagian form CMS profil                                                                                                                                                                                                                                                   |
| `emails/inner-circle/`                                                           | Tampilan email bersama                                                                                                                                                                                                                                                                            |
| `resources/js/inner-circle.js`                                                   | Modal bertumpuk, lembar check-in, geolokasi, pemindai QR, pembatas klik ganda                                                                                                                                                                                                                     |
| `resources/css/inner-circle.css`                                                 | Seluruh tema area anggota (`.ic-app`), satu berkas                                                                                                                                                                                                                                                |
| `resources/css/app.css`                                                          | Tailwind + tema CMS. `body.dash-theme` (CMS Artis) dan `body.cms-ic` (CMS Inner Circle) menimpa variabel `--color-*` Tailwind, jadi seluruh halaman berganti tema tanpa mengedit view. Bagian yang harus tetap warna asli (dock, kartu QR, pratinjau email) memakai class `cms-dock` / `cms-asli` |

### 4.5 Database

| Tabel                                      | Kolom utama                                                                                                                                                                                        |
| ------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `accounts`                                 | Admin CMS (`username`, `password`, `email` untuk OTP)                                                                                                                                              |
| `members`                                  | `kode_member` (`IC-00001`), `email`, `password` (kosong untuk akun Google), `avatar`, `stempel_total` (cache), `google_id`, `email_verified_at`, `sesi_versi`                                      |
| `ic_events`                                | `kode`, `tanggal`, `kota`, `venue`, `tipe`, `token` (terenkripsi), `jendela_mulai/selesai`, titik lokasi (`lokasi_lat`, `lokasi_lng`, `lokasi_radius`, `lokasi_sumber`, `lokasi_diatur_oleh/pada`) |
| `ic_stempel`                               | Buku besar, hanya ditambah. Unik `(member_id, event_id)`; `sumber` (checkin/manual), `jarak_m`, `akurasi_m`                                                                                        |
| `ic_rewards`, `ic_claims`, `ic_claim_logs` | Tahap hadiah (`target_stempel` unik), pengajuan (unik per anggota per tahap), jejak status                                                                                                         |
| `ic_broadcasts`                            | Pengumuman (`unggulan`, `terbit_pada`, `aktif`)                                                                                                                                                    |
| `ic_memories`, `ic_memory_likes`           | Kenangan (tayang/disembunyikan) dan tanda suka                                                                                                                                                     |
| `ic_pengaturan`                            | Teks konten yang **diubah** admin (`kunci` unik, `nilai`, `diubah_oleh`). Tanpa baris = teks bawaan, jadi tabel kosong tidak mengubah tampilan                                                     |
| `ic_item`                                  | Daftar berurutan untuk konten (`grup`, `urutan`, `judul`, `isi`, `ikon`, `aktif`); dipakai mulai Fase 3                                                                                            |
| `log_errors`, `log_aktivitas`              | Log error (digabung) dan jejak aksi admin                                                                                                                                                          |
| Situs artis                                | `banners`, `headers`, `albums`, `news`, `merchandise`, `color_pages`, `statistik`, `highlight`, tabel profil                                                                                       |
| Sistem                                     | `sessions`, `cache`, `jobs`, `migrations`                                                                                                                                                          |

Check-in bukan tabel sendiri: ia baris `ic_stempel` dengan `sumber = checkin`. `members.stempel_total` hanya cache yang diperbarui di transaksi yang sama.

### 4.6 Konfigurasi `.env`

`.env.example` berisi nilai aman untuk **server**. Untuk lokal, salin ke `.env` lalu ubah `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL`, `DB_*`, dan `SESSION_SECURE_COOKIE=false` bila tidak memakai HTTPS. **`.env` tidak boleh masuk git, zip, atau chat, dan `.env` lokal jangan pernah menimpa `.env` server.**

| Variabel                                                                                       | Fungsi                                                                                          |
| ---------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| `APP_KEY`                                                                                      | Dibuat sekali per server, **jangan diubah**: token QR dan sesi dienkripsi dengannya             |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`                                                    | Dari cPanel, MySQL Databases                                                                    |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD`                                                             | Akun admin pertama untuk seeder (sandi min. 12 karakter); kosongkan sandinya setelah akun ada   |
| `ADMIN_EMAIL`, `ADMIN_2FA_AKTIF`                                                               | Email tujuan OTP login dan saklar OTP (bawaan `false`)                                          |
| `LOG_ALERT_EMAIL`                                                                              | Tujuan email peringatan error (kosong = tanpa email)                                            |
| `INNER_CIRCLE_AKTIF`                                                                           | Menyalakan seluruh Inner Circle                                                                 |
| `INNER_CIRCLE_GOOGLE_AKTIF`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Login Google; tombol tampil hanya bila flag `true` dan ID + rahasia terisi                      |
| `MAIL_*`                                                                                       | SMTP pengirim email ([5.4](#54-layanan-pihak-ketiga)); `MAIL_MAILER=log` berarti tidak terkirim |
| `BANDSINTOWN_APP_ID`, `BANDSINTOWN_ARTIST`                                                     | Sinkron jadwal tur                                                                              |
| `KONTAK_PRIVASI_EMAIL`                                                                         | Email kontak di `/privasi`                                                                      |
| `IC_SEED_CONTOH`                                                                               | Mengisi jadwal contoh saat `db:seed`. Hanya lokal; **harus `false` di server**                  |
| `TRUSTED_PROXIES`, `MAVNUS_URL`                                                                | Proxy/CDN (kosong = tanpa) dan alamat toko merchandise                                          |

Aturan penulisan: buang komentar `#` di baris yang diisi; beri tanda kutip pada nilai yang mengandung spasi, `#`, `$`, atau `"`.

### 4.7 Tes

```bash
php artisan test
```

285 tes. Database SQLite di memori; tes **tidak bergantung pada `.env`** (semua env dipaku di `phpunit.xml`), jadi hasilnya sama di lokal dan di GitHub. `ImageProcessorTest` dilewati otomatis bila ekstensi `gd`/`exif` tidak aktif. SQLite tidak menguji kunci baris (`lockForUpdate`): coba klik ganda klaim dan suka secara manual di MySQL.

Berkas tes: `InnerCircleTest` (inti), `...Lokasi`, `...Hadiah`, `...Komunitas`, `...Keamanan`, `...Google`, `...VerifikasiEmail`, `...AkunPrivasi`, `...SitusPublik`, `...Fase9`, `...Fase10`, `...FilterBulan`, `...Konten`, `CmsMode`, `AdminLogDuaFaktor`, dan `Unit/` (`HtmlSanitizer`, `ImageProcessor`).

### 4.8 Keamanan (ringkas)

- Login admin: pembatas 5/menit per username+IP dan 10/menit per IP, hash selalu dicek, OTP email opsional. Sandi admin pernah tertulis di seeder lama (ada di riwayat git): **anggap bocor dan pastikan sandi di setiap database sudah diganti.**
- Login anggota dibatasi per email+IP dan per IP. Pembatas bernama di `AppServiceProvider` (batas anggota di `config/inner_circle.php`).
- Login Google: `state`, `nonce`, PKCE; id_token diperiksa; email wajib terverifikasi Google; akun sandi yang dikaitkan ke Google kehilangan sandinya (cegah pengambilalihan). Token Google tidak disimpan.
- Semua `{!! !!}` diperiksa; teks kaya dibersihkan `HtmlSanitizer`; JSON-LD memakai `JSON_HEX_TAG`.
- Unggahan: tipe ditentukan dari isi berkas, batas ukuran dan megapiksel, EXIF/GPS dibuang dari foto anggota.
- `.env`, `storage/`, `bootstrap/cache/` tidak pernah dikirim oleh auto deploy.

**Belum dikerjakan (urut prioritas):** cadangan database terjadwal + HTTPS paksa; Content-Security-Policy (pasang dulu sebagai `Report-Only` di preview karena widget Bandsintown dan Google Fonts); peran admin (saat ini satu bendera sesi tanpa peran); `composer audit` dan `npm audit` rutin; ganti `throttle:N,M` lama di rute admin dengan pembatas bernama; `ic-kenangan` memakai dua batas dengan kunci yang sama sehingga batas harian tidak pernah berlaku; pin host key SSH di workflow (sekarang `ssh-keyscan` mempercayai host saat itu juga).

### 4.9 Aturan bisnis penting

**Check-in** (`CheckinService`). QR berisi `/innercircle/check-in?kode=WS-XXXXXXXXXXXX`. Server memeriksa: anggota login dan terverifikasi, acara bisa check-in (bukan privat, ada venue), di dalam jendela waktu, token cocok, belum pernah check-in di acara itu. Token menentukan acara; bila token milik acara lain yang jendelanya terbuka, stempel dicatat ke acara pemilik token. Pembatas 8/menit per anggota.

**Lokasi check-in** (`LokasiService`). Acara dengan titik lokasi hanya menerima check-in dari dalam radius (bawaan 150 m, 30 sampai 2.000 m). Browser mengirim `lat`, `lng`, `akurasi`; server menolak bila akurasi lebih buruk dari 150 m atau `jarak - min(akurasi, 50) > radius`. Acara **tanpa** titik tetap bisa check-in dari mana saja, jadi atur titiknya sebelum acara. Titik diisi staff dari GPS HP di venue, hasil cari alamat, atau koordinat ketikan; memindahkan titik lebih dari 1 km harus dikonfirmasi; nama petugas tercatat di Log (akun CMS dipakai bersama). Titik acara tidak dikirim ke browser anggota dan koordinat anggota tidak disimpan (hanya `jarak_m` dan `akurasi_m`). Batas: koordinat dikirim browser, jadi aplikasi lokasi palsu di Android masih bisa mengelabuinya; ini menutup kasus QR difoto lalu dikirim ke orang yang tidak hadir. Pengaturan: `config/inner_circle.php` (`lokasi`). Anggota yang gagal karena GPS dibantu lewat stempel manual.

**Klaim** (`ClaimService`). Hanya bila email terverifikasi dan stempel mencapai target, sekali per anggota per tahap (baris anggota dikunci dalam transaksi; kunci unik sebagai pengaman terakhir). Tahap 50 stempel wajib memilih satu dari dua opsi. Klaim ditolak bisa dibuka kembali admin.

**Tampil/sembunyikan.** Tahap hadiah dan pengumuman punya tombol (kolom `aktif`); minimal satu tahap harus aktif; pengajuan yang sudah masuk tetap diproses.

**Kenangan.** Caption maks 240 karakter, tempat maks 65, langsung tayang; admin bisa menyembunyikan. Nama di galeri disingkat ("Ancha M."). Foto dikecilkan ke 1600 px, profil 512 px.

**Hapus akun** (`HapusAkunService`): ketik `HAPUS` (dan sandi bila ada). Menghapus akun, foto, stempel, klaim, kenangan, dan suka; **ditahan** bila ada klaim `diajukan`, `ditinjau`, atau `disetujui`.

**Pembatas laju dan unggahan.** Anggota: check-in 8/menit, klaim 6/menit, kenangan 4/menit, suka 60/menit, foto 6/menit, hapus akun 5/menit (per anggota, bukan IP, karena penonton satu venue sering berbagi IP). Lupa sandi punya batas harian global 100. Unggahan CMS: banner dan hero 2 MB; album, berita, merchandise, header 1 MB; video header 50 MB (batas upload server harus ikut naik); semua gambar maks 16 megapiksel. Foto anggota: kenangan 4 MB, profil 5 MB.

**Menambah teks yang bisa diubah dari CMS.** (1) Daftarkan kunci di `config/ic_konten.php`: `grup`, `label`, `bawaan` (teks yang tampil sekarang, persis), `maks`, dan `baris` bila beberapa baris. (2) Ganti teks di view dengan `{{ ic_teks('grup.kunci') }}` (selalu `{{ }}`, jangan `{!! !!}`). (3) Bila grupnya baru, tambahkan di `grup` pada config itu. Form di tab Konten, validasi, dan label Log otomatis ikut. Kunci yang salah ketik melempar galat saat pengembangan dan tes; di produksi mengembalikan teks kosong. Tes `InnerCircleKontenTest` memastikan setiap kunci di view terdaftar **dan** setiap kunci di config dipakai view (tidak ada isian CMS yang tidak berpengaruh). Teks bersifat biasa: `<` dan `>` ditolak; placeholder yang boleh: `{program}` dan yang didaftarkan per kunci.

**Keputusan arsitektur yang masih berlaku:** logout anggota memakai `Auth::guard('member')->logout()` + `regenerateToken()` (bukan `session()->invalidate()`) supaya sesi admin di browser yang sama tidak terhapus; OAuth Google ditulis dengan HTTP client bawaan (tanpa Socialite) karena hosting tanpa terminal; email tanpa antrean (dikirim setelah respons) karena hosting tanpa worker; status acara (lewat/hari ini/akan datang) dihitung dari `now()` tanpa cron.

---

## 5. Auto deploy

Berkas: `.github/workflows/deploy.yml` (GitHub Actions + SSH). Setiap **push ke `main`** menjalankan dua job berurutan; bila tes gagal, deploy tidak jalan. Bisa juga dijalankan manual dari tab Actions (`workflow_dispatch`). Hanya satu deploy berjalan pada satu waktu (`concurrency`).

### 5.1 Alur

| #   | Job / langkah       | Yang terjadi                                                                                                                                                                                                           |
| --- | ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Tes**             | PHP 8.4, `composer install`, `npm ci` + `npm run build` (tes halaman butuh `manifest.json`), siapkan folder `storage/`, lalu `vendor/bin/phpunit` (SQLite memori, tanpa `.env`)                                        |
| 2   | **Deploy**: build   | `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build` di GitHub, bukan di hosting                                                                                                               |
| 3   | Siapkan SSH         | Tulis kunci privat dari Secrets, `ssh-keyscan` host                                                                                                                                                                    |
| 4   | Cek folder tujuan   | Pastikan `APP_PATH/artisan`, `APP_PATH/.env`, dan `PUBLIC_PATH/index.php` ada, supaya tidak menimpa folder yang salah                                                                                                  |
| 5   | Kemas dan kirim     | Satu berkas `tar.gz` lewat SSH (server tanpa `rsync`)                                                                                                                                                                  |
| 6   | Pasang di server    | Ekstrak ke folder sementara, ganti utuh `app`, `config`, `database`, `resources`, `routes`, `vendor`, `public/build`; timpa sisanya; hapus cache bootstrap dan `public/hot`; salin `aset` dan `build` ke `PUBLIC_PATH` |
| 7   | Migrasi dan cache   | `php artisan migrate --force` lalu `optimize:clear`                                                                                                                                                                    |
| 8   | Bersihkan kunci SSH | Selalu jalan (`if: always()`)                                                                                                                                                                                          |

**Tidak pernah disentuh di server:** `.env`, `storage/` (foto unggahan, sesi, log), `bootstrap/cache/`, `.git` server, `index.php` dan `.htaccess` di `public_html`. Kode dikirim ke dua tempat: folder Laravel (`APP_PATH`) dan `public/aset` + `public/build` ke `PUBLIC_PATH` (folder web publik). Folder `public/` di dalam folder Laravel ikut terisi karena `public_path()` dan manifest Vite membacanya.

### 5.2 Secrets dan variabel

GitHub repo, Settings, Secrets and variables, Actions:

| Nama                                    | Isi                                                    |
| --------------------------------------- | ------------------------------------------------------ |
| `SSH_HOST`, `SSH_PORT`, `SSH_USER`      | Host, port, dan username SSH hosting                   |
| `SSH_PRIVATE_KEY`                       | Isi kunci privat khusus deploy (seluruh file)          |
| `APP_PATH`                              | Folder Laravel di server (berisi `artisan` dan `.env`) |
| `PUBLIC_PATH`                           | Folder web publik di server (berisi `index.php`)       |
| `PHP_BIN` (tab **Variables**, opsional) | Path PHP 8.4 bila `php` di SSH bukan 8.4               |

### 5.3 Menyiapkan server baru (sekali saja)

1. **Server:** cPanel MultiPHP Manager: PHP 8.4+; ekstensi `gd` (WebP), `exif`, `mbstring`, `pdo_mysql`; `upload_max_filesize` dan `post_max_size` minimal `6M` (untuk video latar header, naikkan sesuai batas 50 MB); HTTPS aktif dengan Force HTTPS; cadangan database terjadwal. Disarankan subdomain **preview** dengan database sendiri.
2. **`.env` server:** unggah `.env.example` ke folder Laravel, ganti nama jadi `.env`, isi semua baris `GANTI`. Wajib: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `DB_*`, `SESSION_SECURE_COOKIE=true`, `IC_SEED_CONTOH=false`. `APP_KEY` dibuat sekali di lokal (`php artisan key:generate --show`). Server yang sudah live **jangan ditimpa**; tambahkan hanya variabel yang belum ada. Cek `https://domain/.env` harus 404/403.
3. **Kunci SSH deploy:** buat pasangan kunci khusus deploy, pasang kunci publik di cPanel (SSH Access), isi kunci privatnya ke Secrets.
4. **Push pertama:** deploy membuat semua tabel lewat `migrate --force`. Lalu ganti sandi admin bila akun berasal dari seeder lama (buat hash dengan `php -r "echo password_hash('SANDI-BARU', PASSWORD_BCRYPT);"`, tempel ke kolom `password` tabel `accounts` di phpMyAdmin).
5. Pastikan `public/robots.txt` **tidak ada** di server (rute dinamis yang dipakai) dan `public/storage` tertaut ke `storage/app/public`.
6. **Menyalakan Inner Circle:** isi CMS (jadwal, hadiah, satu pengumuman), isi `KONTAK_PRIVASI_EMAIL`, set `INNER_CIRCLE_GOOGLE_AKTIF=true` lalu `INNER_CIRCLE_AKTIF=true`, hapus cache. **Mematikan darurat:** `INNER_CIRCLE_AKTIF=false`, hapus cache.

### 5.4 Layanan pihak ketiga

**Login Google (gratis, tanpa penagihan).**

1. Buat project Google Cloud terpisah untuk produksi. **Google Auth Platform, Get started**: nama aplikasi, email dukungan, audiens **External**.
2. **Branding:** beranda `https://whisnusantika.com`, kebijakan privasi `https://whisnusantika.com/privasi`, authorized domain `whisnusantika.com`. Kosongkan logo (logo memicu verifikasi merek).
3. **Data Access:** hanya `openid`, `userinfo.email`, `userinfo.profile`.
4. **Clients, Create client, Web application**, redirect URI persis: `https://whisnusantika.com/innercircle/google/callback` (produksi), `http://127.0.0.1:8000/innercircle/google/callback` (lokal). Pakai client terpisah untuk lokal dan produksi. Salin Client ID dan secret saat itu juga (secret hanya tampil sekali).
5. **Audience, Publish app** supaya status **In production**. Selama _Testing_, hanya Test users yang bisa masuk.
6. Isi `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `.env` server, lalu `INNER_CIRCLE_GOOGLE_AKTIF=true`.

Galat umum: `redirect_uri_mismatch` (alamat callback tidak sama persis), `access_denied` (masih _Testing_), `invalid_client` (ID/secret salah atau cache konfigurasi belum dihapus).

**Email (SMTP Gmail dengan Sandi Aplikasi).** Aktifkan Verifikasi 2 Langkah di akun Google pengirim, buat sandi aplikasi di <https://myaccount.google.com/apppasswords>, lalu isi `.env` server:

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

Tulis sandi tanpa spasi. `MAIL_FROM_ADDRESS` harus sama dengan `MAIL_USERNAME`. Kuota sekitar 500 penerima/hari. Bila port 465 gagal, coba `587` dengan `MAIL_SCHEME=smtp`; bila hosting memblokir port keluar, minta Rumahweb membukanya. Uji dari CMS Inner Circle, tab **Email**, **Email tes**. Tanpa email yang berfungsi, anggota yang daftar dengan sandi tidak bisa mengajukan hadiah. (SMTP lewat domain sendiri `mail.whisnusantika.com` belum dikerjakan.)

**Bandsintown.** Isi `BANDSINTOWN_APP_ID` (butuh persetujuan Bandsintown) dan `BANDSINTOWN_ARTIST`. Manual: CMS Inner Circle, **Acara & QR**, **Sinkron sekarang**. Acara baru dibuat dengan QR sendiri; acara yang ada tidak ditimpa dan tidak dihapus otomatis. Otomatis pukul 04.00 WIB: di cPanel, Cron Jobs, tiap menit: `* * * * * /usr/local/bin/php /home/USERNAME/laravel/artisan schedule:run >> /dev/null 2>&1` (sesuaikan path).

**Kode login admin (opsional).** Pastikan email tes terkirim dan kolom `accounts.email` terisi, isi `LOG_ALERT_EMAIL`, set `ADMIN_2FA_AKTIF=true`. **Jangan logout dulu:** coba login di jendela penyamaran. Terkunci? Set `ADMIN_2FA_AKTIF=false` di `.env` server lalu hapus cache.

### 5.5 Setelah deploy: cek

- [ ] Job **Tes** dan **Deploy** di tab Actions hijau.
- [ ] Situs terbuka; `https://domain/.env` tidak terbuka; header `X-Frame-Options` dan `Strict-Transport-Security` ada.
- [ ] `/robots.txt` memuat `Disallow: /innercircle`; `/sitemap.xml` memuat `/privasi`.
- [ ] Login Google dengan akun yang **bukan** penguji berhasil, dari ponsel.
- [ ] Alur QR: tamu memindai, masuk, kembali ke check-in. Uji check-in dengan lokasi dari HP sungguhan di HTTPS.
- [ ] CMS Inner Circle: titik lokasi tiap acara sudah diisi (acara tanpa titik bisa check-in dari mana saja).
- [ ] Email tes terkirim; menu **Log** terbuka.
- [ ] Uji di HP: kamera pindai QR (Safari iOS dan Chrome Android, hanya jalan lewat HTTPS), unggah foto dari galeri (termasuk HEIC), tampilan 320 dan 375 px.

### 5.6 Rollback dan masalah umum

**Rollback:** revert commit di GitHub lalu push; deploy jalan lagi dengan kode lama. Migrasi tidak ikut mundur, jadi buat migrasi baru bila perlu.

| Gejala                                                                     | Penyebab dan solusi                                                                                                                                              |
| -------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Job **Tes** merah                                                          | Baca log langkah "Jalankan tes"; perbaiki, push lagi. Deploy otomatis tidak jalan                                                                                |
| **Siapkan SSH** gagal (`ssh-keyscan` exit 1, sekitar 5 detik)              | Server tidak terjangkau sesaat (SSH sibuk atau membatasi koneksi berturut-turut). **Re-run failed jobs**; bila berulang, cek SSH aktif dan `SSH_HOST`/`SSH_PORT` |
| **Cek folder tujuan** gagal                                                | `APP_PATH`/`PUBLIC_PATH` salah, atau `.env` belum ada di folder Laravel                                                                                          |
| Halaman putih / 500 setelah deploy                                         | Cache lama: `php artisan optimize:clear`; pastikan `bootstrap/cache/*.php` tidak ada; periksa `storage/logs`                                                     |
| Situs mencari aset ke `localhost:5173`                                     | `public/hot` ikut terunggah; hapus di server (workflow sudah menghapusnya)                                                                                       |
| "The MAC is invalid" di halaman QR                                         | `APP_KEY` berbeda dari saat token dibuat; klik **Perbaiki token** di Acara & QR                                                                                  |
| Foto gagal diunggah dengan "419"                                           | `upload_max_filesize`/`post_max_size` server terlalu kecil                                                                                                       |
| `optimize:clear` di lokal membuat banyak "file terhapus" di GitHub Desktop | File Blade terkompilasi tidak boleh di git; pastikan `storage/framework/views/.gitignore` ada                                                                    |

---

## 6. Rencana: pisah CMS artis dan CMS Inner Circle (sementara)

> Bagian ini hanya selama pengerjaan. Setelah semua fase selesai: **hapus bagian 6 ini**, lalu perbarui poin lain (lihat [Fase 8](#fase-8-penutup)).

**Status: Fase 0, 1, dan 2 selesai. Berikutnya Fase 3.**

### 6.1 Tujuan

1. CMS dibagi dua **mode**: **CMS Artis** (yang sekarang) dan **CMS Inner Circle**, masing-masing dengan menu dan tema sendiri.
2. Di sebelah tombol **Keluar** ada **saklar mode** untuk berpindah dari CMS Artis ke CMS Inner Circle, dan sebaliknya.
3. Semua teks dan elemen Inner Circle yang masih **hard code** di kode dijadikan konten yang bisa diubah dari CMS Inner Circle (tab baru **Konten**).

### 6.2 Keadaan sekarang dan dasar teknis (sudah dicek di kode)

- Inner Circle sekarang hanya **satu item menu** ("Inner Circle") di dock CMS Artis, dan 8 halamannya memakai `template/dashboardLayout` (halaman cetak QR berdiri sendiri) yang sama dengan CMS Artis (hitam, aksen merah tua).
- **Mode ditentukan oleh route**, tanpa menyimpan apa pun di sesi: semua route `ic.admin.*` = mode Inner Circle, sisanya = mode Artis. Alamat tetap `/dashboard/inner-circle/*`, jadi nama route, tes, dan label Log tidak berubah.
- **Tema bisa diganti per cakupan tanpa menulis ulang view.** Utility Tailwind v4 dikompilasi menjadi `var(--color-red-950)` dan seterusnya. Aksen CMS memakai dua warna saja: `red-950` (tombol, belasan tempat) dan `red-900` (hover dan fokus, sekitar 60 tempat). Warna galat dan tombol hapus memakai `red-300/400/500`. Jadi cukup menimpa `--color-red-950` dan `--color-red-900` di `body.cms-ic`: aksen berubah jadi lavender, galat tetap merah.
- Area anggota sudah memakai token warna `--ic-*` (di `inner-circle.css`), jadi tema anggota bisa diubah dari satu tempat (Fase 6).
- `app/Support/InnerCircleDummy.php` (454 baris) tidak dipanggil dari mana pun: **sudah dihapus (Fase 0)**.
- Filter bulan di halaman Check-in dikunci ke **Oktober, November, Desember** (`config/inner_circle.php`, kunci `bulan_event`) dan judulnya "Kuartal IV 2026". Mulai **Januari 2027** acara baru tidak bisa difilter. **Sudah diperbaiki (Fase 0):** pilihan bulan kini diambil dari tanggal acara.

### 6.3 Keputusan (sudah final)

| #   | Keputusan               | Hasil                                                                                                                                                                                                                    |
| --- | ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | Tema CMS Inner Circle   | Mengikuti tema lavender area anggota, disesuaikan agar **nyaman di mata** di dashboard: latar lavender muda (bukan putih murni), teks ungu-tua, aksen lilac. Dibuat lewat class `cms-ic` (lihat `resources/css/app.css`) |
| 2   | Navbar CMS Inner Circle | **Sama persis dengan CMS Artis** (dock + bottom sheet di HP); yang beda hanya isi menu dan tema. Bar tab lama dibuang supaya tidak ada dua navigasi                                                                      |
| 3   | Teks kosong di CMS      | Memakai **teks bawaan** (isi persis seperti sekarang), jadi tabel kosong = tampilan tidak berubah                                                                                                                        |
| 4   | Jenis isian             | Teks biasa dengan batas panjang (tanpa HTML). Pengecualian opsional: `/privasi` dengan Quill + `HtmlSanitizer`                                                                                                           |
| 5   | Hak akses               | **Satu akun admin** untuk kedua mode (tanpa peran), supaya sederhana                                                                                                                                                     |
| 6   | Mode setelah login      | Selalu CMS Artis; tidak mengingat mode terakhir                                                                                                                                                                          |

### 6.4 Inventaris hard-code Inner Circle

| #   | Lokasi                                              | Yang masih hard code                                                                                         | Prioritas         | Jadi apa                                                                  |
| --- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ | ----------------- | ------------------------------------------------------------------------- |
| 1   | `pages/inner-circle/checkin` + `config bulan_event` | Judul "Jadwal Pertunjukan WS · Kuartal IV 2026", penjelasan, bulan Okt-Des                                   | **Tinggi (bug)**  | Bulan dari data acara (Fase 0); judul dan teks jadi konten                |
| 2   | `pages/inner-circle/tentang`                        | Hero, 4 langkah "Cara kerjanya", 4 FAQ, ajakan akhir, deskripsi meta                                         | Tinggi            | Pengaturan + daftar item (langkah, FAQ)                                   |
| 3   | `components/inner-circle-teaser`                    | Teaser di beranda artis: judul, deskripsi, 3 poin, label tombol                                              | Sedang            | Pengaturan + daftar item                                                  |
| 4   | `pages/inner-circle/beranda`                        | "Musik seru. Teman baru.", kalimat terima kasih, stiker "Setiap momen berarti", judul aktivitas, teks kosong | Sedang            | Pengaturan                                                                |
| 5   | `pages/inner-circle/hadiah`                         | Eyebrow dan deskripsi header                                                                                 | Sedang            | Pengaturan                                                                |
| 6   | `pages/inner-circle/komunitas`                      | Eyebrow, deskripsi, banner ("Lebih banyak musik. Teman yang sama.", tombol), judul bagian                    | Sedang            | Pengaturan                                                                |
| 7   | `pages/inner-circle/profil`                         | Judul, teks foto profil, teks akun dan privasi                                                               | Rendah            | Pengaturan                                                                |
| 8   | `masuk`, `daftar`, `lupa-sandi`, `atur-ulang`       | Judul dan kalimat pembuka                                                                                    | Rendah            | Pengaturan                                                                |
| 9   | `components/inner-circle/*`                         | "KARTU KEANGGOTAAN DIGITAL", pesan bar stempel, "Kabar pilihan"                                              | Rendah            | Pengaturan                                                                |
| 10  | `config tipe_event`, `kategori_kenangan`            | Label, ikon, warna tipe acara; kategori kenangan                                                             | Sedang            | Daftar yang bisa diubah (kunci tetap karena dipakai logika dan data lama) |
| 11  | `ic_event` (jendela), `config lokasi`, batas        | Jam jendela bawaan 12.00 sampai H+1 06.00; radius bawaan 150 m; akurasi maks                                 | Sedang            | Pengaturan aturan (isi bawaan tetap dari config)                          |
| 12  | `app/Mail/InnerCircle/*`, `emails/`                 | Subjek dan paragraf 5 jenis email                                                                            | Rendah (opsional) | Subjek + pembuka dengan placeholder aman                                  |
| 13  | `pages/privasi`                                     | Seluruh teks kebijakan                                                                                       | Rendah (opsional) | Satu isian teks kaya                                                      |
| 14  | `inner-circle.css`, layout, `logo`                  | Warna lavender, nama "WS Inner Circle" di banyak tempat, logo                                                | Sedang            | Tema (warna), nama program, logo                                          |

**Sengaja tidak dijadikan CMS:** label dock anggota, nama tab admin, label status klaim (terikat logika), pesan validasi dan galat sistem, dan batas keamanan (pembatas laju, ukuran unggahan).

**Sudah bisa diubah dari CMS (tidak perlu dikerjakan):** jadwal acara dan QR, titik lokasi, hadiah (judul, deskripsi, ikon, warna, opsi), pengumuman, moderasi kenangan, dan pengiriman email.

### 6.5 Model konten (dipakai Fase 2 sampai 7)

Dua tabel kecil dan satu service, supaya tiap fase cukup menyambungkan view ke kunci baru:

| Bagian                              | Isi                                                                                                                                                                                                               |
| ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `ic_pengaturan`                     | `kunci` (unik, mis. `beranda.hero.judul`), `nilai` (teks, boleh kosong), `diubah_oleh`, timestamps. Satu baris = satu teks                                                                                        |
| `ic_item`                           | Daftar berurutan: `grup` (`langkah`, `faq`, `teaser_poin`, `kategori_kenangan`), `urutan`, `judul`, `isi`, `ikon`, `aktif`                                                                                        |
| `config/ic_konten.php`              | Daftar semua kunci: **teks bawaan** (sama persis dengan yang sekarang), batas panjang, label dan petunjuk untuk form CMS                                                                                          |
| `App\Services\InnerCircle\IcKonten` | `teks('kunci')`: isi tabel bila ada, kalau tidak teks bawaan; `daftar('grup')`; hasil di-cache dan cache dihapus saat simpan. Mendukung placeholder `{nama}` dan `{program}` yang diganti dengan nilai ter-escape |
| Pemakaian di view                   | `{{ ic_teks('beranda.hero.judul') }}` (selalu di-escape, tidak ada HTML dari CMS)                                                                                                                                 |

Aman diluncurkan: tabel kosong = tampilan sama persis. Rollback = revert commit.

### 6.6 Fase

Dikerjakan **satu fase utuh per giliran**; tiap fase di-push dan auto deploy menjalankan migrasinya. Setiap fase menambah tes dan lulus penuh sebelum push.

#### Fase 0: Persiapan dan perbaikan (SELESAI)

Hapus `InnerCircleDummy`; filter bulan Check-in dan Acara & QR kini diambil dari data acara (`ic_event::pilihanBulan()`, format `2026-10`, tautan lama `?bulan=10` dianggap "semua"); judul kartu jadwal dibuat netral ("Jadwal Pertunjukan WS"); kunci `bulan_event` dibuang dari config. Tes: `InnerCircleFilterBulanTest`.

#### Fase 1: Pisah CMS dan saklar mode (SELESAI)

- `template/icDashboardLayout` (body `cms-ic`) dipakai 8 halaman CMS Inner Circle; `cetak-qr` tetap berdiri sendiri.
- `components/dashboard/navbar` kini sadar mode: menu Artis (8 item) atau menu Inner Circle (dibaca dari `config('inner_circle.tab_admin')`, Ringkasan jadi tombol rumah, lencana klaim di Klaim), plus **saklar mode** di sebelah tombol Keluar (dock desktop dan bottom sheet HP). Menu Artis tidak lagi memuat item "Inner Circle".
- Tab bar lama dibuang: `inner-circle/tabs` menjadi `inner-circle/kepala` (hanya judul dan skrip bersama).
- Tema `body.cms-ic` di `resources/css/app.css`; SweetAlert (`resources/js/sweetalert.js`) mengikuti tema.
- Tes: `CmsModeTest`. Dicek di 320, 375, 768, 1024, dan 1366 px (tanpa halaman yang melebar).

Catatan untuk fase berikutnya: halaman baru di CMS Inner Circle cukup menambah satu entri di `config('inner_circle.tab_admin')`, otomatis muncul di dock. Warna "terang" yang harus tetap putih (kartu QR, pratinjau) diberi class `cms-asli`; dock memakai `cms-dock`.

#### Fase 2: Fondasi konten dan tab Konten (SELESAI)

- Migrasi `ic_pengaturan` dan `ic_item`; model `ic_pengaturan`, `ic_item`.
- `config/ic_konten.php` (daftar kunci: grup, label, teks bawaan, batas panjang), `IcKonten` (service + cache), `ic_teks()` / `ic_daftar()`.
- Tab **Konten** (`kontenController`, `simpanKontenRequest`, `konten.blade.php`): isian per grup, teks bawaan sebagai petunjuk, penghitung huruf, tombol **Kembalikan bawaan**, validasi (`<` `>` ditolak, batas panjang, placeholder asing ditolak). Muncul otomatis di dock CMS Inner Circle.
- **Grup pertama yang sudah hidup: Halaman Check-in** (6 teks: eyebrow, penjelasan, judul dan penjelasan kartu jadwal, judul dan penjelasan kotak "sudah di lokasi") sebagai bukti alurnya jalan dari CMS sampai halaman anggota.
- Beda dari rencana awal: baris `ic_pengaturan` hanya ada bila teksnya **berbeda** dari bawaan (mengosongkan atau menyamakan dengan bawaan menghapus barisnya), dan `ic_item` belum diisi (daftar awalnya dibuat saat Fase 3 memakainya).
- Tes: `InnerCircleKontenTest` (bawaan, simpan, kembalikan, cache, validasi, escape, hak akses, tabel belum ada, daftar item, dan pemindai kunci view-config).

#### Fase 3: Halaman Tentang dan teaser beranda

1. `tentang.blade.php`: hero, deskripsi meta, ajakan akhir lewat kunci baru di `config/ic_konten.php` + `ic_teks()` (lihat cara di 4.9); "Cara kerjanya" dari `ic_item` grup `langkah`; FAQ dari grup `faq`.
2. `inner-circle-teaser.blade.php`: judul, deskripsi, 3 poin (grup `teaser_poin`), label tombol.
3. Tab Konten: pengelola daftar (tambah, ubah, urutkan naik/turun, aktif/sembunyikan, hapus) untuk langkah, FAQ, dan poin teaser. Seeder/migrasi mengisi daftar awal sama dengan teks sekarang.
4. Tes: tampilan default identik dengan sekarang; ubah teks tampil; item disembunyikan hilang; urutan.

**Selesai bila:** halaman `/innercircle/tentang` dan teaser sepenuhnya dikendalikan dari CMS.

#### Fase 4: Layar anggota

Sambungkan ke `ic_teks()`: beranda (eyebrow, sapaan, terima kasih, stiker, judul aktivitas, teks kosong), hadiah (header), komunitas (header dan banner), check-in (header, **judul dan penjelasan kartu jadwal**), profil (header, teks foto, teks akun dan privasi), masuk, daftar, lupa sandi, atur ulang, kartu anggota, bar stempel.

Aturan: kunci per halaman (`beranda.*`, `hadiah.*`, ...); placeholder `{nama}` hanya di sapaan. Tes yang menegaskan teks lama tetap lulus karena bawaannya sama.

**Selesai bila:** semua teks inventaris nomor 1, 4 sampai 9 bisa diubah dari tab Konten.

#### Fase 5: Aturan dan daftar pilihan

1. **Tipe acara:** label, ikon, dan warna bisa diubah; kunci (`festival`, `club`, `private`) tetap karena dipakai logika (acara privat tidak bisa check-in).
2. **Kategori kenangan:** tambah dan ubah label; kategori yang sudah dipakai kenangan tidak bisa dihapus (bisa disembunyikan).
3. **Aturan bawaan:** jam jendela check-in bawaan (`jendela_buka` dan `jendela_tutup` di model `ic_event`), radius bawaan, batas akurasi. Nilai dibaca dari `IcKonten` dengan cadangan `config`. Acara yang punya jendela atau radius sendiri tidak terpengaruh.
4. Tes: label baru tampil di kartu dan form; kategori terpakai tidak terhapus; jendela bawaan baru berlaku untuk acara baru saja.

**Selesai bila:** inventaris nomor 10 dan 11 selesai tanpa mengubah perilaku acara yang sudah ada.

#### Fase 6: Tema dan merek

1. Tab Konten, bagian **Tampilan**: warna aksen, latar, dan kartu (isian `#RRGGBB` dengan pemilih warna), nama program, logo (unggah JPG/PNG diubah ke WebP lewat `ImageProcessor`).
2. Tiga layout anggota (`innerCircleLayout`, `AuthLayout`, `PublikLayout`) menulis `<style>:root{--ic-aksen:...}</style>` dari pengaturan, dengan nilai yang divalidasi (hanya `#RRGGBB`, tidak pernah menyisipkan teks bebas ke CSS).
3. Pemeriksaan kontras: tolak pasangan teks/latar di bawah 4.5:1 dengan pesan jelas; tombol **Kembalikan bawaan**.
4. Nama program dipakai lewat `{program}` di teks bawaan.
5. Tes: warna tersimpan tampil di layout; nilai tidak valid ditolak; kontras rendah ditolak; logo tersimpan sebagai WebP.

**Selesai bila:** warna dan nama program berubah dari CMS tanpa menyentuh CSS.

#### Fase 7: Email dan privasi (opsional)

1. Subjek dan paragraf pembuka lima jenis email dari pengaturan, dengan placeholder yang diizinkan per jenis (`{nama}`, `{acara}`, `{status}`); tautan dan token tetap dibuat kode, tidak bisa diubah dari CMS. Pratinjau di tab Email memakai teks terbaru.
2. `/privasi`: isi teks kaya (Quill) disaring `HtmlSanitizer`, dan `privasi_diperbarui` terisi otomatis saat disimpan.
3. Tes: placeholder asing ditolak; teks email ter-escape; HTML berbahaya di privasi dibersihkan.

**Selesai bila:** tab Email dan `/privasi` memakai isi dari CMS.

#### Fase 8: Penutup

1. Uji semua halaman di 320, 375, 768, dan 1366 px; uji di HP sungguhan lewat HTTPS.
2. **Hapus bagian 6 ini** dari README, lalu perbarui:
    - Bagian **3.4**: tambah tab **Konten** dan penjelasan saklar mode di bagian 3.3/3.4.
    - Bagian **4.3** (service `IcKonten`, controller `kontenController`, request), **4.4** (layout `icDashboardLayout`, navbar IC, saklar mode, view konten), dan **4.5** (tabel `ic_pengaturan`, `ic_item`).
    - Bagian **2** bila ada aturan yang berubah, dan bagian **4.7** (jumlah tes terbaru).
    - Daftar isi.
3. Pastikan `config/ic_konten.php` memuat semua kunci yang dipakai view (tes yang memindai view mencari `ic_teks('...')` yang tidak terdaftar).
