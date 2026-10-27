# WS Talent Website

Website publik profil artis **Whisnu Santika**, dashboard CMS-nya, dan komunitas penggemar **WS Inner Circle**.

## Daftar isi

- [Ringkasan](#ringkasan)
- [Menjalankan di lokal](#menjalankan-di-lokal)
- [Konfigurasi `.env`](#konfigurasi-env)
- [Tes](#tes)
- [Audit keamanan dan kesiapan produksi](#audit-keamanan-dan-kesiapan-produksi)
- [Panduan rilis ke produksi](#panduan-rilis-ke-produksi)
- [Fase 8: login dengan Google](#fase-8-login-dengan-google)
- [Lampiran A: Rencana dan catatan WS Inner Circle](#lampiran-a-rencana-dan-catatan-ws-inner-circle)

## Ringkasan

| Bagian       | Isi                                                                                                      |
| ------------ | -------------------------------------------------------------------------------------------------------- |
| Situs publik | `/` (home) dan `/profile`; `robots.txt` dan `sitemap.xml` dibuat dinamis oleh `SeoController`            |
| CMS          | `/dashboard` (Banner, Headers, Profile, Warna Web, Albums, News, Merchandise), login admin di `/login`   |
| Inner Circle | `/inner-circle/*` (anggota) dan `/dashboard/inner-circle` (admin), di belakang flag `INNER_CIRCLE_AKTIF` |
| Stack        | Laravel 13, **PHP 8.4+**, MySQL, Tailwind CSS 4, Vite, Swiper, SweetAlert2, Bootstrap Icons, Quill (CMS) |
| Hosting      | cPanel bersama (Rumahweb) tanpa terminal; sesi, antrean, dan cache memakai driver `database`             |

PHP 8.4+ wajib: `composer.lock` memuat Symfony 8.1 yang mensyaratkan PHP >= 8.4.1 (`composer.json` kini juga `^8.4`).

## Menjalankan di lokal

Semua perintah dijalankan di terminal yang terbuka **di folder project** (di VS Code: menu Terminal, New Terminal). Butuh PHP 8.4+, Composer, Node.js, dan MySQL.

```bash
composer install                    # memasang pustaka PHP
cp .env.example .env                # salin contoh konfigurasi (Windows PowerShell: copy .env.example .env)
php artisan key:generate            # membuat APP_KEY
# isi DB_* di .env (DB_CONNECTION=mysql, nama database, user, sandi), lalu:
php artisan migrate --seed          # membuat tabel dan data awal; sandi admin acak dicetak sekali di terminal
php artisan storage:link            # menautkan storage/app/public ke public/storage (foto unggahan)
npm install                         # memasang pustaka JS
npm run dev                         # menjalankan Vite (biarkan terbuka), lalu di terminal kedua:
php artisan serve                   # membuka situs di http://127.0.0.1:8000
```

`npm run build` membuat berkas siap produksi di `public/build/`. Jangan unggah `public/hot` ke server (berkas itu ada selama `npm run dev` berjalan dan membuat situs mencari aset ke komputermu).

## Konfigurasi `.env`

- Lokal: `.env.example`. Server: `.env.production.example` (debug mati, cookie sesi aman, log harian, kredensial kosong).
- Variabel yang spesifik project:

| Variabel                                                                                       | Fungsi                                                                                     |
| ---------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `INNER_CIRCLE_AKTIF`                                                                           | Menyalakan seluruh fitur Inner Circle (default `false`)                                    |
| `INNER_CIRCLE_GOOGLE_AKTIF`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Login dengan Google (Fase 8). Tombol muncul hanya bila flag `true` dan ID + rahasia terisi |
| `KONTAK_PRIVASI_EMAIL`                                                                         | Email kontak yang tampil di halaman `/privasi` (kosong = mengarah ke Profil)               |
| `IC_SEED_CONTOH`                                                                               | Mengisi jadwal acara contoh saat `db:seed`. Hanya lokal/preview                            |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD`                                                             | Akun admin pertama untuk seeder (sandi min. 12 karakter). Wajib di produksi                |
| `TRUSTED_PROXIES`                                                                              | Isi bila di belakang proxy/CDN (mis. Cloudflare); kosong = tidak ada proxy                 |
| `MAVNUS_URL`                                                                                   | Alamat toko merchandise (default `https://mavnus.com`)                                     |

- `.env` tidak boleh masuk git dan tidak boleh ikut dikirim dalam zip. `APP_DEBUG=true` di server membocorkan isi `.env` lewat halaman galat.

## Tes

```bash
php artisan test
```

Perintah ini menjalankan semua tes otomatis (database SQLite di memori, tidak menyentuh database lokalmu). `ImageProcessorTest` otomatis dilewati bila ekstensi PHP `gd` dan `exif` tidak aktif, jadi jalankan sekali di mesin yang memilikinya. Tes tidak menguji kunci baris MySQL (klik ganda klaim dan suka): coba manual di MySQL.

## Audit keamanan dan kesiapan produksi

Audit dilakukan pada kode di commit `b18c7ea` (Fase 7). **Cakupan:** konfigurasi dan `.env`, login admin dan anggota, middleware, validasi dan unggahan, semua keluaran tanpa escape (`{!! !!}`), SQL mentah, redirect, mass assignment, `HtmlSanitizer` (diuji dengan payload XSS), `npm audit`, dan pencocokan `composer.lock` dengan database advisory FriendsOfPHP (snapshot 20 Oktober 2026). **Di luar cakupan:** uji penetrasi, server dan hosting sungguhan, konfigurasi cPanel, dan `composer audit` resmi (Packagist tidak terjangkau dari lingkungan audit, jadi jalankan sendiri, lihat checklist). Audit ini mengurangi risiko, bukan menjamin situs bebas celah.

### Temuan dan status

| #   | Tingkat | Temuan                                                                                                                                                                             | Status                                                                                             |
| --- | ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| 1   | Tinggi  | Username dan sandi admin tertulis di `accountSeeder` (ada di riwayat git)                                                                                                          | Kode diperbaiki (kredensial dari `.env`). **Sandi lama harus diganti**, lihat di bawah             |
| 2   | Sedang  | `HtmlSanitizer` meloloskan `<svg><script>` dan atribut di dalam tag tak dikenal (elemen yang dibuka bungkusnya tidak diperiksa ulang). Hanya admin yang bisa memasukkan konten ini | Diperbaiki, tes baru; teks juga dibersihkan lagi saat ditampilkan (data lama aman)                 |
| 3   | Sedang  | `public/robots.txt` statis menimpa route dinamis, jadi `Disallow: /inner-circle` dari Fase 7 tidak pernah tayang di Apache                                                         | Berkas statis dihapus                                                                              |
| 4   | Sedang  | `npm audit`: 10 kerentanan (vite dev server, axios, follow-redirects, dll.), semuanya di devDependencies, 0 di dependensi produksi                                                 | `npm audit fix`, tersisa 0                                                                         |
| 5   | Sedang  | `composer.json` menulis PHP `^8.3`, padahal lock butuh >= 8.4.1: instalasi di PHP 8.3 gagal dengan pesan membingungkan                                                             | Disamakan `^8.4`, lock disinkronkan                                                                |
| 6   | Sedang  | Tidak ada header keamanan                                                                                                                                                          | Middleware `SecurityHeaders` (nosniff, frame, referrer, permissions, HSTS di produksi)             |
| 7   | Sedang  | Login anggota hanya dibatasi per IP (10/menit): tebak sandi satu akun tidak tertahan, sedangkan penonton satu venue yang berbagi IP bisa saling terkunci                           | Pembatas baru `ic-masuk` (5/menit per email+IP, 60/menit per IP) dan `ic-daftar` (30/menit per IP) |
| 8   | Rendah  | Login admin: waktu respons membedakan username yang ada dan tidak ada                                                                                                              | Hash selalu dicek (hash bcrypt pengganti)                                                          |
| 9   | Rendah  | `.env.example` berisi `APP_DEBUG=true`, tidak ada contoh `.env` server                                                                                                             | `.env.production.example` ditambah                                                                 |
| 10  | Info    | Zip kerja memuat `.env` dan `public/hot` (keduanya di luar git). `public/hot` membuat situs rusak bila terunggah; `.env` memuat kredensial                                         | Jangan unggah/kirim keduanya                                                                       |
| 11  | Rendah  | Kata sandi yang diketik di form daftar/masuk ikut tersimpan di sesi ("old input") saat validasi gagal                                                                              | `dontFlash` untuk `sandi` di `bootstrap/app.php`, diuji                                            |

Hasil pencocokan Composer: 110 paket, 0 cocok dengan advisory yang dikenal (termasuk `laravel/framework` v13.38.0 dan Symfony 8.1.x).

### Tindakan wajib: ganti sandi admin

Sandi admin lama pernah tertulis di repo, jadi anggap bocor. Lakukan di **setiap** database (lokal, preview, produksi) yang akunnya dibuat dari seeder lama:

1. Di komputermu, buka terminal di folder project dan jalankan perintah ini. Ia mencetak hash bcrypt dari sandi baru (ganti `SANDI-BARU-YANG-PANJANG` dengan sandi pilihanmu, minimal 12 karakter):
    ```bash
    php -r "echo password_hash('SANDI-BARU-YANG-PANJANG', PASSWORD_BCRYPT), PHP_EOL;"
    ```
2. Buka cPanel, menu **phpMyAdmin**, pilih database situs, tabel `accounts`, klik **Edit** pada baris admin, tempel hash tadi ke kolom `password`, lalu **Go/Kirim**. Ubah juga `username` bila perlu.
3. Coba login di `/login` dengan sandi baru. Simpan sandi di pengelola kata sandi, jangan di chat atau repo.

### Belum dikerjakan (rekomendasi, urut prioritas)

1. **Content-Security-Policy.** Belum dipasang karena widget Bandsintown, Google Fonts, dan embed video bisa mati bila terlalu ketat. Pasang dulu sebagai `Content-Security-Policy-Report-Only` di preview, perbaiki pelanggaran di konsol browser, baru ditegakkan.
2. **Verifikasi email dan "lupa kata sandi"** (butuh SMTP). Tanpa itu, akun yang salah ketik email tidak bisa dipulihkan.
3. **Batas ukuran unggahan di CMS:** `header_background` (video) dan `banner_cover` tidak punya `max`. Hanya admin yang bisa mengunggah, risikonya disk penuh.
4. **Login admin:** satu sesi bendera `login` tanpa peran, log aktivitas, atau 2FA. Cukup untuk tim kecil; pertimbangkan bila tim bertambah.
5. **Cadangan database** terjadwal di cPanel (Backup / JetBackup) sebelum Inner Circle tayang, karena anggota mulai menyimpan data.
6. **HTTPS paksa** (cPanel: Domains, Force HTTPS Redirect) dan sertifikat AutoSSL aktif.
7. Periksa `composer audit` dan `npm audit` rutin (misal tiap bulan atau di workflow deploy).

## Panduan rilis ke produksi

Panduan kerja untuk memasang situs (dengan Inner Circle) ke hosting cPanel **tanpa terminal**. Kerjakan berurutan.

| #   | Tahap                    | Hasil                                       |
| --- | ------------------------ | ------------------------------------------- |
| 1   | Server siap              | PHP 8.4+, ekstensi, HTTPS                   |
| 2   | `.env` server            | Konfigurasi aman                            |
| 3   | Build dan unggah         | Kode terbaru di server                      |
| 4   | Database                 | Tabel Inner Circle ada, sandi admin diganti |
| 5   | Preview dan uji          | Teruji di HTTPS dan ponsel                  |
| 6   | Google produksi          | Login Google untuk publik                   |
| 7   | Data awal dan menyalakan | Inner Circle tayang                         |
| 8   | Verifikasi               | Semua tercek                                |

### 1. Server

- cPanel **MultiPHP Manager**: PHP **8.4 atau lebih**. **MultiPHP INI Editor**: `upload_max_filesize` dan `post_max_size` minimal `6M`.
- Ekstensi aktif: `gd` (dengan WebP), `exif`, `mbstring`, `pdo_mysql`.
- HTTPS aktif (AutoSSL, gembok muncul) dan **Force HTTPS Redirect** menyala.
- Disarankan satu subdomain **preview** (mis. `preview.whisnusantika.com`) dengan database sendiri.

### 2. `.env` server

- **Server baru (mis. preview):** unggah `.env.production.example` ke folder utama Laravel, ubah namanya jadi `.env` (cPanel **File Manager**, klik kanan, **Rename**), lalu **Edit** dan isi semua baris `GANTI`. Isi `APP_KEY` dari perintah `php artisan key:generate --show` yang dijalankan di komputer lokal (hasilnya diawali `base64:`), dan pakai kunci yang berbeda untuk tiap server.
- **Server utama (sudah live):** jangan ditimpa. Tambahkan hanya variabel yang belum ada, di bagian bawah.

| Variabel                                          | Nilai produksi                                                      |
| ------------------------------------------------- | ------------------------------------------------------------------- |
| `APP_ENV`, `APP_DEBUG`                            | `production`, `false`                                               |
| `APP_URL`                                         | `https://domain-server` (wajib https)                               |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`       | Dari cPanel **MySQL Databases** (nama biasanya berawalan nama akun) |
| `SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT`        | `true` (hanya bila HTTPS sudah aktif)                               |
| `KONTAK_PRIVASI_EMAIL`                            | Email yang dipantau, tampil di `/privasi`                           |
| `INNER_CIRCLE_AKTIF`, `INNER_CIRCLE_GOOGLE_AKTIF` | `false` sampai langkah 7                                            |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`        | Kosong sampai langkah 6                                             |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD`                | Hanya untuk seeder; kosongkan sandinya setelah akun admin ada       |
| `TRUSTED_PROXIES`                                 | Kosong, kecuali di belakang proxy/CDN                               |

Aturan penulisan: buang komentar `#` di baris yang kamu isi; beri tanda kutip pada nilai yang mengandung spasi, `#`, `$`, atau `"` (mis. `DB_PASSWORD="sandi#ku"`).

Cek setelah menyimpan: buka `https://domain/.env` di browser (harus 404 atau 403) dan hapus `bootstrap/cache/config.php` bila ada.

### 3. Build dan unggah

1. Di mesin build (lokal atau workflow) dengan PHP 8.4: `composer install --no-dev --optimize-autoloader`, lalu `npm ci` dan `npm run build`.
2. Unggah semua, **termasuk** `vendor/` dan `public/build/`, **kecuali**: `.env`, `.git`, `node_modules`, `public/hot`, `storage/logs/*`, dan `tests`.
3. Pastikan `public/robots.txt` **tidak ada** di server dan `public/storage` tertaut ke `storage/app/public` (foto muncul).
4. Auto deploy: `.env` harus dikecualikan dari unggahan agar tidak tertimpa.

### 4. Database

1. Buat tabel baru di database server: `members`, semua `ic_*`, plus `sessions`, `cache`, dan `jobs` (migration Fase 4 sampai 8).
2. Tanpa terminal, pilih salah satu: **(a)** di lokal jalankan `php artisan migrate` pada database kosong, lalu di phpMyAdmin pilih **Export > Custom**: struktur semua tabel, dan **struktur + data** untuk `migrations` dan `ic_rewards` (lima tahapan hadiah diisi oleh migration); impor hasilnya lewat **Import** di database server. **(b)** jalankan migrasi dari langkah workflow auto deploy.
3. Ganti sandi admin lama (lihat [Tindakan wajib](#tindakan-wajib-ganti-sandi-admin)).

### 5. Preview dan uji

Di preview (HTTPS) uji di ponsel: login Google dan email, kamera pindai QR, unggah foto dari galeri HP, tampilan 375 px, klaim hadiah dua kali cepat (kunci MySQL), hapus akun, halaman `/privasi`, dan header keamanan (DevTools, tab Network).

### 6. Google Cloud untuk produksi

Gratis: login dengan scope dasar (`openid`, `email`, `profile`) tidak ditagih dan tidak butuh akun penagihan. Abaikan tawaran **Try for free**; bila Console meminta kartu, berhenti dan periksa dulu.

1. Buat **project terpisah** untuk produksi (mis. `WS Inner Circle Produksi`), dengan akun Google organisasi.
2. Cari **Google Auth Platform**, klik **Get started**: nama aplikasi, email dukungan, audiens **External**, email kontak.
3. **Branding:** beranda `https://whisnusantika.com`, kebijakan privasi `https://whisnusantika.com/privasi`, authorized domain `whisnusantika.com`. Kosongkan logo dulu (setahu saya, logo memicu verifikasi merek).
4. **Data Access:** tambah hanya `openid`, `.../auth/userinfo.email`, `.../auth/userinfo.profile`.
5. **Clients > Create client:** **Web application**, redirect URI `https://whisnusantika.com/inner-circle/google/callback` (tulis tanpa spasi). Salin **Client ID** dan **Client secret** sekarang (secret hanya tampil sekali).
6. **Audience > Publish app** untuk mengubah status dari _Testing_ ke _In production_. Bila Console meminta verifikasi domain, lakukan lewat Google Search Console.
7. Isi `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `.env` server. Rahasia hanya di `.env` server dan secret manager, tidak di repo atau chat.

| Galat dari Google       | Penyebab dan solusi                                                                        |
| ----------------------- | ------------------------------------------------------------------------------------------ |
| `redirect_uri_mismatch` | Alamat callback tidak sama persis dengan yang terdaftar (cek `https`, garis miring, spasi) |
| `access_denied`         | Status masih _Testing_ dan akun belum jadi penguji, atau pengguna menekan batal            |
| `invalid_client`        | Client ID/secret salah atau belum terbaca (hapus cache konfigurasi)                        |

### 7. Data awal dan menyalakan

1. Isi CMS: jadwal acara, hadiah, dan satu pengumuman. Pastikan `IC_SEED_CONTOH` tidak `true`.
2. Isi `KONTAK_PRIVASI_EMAIL`, dan tinjau teks `/privasi` (lihat Fase 8).
3. Set `INNER_CIRCLE_GOOGLE_AKTIF=true`, lalu `INNER_CIRCLE_AKTIF=true`.
4. Tanpa terminal, hapus cache: kosongkan isi `storage/framework/views/` dan hapus `bootstrap/cache/config.php` bila ada.

**Mematikan darurat:** set `INNER_CIRCLE_AKTIF=false` lalu hapus cache seperti di atas. Semua halaman Inner Circle kembali 404, situs publik tidak terpengaruh.

### 8. Verifikasi pasca rilis

- [ ] `/robots.txt` memuat `Disallow: /inner-circle`; `/sitemap.xml` memuat `/privasi`.
- [ ] `https://domain/.env` tidak terbuka; header `X-Frame-Options` dan `Strict-Transport-Security` ada.
- [ ] Login Google dengan akun yang **bukan** penguji berhasil, dari ponsel.
- [ ] Alur QR: tamu scan, masuk lewat Google, kembali ke check-in.
- [ ] `/privasi` terbuka tanpa login dan email kontak benar.
- [ ] Hapus akun uji berhasil (foto ikut hilang).
- [ ] `composer audit` dan `npm audit` dari lokal bersih.

## Fase 8: login dengan Google

**Status:** kode selesai dan teruji (28 tes). Tombol baru tampil setelah kamu membuat client OAuth di Google dan mengisi `.env` (langkah di bawah). Hanya untuk anggota Inner Circle; login admin CMS tidak berubah. Login Google berjalan **berdampingan** dengan email + sandi.

### Cara kerjanya

Tombol "Lanjutkan dengan Google" (halaman masuk) dan "Daftar dengan Google" (halaman daftar) membuka `/inner-circle/google/masuk`, pengunjung dialihkan ke Google, lalu Google mengembalikannya ke `/inner-circle/google/callback`. Server memeriksa jawaban Google, mencocokkan atau membuat anggota, lalu login seperti biasa (dan kembali ke tujuan semula, mis. tautan QR check-in).

Alurnya ditulis langsung dengan HTTP client bawaan Laravel (OAuth 2.0 authorization code + PKCE, OpenID Connect), **tanpa** `laravel/socialite`. Alasannya: tidak ada paket baru yang harus dipasang dan diunggah (`vendor/`) ke hosting tanpa terminal, dan alurnya kecil serta bisa diuji penuh tanpa jaringan. Bila kelak ingin pindah ke Socialite, hanya `GoogleOAuth` yang perlu diganti.

| File                                                              | Peran                                                                         |
| ----------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| `app/Services/InnerCircle/GoogleOAuth.php`                        | Membuat URL izin Google, menukar kode dengan token, memeriksa id_token        |
| `app/Services/InnerCircle/GoogleAkun.php`                         | Mencocokkan identitas Google dengan anggota (masuk, buat baru, atau kaitkan)  |
| `app/Http/Controllers/InnerCircle/googleController.php`           | Dua route: `ic.google.masuk` dan `ic.google.callback`                         |
| `app/Http/Middleware/IcSesiVersi.php`                             | Mengeluarkan sesi anggota yang sudah dicabut (lihat aturan 4)                 |
| `database/migrations/2026_10_27_000000_members_google.php`        | Kolom `google_id`, `email_verified_at`, `sesi_versi`; `password` boleh kosong |
| `resources/views/components/inner-circle/tombol-google.blade.php` | Tombol sesuai pedoman merek Google                                            |

### Aturan keamanan yang diterapkan (semuanya ada tesnya)

1. **`state`, `nonce`, dan PKCE** per permintaan, disimpan di sesi dan dibuang setelah dipakai; kedaluwarsa 10 menit.
2. **id_token diperiksa:** penerbit (`iss`), penerima (`aud` = client ID kita), kedaluwarsa (`exp`), dan `nonce`. Tanda tangan tidak diverifikasi ulang karena token diterima langsung dari Google lewat HTTPS dengan client secret (sesuai dokumentasi Google).
3. **Email harus terverifikasi oleh Google.** Pencocokan memakai `google_id` (`sub`) lebih dulu, baru email.
4. **Cegah pengambilalihan akun.** Pendaftaran sandi belum memverifikasi email, jadi orang lain bisa mendaftarkan email korban lebih dulu. Saat korban masuk lewat Google, akun itu dikaitkan, **sandi lama dihapus**, token "ingat saya" diganti, dan `sesi_versi` dinaikkan sehingga sesi lama penyerang langsung berakhir.
5. **Akun yang sudah tertaut ke akun Google lain ditolak.** Akun hanya-Google tidak bisa masuk dengan sandi (pesan galatnya sama dengan "sandi salah").
6. **Token Google tidak disimpan**; hanya `sub`, email, dan nama.
7. **Galat ramah, bukan error 500:** pembatalan, sesi habis, state salah, Google menolak, atau tidak terjangkau; semuanya kembali ke halaman masuk dengan pesan jelas. Log hanya memuat status dan jenis galat, tanpa rahasia.
8. **Pembatas laju** `ic-google`: 60/menit per IP (longgar karena penonton satu venue berbagi IP).

### Setup di Google (sekali, dikerjakan pemilik akun Google Cloud)

Tampilan Google Cloud Console sering berubah; nama menu di bawah bisa sedikit berbeda, tetapi isinya sama.

1. Buka <https://console.cloud.google.com>, masuk dengan akun Google organisasi WSM (bukan akun pribadi), lalu buat **project** baru (mis. "WS Inner Circle").
2. Buka menu **Google Auth Platform** (dulu: _APIs & Services > OAuth consent screen_). Isi: nama aplikasi "WS Inner Circle", email dukungan, tipe audiens **External**, email kontak.
3. Buka **Clients > Create client**, tipe **Web application**. Pada **Authorized redirect URIs** isi alamat callback persis seperti di bawah (huruf, `http`/`https`, dan garis miring harus sama):
    - lokal: `http://127.0.0.1:8000/inner-circle/google/callback`, dan juga versi `http://localhost:8000/...` bila kamu kadang membukanya lewat `localhost` (alamat di browser harus sama dengan yang terdaftar, kalau tidak Google menampilkan `redirect_uri_mismatch`);
    - preview: `https://<domain-preview>/inner-circle/google/callback`;
    - produksi: `https://whisnusantika.com/inner-circle/google/callback`.

    Disarankan satu client untuk lokal/preview dan satu client lain untuk produksi, supaya rahasianya terpisah.

4. Simpan **Client ID** dan **Client secret**. Rahasia hanya boleh ada di `.env` server dan di secret manager workflow deploy, tidak di repo atau chat.
5. Selama status aplikasi **Testing**, hanya akun yang didaftarkan di **Audience > Test users** yang bisa masuk (dan izin kedaluwarsa tiap 7 hari). Tambahkan Gmail-mu untuk uji coba.

### Menjalankan di lokal

1. Isi `.env` (file di folder project, buka di VS Code). Nilainya dari langkah 4 di atas:
    ```
    INNER_CIRCLE_AKTIF=true
    INNER_CIRCLE_GOOGLE_AKTIF=true
    GOOGLE_CLIENT_ID=isi-client-id-dari-google
    GOOGLE_CLIENT_SECRET=isi-client-secret-dari-google
    ```
    Biarkan `GOOGLE_REDIRECT_URI` kosong (bawaannya alamat callback situs).
2. Di Terminal VS Code (folder project), jalankan perintah ini untuk menambah kolom baru di database lokal dan membersihkan cache konfigurasi:
    ```bash
    php artisan migrate
    php artisan config:clear
    ```
3. Jalankan `php artisan serve` dan `npm run dev`, buka `http://127.0.0.1:8000/inner-circle/masuk` (alamat yang sama dengan yang didaftarkan di Google), lalu klik tombol Google dengan akun penguji.
4. Uji tiga skenario: (a) email Google belum pernah terdaftar (akun baru dibuat), (b) email yang sudah terdaftar lewat sandi (akun dikaitkan dan sandi lama tidak berlaku), (c) klik "Batal" di halaman Google (kembali dengan pesan, tanpa error).

### Halaman privasi dan hapus akun

- **`/privasi`:** publik dan selalu aktif (tidak ikut flag Inner Circle), ada di sitemap, footer, serta halaman masuk dan daftar. Isinya di `resources/views/pages/privasi.blade.php`; email kontak dari `KONTAK_PRIVASI_EMAIL` (kosong = halaman mengarah ke Profil); ubah `privasi_diperbarui` di `config/site.php` setiap isi berubah. **Teks ini draf: tinjau oleh pengelola (dan penasihat hukum bila perlu) sebelum rilis.**
- **Hapus akun:** Profil > **Akun dan privasi** > **Hapus akunku**. Anggota mengetik `HAPUS` (dan kata sandi bila akunnya punya sandi; akun hanya-Google cukup `HAPUS`). Terhapus permanen: akun, foto profil, stempel, pengajuan hadiah beserta lognya, kenangan beserta fotonya, dan tanda suka. **Ditahan** bila masih ada pengajuan berstatus `diajukan`, `ditinjau`, atau `disetujui`. Dibatasi 5 percobaan per menit per anggota. Kode: `HapusAkunService`, `akunController`, `hapusAkunRequest`; tes: `InnerCircleAkunPrivasiTest`.

### Catatan dan batasan

- Foto profil Google **tidak** dipakai; anggota mengunggah foto sendiri.
- Belum ada "lupa kata sandi" dan verifikasi email untuk akun sandi. Akun hanya-Google tidak membutuhkannya.
- Login admin CMS sengaja tidak memakai Google.
- Rilis ke preview dan produksi: ikuti [Panduan rilis ke produksi](#panduan-rilis-ke-produksi).

---

# Lampiran A: Rencana dan catatan WS Inner Circle

> Nomor bagian di lampiran ini (1 sampai 9) berdiri sendiri dan dipakai sebagai rujukan silang di dalamnya.

> Status: **Fase 1 sampai 7 selesai**; Fase 8 (login Google): kode selesai, menunggu setup di Google (lihat bagian _Fase 8_). Fase 1 sampai 3: kerangka UI, 7 layar anggota, dan panel admin 7 tab. Fase 4: akun anggota, check-in QR, dan stempel sudah tersambung ke database. Fase 5: hadiah dan klaim sudah tersambung ke database (tahapan hadiah dikelola di tab Hadiah, antrean klaim di tab Klaim). Fase 6: Komunitas (pengumuman, galeri kenangan, suka) dan foto profil sudah tersambung ke database; semua layar anggota dan 7 tab admin kini memakai data asli. Fase 7: teaser di home, link navbar, robots.txt, pembersihan data dummy, dan daftar rilis. Flag fitur mati secara bawaan. Yang tersisa: uji perangkat nyata (lihat Fase 7), checklist rilis (bagian _Panduan rilis ke produksi_ di atas), dan setup Google (Fase 8).
> Dibuat: 21 Oktober 2026.
> Acuan: `WS_Inner_Circle.html` (prototype native, satu file, data di `localStorage`) dan 19 screenshot UI di `inner-circle-ui-prototype.zip`.

## 1. Tujuan dan cara kerja

Menanam komunitas **WS Inner Circle** di website ini: penggemar mendaftar, mengumpulkan **stempel** dengan hadir di acara (check-in QR), membuka dan mengklaim **hadiah**, membaca **pengumuman**, dan berbagi **kenangan** (galeri foto). Tim WSM mengelolanya dari dashboard CMS yang sudah ada.

Urutan kerja: **UI dulu dengan data dummy, backend menyusul per fitur.**

| Fase | Isi                                                        | Data     |
| ---- | ---------------------------------------------------------- | -------- |
| 1    | Fondasi UI (layout, CSS, komponen, flag fitur)             | Dummy    |
| 2    | UI anggota: 6 layar                                        | Dummy    |
| 3    | UI admin di dashboard CMS                                  | Dummy    |
| 4    | Anggota, check-in, dan stempel sungguhan (selesai)         | Database |
| 5    | Hadiah dan klaim (selesai)                                 | Database |
| 6    | Komunitas (pengumuman, kenangan) dan foto profil (selesai) | Database |
| 7    | Teaser di home, SEO, QA, rilis (selesai)                   | Database |

Fase 1 sampai 3 menghasilkan tampilan lengkap yang bisa ditinjau di browser sebelum satu pun tabel dibuat. Setelah itu tiap fase hanya mengganti sumber data, bukan tampilannya (lihat bagian 4.5).

## 2. Peta layar prototype ke Laravel

| Layar di prototype | URL                                           | Nama route              | Isi                                                             |
| ------------------ | --------------------------------------------- | ----------------------- | --------------------------------------------------------------- |
| Masuk / Daftar     | `/inner-circle/masuk`, `/inner-circle/daftar` | `ic.masuk`, `ic.daftar` | Form email + sandi; daftar: nama, email, sandi, ulangi sandi    |
| Beranda            | `/inner-circle`                               | `ic.beranda`            | Kartu anggota digital, progres stempel, aktivitas terbaru       |
| Hadiah             | `/inner-circle/hadiah`                        | `ic.hadiah`             | 5 ubin tahapan (10, 25, 50, 100, 250), daftar klaim, cara kerja |
| Check-in           | `/inner-circle/check-in`                      | `ic.checkin`            | Jadwal show + filter, kode kehadiran, pindai QR                 |
| Komunitas          | `/inner-circle/komunitas`                     | `ic.komunitas`          | Tab Pengumuman WS dan Galeri Kenangan (+ modal unggah)          |
| Profil & Riwayat   | `/inner-circle/profil`                        | `ic.profil`             | Foto profil, 3 metrik, riwayat stempel, pengajuan hadiah        |
| Admin (1 halaman)  | `/dashboard/inner-circle`                     | `ic.admin.*`            | Dipecah jadi 7 tab di dashboard (bagian 5, Fase 3)              |

Komponen UI yang muncul di banyak layar: kartu anggota, bar progres stempel, ubin hadiah, kartu event, baris aktivitas, kartu pengumuman, kartu kenangan, modal, dan dock navigasi bawah.

## 3. Penyesuaian dari prototype

Prototype dibuat untuk didemokan, bukan untuk dijalankan. Bagian ini mencatat apa yang **dibawa**, **diubah**, dan **dibuang**.

### 3.1 Struktur dan navigasi

| Di prototype                                                                                    | Di Laravel                                                                                                                       |
| ----------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| SPA satu halaman, layar berpindah lewat JS                                                      | Satu layar = satu route = satu Blade = satu `<h1>` (sejalan dengan audit SEO sebelumnya)                                         |
| Gerbang login (`#authGate`) menutupi seluruh halaman, satu form untuk Anggota dan Admin         | Halaman terpisah `/inner-circle/masuk` dan `/inner-circle/daftar`. Tab "Admin" dan kotak kredensial demo dibuang                 |
| Toggle "Anggota / Admin" di header                                                              | Dibuang dari UI anggota. Admin tetap login lewat `/login` yang sudah ada, panelnya pindah ke dashboard CMS                       |
| Sidebar kiri ada di HTML, tapi dimatikan (`display:none`) oleh lapisan CSS terakhir             | Tidak dibawa. Navigasi hanya dock bawah (Beranda, Hadiah, Check-in, Komunitas, Profil), di desktop dan HP                        |
| Filter Check-in (bulan, tipe, cari), tab Komunitas, filter kategori galeri dijalankan oleh JS   | Parameter GET yang dirender server: `?bulan=&tipe=&q=`, `?tab=pengumuman\|galeri`, `?kategori=` (bisa dibagikan, jalan tanpa JS) |
| Panel "Pengaturan acara lama" (disembunyikan), angka admin hardcode `128`, `7`, "1 acara aktif" | Dibuang. Semua angka dihitung dari database                                                                                      |
| Tombol "Simulasi pemindaian dari Admin" dan "Info verifikasi lokasi"                            | Dibuang (khusus demo). Diganti teks singkat "Cara check-in"                                                                      |

### 3.2 Tampilan

- **Empat lapis CSS** menumpuk di prototype (krem-emas, "editorial pop", lalu "Soft Lavender"). Hasil akhir di screenshot adalah lapisan lavender, jadi **hanya itu yang dibawa**, ditulis ulang jadi satu berkas bersih tanpa override bertumpuk.
    - Token: latar `#d9cdf7`, cangkang `#f8f7fc` (border `#dad4ee`), tinta `#111322`, lilac `#c8b2ff`, emas `#ffbf57` / `#f6c65e`, koral `#ffb39e`, biru `#b9d2ff`, pink `#f1a8ea`, teks redup `#7b7890`.
    - Font: DM Sans (UI) dan Space Grotesk (judul besar dan logo). Barlow Condensed di kode prototype sisa lapisan lama dan tidak tampil di hasil akhir, jadi tidak dimuat. Font hanya dimuat di layout Inner Circle.
- **Ikon emoji diganti gambar tetap.** Screenshot tampak 3D karena Windows merender emoji gaya Fluent. Di iOS, Android, dan macOS wajahnya beda, jadi identitas visual berubah per perangkat. Solusi: set ikon gambar (WebP) di `public/aset/inner-circle/ikon/`, mis. Fluent Emoji 3D (berlisensi MIT, cek ulang sebelum dipakai).
- **Glyph teks pada dock** (`⌂ ♧ ⌖ ♡ ◉ ✦`) bergantung font perangkat. Diganti Bootstrap Icons yang sudah ada di project.
- **Tata letak:** badge tipe/status di kartu event menempel di baris kota (terlihat di screenshot Check-in). Dipisah jadi baris sendiri.
- **Stiker "Setiap momen berarti ↗"** di Beranda bukan tombol di prototype. Hilangkan panahnya, atau jadikan tautan ke Komunitas.
- **Aksesibilitas** (mengikuti temuan audit sebelumnya): target sentuh minimal 44x44 px, hormati `prefers-reduced-motion`, cek kontras teks kecil (`eyebrow`, teks abu-abu di atas lavender) terhadap WCAG AA.

### 3.3 Bug dan inkonsistensi di prototype

- **Tombol "undefined" di antrean klaim admin** (terlihat di screenshot). Sisi anggota menyimpan status `Diajukan`, tombol admin memetakan `Requested / Review / Approved / Fulfilled`. Dijadikan satu set baku: `diajukan` → `ditinjau` → `disetujui` → `selesai`, ditambah `ditolak`.
- **Bahasa campur.** "Reward request submitted", "Claim updated", "Remove", "10 stamps ·", "more stempel lagi", "Past schedule", "Scheduled (draft)", "Verified (demo)". Semua diseragamkan ke Indonesia.
- **Pilihan hadiah 50 stempel memakai `prompt()`** (ketik A atau B). Diganti modal dengan dua kartu pilihan: Panggilan video atau Ucapan video khusus.
- **`isPastShow` hardcode `2026-10-13`.** Diganti perhitungan dari `now()` (zona `Asia/Jakarta` sudah diset di `config/app.php`).
- **Kata sandi minimal 6 karakter.** Anggota baru minimal 8. Akun admin yang sudah ada tidak diubah.
- **Teks "simulasi / demo / dummy / prototipe"** di header, footer, dan disclaimer dibuang, diganti copy final.

### 3.4 Yang pindah dari browser ke server

| Di prototype (`localStorage` / JS)                       | Di Laravel                                                                                                                                                |
| -------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Akun dan sandi di `localStorage`                         | Tabel `members`, sandi di-hash, guard `member`                                                                                                            |
| ID anggota `max(id)+1` di browser                        | `id_member` auto-increment; `kode_member` = `IC-` + 5 digit (`IC-00001`), dibuat dalam transaksi                                                          |
| `state.stamps` (angka 68) + `state.activity`             | Tabel `ic_stempel` (buku besar) + cache `members.stempel_total`                                                                                           |
| `definitions[]` (5 hadiah hardcode)                      | Tabel `ic_rewards`, diseed dari prototype, bisa diedit di CMS                                                                                             |
| `state.claims`                                           | Tabel `ic_claims` + `ic_claim_logs`                                                                                                                       |
| `tourEvents[]` (50 baris dummy)                          | Tabel `ic_events`, CRUD + impor CSV di CMS                                                                                                                |
| QR dibuat dan diverifikasi di browser, `BarcodeDetector` | Token dibuat server, QR dirender server (SVG), verifikasi di server. Kamera: pustaka `qr-scanner` (`BarcodeDetector` tidak ada di Safari iOS dan Firefox) |
| `broadcasts[]`, `memories[]` hardcode                    | Tabel `ic_broadcasts`, `ic_memories`, `ic_memory_likes`, dikelola di CMS                                                                                  |
| Foto kenangan `dataURL`, avatar di `localStorage`        | Upload ke disk `public`, dikonversi WebP. Batas tetap: kenangan 4 MB, avatar 5 MB                                                                         |

## 4. Keputusan arsitektur

### 4.1 Flag fitur

Seluruh Inner Circle berada di belakang satu flag supaya kodenya boleh naik ke server (deploy manual maupun otomatis) tanpa langsung tayang:

- `config/inner_circle.php` dengan `aktif` = `env('INNER_CIRCLE_AKTIF', false)`.
- Middleware `icAktif` (alias di `bootstrap/app.php`, seperti `cekLogin`) mengembalikan 404 untuk semua route Inner Circle bila flag mati.
- Link di dashboard, navbar publik, dan teaser home hanya muncul bila flag menyala.
- `.env` server tetap `INNER_CIRCLE_AKTIF=false` sampai rilis (lihat _Panduan rilis ke produksi_). Lokal dan preview boleh `true`.

### 4.2 Tampilan terpisah dari tema situs

- Layout baru `template/innerCircleLayout.blade.php` (`lang="id"`, `noindex`). Layout situs publik dan dashboard tidak disentuh.
- Entry Vite baru: `resources/css/inner-circle.css` dan `resources/js/inner-circle.js`, ditambahkan ke `vite.config.js`.
- Semua class diberi prefix `ic-` dan dibatasi di bawah wrapper `.ic-app` supaya tidak bocor ke halaman lain.
- CSS ditulis sebagai CSS biasa (tidak mengimpor Tailwind), jadi tidak ada konflik reset. Halaman Inner Circle memakai class `ic-*` saja.
- JS vanilla. Notifikasi dan konfirmasi memakai SweetAlert2 dengan `Swal.mixin` lavender sendiri; wrapper `resources/js/sweetalert.js` yang sudah ada bertema gelap dan tidak dipakai di sini.
- Komponen Blade berupa komponen anonim (`<x-inner-circle.nama-komponen />`) di `resources/views/components/inner-circle/`. Berbeda dari komponen lama yang memakai `@include`, karena komponen baru butuh props dan slot (mis. modal).

### 4.3 Autentikasi anggota

Login admin sengaja sederhana (tabel `accounts`, `session('login')`, middleware `cekLogin`) dan **tidak disentuh**.

- Model `member` + guard **`member`** + provider `members` di `config/auth.php`.
- Jangan memakai `App\Models\User`: filenya tidak ada di `app/Models`, sementara `config/auth.php` masih meng-import-nya.
- Route anggota memakai `auth:member`. Route admin tetap `cekLogin`.
- Logout anggota: `Auth::guard('member')->logout()` + `regenerateToken()`. **Jangan** `session()->invalidate()`, supaya sesi admin di browser yang sama tidak ikut terhapus.
- Pendaftaran dan login dibatasi `throttle`.

### 4.4 Konvensi kode

- Model dan tabel mengikuti repo: nama model huruf kecil (`member`, `ic_event`, ...), primary key `id_<nama>` (mis. `id_member`, `id_event`).
- Controller `lowerCamel` di subfolder `app/Http/Controllers/InnerCircle/`.
- Logika di service: `CheckinService`, `ClaimService`, `QrService`. Controller tipis. Form Request per aksi tulis, pesan error Bahasa Indonesia.
- Controller lama (`dashboardController`, `profileController`) **tidak diubah**. Pemrosesan gambar Inner Circle memakai helper baru `app/Support/ImageProcessor.php` (terima WebP, perbaiki orientasi EXIF, batasi dimensi), karena `convertToWebP()` lama bersifat private dan hanya menerima JPEG/PNG.
- Status event (lewat / hari ini / akan datang) dihitung dari `now()` saat dibaca. Tidak butuh cron.

### 4.5 Data dummy memakai bentuk yang sama dengan data asli

Selama Fase 1 sampai 3, `app/Support/InnerCircleDummy.php` mengembalikan objek (`(object) [...]`) dengan nama properti yang sama dengan kolom database nanti. Blade memakai `$x->properti` dan tidak tahu datanya dari mana. Mulai Fase 4, hanya controller yang berganti ke Eloquent; view tidak diubah. `InnerCircleDummy.php` sudah dihapus di Fase 7 karena tidak ada lagi yang memakainya.

Kontrak data per layar:

| Layar     | Variabel ke view                                                                                                                                                                  |
| --------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Beranda   | `$member` (`nama`, `kode_member`, `stempel_total`), `$berikutnya` (`target_stempel`, `sisa`, `persen`), `$aktivitas` (5 terbaru: `judul`, `ikon`, `jumlah`, `tanggal`)            |
| Hadiah    | `$rewards` (`target_stempel`, `judul`, `deskripsi`, `ikon`, `warna`, `opsi`, `status_member`), `$member`                                                                          |
| Check-in  | `$events` (`kode`, `tanggal`, `kota`, `venue`, `tipe`, `status_waktu`, `bisa_checkin`, `sudah_checkin`), `$filter`, `$eventDipilih`                                               |
| Komunitas | `$tab`, `$pengumuman` (`judul`, `isi`, `tag`, `ikon`, `warna`, `cta_label`, `cta_tujuan`, `unggulan`, `terbit_pada`), `$kenangan` (+ `jumlah_like`, `disukai_saya`, `milik_saya`) |
| Profil    | `$member`, `$metrik` (`total_stempel`, `tahap_terbuka`, `jumlah_klaim`), `$riwayat`, `$klaim`                                                                                     |

`status_member` hadiah: `terkunci`, `terbuka`, `diajukan`, `ditinjau`, `disetujui`, `selesai`, `ditolak`.

## 5. Fase pengerjaan

<details>
<summary><b>Fase 1: Fondasi UI (selesai)</b></summary>

**Tujuan:** kerangka tampilan siap, belum ada layar isi.

File yang dibuat atau diubah:

1. `config/inner_circle.php`: flag `aktif`, peta ikon, tipe event, kategori kenangan
2. `.env.example`: `INNER_CIRCLE_AKTIF=false`
3. `app/Http/Middleware/IcAktif.php` + alias `icAktif` di `bootstrap/app.php`
4. `routes/web.php`: grup `/inner-circle` (`ic.beranda`, `ic.hadiah`, `ic.checkin`, `ic.komunitas`, `ic.profil`)
5. `app/Support/InnerCircleDummy.php` + `database/data/ic_events_contoh.csv` (50 baris dari prototype)
6. `resources/css/inner-circle.css`, `resources/js/inner-circle.js`, `vite.config.js` (tambah 2 entry)
7. `resources/views/template/innerCircleLayout.blade.php` (header, dock, `noindex`, `lang="id"`)
8. `resources/views/components/inner-circle/`: `ikon`, `logo`, `avatar`, `dock`, `kartu-anggota`, `bar-stempel`, `ubin-hadiah`, `kartu-event`, `baris-aktivitas`, `pengumuman-unggulan`, `item-pengumuman`, `kartu-kenangan`, `modal`
9. `app/Http/Controllers/InnerCircle/memberController.php` + `resources/views/pages/inner-circle/placeholder.blade.php` (halaman sementara berisi pratinjau komponen)
10. `public/aset/inner-circle/ikon/`: 15 ikon WebP 256 px (Fluent Emoji 3D, lisensi MIT)

Cara mencoba: set `INNER_CIRCLE_AKTIF=true` di `.env`, jalankan `npm run dev` dan `php artisan serve`, buka `/inner-circle`.

Cek hasil:

- Flag mati: semua route `/inner-circle` 404. Flag menyala: layout tampil.
- Dock muncul di 375 px dan 1280 px, tombol aktif mengikuti route.
- Pratinjau komponen: ubin hadiah (terbuka, terkunci, lebar), kartu event (lewat, sudah check-in, privat, tanpa venue), aktivitas, pengumuman, kenangan, dan modal (tutup dengan silang, Esc, klik latar).
- Warna dan font mengikuti screenshot prototype.

</details>

<details>
<summary><b>Fase 2: UI anggota (data dummy) (selesai)</b></summary>

**Urutan layar:** Masuk dan Daftar → Beranda → Hadiah → Check-in → Komunitas → Profil.

File yang dibuat atau diubah:

1. `config/inner_circle.php`: tambah `status_klaim` (label + warna lencana) dan `bulan_event`
2. `app/Support/InnerCircleDummy.php`: tambah `riwayat()`, `klaim()`, `metrik()`
3. `routes/web.php`: `ic.masuk`, `ic.masuk.proses`, `ic.daftar`, `ic.daftar.proses` (POST memakai `throttle:10,1`)
4. `app/Http/Controllers/InnerCircle/authController.php` (baru): form hanya divalidasi, lalu diarahkan ke Beranda. Kata sandi tidak ikut disimpan ke sesi
5. `app/Http/Controllers/InnerCircle/memberController.php`: lima layar asli; filter Check-in dan tab Komunitas dibaca dari GET
6. `resources/views/template/innerCircleLayout.blade.php`: tombol Keluar jadi tautan ke Masuk, toast dari flash sesi
7. `resources/views/template/innerCircleAuthLayout.blade.php` (baru): layout kartu tengah untuk Masuk dan Daftar
8. `resources/views/pages/inner-circle/{masuk,daftar,beranda,hadiah,checkin,komunitas,profil}.blade.php`
9. `resources/css/inner-circle.css` dan `resources/js/inner-circle.js`
10. `public/aset/inner-circle/ikon/ok-hand.webp` (Fluent Emoji 3D, MIT)

Hapus manual: `resources/views/pages/inner-circle/placeholder.blade.php` (sudah tidak dipakai controller).

Yang sudah hidup di fase ini: filter Check-in lewat GET (`?bulan=&tipe=&q=&acara=`), tab dan filter kategori Komunitas (`?tab=&kategori=`), modal unggah kenangan, modal pilihan A/B hadiah 50, konfirmasi klaim, tampil/sembunyi kata sandi, pratinjau foto profil, tanda suka (hanya tampilan), toast. Tombol kamera, unggah QR, simpan foto, hapus foto, klaim, dan unggah kenangan hanya menampilkan toast "segera hadir" (Fase 4 sampai 6).

Cek hasil:

- Semua layar bisa dijelajahi, cocok dengan screenshot di desktop dan HP (375 px).
- Event privat dan event tanpa venue tampil tapi tidak bisa dipilih.
- Tiap halaman punya satu `<h1>`. Tidak ada kata "simulasi", "demo", atau "dummy" di teks yang terlihat.
- Untuk anggota dummy 68 stempel: hadiah 10 `diajukan`, 25 dan 50 `terbuka`, 100 dan 250 `terkunci` (sama seperti screenshot).
- Catatan: pastikan `public/hot` (sisa `npm run dev`) tidak ikut ter-upload ke hosting, kalau ikut, CSS dan JS halaman akan dicari ke server dev.

</details>

<details>
<summary><b>Fase 3: UI admin di dashboard CMS (data dummy) (selesai)</b></summary>

Prototype menaruh admin dalam satu halaman panjang. Di dashboard dibuat menu **Inner Circle** (`/dashboard/inner-circle`) dengan bar tab; tiap tab satu route, satu Blade, satu `<h1>`:

| Tab        | Route (`ic.admin.*`) | Isi                                                                                        |
| ---------- | -------------------- | ------------------------------------------------------------------------------------------ |
| Ringkasan  | `ringkasan`          | 4 angka (anggota, kehadiran, klaim menunggu, acara aktif), klaim terbaru, acara berikutnya |
| Anggota    | `anggota`            | Daftar, cari (`?q=`), dialog tambah stempel manual                                         |
| Acara & QR | `acara`              | Panel QR per acara, form acara + preview, impor CSV, jadwal terfilter dan terpaginasi      |
| Klaim      | `klaim`              | Antrean pengajuan dengan tombol alur status dan tolak                                      |
| Hadiah     | `hadiah`             | Edit 5 tahapan (judul, deskripsi, ikon, warna, pilihan) + preview                          |
| Pengumuman | `pengumuman`         | Form kiri / preview kanan (Kabar Pilihan atau item biasa) + daftar tersimpan               |
| Kenangan   | `kenangan`           | Moderasi: filter kategori dan status, sembunyikan / tayangkan                              |

File yang dibuat atau diubah:

1. `routes/web.php`: grup `/dashboard/inner-circle` (`cekLogin` + `icAktif`)
2. `config/inner_circle.php`: tambah `tab_admin`, `alur_klaim`, `ikon_pilihan`, `warna_kartu`, `cta_pengumuman`
3. `app/Support/InnerCircleDummy.php`: tambah `anggota()`, `klaimAdmin()`, `ringkasan()`, `eventsAdmin()`, `tokenAcara()`, `pengumumanAdmin()`, `kenanganAdmin()`
4. `app/Http/Controllers/InnerCircle/adminController.php` (baru)
5. `resources/views/template/dashboardLayout.blade.php`: tambah `@stack('head')` dan `@stack('scripts')`
6. `resources/views/components/dashboard/inner-circle/tabs.blade.php` (baru): judul, bar tab, catatan data contoh, skrip bersama
7. `resources/views/pages/dashboard-pages/inner-circle/{ringkasan,anggota,acara,klaim,hadiah,pengumuman,kenangan}.blade.php` (baru)
8. `resources/views/components/dashboard/navbar.blade.php`: menu Inner Circle (hanya bila flag menyala)
9. `resources/views/pages/dashboard.blade.php`: kartu pintasan 7 tab (hanya bila flag menyala)

Yang sudah hidup di fase ini: preview memakai **komponen anggota yang sama** (`kartu-event`, `ubin-hadiah`, `pengumuman-unggulan`, `item-pengumuman`, `kartu-kenangan`) di dalam `.ic-app`, dan CSS Inner Circle hanya dimuat di tab yang punya preview (Anggota tidak memuatnya). Preview form Acara, Hadiah, dan Pengumuman berubah langsung saat mengetik; tombol alur Klaim (Tinjau, Setujui, Selesaikan, Tolak) dan Sembunyikan/Tayangkan Kenangan mengubah tampilan di halaman saja. Gambar QR di panel Acara hanyalah pratinjau tata letak dan belum bisa dipindai. Tombol simpan, unduh, cetak, impor, dan hapus hanya menampilkan pemberitahuan "segera aktif" (Fase 4 sampai 6).

Catatan:

- Saat flag menyala, dock desktop memuat 8 menu. Di layar 1024 sampai 1279 px label disembunyikan (ikon saja, ada `title`), label penuh mulai 1280 px. Bila flag mati, dock tidak berubah.
- Tab Acara memakai `?acara=KODE` untuk memilih acara pada panel QR.

Cek hasil: 7 tab tampil dengan data contoh, preview identik dengan tampilan anggota, tombol antrean klaim tidak ada yang "undefined", flag mati membuat semua route admin 404.

</details>

<details>
<summary><b>Fase 4: Anggota, check-in, dan stempel sungguhan (selesai)</b></summary>

File yang dibuat atau diubah:

1. Migration `members`, `ic_events`, `ic_stempel` (`database/migrations/2026_10_22_10*`)
2. Model `member` (guard `member`), `ic_event`, `ic_stempel`; `config/auth.php` (guard + provider `members`)
3. Form Request di `app/Http/Requests/InnerCircle/`: `daftarRequest`, `masukRequest`, `checkinRequest`, `simpanEventRequest`, `stempelManualRequest`, `imporEventRequest`
4. Service di `app/Services/InnerCircle/`: `QrService` (SVG, `bacon/bacon-qr-code`), `CheckinService`, `StempelService`, `AcaraService` (kode otomatis + impor CSV), `CheckinGagal`
5. Controller `authController` (masuk, daftar, keluar), `memberController` (Beranda, Check-in, Profil, Hadiah, Komunitas memakai anggota login), `adminController` (Ringkasan, Anggota, Acara & QR + route tulis)
6. `bootstrap/app.php` (tamu area anggota diarahkan ke `/inner-circle/masuk`), `AppServiceProvider` (limiter `ic-checkin` per anggota), `routes/web.php`, `config/inner_circle.php` (`seed_contoh`, `fase_tersambung`, `batas_checkin_per_menit`)
7. View: `innerCircleLayout` (Keluar jadi form POST), `checkin` (form sungguhan + modal pemindai), dashboard `ringkasan`, `anggota`, `acara`, `cetak-qr` (baru), `tabs` (catatan "data contoh" hanya di tab Fase 5 ke atas)
8. `resources/js/inner-circle.js` (`qr-scanner` dimuat lazy: kamera dan unggah gambar) + sedikit CSS pemindai
9. `database/seeders/icEventContohSeeder.php` (hanya jalan bila `IC_SEED_CONTOH=true`), `tests/Feature/InnerCircleTest.php`, `phpunit.xml` (`INNER_CIRCLE_AKTIF=true` untuk tes)

Dependensi baru: `composer require bacon/bacon-qr-code` dan `npm install qr-scanner`. Setelah deploy: `php artisan migrate`, dan jalankan `npm run build` seperti biasa.

Penyesuaian dari rencana awal:

- **Token menentukan acara.** Kode acara di halaman hanya petunjuk. Token terenkripsi tidak bisa dicari lewat SQL, jadi pencarian dari token hanya membandingkan acara yang jendelanya sedang terbuka. Anggota bisa langsung memindai QR tanpa memilih acara dulu.
- Kolom tambahan `ic_stempel.catatan` menyimpan alasan stempel manual dari admin.
- Acara yang sudah punya check-in tidak bisa dihapus (buku besar tidak boleh kehilangan acaranya).
- Jendela check-in khusus per acara bisa diatur di form Acara (kosong = bawaan 12.00 sampai H+1 06.00 WIB).
- Impor CSV semua atau tidak sama sekali; kode yang sudah ada diperbarui tanpa mengganti token.
- Tab Klaim di dashboard memakai data contoh sampai Fase 5 (sudah tersambung ke database di Fase 5).
- Kamera pemindai butuh HTTPS (atau localhost).

Aturan check-in:

- QR berisi URL `/inner-circle/check-in?kode=WS-XXXXXXXXXXXX`. Dipindai kamera bawaan HP pun tetap sampai ke halaman yang benar (setelah login), kode terisi otomatis.
- Dicek di server: anggota login, acara bisa check-in (bukan privat, ada venue), di dalam jendela waktu, token cocok, belum pernah check-in di acara itu.
- Jendela default: **tanggal show pukul 12.00 sampai H+1 pukul 06.00 WIB**, bisa diubah per acara.
- Limiter per **anggota**, bukan per IP.
- Tombol "Buat QR Baru" mengganti token; token lama langsung tidak berlaku.

Cek hasil:

- Daftar → dapat `IC-00001`, stempel 0.
- Check-in dengan token benar → +1 stempel dan masuk riwayat. Ulangi → ditolak. Token lama, event privat, event tanpa venue, di luar jendela → ditolak dengan pesan jelas.
- Event contoh diganti event asli lewat CMS atau impor CSV.
- Tes otomatis: `php artisan test --filter=InnerCircleTest`. Kunci baris (`lockForUpdate`) tidak teruji di SQLite.

</details>

<details>
<summary><b>Fase 5: Hadiah dan klaim (selesai)</b></summary>

File yang dibuat atau diubah:

1. Migration `ic_rewards`, `ic_claims`, `ic_claim_logs` (`database/migrations/2026_10_23_07000*`)
2. Model `ic_reward`, `ic_claim`, `ic_claim_log`; `member` (relasi `klaim()` dan nilai bawaan `stempel_total` = 0)
3. `database/seeders/icRewardSeeder.php` + `DatabaseSeeder`; seeder yang sama dipanggil dari migration `ic_rewards`
4. Form Request di `app/Http/Requests/InnerCircle/`: `ajukanKlaimRequest`, `ubahKlaimRequest`, `simpanHadiahRequest`
5. Service `ClaimService` dan `KlaimGagal` di `app/Services/InnerCircle/`
6. `memberController` (Beranda, Hadiah, `ajukanKlaim`, Profil) dan `adminController` (Ringkasan, Klaim, `ubahKlaim`, Hadiah, `ubahHadiah`)
7. `routes/web.php` (`ic.hadiah.klaim`, `ic.admin.klaim.proses`, `ic.admin.hadiah.ubah`), `AppServiceProvider` (limiter `ic-klaim` per anggota), `config/inner_circle.php` (`fase_tersambung` = 5, `batas_klaim_per_menit`)
8. View anggota `hadiah`, `profil`, `innerCircleLayout`; view dashboard `klaim` dan `hadiah`
9. `resources/js/inner-circle.js` (form klaim dengan konfirmasi, pengaman kirim ganda, toast galat)
10. `tests/Feature/InnerCircleHadiahTest.php`

Setelah deploy: `php artisan migrate` (lima tahapan hadiah ikut terisi) dan `npm run build` seperti biasa.

Aturan yang berlaku (semua dicek di server, `ClaimService`):

- Klaim hanya bila `stempel_total >= target_stempel`, **sekali** per anggota per tahap. Baris anggota dikunci selama transaksi (`lockForUpdate`) dan kunci unik `(member_id, reward_id)` jadi pengaman terakhir, jadi klik ganda tidak membuat dua klaim. Stempel tidak berkurang.
- Tahap yang punya pilihan (50 stempel) wajib memilih salah satunya; nilainya harus salah satu pilihan tahap itu. Tahap tanpa pilihan mengabaikan kiriman `opsi`.
- Alur status: `diajukan` → `ditinjau` → `disetujui` → `selesai`, atau `ditolak` (alasan wajib). `selesai` dan `ditolak` adalah akhir alur. Tiap perubahan masuk `ic_claim_logs` (siapa, kapan, catatan).
- `catatan_admin` (kode voucher, alasan penolakan) tampil di Profil dan di daftar Hadiah anggota.
- Dashboard: tab Klaim (filter `?status=menunggu|semua|selesai|ditolak`, bawaan menunggu dengan yang tertua dulu, 15 per halaman, riwayat per pengajuan) dan tab Hadiah (edit judul, deskripsi, ikon, warna, pilihan per tahap; target stempel tetap).

Penyesuaian dari rencana awal:

- **Lima tahapan diisi lewat migration**, bukan hanya seeder, karena deploy manual hanya menjalankan `migrate`. Seeder memakai `firstOrCreate`: tahap yang sudah ada (dan sudah diedit di CMS) tidak ditimpa.
- **Perpindahan status membawa status yang dilihat admin** (`dari`). Bila status sudah berubah (klik ganda atau admin lain), perubahan ditolak, jadi status tidak melompat dua langkah.
- Aksi tambahan **Catatan**: mengubah catatan tanpa mengubah status, bisa kapan saja (mis. kode voucher dikirim setelah disetujui, atau setelah selesai).
- Tahap hadiah punya **tepat 0 atau 2 pilihan**. Mengubah pilihan tidak mengubah pengajuan yang sudah masuk (`opsi_dipilih` tersimpan apa adanya).
- Pengajuan **ditolak bersifat final**: kunci unik membuat anggota tidak bisa mengajukan ulang tahap yang sama.
- Kolom `ic_rewards.aktif` dan `urutan` sudah ada di skema, tetapi belum ada tombolnya di CMS (lima tahap tetap).
- Pesan galat klaim memakai toast terpisah (`ic_toast_galat`, tampil 6 detik), bukan toast sukses.
- Temuan saat tes: anggota yang baru dibuat membawa `stempel_total` null di memori (kolom baru terisi default 0 di database). Dulu tertutup oleh data contoh. `member` sekarang punya nilai bawaan 0.

Cek hasil:

- Anggota dengan stempel cukup bisa mengajukan; yang kurang ditolak dengan pesan jelas. Klaim dobel ditolak. Tahap 50 wajib memilih opsi.
- Antrean admin menggerakkan status; anggota melihat statusnya dan catatan WSM di Profil dan Hadiah.
- Mengubah tahap di CMS langsung tampil di halaman Hadiah anggota; isian yang gagal divalidasi tampil lagi di tahap yang sama.
- Tes otomatis: `php artisan test --filter=InnerCircleHadiahTest` (22 tes). Kunci baris (`lockForUpdate`) tidak teruji di SQLite, jadi uji klik ganda klaim juga secara manual di MySQL.

</details>

<details>
<summary><b>Fase 6: Komunitas dan foto profil (selesai)</b></summary>

File yang dibuat atau diubah:

1. Migration `ic_broadcasts`, `ic_memories`, `ic_memory_likes` (`database/migrations/2026_10_23_14000*` sampai `14002*`)
2. Model `ic_broadcast` dan `ic_memory`; `member` (relasi `kenangan()`). Tanda suka tidak punya model: dipakai lewat relasi `ic_memory::penyuka()`
3. `app/Support/ImageProcessor.php` dan `GambarGagal.php` (JPG/PNG/WebP, EXIF diluruskan, dikecilkan, disimpan WebP)
4. `app/Policies/MemoryPolicy.php`, didaftarkan di `AppServiceProvider` lewat `Gate::policy` (nama model huruf kecil)
5. Form Request di `app/Http/Requests/InnerCircle/`: `simpanKenanganRequest`, `simpanFotoProfilRequest`, `simpanPengumumanRequest`, `moderasiKenanganRequest`
6. Service di `app/Services/InnerCircle/`: `KenanganService`, `PengumumanService`, `AvatarService`
7. Controller: `kenanganController` (unggah, hapus, suka) dan `fotoProfilController` (simpan, hapus) baru; `memberController` (Komunitas dari database), `adminController` (tab Pengumuman dan Kenangan + aksi tulis)
8. `routes/web.php` (`ic.kenangan.*`, `ic.profil.foto*`, `ic.admin.pengumuman.*`, `ic.admin.kenangan.moderasi`), `AppServiceProvider` (limiter `ic-kenangan`, `ic-suka`, `ic-foto`), `config/inner_circle.php` (`fase_tersambung` = 6, batas unggah, ukuran foto, `warna` dan `sampul` per kategori kenangan)
9. View anggota `komunitas`, `profil`, `innerCircleLayout` (meta CSRF); komponen `kartu-kenangan`, `item-pengumuman`, `pengumuman-unggulan`, `modal`; view dashboard `pengumuman` dan `kenangan`
10. `resources/js/inner-circle.js` (suka lewat fetch, modal terbuka otomatis, konfirmasi hapus, kunci kirim ganda, foto profil)
11. Tes: `tests/Feature/InnerCircleKomunitasTest.php` (32 tes) dan `tests/Unit/ImageProcessorTest.php` (15 tes)

Setelah deploy: `php artisan migrate` (tiga tabel baru) dan `npm run build` seperti biasa. Pastikan `php artisan storage:link` sudah pernah dijalankan (foto disimpan di disk `public`). Di cPanel, naikkan `upload_max_filesize` dan `post_max_size` ke minimal **6M** (MultiPHP INI Editor), dan pastikan ekstensi PHP **gd** dan **exif** aktif. Kalau `post_max_size` terlalu kecil, form unggah gagal dengan halaman "419 Page Expired" sebelum validasi sempat jalan.

Aturan yang berlaku (dicek di server):

- **Pengumuman:** tampil ke anggota hanya bila `aktif` dan tanggal terbitnya sudah tiba (zona `Asia/Jakarta`). Tanggal di masa depan = **terjadwal** (ditandai di dashboard, belum tampil ke anggota). Hanya satu yang boleh jadi **unggulan** (kartu besar "Kabar pilihan"): menandai satu pengumuman otomatis menurunkan yang lama, dalam satu transaksi (`PengumumanService`). Tujuan tombol hanya boleh `ic.checkin`, `ic.hadiah`, atau `ic.komunitas`; bila route di database sudah tidak ada, tombol jatuh ke Komunitas, bukan error. Daftar anggota dibatasi 20 pengumuman terbaru (di luar unggulan).
- **Kenangan:** caption maks 240 karakter (wajib), tempat maks 65 (opsional), kategori Festival / Acara klub / Momen pribadi, foto opsional maks 4 MB. Langsung tayang; admin bisa menyembunyikan dan menayangkan lagi (tidak menghapus apa pun). Tanpa foto, kartu memakai sampul berwarna dan ikon sesuai kategori. Hanya pemilik yang bisa menghapus kenangannya (`MemoryPolicy`); foto dan tanda suka ikut terhapus. Galeri dipaginasi 12 per halaman.
- **Suka:** toggle per anggota lewat `POST /inner-circle/kenangan/{id}/suka`, jawabannya JSON `{disukai, jumlah}` dan angka di layar mengikuti jawaban server. Kunci utama `(member_id, memory_id)` mencegah suka ganda walau dua klik datang bersamaan. Kenangan yang disembunyikan tidak bisa disukai (404).
- **Foto:** diolah `ImageProcessor`, bukan hanya divalidasi. Format ditentukan dari **isi** berkas (nama atau tipe kiriman browser diabaikan), foto dikecilkan (kenangan 1600 px, profil 512 px sisi terpanjang), orientasi EXIF HP diluruskan, lalu disimpan ulang sebagai WebP dengan nama acak. Karena di-encode ulang, metadata EXIF termasuk lokasi GPS ikut terbuang. Foto di atas 40 megapiksel, atau yang tidak muat di `memory_limit` server, ditolak dengan pesan jelas, bukan error 500. Foto lama avatar baru dihapus setelah yang baru tersimpan.
- **Foto profil:** JPG/PNG/WebP maks 5 MB. Tombol Simpan aktif setelah memilih foto; Hapus mengembalikan avatar bawaan (emoji).
- **Batas laju (per anggota, bukan per IP):** unggah kenangan 4 per menit dan 20 per hari, suka 60 per menit, foto profil 6 per menit. Semuanya di `config/inner_circle.php`.
- Teks dari anggota dan admin selalu di-escape (`{{ }}`); tidak ada HTML bebas.

Penyesuaian dari rencana awal:

- **Nama di galeri disingkat** ("Ancha Mahendra" jadi "Ancha M."), seperti contoh di prototype, supaya nama lengkap anggota tidak terlihat sesama anggota. Admin melihat nama lengkap dan kode anggota di tab Kenangan.
- **Laporan kenangan dibuang.** Tab Kenangan versi data contoh punya lencana "n laporan", tetapi rencana awal (bagian 6) tidak punya tabel atau tombol lapor untuk anggota. Moderasi tetap lewat tombol Sembunyikan.
- **Edit pengumuman lewat `?ubah=ID`**, bukan mengisi form dengan JavaScript. Tautannya bisa dibagikan, jalan tanpa JS, dan gagal validasi kembali ke form mode ubah yang sama. Pratinjau langsung tetap memakai JavaScript.
- **Controller dipecah:** `kenanganController` dan `fotoProfilController` untuk aksi tulis; halaman Komunitas dan Profil tetap di `memberController` supaya nama route dan view tidak berubah.
- **Kolom `ic_broadcasts.aktif`** ada di skema tetapi belum ada tombolnya di CMS (pengumuman dihapus, bukan dimatikan), sama seperti `ic_rewards.aktif`.
- **Pengumuman unggulan yang terjadwal:** menyimpan pengumuman unggulan yang tanggal terbitnya di masa depan langsung menurunkan unggulan lama, jadi sampai tanggal itu tiba Komunitas tidak punya kartu "Kabar pilihan". Hindari menjadwalkan unggulan terlalu jauh dari yang sedang tayang.
- Paginasi galeri anggota memakai tautan "Lebih baru" dan "Lebih lama" buatan sendiri, karena tampilan paginasi bawaan Laravel memakai kelas Tailwind yang tidak dimuat di area anggota.
- Foto yang gagal divalidasi tidak bisa diisi ulang otomatis di form unggah (batasan browser): modal terbuka lagi dengan cerita, tempat, dan kategori terisi, foto dipilih ulang.

Cek hasil:

- Unggah kenangan dengan foto HP (termasuk yang miring atau memakai EXIF) tampil tegak; suka, batal suka, dan hapus berfungsi; angka suka sama di dua akun; kenangan yang disembunyikan admin hilang dari galeri.
- Pengumuman terbit sesuai tanggal; menandai satu sebagai unggulan menurunkan yang lain; tombol pengumuman membuka halaman yang benar.
- Ganti foto profil dua kali: berkas lama hilang dari `storage/app/public/inner-circle/avatar`; Hapus mengembalikan emoji.
- Tes otomatis: `php artisan test --filter=InnerCircleKomunitasTest` (32 tes, memakai stub `ImageProcessor` supaya tidak butuh GD) dan `php artisan test --filter=ImageProcessorTest` (15 tes, membutuhkan ekstensi GD dan exif; dilewati otomatis bila tidak ada, jadi jalankan sekali di mesin lokal yang punya GD). Kunci baris tidak relevan di fase ini, tetapi tes klik ganda suka sebaiknya dicoba juga di MySQL.

</details>

### Fase 7: Integrasi situs publik, SEO, QA, rilis (selesai)

File yang dibuat atau diubah:

1. `resources/views/components/inner-circle-teaser.blade.php` (baru): section "WS Inner Circle" bertema situs publik (latar hitam, tombol pill, Bootstrap Icons, animasi `data-motion` yang sudah ada). Tamu melihat "Gabung sekarang" (`ic.daftar`) dan "Sudah punya akun? Masuk" (`ic.masuk`); anggota yang sedang masuk melihat "Buka Inner Circle" (`ic.beranda`). Tombol setinggi minimal 44 px, cincin fokus putih.
2. `resources/views/pages/home.blade.php`: teaser disisipkan setelah pita genre dan sebelum Tour, dibungkus `@if (config('inner_circle.aktif'))`.
3. `resources/views/components/navbar.blade.php`: link "Inner Circle" (ke `ic.beranda`; tamu otomatis dialihkan ke halaman masuk) di navigasi desktop dan drawer mobile, juga hanya bila flag menyala. Navbar dipakai juga di `/profile`, jadi link ikut tampil di sana.
4. `app/Http/Controllers/SeoController.php`: `robots.txt` menambah `Disallow: /inner-circle`. Sitemap tidak diubah (tidak ada halaman Inner Circle yang publik); halaman area anggota sudah `noindex` lewat layoutnya.
5. `app/Support/InnerCircleDummy.php` dihapus. `database/data/ic_events_contoh.csv` **dipertahankan** karena masih dipakai `icEventContohSeeder` (jadwal contoh untuk lokal dan preview).
6. `tests/Feature/InnerCircleSitusPublikTest.php` (baru, 7 tes): flag mati tidak menampilkan apa pun di home dan profile; flag menyala menampilkan teaser dan link; CTA berubah untuk anggota yang sudah masuk; link navbar muncul dua kali (desktop dan drawer); robots menutup `/inner-circle` tapi tidak halaman publik; sitemap bersih; halaman masuk tetap `noindex`.
7. `README.md` (bagian ini, status, dan baris PHP 8.4).

Hasil pemeriksaan:

- Seluruh tes saat Fase 7 selesai: 84 lulus, 15 dilewati (`ImageProcessorTest`, karena PHP yang dipakai untuk memeriksa tidak punya ekstensi GD; jalankan di mesin lokal yang punya GD). `npm run build` berhasil dan semua kelas Tailwind teaser ikut ter-build (dua entry Vite Inner Circle ikut terbangun).
- Audit statis CSS area anggota (bukan pengganti uji perangkat): target sentuh tombol, label centang, dan dock 44 px ke atas; ikon panah 33 px hanya hiasan (`aria-hidden`) di dalam tautan yang lebih besar; `prefers-reduced-motion` mematikan transisi dan animasi di seluruh `.ic-app`; pasangan warna teks utama lolos 4.5:1 (teks redup di kartu 5.0 sampai 5.2, eyebrow 5.5, putih di navy 18.4). Satu pengecualian yang disengaja: kata beraksen ungu di judul besar (54 sampai 92 px) berkontras 3.3:1, memenuhi syarat teks besar (3:1).

Yang tidak bisa diperiksa tanpa perangkat nyata, jadi lakukan sendiri sebelum rilis:

- Kamera pindai QR di Safari iOS dan Chrome Android (kamera hanya jalan lewat **HTTPS**, jadi uji di preview atau produksi, bukan lewat IP lokal).
- Unggah foto langsung dari HP (miring, ukuran besar, dan foto HEIC dari iPhone) dan foto profil.
- Tampilan layar 375 px untuk semua layar anggota, dock bawah tidak menutupi tombol terakhir, dan teaser di home.
- Kunci klik ganda klaim dan suka di MySQL (tes otomatis memakai SQLite).

Rilis: ikuti checklist di bagian _Panduan rilis ke produksi_ (dekat awal README).

## 6. Skema database (dipakai mulai Fase 4)

| Tabel             | Kolom utama                                                                                                                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `members`         | `id_member`, `kode_member` (unik), `nama`, `email` (unik), `password`, `avatar` (nullable), `stempel_total` (cache)                                                                                               |
| `ic_events`       | `id_event`, `kode` (unik, mis. `WS26-1008`), `tanggal`, `kota`, `venue` (nullable), `tipe` (`festival` / `club` / `private`), `token` (cast `encrypted`), `token_dibuat_pada`, `jendela_mulai`, `jendela_selesai` |
| `ic_stempel`      | `id_stempel`, `member_id`, `jumlah`, `sumber` (`checkin` / `manual`), `event_id` (nullable), `judul`, `ikon`, `dicatat_oleh`, `created_at`. Unik `(member_id, event_id)`. Hanya ditambah, tidak diubah            |
| `ic_rewards`      | `id_reward`, `target_stempel` (unik), `judul`, `deskripsi`, `ikon`, `warna`, `opsi` (json nullable), `urutan`, `aktif`                                                                                            |
| `ic_claims`       | `id_claim`, `member_id`, `reward_id`, `opsi_dipilih`, `status`, `catatan_admin`, `diproses_oleh`. Unik `(member_id, reward_id)`                                                                                   |
| `ic_claim_logs`   | `id_log`, `claim_id`, `status`, `oleh`, `catatan`, `created_at`                                                                                                                                                   |
| `ic_broadcasts`   | `id_broadcast`, `judul`, `isi`, `tag`, `ikon`, `warna`, `cta_label`, `cta_tujuan`, `unggulan`, `terbit_pada`, `aktif`                                                                                             |
| `ic_memories`     | `id_memory`, `member_id`, `caption`, `tempat`, `kategori` (`festival` / `club` / `personal`), `foto` (nullable), `status` (`tayang` / `disembunyikan`)                                                            |
| `ic_memory_likes` | `member_id`, `memory_id`. Unik pada pasangan                                                                                                                                                                      |

Catatan:

- Check-in tidak punya tabel sendiri: check-in adalah baris `ic_stempel` dengan `sumber = checkin` dan `event_id` terisi. Satu tabel ini sekaligus melayani riwayat stempel, aturan "1 stempel per anggota per event", dan stempel manual dari admin ("Ikut kegiatan resmi" di prototype).
- `members.stempel_total` hanya cache yang diperbarui di transaksi yang sama dengan penambahan stempel.
- Test memakai SQLite in-memory (`phpunit.xml`): unique key teruji, tapi kunci baris (`lockForUpdate`) tidak. Uji klik ganda klaim juga secara manual di MySQL.
- `ic_broadcasts.unggulan`: aturan "hanya satu" dijaga `PengumumanService` dalam transaksi (MySQL tidak punya kunci unik parsial). `ic_memory_likes` memakai kunci utama gabungan `(member_id, memory_id)` dan tidak punya model.

## 7. Aturan bisnis yang dipertahankan dari prototype

1. Stempel **kumulatif**: tidak berkurang saat klaim, tidak hangus.
2. Tiap hadiah hanya bisa diklaim **1 kali** per anggota.
3. **1 stempel per anggota per event.**
4. Event **Private Event** dan event **tanpa venue** tidak bisa dipakai check-in (di data contoh: `WS26-1023` dan `WS26-1225` tanpa venue, plus 3 Private Event).
5. Tiap event punya token/QR sendiri; membuat ulang token membatalkan yang lama.
6. Tahap hadiah: **10, 25, 50, 100, 250** stempel; tahap 50 punya pilihan A/B.

## 8. Keputusan yang masih terbuka

Default di bawah dipakai sampai ada keputusan lain.

- **Moderasi kenangan:** langsung tayang, admin bisa menyembunyikan (prototype langsung terbit). Alternatif: disetujui admin dulu.
- **Verifikasi email dan "lupa kata sandi":** tidak ada di prototype dan butuh SMTP (`config/mail.php`). Default: ditunda. Pendaftaran lewat Google (Fase 8) memberi email terverifikasi tanpa SMTP; "lupa kata sandi" tetap butuh SMTP.
- **Avatar bawaan** sebelum anggota unggah foto: emoji 😎 seperti prototype, atau inisial nama.
- **Jendela check-in:** default D 12.00 sampai D+1 06.00 WIB. Sesuaikan bila jam show WSM berbeda.
- **Halaman `/inner-circle` untuk pengunjung belum login:** default dialihkan ke `/inner-circle/masuk`. Landing publik penuh (penjelasan program, daftar hadiah) belum ada di prototype; bisa jadi fase tambahan.
- **Ikon:** pakai set gambar berlisensi terbuka (bagian 3.2) atau ilustrasi buatan tim WSM.
- **Klaim yang ditolak:** final (anggota tidak bisa mengajukan ulang tahap itu). Bila WSM ingin bisa membuka kembali, perlu aksi "buka kembali" di antrean Klaim; belum ada.
- **Data event:** 50 baris di prototype adalah data contoh tentatif (33 Club, 14 Festival, 3 Private Event; 2 tanpa venue). Jadwal asli diisi tim WSM lewat CMS atau impor CSV.

## 9. Di luar cakupan

- Sinkron otomatis dengan Bandsintown (section Tour di home tetap widget).
- QR yang berputar otomatis dan verifikasi lokasi (GPS). Mitigasi awal: jendela waktu, token per event, limiter per anggota.
- Notifikasi (email, WhatsApp, Instagram) dan pesan antar anggota.
- Integrasi akun atau voucher dengan Mavnus. Hadiah merchandise dipenuhi manual lewat antrean klaim.
- Perubahan pada login admin, controller CMS yang sudah ada, dan tema situs publik (di luar perbaikan keamanan pada audit, lihat _Audit keamanan_).
- Login Google untuk **admin** CMS: Fase 8 hanya untuk anggota.
