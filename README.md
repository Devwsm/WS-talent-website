# WS Talent Website

Situs resmi DJ **Whisnu Santika** (`whisnusantika.com`) beserta komunitas penggemarnya, **WS Inner Circle**. Satu project Laravel yang berisi dua produk: situs artis dengan CMS-nya, dan komunitas Inner Circle dengan CMS-nya.

Status: tayang di production dengan auto deploy (GitHub Actions + SSH) dan sudah dipakai anggota. **Semua fase pengerjaan selesai**; yang belum dikerjakan ada di bagian 6. Terakhir **807 tes** (tes gambar dilewati otomatis bila ekstensi `gd` tanpa WebP tidak aktif).

## Daftar isi

1. [WS Artis](#1-ws-artis)
2. [WS Inner Circle](#2-ws-inner-circle)
3. [Halaman dan fitur](#3-halaman-dan-fitur)
4. [Arsitektur file](#4-arsitektur-file)
5. [Auto deploy](#5-auto-deploy)
6. [Aturan akhir Inner Circle dan sisa pekerjaan](#6-aturan-akhir-inner-circle-dan-sisa-pekerjaan)

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

Komunitas penggemar resmi. Penggemar **hadir di acara, mengumpulkan poin, dan membuka hadiah**. Tujuannya mengubah kehadiran di konser menjadi hubungan jangka panjang dengan penggemar.

**Perjalanan anggota:**

1. **Daftar** dengan email + kata sandi, atau dengan akun Google.
2. **Antri GL gratis** dari halaman acara. **Datang ke acara** lalu tekan **Check-in**: kehadiran dinilai dari lokasi HP (di dalam radius venue), tanpa QR. Pemenang GL menunjukkan Kartu GL ke staf, lalu memindai QR acara untuk mendapat poin.
3. **Kumpulkan poin.** Satu acara = satu kehadiran, punya poin sendiri (GL 1.000, check-in mandiri 2.000, bisa diubah admin), tercatat di **Riwayat aktivitas** bersama riwayat GL, dan bisa ditukar dengan hadiah.
4. **Tukar poin dengan hadiah** dari katalog (Meet & Greet 150.000 poin, Merchandise 250.000 poin, admin bisa menambah hadiah). Poin **ditahan** saat mengajukan; admin memeriksa: diajukan, ditinjau, disetujui, selesai (poin terpakai), atau ditolak. Penukaran yang ditolak atau dibatalkan anggota mengembalikan poin dan stok. Hadiah yang stoknya habis punya daftar tunggu. Anggota dikabari lewat halaman Hadiah dan email. Kendala bisa dikirim lewat halaman **Pengaduan**.
5. **Komunitas**: membaca pengumuman resmi WSM, membagikan kenangan (foto + cerita), menyukai dan mengomentari kenangan anggota lain. Nama dan foto profil tampil lengkap; penyuka tampil ala IG (tumpukan foto + "Disukai oleh ...").

**Aturan yang perlu diketahui:**

- Check-in, unggah kenangan, suka, dan klaim hadiah butuh **email terverifikasi**. Akun Google otomatis terverifikasi.
- Token QR milik **acara** dan terenkripsi dengan `APP_KEY`. Jendela check-in bawaan: tanggal show 12.00 sampai H+1 06.00 WIB (bisa diubah per acara).
- **Lokasi check-in:** titik dan radius milik acara (bukan milik akun CMS), jadi banyak petugas boleh memakai satu akun. Koordinat anggota tidak disimpan, hanya jarak dan akurasinya. Detail di [4.9](#49-aturan-bisnis-penting).
- Anggota bisa **menghapus akunnya sendiri** (kecuali ada penukaran hadiah yang sedang diproses). Kebijakan ada di `/privasi`.
- Inner Circle dikendalikan **flag** `INNER_CIRCLE_AKTIF`. Bila mati, semua route-nya 404 dan menu hilang dari dashboard.

**Tampilan terpisah** dari situs artis: tema biru sendiri (lihat 4.4), CSS dan JS sendiri (semua class berprefix `ic-` di bawah `.ic-app`), dengan navigasi dock di bawah.

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
| Beranda          | `/innercircle`                  | Kartu anggota, progres poin, GL gratis saya, aktivitas terbaru (poin dan GL)                         |
| Check-in         | `/innercircle/check-in`         | Filter jadwal, lembar check-in dari bawah (dinilai dari lokasi), pindai QR khusus pemenang GL (kamera atau unggah gambar) |
| Hadiah           | `/innercircle/hadiah`           | Dompet poin (saldo, ditahan, terpakai), katalog, tukar poin, progres bila poin kurang, batalkan penukaran, daftar tunggu stok habis |
| Pengaduan        | `/innercircle/pengaduan`        | Kirim pengaduan (kategori, acara terkait, isi maks 1.000 karakter, 3 per hari) dan lihat statusnya    |
| Komunitas        | `/innercircle/komunitas`        | Pengumuman, galeri kenangan (bingkai 4:5), suka dengan foto penyuka, komentar, halaman detail kenangan, unggah kenangan |
| Profil           | `/innercircle/profil`           | Satu kartu member (pensil untuk ganti foto dan nama), Riwayat aktivitas (poin + GL), pengajuan hadiah, Akun dan privasi (pengaduan, hapus akun) |
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

Tema lavender sendiri (beda dari area anggota yang biru), dibuat lembut di mata: latar lavender muda, teks ungu-tua. Dock navigasi dan tombolnya ungu-tua senada, dengan pil aktif lavender muda. Menu CMS ini ada di dock: Anggota, Acara & QR, Klaim (dengan lencana jumlah yang menunggu), Hadiah, Pengumuman, Kenangan, Konten, Email; tombol rumah membuka Ringkasan.

| Tab        | Fungsi                                                                                                                                                                                                                                                                                                           |
| ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Ringkasan  | Angka ringkas komunitas                                                                                                                                                                                                                                                                                          |
| Anggota    | Daftar anggota dengan foto profil, tambah poin manual (untuk anggota yang gagal check-in), tarik poin check-in mencurigakan                                                                                                                                                                                                                                           |
| Acara (Pengaturan acara dan Hari H) | Tambah/ubah/hapus acara, impor CSV, sinkron Bandsintown, QR cetak, perbaiki token, **titik lokasi check-in**                                                                                                                                                                                                     |
| Penukaran  | Periksa penukaran hadiah per status (menunggu, semua, selesai, ditolak, dibatalkan); poin ditahan dikembalikan otomatis saat ditolak |
| Hadiah     | Katalog hadiah: tambah, ubah harga/stok/tampilan, tampil/sembunyikan, lihat daftar tunggu |
| Pengumuman | CRUD, unggulan (hanya satu), terjadwal, tampil/sembunyikan                                                                                                                                                                                                                                                       |
| Kenangan   | Moderasi kenangan dan komentar (sembunyikan/tayangkan)                                                                                                                                                                                                                                                                                 |
| Konten     | **13 bagian, pindah lewat navbar atas**: teks tiap halaman anggota/publik, daftar berurutan (langkah, FAQ, poin teaser), tipe acara, kategori kenangan, aturan check-in, tampilan dan merek, kebijakan privasi. Tiap bagian terdiri dari modul kecil; tiap modul punya form dan pratinjau potongan di sampingnya |
| Email      | Pratinjau format email, **ubah subjek dan paragraf pembuka** tiap jenis email, cek pengaturan kirim, email tes                                                                                                                                                                                                   |

### 3.5 Fitur lintas

- **Login admin** dengan pembatas percobaan dan **kode OTP email** (opsional, `ADMIN_2FA_AKTIF`).
- **Log**: error tak terduga dan jejak aksi admin, plus email peringatan error yang dibatasi.
- **Email** (lupa sandi, verifikasi, selamat datang, status penukaran hadiah, status pengaduan) lewat SMTP Gmail, dikirim setelah respons tanpa antrean.
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
config/                  inner_circle.php, ic_konten.php (teks yang bisa diubah dari CMS), ic_konten_modul.php (modul formulir tab Konten), site.php, log_admin.php, dst.
database/migrations/     39 migrasi;  database/seeders/  data awal
public/                  index.php, favicon.ico (= WS Artis), build/ (hasil Vite)
  aset/logo/             logo WS Artis
  aset/inner-circle/     ikon 3D dan logo resmi WS Inner Circle (logo/wsic-*.webp)
  aset/favicon/          set favicon per merek: ws-artis/ dan ws-inner-circle/ (ico, 16/32/48 px, apple-touch-icon,
                         android-chrome 192/512, site.webmanifest); dipasang lewat <x-favicon merek="...">
  aset/og/               gambar Open Graph 1200x630: og-ws-artis.jpg, og-ws-inner-circle.jpg
resources/css/           app.css (Tailwind), inner-circle.css (tema biru)
resources/js/            app.js, dashboard.js, sweetalert.js, inner-circle.js
resources/views/         template/, pages/, components/, emails/, errors/
routes/                  web.php, console.php (jadwal)
tests/                   Feature/ (InnerCircle/, Cms/, Admin/, Keamanan/), Unit/
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

| File                   | Peran                                                                                                                                                                         |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `authController`       | Masuk, daftar, keluar anggota                                                                                                                                                 |
| `googleController`     | Masuk dengan Google (`ic.google.masuk`, `ic.google.callback`)                                                                                                                 |
| `lupaSandiController`  | Lupa dan atur ulang kata sandi                                                                                                                                                |
| `memberController`     | Layar anggota: beranda, check-in, hadiah, komunitas, profil                                                                                                                   |
| `kenanganController`   | Unggah, hapus, suka, komentar, halaman detail, dan daftar penyuka (JSON) kenangan                                                                                                                                              |
| `fotoProfilController` | Ganti dan hapus foto profil                                                                                                                                                   |
| `akunController`       | Hapus akun anggota                                                                                                                                                            |
| `tentangController`    | Halaman publik penjelasan program                                                                                                                                             |
| `acaraController`, `acaraHariHController` | CMS Inner Circle menu Acara: Pengaturan acara (tambah, impor CSV, Bandsintown, ubah, hapus, jadwal, halaman Atur acara dengan peta dan sandi) dan Hari H (daftar acara, detail yang hadir GL/tiket, QR, titik lokasi, ekspor kehadiran); alamat lama `/acara?acara=KODE` dialihkan ke Hari H |
| `pengaduanController`, `pengaduanAdminController` | Pengaduan: formulir dan daftar anggota (`/innercircle/pengaduan`), tindak lanjut admin dari detail anggota |
| `adminController`      | CMS Inner Circle: ringkasan, anggota, penukaran, katalog hadiah, pengumuman, kenangan |
| `emailAdminController` | Tab Email: pratinjau, cek pengiriman, email tes                                                                                                                               |
| `kontenController`     | Tab Konten: `index` (membuka bagian pertama), `bagian` (satu bagian + modulnya), simpan per modul/grup, kembalikan bawaan                                                     |
| `konten*Controller`    | Bagian tab Konten: `Daftar` (langkah, FAQ, poin teaser), `Pilihan` (tipe acara, kategori kenangan), `Aturan` (check-in), `Tampilan` (nama, warna, logo), `Privasi` (/privasi) |

**Middleware** (`app/Http/Middleware/`): `CekLogin` (gerbang admin), `IcAktif` (flag fitur, 404 bila mati), `IcSesiVersi` (mengeluarkan sesi anggota yang dicabut), `IcTerverifikasi` (aksi anggota butuh email terverifikasi), `CatatAktivitas` (mencatat aksi tulis admin), `SecurityHeaders` (nosniff, frame, referrer, `Permissions-Policy` dengan `geolocation=(self)`, HSTS di produksi).

**Service** (`app/Services/`)

| File                                                             | Peran                                                                                                                                                                                                                                                                                      |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `AdminOtpService`                                                | Kode OTP 6 digit login admin (hash HMAC, 10 menit, sekali pakai)                                                                                                                                                                                                                           |
| `AktivitasService`, `LogErrorService`                            | Jejak aksi admin dan penyimpanan error (digabung per sidik jari, disamarkan)                                                                                                                                                                                                               |
| `InnerCircle/CheckinService`                                     | Semua aturan check-in (anggota, jendela waktu, token, lokasi, satu kali per acara)                                                                                                                                                                                                         |
| `InnerCircle/LokasiService`                                      | Jarak haversine dan penilaian radius + akurasi GPS                                                                                                                                                                                                                                         |
| `InnerCircle/GeocodeService`                                     | Cari koordinat dari alamat venue (Nominatim/OpenStreetMap)                                                                                                                                                                                                                                 |
| `InnerCircle/PoinService`                                        | Satu-satunya tempat poin berubah (buku besar `ic_poin`): tambah, kurangi (koreksi), tahan dan kembalikan untuk penukaran hadiah |
| `InnerCircle/GlService`                                         | Antrean GL sisi anggota: ikut, keluar, status tombol, kartu GL pemenang (sandi acara hanya keluar dari sini)                                                                                |
| `InnerCircle/UndianService`                                     | Mesin undian GL: undian otomatis dan manual, tunda/lanjutkan, status tertunda, pengaman kunjungan, catatan Log                                                                              |
| `InnerCircle/ClaimService`                                       | Penukaran hadiah: katalog anggota, dompet, tukar (tahan poin dan stok), batalkan, proses admin (tolak mengembalikan poin), daftar tunggu |
| `InnerCircle/PengaduanService`                                   | Pengaduan anggota: kirim (batas harian), daftar milik anggota, tindak lanjut admin |
| `InnerCircle/AcaraService`                                       | Buat acara dan impor CSV                                                                                                                                                                                                                                                                   |
| `InnerCircle/QrService`                                          | QR check-in sebagai SVG (tanpa GD)                                                                                                                                                                                                                                                         |
| `acaraGlController` (`vendor`)                                  | Pratinjau Kartu GL untuk vendor (data untuk PDF yang digambar di browser, memuat username dan sandi acara; tercatat di Log) |
| `InnerCircle/BandsintownService`                                 | Tarik jadwal tur ke `ic_events` (tidak pernah menghapus)                                                                                                                                                                                                                                   |
| `InnerCircle/KenanganService`, `PengumumanService`               | Galeri kenangan (terbit, suka, moderasi) dan pengumuman (satu unggulan)                                                                                                                                                                                                                    |
| `InnerCircle/AvatarService`, `HapusAkunService`                  | Foto profil dan penghapusan akun                                                                                                                                                                                                                                                           |
| `InnerCircle/GoogleOAuth`, `GoogleAkun`                          | OAuth Google (code + PKCE, OIDC) dan pencocokan akun                                                                                                                                                                                                                                       |
| `InnerCircle/EmailService`                                       | Semua email Inner Circle (kapan, ke siapa, contoh isi)                                                                                                                                                                                                                                     |
| `InnerCircle/IcBagian`                                           | Daftar 13 bagian tab Konten (urutan di navbar, ikon, alamat) dan modulnya (`modul()`); `url()`, `untukDaftar()`, `untukPilihan()`, `petaJangkarLama()`                                                                                                                                     |
| `Support/IcReplika`                                              | Bentuk replika tiap kunci teks untuk pratinjau tab Konten (tipe elemen dan penggantian placeholder), murni PHP                                                                                                                                                                             |
| `Services/InnerCircle/IcPotongan`                                | Pratinjau tab Konten: merender halaman anggota/publik asli dengan anggota contoh, memotong blok yang memuat isian modul, dan mengubah penanda teks jadi kait pembaruan langsung                                                                                                            |
| `vite/buat-kontainer.js`                                         | Membuat `resources/css/ic-kontainer.css`: salinan aturan responsif area anggota (`@media` jadi `@container ic-panel`, `vw` jadi `cqw`) untuk pratinjau CMS Inner Circle; dijalankan `npm run dev` dan `npm run build`                                                                      |
| `InnerCircle/IcKonten`                                           | Konten yang bisa diubah dari CMS: `teks(kunci)` = isi `ic_pengaturan` bila ada, kalau tidak teks bawaan dari `config/ic_konten.php`; `daftar(grup)`; cache dibuang tiap simpan; aman bila tabel belum ada                                                                                  |
| `InnerCircle/IcPilihan`                                          | Tipe acara (label, ikon, warna; kunci tetap) dan kategori kenangan (tambah, ubah, urutkan, sembunyikan, hapus bila belum dipakai) dari tabel `ic_pilihan`; cadangan `config/inner_circle.php` bila tabel kosong atau belum ada. Dipakai lewat `ic_tipe_event()` / `ic_kategori_kenangan()` |
| `InnerCircle/IcAturan`                                           | Aturan bawaan check-in dari CMS: jam buka/tutup jendela, radius, batas akurasi GPS (kunci `aturan.*` di `ic_pengaturan`, hanya ada bila beda dari config). Mengubah jendela/radius membekukan acara yang masih memakai bawaan pada nilai lamanya                                           |
| `InnerCircle/IcDaftar`, `IcTampilan`, `IcEmailTeks`, `IcPrivasi` | Daftar berurutan (`ic_item`); nama program, warna, dan logo (`IcTampilan::warnaEmail()` juga memberi palet email); teks email per jenis; isi kebijakan privasi (disaring `HtmlSanitizer`)                                                                                                  |
| `InnerCircle/*Gagal`                                             | Pengecualian berpesan aman untuk ditampilkan ke pengguna                                                                                                                                                                                                                                   |

**Model** (`app/Models/`): situs artis (`banner`, `header`, `albums`, `news`, `merchandise`, `color_pages`, `statistik`, `highlight`, dan 7 model profil: `profile_hero`, `genre`, `bio`, `collab`, `media_coverage`, `booking`, `media_sosial`); admin dan log (`account`, `log_error`, `log_aktivitas`); Inner Circle (`member`, `ic_event`, `ic_poin`, `ic_reward`, `ic_claim`, `ic_claim_log`, `ic_broadcast`, `ic_memory`, `ic_pengaturan`, `ic_item`).

**Lainnya:** `Mail/InnerCircle/*` (reset sandi, akun Google, selamat datang, status klaim, verifikasi, kartu GL pemenang; dasar bersama `PesanIc`) dan `Mail/Admin/*` (kode login, peringatan error); `Policies/MemoryPolicy` (hak atas kenangan, didaftarkan manual di `AppServiceProvider`); `Rules/SandiAnggota`; `Support/ImageProcessor` (foto anggota: EXIF diluruskan, WebP, metadata dibuang), `Support/HtmlSanitizer`, `Support/GambarGagal`, `Support/helpers.php` (fungsi view `ic_teks()` dan `ic_daftar()`, dimuat oleh `AppServiceProvider`, tidak perlu `composer dump-autoload`); `Console/Commands/SinkronBandsintown` dan `OptimalkanGambar`.

> `ic_event` juga memuat `pilihanBulan()` dan `scopeDiBulan()`: pilihan filter bulan di area anggota dan dashboard diambil dari tanggal acara yang ada, bukan daftar tetap.

### 4.4 Frontend: penjelasan per folder

| Lokasi                                                                           | Isi                                                                                                                                                                                                                                                                                                                                                     |
| -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `template/dashboardLayout`                                                       | Kerangka CMS Artis (gelap, Tailwind)                                                                                                                                                                                                                                                                                                                    |
| `template/icDashboardLayout`                                                     | Kerangka CMS Inner Circle: sama dengan `dashboardLayout`, bedanya `<body class="cms-ic">` yang mengganti tema warna                                                                                                                                                                                                                                     |
| `template/innerCircleLayout`, `innerCircleAuthLayout`, `innerCirclePublikLayout` | Kerangka area anggota, halaman masuk/daftar, dan halaman publik Inner Circle                                                                                                                                                                                                                                                                            |
| `template/layout`                                                                | Kerangka situs publik artis                                                                                                                                                                                                                                                                                                                             |
| `pages/`                                                                         | Halaman: `home`, `login`, `login-otp`, `privasi`, `dashboard-pages/` (CMS), `inner-circle/` (area anggota)                                                                                                                                                                                                                                              |
| `pages/dashboard-pages/inner-circle/`                                            | 10 berkas CMS Inner Circle (ringkasan, anggota, acara, cetak-qr, klaim, hadiah, pengumuman, kenangan, konten-bagian = tab Konten (satu bagian per halaman), email). Semua memakai `icDashboardLayout` kecuali cetak QR yang berdiri sendiri                                                                                                             |
| `components/dashboard/inner-circle/`                                             | Bagian tab Konten dan Email: `daftar-konten`, `form-butir`, `pilihan-tipe`, `pilihan-kategori`, `aturan-checkin`, `tampilan`, `privasi-editor` (Quill mengikuti variabel warna tema), `replika` dan `replika-tampilan` (pratinjau)                                                                                                                      |
| `components/dashboard/`                                                          | `navbar` (dock + bottom sheet, **sadar mode**: menu Artis atau Inner Circle plus saklar mode), modal edit/hapus tiap modul, `inner-circle/kepala` (judul halaman CMS Inner Circle dan skrip bersama)                                                                                                                                                    |
| `components/inner-circle/`                                                       | Komponen area anggota: dock, kartu event, ubin hadiah, kartu kenangan, modal (varian lembar bawah), bar poin, dst.                                                                                                                                                                                                                                      |
| `components/inner-circle-teaser`                                                 | Teaser Inner Circle di beranda artis (tema situs artis, bukan lavender)                                                                                                                                                                                                                                                                                 |
| `components/profile/`                                                            | Profil publik dan bagian-bagian form CMS profil                                                                                                                                                                                                                                                                                                         |
| `emails/inner-circle/`                                                           | Tampilan email bersama; warnanya satu tema dengan area anggota (biru + navy, dari `IcTampilan::warnaEmail()`), tidak memakai merah CMS Artis                                                                                                                                                                                                            |
| `resources/js/inner-circle.js`                                                   | Modal bertumpuk, lembar check-in, geolokasi, pemindai QR, pembatas klik ganda                                                                                                                                                                                                                                                                           |
| `resources/css/inner-circle.css`                                                 | Seluruh tema area anggota (`.ic-app`), satu berkas                                                                                                                                                                                                                                                                                                      |
| `resources/css/app.css`                                                          | Tailwind + tema CMS. `body.dash-theme` (CMS Artis) dan `body.cms-ic` (CMS Inner Circle) menimpa variabel `--color-*` Tailwind, jadi seluruh halaman berganti tema tanpa mengedit view. Kartu QR dan pratinjau email tetap warna asli lewat class `cms-asli`; dock navigasi memakai class `cms-dock` (di CMS Inner Circle diberi palet ungu-tua sendiri) |

**Catatan tema dan pratinjau**

- **Tema biru area anggota:** latar gradasi biru laut, kartu putih, aksen `#1b68a0`, judul biru tua `#05467c`, tombol berbentuk pil. Semua lewat token `--ic-*` di `inner-circle.css` ditambah lapisan "Tema biru" di akhir berkasnya. Warna bawaan di `config/ic_konten.php` (`tampilan.warna` dan `tampilan.teks`) harus sama dengan token CSS (dijaga tes). Nama warna kartu (`koral`, `kuning`, `biru`, `lilac`, `pink`, `hijau`, `merah`) dipertahankan agar data lama aman, tetapi kini memetakan tujuh gradasi biru. Teks di atas ubin terang memakai navy dan di atas ubin gelap putih (lolos kontras AA). Ikon 3D diubah jadi ukiran satu warna lewat CSS (`mix-blend-mode`); mengganti ke ikon garis hanya perlu mengganti berkas di `public/aset/inner-circle/ikon/`.
- **Dock CMS Inner Circle:** tinta `#27233f` (sama dengan tombol Kelola dan judul halaman), pil aktif lavender `#ebe7f7`. Aturannya di `body.cms-ic .cms-dock` pada `app.css`.
- **Filter dan tab CMS** memakai satu komponen chip mengalir (`.cms-chips` dan `.cms-chip` di `app.css`), bukan baris yang digeser ke samping.
- **Pola halaman modul CMS:** judul bagian, kartu form di kiri, kartu pratinjau di kanan (`lg:grid-cols-2`, pratinjau `lg:sticky`; di bawah 1024 px pratinjau turun ke bawah form). CMS Artis dan CMS Inner Circle memakai pola yang sama.
- **Pratinjau tab Konten = potongan halaman ASLI, bukan gambar ulang tulisan tangan.** Saat halaman bagian dibuka, `App\Services\InnerCircle\IcPotongan` merender view yang sama dengan halaman anggota/publik SEKALI per halaman (anggota contoh id 0, tidak pernah ditulis ke database; tanpa iframe dan tanpa permintaan per ketukan), memberi penanda tak terlihat di tiap teks konten (`IcKonten::pakaiPenanda`), mengubahnya jadi `<span data-replika-teks="kolom" data-bawaan="...">`, lalu hanya menyisakan blok yang memuat isian modul lengkap dengan pembungkus aslinya. Skrip kecil di `konten-bagian.blade.php` memperbarui span itu saat mengetik; teks yang sedang difokuskan diberi garis penuh, yang sudah diubah garis putus-putus, dan seluruh potongan `inert` (tidak bisa diklik). Susunan otomatis selalu sama dengan halaman asli, jadi view baru tidak perlu dibuatkan replika. Isian yang tidak tampil di halaman contoh (mis. tombol khusus anggota yang sudah masuk, teks "belum ada" saat datanya ada) dan modul Teaser (tema situs artis) memakai replika sederhana `replika.blade.php` (`App\Support\IcReplika::tipe()`), dan tes memastikan setiap isian punya tempat di pratinjau. Pratinjau berskala layar HP yang TETAP (class `ic-ponsel`: lebar 390 px di tengah panel; tidak ikut lebar panel atau layar). CSS area anggota menyesuaikan diri dengan lebar LAYAR (`@media` dan satuan `vw`), jadi `vite/buat-kontainer.js` menyalinnya ke `resources/css/ic-kontainer.css` khusus di dalam `.ic-replika--halaman`: tiap `@media (max-width: N)` menjadi `@container ic-panel (max-width: N)` dan `Nvw` menjadi `Ncqw`, sehingga tampilan sama dengan HP asli. Berkas itu dibuat otomatis oleh `npm run dev` dan `npm run build` (dan di-commit); jangan diedit manual, ubah `inner-circle.css` lalu jalankan ulang. Pratinjau di CMS tidak memakai gradasi latar (kecuali pratinjau Tampilan). Bagian Tampilan memakai `replika-tampilan.blade.php`. Bila potongan gagal dibuat, halaman CMS tetap terbuka dengan replika sederhana. CMS Artis tetap memakai replika yang digambar di browser.
- **`config/ic_konten_modul.php` wajib ikut di-commit** (35 modul). Tanpa berkas ini semua bagian tab Konten tampil tanpa form dan pratinjau. Grup teks baru di `config/ic_konten.php` wajib dibuatkan modulnya; tiap kunci teks harus ada di tepat satu modul (dijaga tes).
- Peringatan VS Code `invalidConfigPath` pada `config('ic_konten...')` adalah false positive ekstensi Tailwind IntelliSense. Atur `tailwindCSS.lint.invalidConfigPath` ke `ignore` di Settings workspace (folder `.vscode` tidak ikut Git).

### 4.5 Database

| Tabel                                      | Kolom utama                                                                                                                                                                                        |
| ------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `accounts`                                 | Admin CMS (`username`, `password`, `email` untuk OTP)                                                                                                                                              |
| `members`                                  | `kode_member` (`IC-00001`), `email`, `password` (kosong untuk akun Google), `avatar`, `poin_total` (cache), `google_id`, `email_verified_at`, `sesi_versi`                                         |
| `ic_events`                                | `kode`, `tanggal`, `kota`, `venue`, `tipe`, `token` (terenkripsi), `jendela_mulai/selesai`, titik lokasi (`lokasi_lat`, `lokasi_lng`, `lokasi_radius`, `lokasi_sumber`, `lokasi_diatur_oleh/pada`), kolom GL (`username_acara`, `sandi_acara` terenkripsi, `kuota_gl`, `undian_pada`, `status_undian`, `undian_selesai_pada`, `poin_gl`, `poin_mandiri`, `poster`, `deskripsi`, `petunjuk_masuk`) |
| `ic_gl_antrean`                            | Pendaftaran antrean GL: unik `(event_id, member_id)`, `status` (antri/menang/tidak_terpilih), `urutan_undi`, `kartu_dikirim_pada`, `diterima_show_pada`. Baris pemenang jadi dasar kartu GL, tidak dihapus setelah undian |
| `ic_poin`                                  | Buku besar, hanya ditambah. Unik `(member_id, event_id)` dan unik `(claim_id, sumber)`; `sumber` (checkin/manual/koreksi/`tukar` = poin ditahan, negatif/`kembali` = poin dikembalikan), `claim_id`, `jarak_m`, `akurasi_m` |
| `ic_rewards`, `ic_claims`, `ic_claim_logs` | Hadiah (`harga_poin` boleh sama, `stok` kosong = tak terbatas), penukaran (`poin_ditahan`, 0 = klaim lama; boleh berulang), jejak status |
| `ic_reward_tunggu`                         | Daftar tunggu hadiah stok habis, unik `(member_id, reward_id)` |
| `ic_pengaduan`                             | Pengaduan anggota: `kategori`, `event_id` (opsional), `isi`, `status` (baru/diproses/selesai), `catatan_admin`; ikut terhapus bersama akun |
| `ic_broadcasts`                            | Pengumuman (`unggulan`, `terbit_pada`, `aktif`)                                                                                                                                                    |
| `ic_memories`, `ic_memory_likes`           | Kenangan (tayang/disembunyikan) dan tanda suka                                                                                                                                                     |
| `ic_pengaturan`                            | Teks konten yang **diubah** admin (`kunci` unik, `nilai`, `diubah_oleh`). Tanpa baris = teks bawaan, jadi tabel kosong tidak mengubah tampilan                                                     |
| `ic_pilihan`                               | Tipe acara dan kategori kenangan (`jenis`, `kunci` tetap, `label`, `ikon`, `sampul`, `warna`, `urutan`, `aktif`); diisi awal dari config oleh migrasi; unik `(jenis, kunci)`                       |
| `ic_item`                                  | Daftar berurutan untuk konten (`grup`, `urutan`, `judul`, `isi`, `ikon`, `aktif`)                                                                                                                  |
| `log_errors`, `log_aktivitas`              | Log error (digabung) dan jejak aksi admin                                                                                                                                                          |
| Situs artis                                | `banners`, `headers`, `albums`, `news`, `merchandise`, `color_pages`, `statistik`, `highlight`, tabel profil                                                                                       |
| Sistem                                     | `sessions`, `cache`, `jobs`, `migrations`                                                                                                                                                          |

Check-in bukan tabel sendiri: ia baris `ic_poin` dengan `sumber = checkin`. `members.poin_total` hanya cache yang diperbarui di transaksi yang sama.

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

Jumlah tes ada di baris Status di atas. Database SQLite di memori; tes **tidak bergantung pada `.env`** (semua env dipaku di `phpunit.xml`), jadi hasilnya sama di lokal dan di GitHub. `ImageProcessorTest` dilewati otomatis bila ekstensi `gd`/`exif` tidak aktif. SQLite tidak menguji kunci baris (`lockForUpdate`): coba klik ganda klaim dan suka secara manual di MySQL.

Tes dikelompokkan per area di `tests/` (nama berkas berakhiran `Test`):

| Folder                       | Isi                                                                                                   |
| ---------------------------- | ----------------------------------------------------------------------------------------------------- |
| `tests/Feature/InnerCircle/` | Subfolder `Acara`, `Admin`, `Akun`, `Checkin`, `Hadiah`, `Komunitas`, `KontenCms`, `Publik`           |
| `tests/Feature/Cms/`         | CMS Artis: cache publik, jadwal tayang, urutan, pencarian, validasi, konversi WebP, mode dan navigasi |
| `tests/Feature/Admin/`       | Login admin, OTP, sesi, log error dan aktivitas                                                       |
| `tests/Feature/Keamanan/`    | CSP, header keamanan, noindex CMS, dependensi dan deploy                                              |
| `tests/Unit/`                | `HtmlSanitizer`, `ImageProcessor`, `IcReplika`, `PenjelasLog`, dan lainnya                            |

### 4.8 Keamanan (ringkas)

- Login admin: pembatas 5/menit per username+IP dan 10/menit per IP, hash selalu dicek, OTP email opsional. Sandi admin pernah tertulis di seeder lama (ada di riwayat git): **anggap bocor dan pastikan sandi di setiap database sudah diganti.**
- Login anggota dibatasi per email+IP dan per IP. Pembatas bernama di `AppServiceProvider` (batas anggota di `config/inner_circle.php`).
- Login Google: `state`, `nonce`, PKCE; id_token diperiksa; email wajib terverifikasi Google; akun sandi yang dikaitkan ke Google kehilangan sandinya (cegah pengambilalihan). Token Google tidak disimpan.
- Semua `{!! !!}` diperiksa; teks kaya dibersihkan `HtmlSanitizer`; JSON-LD memakai `JSON_HEX_TAG`.
- Unggahan: tipe ditentukan dari isi berkas, batas ukuran dan megapiksel, EXIF/GPS dibuang dari foto anggota.
- `.env`, `storage/`, `bootstrap/cache/` tidak pernah dikirim oleh auto deploy.

**Backlog keamanan dan perbaikan (urut prioritas):**

1. Cadangan database terjadwal dan HTTPS paksa.
2. Content-Security-Policy: **sudah dipasang sebagai `Report-Only`** (bawaan di production, atur lewat `CSP_MODE` di `.env`: `off` / `report` / `enforce`; daftar sumber di `config/keamanan.php`). Pelanggaran dilaporkan ke `/csp-report` dan tampil di halaman Log dashboard sebagai `CSP:<aturan>`. Setelah laporan bersih beberapa hari, ganti ke `CSP_MODE=enforce`. Tahap lanjut: beri `nonce` pada semua `<script>` lalu hapus `'unsafe-inline'` dari `script-src` (tanpa itu CSP belum menghentikan skrip inline yang disuntikkan).
3. Peran admin (saat ini satu bendera sesi tanpa peran). **Pengecekan ulang akun admin sudah ada** di `CekLogin`: sesi yang dibuat lewat login membawa id akun dan sidik sandi, dan dicabut bila sandi diganti atau akun dihapus. Sesi tanpa penanda (mis. dari tes yang memakai `withSession(['login' => true])`) masih diterima; mewajibkannya berarti menyiapkan akun di semua tes tersebut.
4. Tautan di CMS Artis dibatasi 255 karakter (kolom `string`). Bila perlu tautan lebih panjang (mis. Spotify dengan parameter pelacakan), ubah kolomnya menjadi `text` lewat migrasi.
5. Ganti `throttle:N,M` lama di rute admin dengan pembatas bernama; `composer audit` dan `npm audit` rutin.
6. ~~Pin host key SSH di workflow~~ **Selesai**: secret `SSH_KNOWN_HOSTS` diperiksa lebih dulu di langkah Siapkan SSH (entri host:port dan sidik jari harus cocok dengan server). Tanpa secret itu workflow masih jatuh ke `ssh-keyscan` dengan peringatan.

### 4.9 Aturan bisnis penting

**Check-in** (`CheckinService`). Dua jalur. **Check-in mandiri** (bukan pemenang GL): tombol Check-in di halaman acara tanpa QR; server memeriksa anggota login dan terverifikasi, acara bisa check-in (bukan privat, ada venue), di dalam jendela waktu (bawaan 22.00 sampai H+1 04.00, bisa diubah), lokasi di dalam radius, dan belum pernah check-in di acara itu. **Pemenang GL**: tombol mandiri tertutup; mereka menunjukkan Kartu GL ke staf lalu memindai QR acara (`/innercircle/check-in?kode=WS-XXXXXXXXXXXX`); token menentukan acara. Pembatas 8/menit per anggota.

**Lokasi check-in** (`LokasiService`). Acara dengan titik lokasi hanya menerima check-in dari dalam radius (bawaan 150 m, 30 sampai 2.000 m). Browser mengirim `lat`, `lng`, `akurasi`; server menolak bila akurasi lebih buruk dari 150 m atau `jarak - min(akurasi, 50) > radius`. Acara **tanpa** titik tetap bisa check-in dari mana saja, jadi atur titiknya sebelum acara. Titik diisi staff dari GPS HP di venue, hasil cari alamat, atau koordinat ketikan; memindahkan titik lebih dari 1 km harus dikonfirmasi; nama petugas tercatat di Log (akun CMS dipakai bersama). Titik acara tidak dikirim ke browser anggota dan koordinat anggota tidak disimpan (hanya `jarak_m` dan `akurasi_m`). Batas: koordinat dikirim browser, jadi aplikasi lokasi palsu di Android masih bisa mengelabuinya; ini menutup kasus QR difoto lalu dikirim ke orang yang tidak hadir. Pengaturan: `config/inner_circle.php` (`lokasi`). Anggota yang gagal karena GPS mengajukan pemeriksaan (Fase 5B); tim WSM menyetujui atau menolak di Acara > Pemeriksaan, dan check-in yang janggal masuk daftar Penanda untuk ditinjau.

**Penukaran hadiah** (`ClaimService`, `PoinService`). Hanya bila email terverifikasi, hadiah aktif, stok ada, dan saldo (`poin_total`) cukup. Saat diajukan: baris anggota dan hadiah dikunci dalam transaksi, `ic_claims.poin_ditahan` diisi dengan harga saat itu, baris negatif `tukar` masuk buku besar (saldo berkurang), dan stok berkurang. Satu penukaran per hadiah yang boleh berjalan (diajukan/ditinjau/disetujui); setelah selesai boleh menukar lagi. `selesai` = poin terpakai (tidak ada baris baru). `ditolak` (admin, alasan wajib) atau `dibatalkan` (anggota, hanya selama `diajukan`) menambah baris positif `kembali` dan mengembalikan stok; kunci unik `(claim_id, sumber)` menjamin tahan dan kembali masing-masing sekali. Klaim lama (`poin_ditahan` = 0) tidak memotong atau mengembalikan apa pun dan masih bisa dibuka kembali; penukaran baru yang ditolak tidak bisa dibuka kembali (anggota mengajukan lagi). Urutan kunci baris selalu penukaran, anggota, hadiah. Pilihan A/B (`opsi`) tetap didukung.

**Pengaduan** (`PengaduanService`). Kategori dari daftar baku, isi 10 sampai 1.000 karakter, tanpa lampiran, acara terkait opsional, paling banyak 3 per anggota per hari (dihitung dari database, baris anggota dikunci) dan 3 per menit. Admin mengubah status (baru, diproses, selesai) dan catatan dari detail anggota; email status dikirim hanya saat statusnya berubah.

**Tampil/sembunyikan.** Tahap hadiah dan pengumuman punya tombol (kolom `aktif`); minimal satu tahap harus aktif; pengajuan yang sudah masuk tetap diproses.

**Kenangan.** Caption maks 240 karakter, tempat maks 65, langsung tayang; admin bisa menyembunyikan. Nama di galeri, komentar, dan daftar penyuka tampil lengkap bersama foto profil (Kebijakan Privasi 22 November 2026). Foto dikecilkan ke 1600 px, profil 512 px.

**Hapus akun** (`HapusAkunService`): ketik `HAPUS` (dan sandi bila ada). Menghapus akun, foto, poin, klaim, pengaduan, kenangan, dan suka; **ditahan** bila ada klaim `diajukan`, `ditinjau`, atau `disetujui`.

**Pembatas laju dan unggahan.** Anggota: check-in 8/menit, penukaran, batal, dan daftar tunggu 6/menit (satu pembatas), pengaduan 3/menit, kenangan 4/menit, suka 60/menit, foto 6/menit, hapus akun 5/menit (per anggota, bukan IP, karena penonton satu venue sering berbagi IP). Lupa sandi punya batas harian global 100. Unggahan CMS: banner dan hero 2 MB; album, berita, merchandise, header 1 MB; video header 50 MB (batas upload server harus ikut naik); semua gambar maks 16 megapiksel. Foto anggota: kenangan 4 MB, profil 5 MB.

**Menambah teks yang bisa diubah dari CMS.** (1) Daftarkan kunci di `config/ic_konten.php`: `grup`, `label`, `bawaan` (teks yang tampil sekarang, persis), `maks`, dan `baris` bila beberapa baris. (2) Ganti teks di view dengan `{{ ic_teks('grup.kunci') }}` (selalu `{{ }}`, jangan `{!! !!}`). (3) Bila grupnya baru, tambahkan di `grup` pada config itu. Form di tab Konten, validasi, dan label Log otomatis ikut. Kunci yang salah ketik melempar galat saat pengembangan dan tes; di produksi mengembalikan teks kosong. Tes `InnerCircleKontenTest` memastikan setiap kunci di view terdaftar **dan** setiap kunci di config dipakai view (tidak ada isian CMS yang tidak berpengaruh). Teks bersifat biasa: `<` dan `>` ditolak; placeholder yang boleh: `{program}` dan yang didaftarkan per kunci (`placeholder` atau `tautan`). Isian beberapa baris atau yang memuat tautan ditulis `{{ ic_html('kunci') }}`. Daftar berurutan baru: tambah definisinya di `daftar` pada config yang sama (judul/isi/ikon, `maks_item`, `bawaan`), daftarkan di `grup.<nama>.daftar`, lalu pakai `ic_daftar('nama')` di view dan tanam isi awalnya lewat migrasi `IcDaftar::tanamBawaanBilaKosong()`.

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

**Bandsintown.** Isi `BANDSINTOWN_APP_ID` (butuh persetujuan Bandsintown) dan `BANDSINTOWN_ARTIST`. Manual: CMS Inner Circle, **Acara & QR**, **Sinkron sekarang**. Acara baru dibuat dengan QR sendiri; acara yang ada tidak ditimpa dan tidak dihapus otomatis. Otomatis pukul 04.00 WIB (cron yang sama juga menjalankan **undian GL tiap menit**, `ic:undi-gl`) (jadwal ditulis tanpa proses baru karena `proc_open` mati di hosting, lihat 6.3): di cPanel, Cron Jobs, tiap menit: `* * * * * /usr/local/bin/php /home/USERNAME/laravel/artisan schedule:run >> /dev/null 2>&1` (sesuaikan path). Di Rumahweb pakai PHP yang sama dengan situs (mis. `/usr/local/bin/ea-php84`). Bagian `>> /dev/null 2>&1` wajib: tanpanya cPanel mengirim email hasil cron tiap menit. Cron hidup atau tidak terlihat di kartu **Jadwal otomatis** pada halaman Log (Normal bila detak terakhir kurang dari 3 menit). Kegagalan sinkron Bandsintown dan undian GL muncul sebagai error di Log CMS Inner Circle dan ikut email peringatan. Bila `BANDSINTOWN_APP_ID` kosong, sinkron otomatis dilewati tanpa pesan.

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

## 6. Aturan akhir Inner Circle dan sisa pekerjaan

**Status: semua fase pengerjaan (1 sampai 10) selesai dan tayang.** Fase 11 (tab Kendala, akun Tim Show, WhatsApp otomatis) tidak dikerjakan; kebutuhannya dijawab dengan **Kartu GL untuk vendor** (PDF yang bisa dipratinjau dan dibagikan). Pekerjaan yang masih menunggu keputusan ada di 6.5. Aturan: tiap push yang punya migrasi harus aman untuk data yang ada, dan **database dicadangkan (ekspor phpMyAdmin) sebelum push** karena auto deploy langsung menjalankan `migrate`.

### 6.1 Aturan

| Topik             | Aturan                                                                                                                                                                                                  |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Poin              | **GL gratis = 1.000 poin. Check-in mandiri (tanpa GL) = 2.000 poin.** Angka bawaan yang bisa diubah admin (global dan per acara). Satu kehadiran per anggota per acara. Poin menggantikan stempel lama.   |
| Antrean GL        | Dibuka sejak admin menginput acara, **ditutup dan diundi otomatis pada hari H pukul 18.00 WIB**. Satu pendaftaran per anggota per acara. Admin bisa menunda atau mengundi sekarang (wajib alasan, tercatat di Log). |
| Kartu GL          | Pemenang mendapat kartu berisi nama anggota, nama acara, username acara, dan sandi acara. Di pintu, pemenang menunjukkan kartu.                                                                         |
| Check-in mandiri  | Untuk anggota yang tidak menang GL: bawaan **22.00 sampai H+1 04.00**, dinilai dari lokasi (radius venue), **tanpa QR**.                                                                                |
| Pemenang GL       | Tidak bisa check-in mandiri (supaya poin tidak dobel). Hadir dicatat lewat **pemindaian QR acara** yang ditunjukkan staf: +1.000 poin.                                                                  |
| Kartu GL vendor   | PDF berisi kartu acara (tempat, nama acara, tanggal, username, sandi, QR acara) dan daftar pemenang (nama dan Member ID, tanpa email) dengan kolom Hadir, untuk vendor club atau festival. Hanya setelah undian selesai. |
| Hadiah            | Ditukar dengan poin: Meet & Greet 150.000, Merchandise 250.000 (admin bisa menambah). Poin ditahan saat mengajukan; batal, ditolak, atau stok habis = poin kembali.                                       |
| Pengaduan         | Dikirim dari Profil (Akun dan privasi); tampil di detail anggota di CMS dengan pintasan di Ringkasan.                                                                                                  |
| Nama dan foto     | Nama lengkap dan foto profil anggota terlihat anggota lain di kenangan, komentar, dan daftar penyuka.                                                                                                   |

### 6.2 Satu acara dari awal sampai akhir

| Kapan                               | Yang terjadi                                                                                                                                                                  |
| ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Admin menginput acara               | Satuan, impor CSV, atau sinkron Bandsintown. Lalu **Atur acara**: venue, titik + radius di peta, kuota GL, username dan sandi acara, jendela check-in, poin. Antrean GL langsung terbuka. |
| Sebelum hari H                      | Anggota membuka detail acara dan menekan **Antri GL**; boleh keluar selama belum ditutup.                                                                                     |
| Hari H pukul 18.00                  | Antrean ditutup, sistem mengundi acak sebanyak kuota, pemenang dikabari lewat email dan halaman acara, kartu GL tampil untuk pemenang.                                         |
| Setelah undian                      | Admin membuka **Kartu GL untuk vendor** (pratinjau), lalu **Bagikan PDF** ke vendor, atau salin daftar untuk tim Show.                                                        |
| Hari H pukul 22.00 sampai H+1 04.00 | Non-pemenang menekan **Check-in** (+2.000). Pemenang menunjukkan kartu dan memindai QR acara (+1.000). Vendor mencocokkan nama di PDF.                                         |
| Sesudahnya                          | Admin melihat yang hadir (GL gratis dan beli tiket sendiri), memeriksa pengajuan lokasi dan penanda mencurigakan, dan bisa menarik poin yang janggal.                         |

CMS **Acara** terdiri dari dua bagian lewat navbar: **Pengaturan acara** (data, peta, undian, poin) dan **Hari H** (daftar acara + QR cetak, detail kehadiran, panel undian GL, Kartu GL untuk vendor).

### 6.3 Catatan teknis penting

| Hal                      | Penanganan                                                                                                                                                                                                                      |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Poin tidak dobel         | Buku besar `ic_poin` hanya-ditambah. Kehadiran: kunci unik (anggota, acara). Penukaran: satu baris tahan dan satu baris kembali per pengajuan, dengan kunci unik. Penarikan poin mencatat koreksi, jejak check-in tetap ada.    |
| Undian aman              | Pemilihan acak memakai `random_int`; satu undian per acara dijamin lewat kunci baris dan status; hasil terkunci; email pemenang bertanda "sudah dikirim". Kunci baris tidak diuji SQLite: uji manual di MySQL.                 |
| Sandi acara              | Disimpan terenkripsi. Tidak ada di CSV, daftar WhatsApp, maupun panel undian. Hanya terlihat di Atur acara, kartu pemenang, dan **Kartu GL untuk vendor**; membuka dua tempat pertama dan halaman vendor tercatat di Log.     |
| PDF Kartu GL             | Digambar **di browser** dengan jsPDF (dimuat saat tombol ditekan, ±130 kB gzip) dari data JSON halaman: server tidak butuh pustaka PDF dan tidak menyimpan PDF. Bagikan memakai lembar bagikan bawaan perangkat (berkas ikut), atau mengunduh bila tidak didukung. Huruf non-Latin (mis. Mandarin) diganti "?" karena font bawaan PDF; aksen dibuang (é jadi e). |
| Peta                     | Leaflet dibundel lewat Vite (bukan CDN, sesuai CSP). Ubin OpenStreetMap hanya di halaman CMS yang butuh peta. Pencarian alamat lewat `GeocodeService`.                                                                         |
| Jam dan zona waktu       | Jendela 22.00 sampai H+1 04.00 melewati tengah malam: hitung dari tanggal acara + jam, bukan "hari ini". Uji di batas 21.59, 22.00, 03.59, 04.01 dan pergantian tanggal.                                                       |
| Jadwal (cron)            | Semua jadwal di `routes/console.php` memakai `Schedule::call()`, **bukan** `Schedule::command()`: hosting bersama menonaktifkan `proc_open`, dan `command()` membuka proses baru sehingga `schedule:run` gagal ("The Process class relies on proc_open"). Dijaga tes `UndianGlTest`.                                  |
| Privasi                  | Nama dan Member ID pemenang dibagikan ke tim Show dan vendor. Nama lengkap dan foto profil terlihat anggota lain (Kebijakan Privasi 22 November 2026). Koordinat anggota tidak disimpan; penanda mencurigakan memakai sidik perangkat, bukan IP mentah. |
| Teks yang sudah tersimpan | Teks bawaan yang berubah (migrasi `fase10_teks_konten` dan sebelumnya) hanya mengganti teks yang **masih sama dengan bawaan lama**; teks yang pernah diubah admin tidak ditimpa.                                                |
| Tab Konten dan pratinjau | Kunci teks baru wajib didaftarkan di `config/ic_konten.php` dan tepat satu modul di `config/ic_konten_modul.php` (dijaga tes). Halaman baru anggota otomatis ikut pratinjau potongan.                                           |
| Struktur kode            | Fitur memakai controller dan service sendiri (`PoinService`, `GlService`, `UndianService`, `PemeriksaanService`, `PengaduanService`, `KenanganService`, `QrService`); `adminController` dan `acara.blade.php` sudah dipecah.     |

### 6.4 Daftar uji lapangan

Undian dengan data lengkap dan tidak lengkap pada 18.00; undian tidak berulang bila dipicu dua kali; kartu GL hanya tampil untuk pemenang; check-in mandiri ditolak sebelum 22.00 dan diterima sesudahnya, ditolak di luar radius, tidak dobel; pemenang GL memindai QR acara untuk poin dan QR acara lain ditolak; pengajuan pemeriksaan bila GPS gagal; sandi acara tidak ada di CSV; **Kartu GL untuk vendor**: pratinjau tampil, PDF terunduh, tombol Bagikan membuka lembar bagikan di HP, QR di PDF bisa dipindai, nama panjang tidak terpotong, daftar lebih dari satu halaman rapi; penukaran memotong dan mengembalikan poin; pengaduan muncul di detail anggota dan Ringkasan; kenangan, komentar, dan suka tampil dengan nama lengkap dan foto profil; Riwayat aktivitas di Profil memuat poin dan GL; kartu member di layar 320 dan 375 px; uji di HP (Safari iOS dan Chrome Android, HTTPS) termasuk peta dan izin lokasi.

### 6.5 Belum dikerjakan

- **Pemisahan akun dan hak akses** (mis. akun Tim Show yang hanya melihat Hari H dan pemenang GL). Menunggu keputusan peran dan akses masing-masing dari tim; sekarang CMS masih satu peran admin.
- **Tab Kendala** di CMS (status, penanggung jawab, batas waktu, catatan audit). Ditunda; pengaduan anggota sudah ada.
- **Kirim WhatsApp otomatis.** Ditunda: nomor HP anggota belum dikumpulkan (perlu perubahan form daftar, persetujuan, dan Kebijakan Privasi) dan layanan pengirim berbayar. Sementara dipakai tombol Bagikan pada Kartu GL dan "Salin untuk WhatsApp".
- **Cron `schedule:run` tiap menit** di cPanel (cara di 5.4) perlu terpasang agar undian 18.00 dan sinkron Bandsintown 04.00 berjalan tepat waktu. Tanpa cron, undian tetap dipicu kunjungan pertama setelah 18.00 dan tombol **Undi sekarang**.
- **Kerentanan `quill` (moderat, XSS)** di dependensi CMS Artis; perbaikannya (quill 2) perubahan besar, jadwalkan terpisah. Audit CI hanya memberi peringatan untuk tingkat tinggi.
