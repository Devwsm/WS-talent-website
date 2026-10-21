# WS Talent Website

Website publik profil artis **Whisnu Santika** beserta dashboard CMS-nya.

- **Stack:** Laravel 13 · PHP 8.3 · Tailwind CSS 4 · Vite · Swiper · SweetAlert2 · MySQL (lokal dan produksi; `.env.example` masih bawaan SQLite, ganti `DB_CONNECTION=mysql` di `.env`)
- **Publik:** `/` (home) dan `/profile`
- **CMS:** `/dashboard` (Banner, Headers, Profile, Warna Web, Albums, News, Merchandise)
- **Sesi & antrean:** driver `database`

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run dev
```

---

# Rencana Penerapan: WS Inner Circle (versi 2)

> Status: **rencana, belum ada kode yang diubah.**
> Direvisi: 21 Oktober 2026.
> Acuan desain dan alur: prototype `WS_Inner_Circle.html` (satu file HTML, data di `localStorage`).
> Cakupan: Inner Circle hidup di **website ini** dan **dipakai juga oleh Mavnus** (`mavnus.com`).

## 0. Hasil review rencana sebelumnya

Rencana lama sudah kuat di sisi fitur dan keamanan. Yang diperbaiki di versi 2:

| #   | Temuan                                                                                                                                                                                                         | Perubahan di versi 2                                                                                                                 |
| --- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | **Mavnus belum tercakup sama sekali.** Padahal dua hadiah prototype (diskon merchandise, akses awal merchandise) hanya bermakna kalau Mavnus bisa tahu siapa anggotanya.                                       | Bagian 4.7 (arsitektur lintas situs) dan **Fase IC-7** (API + login lewat Inner Circle + voucher).                                   |
| 2   | Rujukan prototype salah nama (`..._Daftar_Anggota_Baru.html`); file yang ada sekarang `WS_Inner_Circle.html`.                                                                                                  | Rujukan diperbarui.                                                                                                                  |
| 3   | README menulis dev memakai SQLite, padahal `.env` lokal memakai MySQL. Keputusan "database produksi" jadi sudah terjawab: **MySQL (InnoDB)**, sehingga `lockForUpdate` dan unique key bekerja benar.           | Keputusan lama no. 8 dihapus; stack di atas dikoreksi.                                                                               |
| 4   | Rencana lama menyuruh mengekstrak `convertToWebP()` ke helper bersama. Komentar di kode justru menyebut duplikasi itu **disengaja** (tiap controller berdiri sendiri).                                         | Controller lama **tidak disentuh**. Dibuat `ImageProcessor` baru khusus Inner Circle.                                                |
| 5   | `convertToWebP()` lama hanya menerima JPEG/PNG (WebP melempar error), tidak memperbaiki orientasi foto ponsel, dan tidak membatasi dimensi. Foto kenangan dari HP bisa tampil miring atau menghabiskan memori. | `ImageProcessor` baru: terima WebP, perbaiki orientasi EXIF sebelum metadata dibuang, batasi dimensi, tolak gambar raksasa.          |
| 6   | `throttle` berbasis IP tidak cocok untuk check-in: ratusan penonton di satu venue berbagi jaringan/IP yang sama dan bisa saling memblokir.                                                                     | Limiter check-in per **anggota**, bukan per IP (bagian 7).                                                                           |
| 7   | 50 event di prototype adalah **data contoh/tentatif**. Per 21 Okt 2026 sebanyak 9 sudah lewat, dan `isPastShow` di prototype hardcode `2026-10-13`.                                                            | Seeder event hanya untuk lokal/preview (flag eksplisit). Produksi diisi lewat impor CSV di CMS. Status lewat/mendatang dari `now()`. |
| 8   | Jendela check-in di rencana lama tidak realistis untuk klub: show tanggal D selesai lewat tengah malam.                                                                                                        | Default jendela: **D pukul 12.00 sampai D+1 pukul 06.00 WIB**, bisa diubah per event.                                                |
| 9   | Hosting tanpa terminal (FTP/cPanel) tidak dibahas: `migrate`, seeder, cron `schedule:run`, antrean, `vendor/` baru, dan `public/build/` tidak ada jalannya.                                                    | Bagian 8 dan langkah rilis di Fase IC-6.                                                                                             |
| 10  | Fase lama berupa daftar isi, belum berupa langkah yang bisa dijalankan berurutan.                                                                                                                              | Tiap fase kini punya: file (berurutan), perintah (lengkap dengan tempat menjalankannya), cek hasil, dan pesan commit.                |
| 11  | Logout anggota di rencana lama memanggil `invalidate()` yang ikut menghapus sesi admin di browser yang sama.                                                                                                   | Logout anggota hanya keluar dari guard `member`.                                                                                     |
| 12  | Tabel skema di README lama rusak formatnya (tanda `_` dan `*` termakan formatter).                                                                                                                             | Dirapikan.                                                                                                                           |
| 13  | Token QR statis bisa difoto lalu dibagikan ke orang yang tidak hadir.                                                                                                                                          | Mitigasi: jendela waktu + limiter + opsi QR dinamis (bagian 11, opsional).                                                           |

## 1. Tujuan

Menanam komunitas **WS Inner Circle** di website ini: penggemar mendaftar jadi anggota, mengumpulkan **stempel** dengan hadir di acara (check-in QR), membuka dan mengklaim **hadiah** per tahapan, membaca **pengumuman**, dan berbagi **kenangan** (galeri foto). Tim WSM mengelola semuanya dari dashboard CMS yang sudah ada.

Lalu **Mavnus** memakai identitas dan keuntungan Inner Circle yang sama: anggota bisa masuk ke Mavnus lewat akun Inner Circle, memakai voucher diskon, dan mendapat akses awal ke merchandise tertentu. Satu sumber data anggota, tidak ada akun ganda, tidak ada stempel yang harus disinkronkan manual.

## 2. Isi prototype dan aturan bisnis

| Layar          | Fungsi                                                                                                                                                | Data di prototype                |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------- |
| Masuk / Daftar | Auth anggota dan admin dalam satu gerbang                                                                                                             | `users[]` di localStorage        |
| Beranda        | Kartu anggota digital, progres stempel, aktivitas terbaru                                                                                             | `state.stamps`, `state.activity` |
| Hadiah         | 5 tahapan: **10, 25, 50, 100, 250** stempel; klaim 1x per tahapan; tahapan 50 punya pilihan A/B                                                       | `definitions[]`, `state.claims`  |
| Check-in       | Jadwal Q4 2026 (**50 show**: 33 Club, 14 Festival, 3 Private Event) + filter bulan/tipe/cari; verifikasi lewat QR kamera, unggah QR, atau kode manual | `tourEvents[]`, token per event  |
| Komunitas      | Tab **Pengumuman WS** dan **Galeri Kenangan** (unggah foto, kategori, like, hapus milik sendiri)                                                      | `broadcasts[]`, `memories[]`     |
| Profil         | Foto profil, total stempel, riwayat stempel, pengajuan hadiah                                                                                         | `state`, avatar di localStorage  |
| Admin          | Pembuat QR per event (buat/unduh/cetak), antrean klaim, direktori anggota                                                                             | `tokens[]`, `state.claims`       |

Aturan bisnis yang tersirat di prototype dan **dipertahankan**:

1. Stempel **kumulatif**: tidak berkurang saat klaim, tidak hangus.
2. Tiap hadiah hanya bisa diklaim **1 kali** per anggota.
3. **1 stempel per anggota per event**.
4. Event tipe **Private Event** dan event **tanpa venue** tidak bisa dipakai check-in (di data contoh: `WS26-1023` dan `WS26-1225` tanpa venue; 3 Private Event).
5. Tiap event punya token/QR sendiri; admin bisa membuat ulang, token lama otomatis tidak berlaku.

Yang **tidak dibawa** dari prototype: kredensial demo (`member123`, `admin123`), panel "Pengaturan acara lama" yang sudah disembunyikan, angka admin hardcode (`128`, `7`), tiga lapis CSS arah desain lama (hanya arah lavender terakhir yang dipakai), dan campuran label Inggris/Indonesia (diseragamkan Indonesia).

## 3. Pemetaan prototype ke Laravel

| Di prototype (browser)                                                                                          | Di Laravel                                                                                               |
| --------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| `users[]` + login di localStorage                                                                               | Model `member`, guard `member`, tabel `members`, sandi di-hash, minimal 8 karakter                       |
| ID anggota `max(id)+1`                                                                                          | Auto-increment `id_member`; `kode_member` = `IC-` + 5 digit (`IC-00001`), dibuat di transaksi yang sama  |
| `state.stamps` + `state.activity`                                                                               | Tabel `ic_stempel_ledger` (sumber kebenaran) + cache `members.stempel_total`                             |
| `definitions[]` (5 hadiah hardcode)                                                                             | Tabel `ic_rewards`, diseed dari prototype, bisa diedit di CMS                                            |
| `state.claims` + `prompt()` pilihan A/B                                                                         | Tabel `ic_claims` + `opsi_dipilih` di form klaim                                                         |
| Status klaim `Requested/Review/Approved/Fulfilled` (kode member: `Diajukan`, tidak sinkron dengan tombol admin) | Satu set baku: `diajukan` → `ditinjau` → `disetujui` → `selesai` / `ditolak`, dicatat di `ic_claim_logs` |
| `tourEvents[]` (50 baris)                                                                                       | Tabel `ic_events`; CRUD + impor CSV di CMS                                                               |
| Token QR dibuat dan dicek di browser                                                                            | Token dibuat server (cast `encrypted`), dicek di `CheckinService`; QR dirender server (SVG)              |
| `selectedCheckins` (Set)                                                                                        | Tabel `ic_checkins`, unique `(member_id, event_id)`                                                      |
| `broadcasts[]`                                                                                                  | Tabel `ic_broadcasts`, CRUD di CMS                                                                       |
| `memories[]` + foto `dataURL`                                                                                   | Tabel `ic_memories` + upload ke disk `public` (WebP) + moderasi                                          |
| Like di array JS                                                                                                | Tabel `ic_memory_likes`, endpoint JSON                                                                   |
| Avatar di localStorage                                                                                          | Kolom `members.avatar` + upload server                                                                   |
| `isPastShow` hardcode `2026-10-13`                                                                              | `now()` (zona waktu aplikasi sudah `Asia/Jakarta`)                                                       |
| Satu gerbang login member/admin                                                                                 | Dipisah: member `/inner-circle/masuk`; admin tetap `/login` yang sudah ada                               |
| `BarcodeDetector` untuk baca QR                                                                                 | Pustaka `qr-scanner` (di-bundle Vite); `BarcodeDetector` tidak ada di Safari iOS dan Firefox             |

## 4. Keputusan arsitektur

### 4.1 Letak di aplikasi

- **Landing publik** `/inner-circle`: penjelasan program, daftar hadiah, tombol daftar. Masuk sitemap.
- **Area anggota** `/inner-circle/app/*`: wajib login anggota, `noindex`, masuk `Disallow` di `SeoController@robots`.
- **Teaser di home:** 1 komponen `inner-circle-teaser` di `pages/home.blade.php` + 1 link "Inner Circle" di `components/navbar.blade.php` (desktop dan menu mobile).
- Prototype adalah SPA satu halaman. Di Laravel dibuat **Blade per layar**: 1 layar = 1 route = 1 `<h1>`, sejalan dengan temuan audit SEO sebelumnya.

### 4.2 Autentikasi: guard terpisah untuk anggota

Auth admin sengaja sederhana (tabel `accounts`, `session('login')`, middleware `cekLogin`) dan **tidak disentuh**.

- Model `member` + guard **`member`** + provider **`members`** di `config/auth.php`.
- Jangan memakai `users` / `App\Models\User`: `User.php` tidak ada di `app/Models`, sementara `config/auth.php` masih meng-import-nya.
- Route anggota memakai `auth:member`; route admin tetap `cekLogin`.
- Logout anggota: `Auth::guard('member')->logout()` + `regenerateToken()`. **Jangan** `session()->invalidate()` supaya sesi admin di browser yang sama tidak ikut terhapus. Sebaliknya, logout admin yang sudah ada memang `invalidate()`, jadi ikut mengeluarkan anggota di browser itu. Itu wajar dan cukup dicatat.
- Nama class mengikuti konvensi repo (huruf kecil: `member`, `ic_event`). Karena itu **Policy didaftarkan manual** lewat `Gate::policy(...)` di `AppServiceProvider`, bukan mengandalkan auto-discovery.

### 4.3 Tampilan

Prototype punya identitas visual sendiri (lavender, editorial pop), berbeda dari tema gelap + `Warna Web`. Area Inner Circle **dipisah**:

- Layout `template/innerCircleLayout.blade.php`, entry Vite baru `resources/css/inner-circle.css` dan `resources/js/inner-circle.js`.
- Semua class diberi prefix `ic-` supaya CSS tidak bocor ke halaman publik.
- CSS dibawa sebagai CSS biasa (bukan `@apply`), jadi aman dari batasan Tailwind v4 yang tidak mengizinkan `@apply` merantai class custom.
- Font prototype (DM Sans, Space Grotesk, Barlow Condensed) hanya dimuat di layout Inner Circle.
- Teaser di home dan landing publik tetap mengikuti tema situs (`ThemeColorComposer`).
- Tanpa framework JS baru: Blade + JS vanilla + SweetAlert2.

### 4.4 Sumber data event

Section Tour di home hanya widget **Bandsintown**; datanya tidak ada di database kita, sedangkan check-in butuh data event milik sendiri:

- Event disimpan di `ic_events`, dikelola dari CMS (form + **impor CSV**).
- Data 50 baris prototype disimpan sebagai berkas CSV contoh dan hanya di-seed bila `IC_SEED_CONTOH=true` (lokal/preview). **Jangan pernah aktif di produksi.**
- Sinkron otomatis dengan Bandsintown **tidak** masuk rencana (butuh akses API dan keputusan tim).

### 4.5 Stempel = buku besar (ledger)

Total stempel tidak di-`++` begitu saja. Sumber kebenaran adalah `ic_stempel_ledger` yang hanya bisa ditambah (append-only); `members.stempel_total` hanya cache yang diperbarui di transaksi yang sama. Memudahkan audit, koreksi admin, dan menjawab "kenapa stempel saya segini".

### 4.6 Struktur kode

- **Controller tipis, logika di service:** `CheckinService`, `ClaimService`, `VoucherService` (IC-7), `QrService`.
- **Form Request** per aksi tulis, pesan error Bahasa Indonesia.
- **Policy** `MemoryPolicy` dan `ClaimPolicy` (pemilik saja).
- **Scope** `ic_event::bisaCheckin()` supaya aturan "bisa check-in" ditulis sekali.
- **Scheduled command** menandai event `selesai` setelah jendela check-in tutup.
- **Blade component** untuk kartu anggota, hadiah, kenangan, event.
- `app/Support/ImageProcessor.php` baru, **khusus Inner Circle** (lihat Fase IC-0).

### 4.7 Integrasi dengan Mavnus (lintas situs)

Mavnus adalah aplikasi Laravel terpisah (domain, database, dan hosting sendiri). Cookie login **tidak bisa dibagi** antara `whisnusantika.com` dan `mavnus.com` (beda domain induk). Tiga pilihan:

| Opsi            | Cara                                                                                                                                          | Catatan                                                                                                                     |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| **A (dipilih)** | Data anggota hidup **hanya** di situs ini. Mavnus memanggil API server-ke-server bertanda tangan + login lewat redirect berkode sekali pakai. | Satu sumber kebenaran. Tanpa paket tambahan (cocok dengan hosting cPanel). Mavnus tidak pernah memegang sandi anggota.      |
| B               | Pisahkan Inner Circle jadi aplikasi ketiga di subdomain (mis. `circle.whisnusantika.com`).                                                    | Lebih rapi jangka panjang, tetapi menambah satu deployment lagi. Bisa jadi tujuan akhir; desain opsi A mudah dipindah ke B. |
| C               | Salin tabel anggota/stempel ke Mavnus.                                                                                                        | **Ditolak.** Stempel dan klaim pasti tidak sinkron.                                                                         |

Cara kerja opsi A:

```
Login di Mavnus ("Masuk dengan Inner Circle")
  Mavnus  → redirect ke  whisnusantika.com/inner-circle/sso/mavnus?state=XYZ&kembali=<url callback Mavnus>
  Situs ini: kalau belum login, minta login anggota dulu; cek "kembali" ada di daftar putih (.env)
  Situs ini → redirect ke  <callback Mavnus>?code=<kode acak, berlaku 60 detik, sekali pakai>&state=XYZ
  Server Mavnus → POST /api/ic/v1/sso/tukar {code}   (bertanda tangan HMAC)
  Situs ini membalas JSON: kode_member, nama, stempel_total, avatar
  Mavnus menyimpan ringkasan itu di SESI-nya (bukan sandi, bukan tabel anggota)

Keuntungan di Mavnus
  Diskon  : hadiah tipe voucher_merch → situs ini menerbitkan voucher → Mavnus memvalidasi dan memakainya di checkout
  Akses awal: Mavnus menanyakan stempel_total anggota; produk "akses awal" hanya bisa dibeli bila stempel >= batas
```

Prinsip yang dikunci:

- Checkout Mavnus **tidak boleh bergantung** pada situs ini. Kalau API Inner Circle tidak merespons, Mavnus jalan terus tanpa voucher dan menampilkan pesan, tidak memblokir pembelian.
- Perhitungan diskon tetap **di server Mavnus** (sama seperti perhitungan ongkir yang sudah di server).
- Pesanan Mavnus yang kedaluwarsa/batal otomatis **melepas** voucher yang terpakai (endpoint `lepas`).
- Rencana Mavnus yang masih tertunda ("sistem akun customer" dan "poin loyalitas") sebaiknya **digabung** dengan ini agar tidak ada dua sistem. Cek dulu di repo Mavnus kondisi akun customer yang ada sekarang sebelum IC-7.
- Anggota boleh tetap belanja sebagai tamu; Inner Circle opsional.

## 5. Skema database

Konvensi repo: nama model huruf kecil snake*case, primary key `id*<nama>`, kolom Bahasa Indonesia. Semua tabel baru berawalan `ic\_`kecuali`members`. Karena primary key tidak bernama `id`, **tiap relasi Eloquent menyebut kunci eksplisit**.

| Tabel                 | Kolom penting                                                                                                                                                                                                                                                                                      | Catatan                                                                                               |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `members`             | `id_member`, `kode_member` (unik), `nama`, `email` (unik), `password`, `avatar`, `stempel_total`, `status` (`aktif`/`diblokir`), `wajib_ganti_sandi`, `setuju_at`, `email_verified_at`, `remember_token`                                                                                           | Authenticatable untuk guard `member`                                                                  |
| `ic_events`           | `id_event`, `kode` (unik, `WS26-1001`), `tanggal`, `kota`, `venue`, `tipe` (`festival`/`club`/`private`), `checkin_buka_at`, `checkin_tutup_at`, `latitude`, `longitude`, `radius_m`, `token` (cast `encrypted`), `token_versi`, `status` (`draft`/`aktif`/`selesai`), `nilai_stempel` (default 1) | Bisa check-in bila bukan `private`, venue terisi, status `aktif`, dan sekarang di dalam jendela waktu |
| `ic_checkins`         | `id_checkin`, `member_id`, `event_id`, `metode` (`qr`/`kode`), `token_versi`, `lat`, `lng` (nullable), `created_at`                                                                                                                                                                                | **Unik `(member_id, event_id)`**: menjamin 1 stempel per event, termasuk saat request ganda           |
| `ic_stempel_ledger`   | `id_ledger`, `member_id`, `delta`, `sumber` (`checkin`/`kegiatan`/`koreksi_admin`), `ref_type`, `ref_id`, `keterangan`, `dibuat_oleh`, `created_at`                                                                                                                                                | Append-only. Mewadahi "Ikut kegiatan resmi" di prototype dan koreksi admin                            |
| `ic_rewards`          | `id_reward`, `ambang`, `judul`, `deskripsi`, `ikon`, `tone`, `opsi` (JSON), `tipe` (`manual`/`voucher_merch`/`akses_merch`), `butuh_alamat`, `urutan`, `aktif`                                                                                                                                     | Diseed dari 5 tahapan prototype, bisa diedit di CMS                                                   |
| `ic_claims`           | `id_claim`, `member_id`, `reward_id`, `opsi_dipilih`, `data_pengiriman` (cast `encrypted`, nullable), `status`, `catatan_admin`, `judul_snapshot`, `ambang_snapshot`, `diajukan_at`, `diproses_at`                                                                                                 | **Unik `(member_id, reward_id)`**. Snapshot menjaga riwayat tetap utuh walau hadiah diedit            |
| `ic_claim_logs`       | `id_log`, `claim_id`, `dari_status`, `ke_status`, `oleh`, `created_at`                                                                                                                                                                                                                             | Jejak audit perpindahan status                                                                        |
| `ic_broadcasts`       | `id_broadcast`, `judul`, `isi`, `tag`, `ikon`, `tone`, `cta_label`, `cta_tujuan`, `status`, `terbit_at`                                                                                                                                                                                            | Terpisah dari tabel `news` karena audiensnya anggota                                                  |
| `ic_memories`         | `id_memory`, `member_id`, `event_id` (nullable), `label_acara`, `kategori` (`festival`/`club`/`pribadi`), `caption`, `foto` (nullable), `status` (`menunggu`/`disetujui`/`ditolak`), `jumlah_like`, `created_at`                                                                                   | Hanya `disetujui` tampil ke anggota lain                                                              |
| `ic_memory_likes`     | `member_id`, `memory_id`                                                                                                                                                                                                                                                                           | **Unik `(member_id, memory_id)`**                                                                     |
| `ic_vouchers` (IC-7)  | `id_voucher`, `member_id`, `claim_id` (nullable), `kode` (unik), `jenis` (`persen`/`nominal`), `nilai`, `min_belanja` (nullable), `berlaku_sampai`, `status` (`aktif`/`dipakai`/`kedaluwarsa`/`dibatalkan`), `dipakai_at`, `order_ref`                                                             | `order_ref` = nomor pesanan Mavnus; dipakai untuk membuat pemakaian **idempoten**                     |
| `ic_sso_codes` (IC-7) | `id_sso`, `member_id`, `kode_hash` (sha256), `klien` (`mavnus`), `kedaluwarsa_at`, `dipakai_at`                                                                                                                                                                                                    | Kode mentah tidak pernah disimpan, hanya hash-nya                                                     |

**Status klaim (baku):** `diajukan` → `ditinjau` → `disetujui` → `selesai`, plus `ditolak`. Hanya maju satu arah; tiap perpindahan tercatat di `ic_claim_logs`.

## 6. Route

### Publik dan anggota

| Method   | URL                                        | Nama                | Akses                           |
| -------- | ------------------------------------------ | ------------------- | ------------------------------- |
| GET      | `/inner-circle`                            | `ic.landing`        | publik                          |
| GET/POST | `/inner-circle/daftar`                     | `ic.register`       | tamu, limiter `ic-daftar`       |
| GET/POST | `/inner-circle/masuk`                      | `ic.login`          | tamu, limiter `ic-login`        |
| POST     | `/inner-circle/keluar`                     | `ic.logout`         | anggota (POST + CSRF)           |
| GET      | `/inner-circle/app`                        | `ic.home`           | anggota                         |
| GET      | `/inner-circle/app/hadiah`                 | `ic.rewards`        | anggota                         |
| POST     | `/inner-circle/app/hadiah/{reward}/klaim`  | `ic.claim`          | anggota, `throttle:10,1`        |
| GET      | `/inner-circle/app/check-in`               | `ic.checkin`        | anggota                         |
| POST     | `/inner-circle/app/check-in`               | `ic.checkin.verify` | anggota, limiter `ic-checkin`   |
| GET      | `/inner-circle/app/komunitas`              | `ic.community`      | anggota                         |
| POST     | `/inner-circle/app/kenangan`               | `ic.memory.store`   | anggota, `throttle:5,1`         |
| POST     | `/inner-circle/app/kenangan/{memory}/like` | `ic.memory.like`    | anggota (JSON), `throttle:30,1` |
| DELETE   | `/inner-circle/app/kenangan/{memory}`      | `ic.memory.destroy` | pemilik saja                    |
| GET      | `/inner-circle/app/profil`                 | `ic.profile`        | anggota                         |
| POST     | `/inner-circle/app/profil/foto`            | `ic.profile.photo`  | anggota                         |
| POST     | `/inner-circle/app/profil/hapus-akun`      | `ic.profile.delete` | anggota                         |
| GET      | `/inner-circle/sso/mavnus`                 | `ic.sso.mavnus`     | anggota (IC-7)                  |

### Admin (grup `/dashboard` yang sudah ada, middleware `cekLogin`, nama route `ic.admin.*`)

| URL                                     | Fungsi                                                             |
| --------------------------------------- | ------------------------------------------------------------------ |
| `/dashboard/inner-circle`               | Ringkasan: anggota, check-in, klaim menunggu (angka nyata dari DB) |
| `/dashboard/inner-circle/event`         | CRUD event, impor CSV, jendela check-in dan lokasi                 |
| `/dashboard/inner-circle/event/{id}/qr` | Tampilkan, buat ulang, unduh, cetak QR                             |
| `/dashboard/inner-circle/hadiah`        | CRUD tahapan hadiah (form kiri / preview kanan)                    |
| `/dashboard/inner-circle/klaim`         | Antrean klaim, ubah status, catatan                                |
| `/dashboard/inner-circle/broadcast`     | CRUD pengumuman                                                    |
| `/dashboard/inner-circle/kenangan`      | Moderasi galeri                                                    |
| `/dashboard/inner-circle/anggota`       | Daftar anggota, blokir, koreksi stempel, sandi sementara           |
| `/dashboard/inner-circle/voucher`       | Daftar voucher (IC-7)                                              |

Menu dashboard: cukup **1 entri "Inner Circle"** (`bi-people-fill`) di `$navLinks`, dan `ic.admin.*` ditambahkan ke `$isKontenActive` di `components/dashboard/navbar.blade.php`. Sub-halaman dibuka lewat tab di halaman hub.

### API untuk Mavnus (IC-7, tanpa sesi, ditandatangani)

| Method | URL                                 | Fungsi                                       |
| ------ | ----------------------------------- | -------------------------------------------- |
| POST   | `/api/ic/v1/sso/tukar`              | Tukar `code` jadi ringkasan anggota          |
| GET    | `/api/ic/v1/anggota/{kode}`         | Ringkasan anggota (nama, stempel, avatar)    |
| GET    | `/api/ic/v1/anggota/{kode}/voucher` | Voucher aktif milik anggota                  |
| POST   | `/api/ic/v1/voucher/{kode}/pakai`   | Pakai voucher untuk `order_ref` (idempoten)  |
| POST   | `/api/ic/v1/voucher/{kode}/lepas`   | Lepas voucher bila pesanan batal/kedaluwarsa |

Tanda tangan: header `X-IC-Timestamp` dan `X-IC-Signature` = `HMAC-SHA256(secret, timestamp + "\n" + METHOD + "\n" + path + "\n" + body)`. Server menolak bila selisih waktu > 300 detik, membandingkan dengan `hash_equals`, dan memakai limiter per IP + per klien.

## 7. Alur inti

### 7.1 Check-in (paling sensitif)

```
Admin: event → "Buat QR"  → server membuat token acak, simpan terenkripsi, token_versi++
QR payload: WSIC|{kode_event}|{token}        (format sama dengan prototype)

Anggota: scan QR / unggah gambar QR / ketik kode  →  POST /check-in {kode_event, token}
Server (1 transaksi DB):
  1. kunci baris anggota (lockForUpdate); status anggota aktif
  2. event ada, status aktif, bukan private, venue terisi
  3. sekarang ada di dalam jendela checkin_buka_at .. checkin_tutup_at
  4. hash_equals(token_tersimpan, token_kiriman)    → tolak bila beda / sudah diganti
  5. (opsional) jarak ke venue <= radius_m          → hanya bila event mensyaratkan lokasi
  6. insert ic_checkins      → unique(member,event) menolak duplikat (SQLSTATE 23000)
  7. insert ic_stempel_ledger(+nilai_stempel) + increment members.stempel_total
  8. balas JSON: stempel baru, total, sisa ke hadiah berikutnya
```

Catatan teknis:

- Limiter `ic-checkin` per **anggota** (misal 10 percobaan/menit) ditambah batas percobaan token salah per anggota. Jangan per IP (lihat temuan 6).
- Pindai QR memakai `qr-scanner` (kamera + gambar) dan **selalu** ada input kode manual.
- Kamera butuh **HTTPS** (atau `localhost`).
- QR dirender server (SVG) lewat paket `bacon/bacon-qr-code`. Cek hasil `composer require` di mesin lokal, jangan asumsi versi. Unduh PNG dibuat di browser (SVG → canvas → PNG); cetak lewat halaman cetak khusus.
- GPS **opsional, default mati**; koordinat anggota tidak disimpan lebih lama dari perlu.

### 7.2 Klaim hadiah

1. Hadiah terbuka bila `stempel_total >= ambang`.
2. Anggota mengisi form klaim (pilihan A/B untuk tahap 50; alamat hanya bila `butuh_alamat`) → insert `ic_claims`; unique key menolak klaim ganda.
3. Admin memindahkan status satu per satu; tiap langkah masuk `ic_claim_logs`.
4. Pada status `selesai`, `data_pengiriman` dikosongkan.
5. (IC-7) Hadiah tipe `voucher_merch` menerbitkan voucher saat status `disetujui`.

### 7.3 Galeri kenangan

1. Anggota unggah foto (opsional) + caption + kategori → status `menunggu`.
2. Admin moderasi → `disetujui` / `ditolak`.
3. Foto diproses `ImageProcessor`: perbaiki orientasi, **re-encode WebP** (membuang EXIF termasuk koordinat GPS), batasi sisi terpanjang.
4. Caption selalu lewat `{{ }}`; **jangan** `{!! !!}` untuk konten anggota.

## 8. Kendala hosting tanpa terminal

Produksi memakai cPanel (Rumahweb) tanpa SSH/terminal. Konsekuensinya untuk Inner Circle:

| Kebutuhan                    | Cara                                                                                                                                                                                                                                                      |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Membuat tabel baru di server | `php artisan migrate` tidak bisa dijalankan. Jalankan migrate di **lokal**, ekspor hanya tabel baru dari phpMyAdmin lokal, impor ke phpMyAdmin server (cPanel → Databases → phpMyAdmin). Tambahkan juga baris nama migrasi barunya ke tabel `migrations`. |
| Seeder                       | Tidak bisa dijalankan di server. Data awal `ic_rewards` ikut terekspor bersama tabelnya. Event produksi diisi lewat **impor CSV di CMS**.                                                                                                                 |
| Paket PHP baru (`vendor/`)   | Setelah `composer require` di lokal, unggah folder paket baru di `vendor/` **dan** folder `vendor/composer/` ke folder `laravel/` di server.                                                                                                              |
| Aset Vite baru               | Jalankan `npm run build` di lokal, unggah seluruh `public/build/` ke `public_html/build/` (termasuk `manifest.json`).                                                                                                                                     |
| Tugas terjadwal              | cPanel → Advanced → **Cron Jobs**: jalankan `schedule:run` tiap menit (contoh perintah ada di Fase IC-6).                                                                                                                                                 |
| Antrean (email)              | Dijalankan lewat scheduler (`queue:work --stop-when-empty`), bukan proses permanen.                                                                                                                                                                       |
| Ekstensi PHP                 | Pastikan aktif di cPanel → MultiPHP INI Editor / Select PHP Version: `gd` (dengan dukungan WebP), `exif`, `mbstring`, `fileinfo`.                                                                                                                         |
| Folder upload                | Pakai mekanisme yang sama dengan fitur yang sudah jalan (`Storage::disk('public')`, mis. `profile-hero`). Cek folder `ic/` terjangkau dari web setelah upload pertama.                                                                                    |

## 9. Keamanan dan privasi

- Seluruh keputusan stempel, klaim, dan voucher di **server**. Browser hanya menampilkan.
- Idempotensi lewat **unique constraint + transaksi DB** (`lockForUpdate` pada baris anggota), bukan sekadar cek di PHP.
- Limiter per anggota di endpoint tulis; verifikasi check-in paling ketat. Login: per kombinasi email + IP; pesan error generik.
- Upload: `mimes:jpg,jpeg,png,webp`, maksimum 4 MB (kenangan) / 5 MB (avatar), nama file acak (UUID), tolak dimensi ekstrem.
- Token event terenkripsi dan tidak pernah dikirim ke halaman anggota. Kode SSO hanya disimpan sebagai hash dan sekali pakai.
- Rahasia API Mavnus (`IC_MAVNUS_SECRET`) hanya di `.env` kedua server; dapat diputar tanpa ubah kode.
- Data pribadi minimal: nama, email, foto profil. Alamat hanya untuk hadiah fisik, terenkripsi, dikosongkan setelah selesai. Ke Mavnus hanya dikirim: kode anggota, nama, stempel, avatar.
- Anggota bisa **menghapus akunnya** (data dianonimkan, foto dihapus, ledger/klaim tetap tanpa identitas).
- Halaman **Syarat & Ketentuan** dan **Kebijakan Privasi** (menyebut Mavnus sebagai pengguna data keanggotaan), dengan persetujuan eksplisit saat daftar dan saat unggah foto. Kepatuhan UU PDP dan batas usia perlu ditinjau pihak WSM/legal; README ini bukan nasihat hukum.
- Aksesibilitas: satu `<h1>` per halaman, target sentuh minimal 44×44 px, kontras WCAG AA, hormati `prefers-reduced-motion`.

## 10. Keputusan yang dibutuhkan dari tim WSM

Tiap baris punya **default**: kalau belum diputuskan, fase tetap jalan memakai default itu dan bisa diubah belakangan.

| #   | Keputusan                                                                          | Default yang dipakai                                                          | Dibutuhkan sebelum |
| --- | ---------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- | ------------------ |
| 1   | Sumber jadwal event: input manual/CSV atau sinkron Bandsintown                     | Manual + impor CSV                                                            | IC-2               |
| 2   | Jendela check-in, GPS wajib atau tidak, radius                                     | D 12.00 sampai D+1 06.00 WIB; GPS mati                                        | IC-2               |
| 3   | Isi dan syarat tiap hadiah (apa yang benar-benar diberikan, siapa yang memenuhi)   | 5 tahapan prototype apa adanya                                                | IC-3               |
| 4   | Alamat kirim untuk hadiah fisik: boleh diminta?                                    | Diminta hanya saat klaim hadiah yang `butuh_alamat`                           | IC-3               |
| 5   | Moderasi galeri: pra atau pasca                                                    | Pra-moderasi                                                                  | IC-4               |
| 6   | Kebijakan stempel: tidak hangus? koreksi admin boleh?                              | Tidak hangus; koreksi boleh dan tercatat di ledger                            | IC-5               |
| 7   | Email (verifikasi, lupa sandi, status klaim): ada SMTP?                            | Tanpa email. Admin membuat sandi sementara; status klaim terlihat di aplikasi | IC-5               |
| 8   | Batas usia dan persetujuan (acara klub)                                            | Centang "berusia minimal 17 tahun"; angka final ditentukan WSM/legal          | IC-6               |
| 9   | Bentuk diskon Mavnus: persen atau nominal, masa berlaku, boleh digabung promo lain | Persen, berlaku 30 hari, tidak digabung promo lain                            | IC-7               |
| 10  | Akses awal merchandise: produk apa, berapa lama                                    | Dipilih per produk di Mavnus, 48 jam                                          | IC-7               |
| 11  | Arsitektur lintas situs: A, B, atau C (bagian 4.7)                                 | A                                                                             | IC-7               |

## 11. Opsional setelah rilis

- **QR dinamis:** token berganti tiap 30–60 detik (HMAC dari kode event + slot waktu), ditampilkan di layar venue. Menutup celah QR cetak yang difoto lalu dibagikan.
- Notifikasi WhatsApp untuk status klaim.
- Pemindahan ke aplikasi mandiri (opsi B) bila Inner Circle makin besar.

---

# Cara menjalankan perintah (baca sekali)

Semua perintah di bawah memakai pola yang sama:

- **Terminal lokal:** buka folder proyek di VS Code → menu **Terminal → New Terminal**. Pastikan alamat folder di terminal adalah folder yang berisi file `artisan` (folder `WS-talent-website`). Tempel perintah, tekan Enter. Kalau memakai Laragon/XAMPP, pastikan MySQL sudah menyala dulu.
- **phpMyAdmin lokal:** buka `http://localhost/phpmyadmin` di browser. Database proyek: `ws_database`.
- **phpMyAdmin dan Cron Jobs di server:** login cPanel Rumahweb. phpMyAdmin ada di bagian **Databases**; Cron Jobs ada di bagian **Advanced**.
- Setiap perintah di fase berikutnya ditulis bersama penjelasan **apa yang dilakukannya**. Kalau hasilnya berisi kata `error`/`failed`, hentikan dan kirim hasilnya sebelum lanjut.

**Cara kerja per fase:** (1) minta file fase itu dikirim satu per satu, (2) taruh tiap file di path yang disebut, (3) jalankan perintah fase itu, (4) lakukan daftar cek, (5) commit dengan pesan yang disarankan, lalu baru fase berikutnya. Satu fase = satu commit besar yang bisa diuji sendiri.

---

# Fase pengerjaan

Ukuran: **S** kecil, **M** sedang, **L** besar. Urutan fase tidak boleh dilompati (IC-2 butuh IC-1, dst.).

## Fase IC-0: Persiapan (S)

**Tujuan:** menyiapkan pustaka dan helper tanpa mengubah perilaku situs.

**File (urutan kirim):**

1. `app/Support/ImageProcessor.php` (baru): menerima JPEG/PNG/WebP; cek `getimagesize` dan tolak sisi > 6000 px; perbaiki orientasi EXIF bila ekstensi `exif` ada; batasi sisi terpanjang 1600 px (avatar: crop tengah persegi 512 px); keluarkan string WebP kualitas 82. Controller lama tidak diubah.
2. `composer.json`, `composer.lock`, `package.json`, `package-lock.json` (berubah otomatis oleh perintah di bawah).

**Perintah (terminal lokal, folder proyek):**

```bash
git checkout -b feat/inner-circle
```

Membuat cabang kerja baru bernama `feat/inner-circle` supaya cabang utama aman.

```bash
composer require bacon/bacon-qr-code
```

Memasang paket pembuat QR sisi server. Baca keluarannya: harus selesai tanpa pesan error dan menyebut versi yang terpasang.

```bash
npm install qr-scanner
```

Memasang pustaka pembaca QR (kamera dan gambar) untuk browser.

```bash
php artisan test
```

Menjalankan tes yang sudah ada. Hasilnya harus tetap hijau.

**Cek:** `composer.json` memuat `bacon/bacon-qr-code`; `package.json` memuat `qr-scanner`; situs lokal (`/`, `/profile`, `/dashboard`) tetap normal.

**Commit:** `chore: add qr dependencies and ImageProcessor for Inner Circle`

## Fase IC-1: Fondasi dan akun anggota (M)

**Tujuan:** orang bisa daftar, masuk, keluar, dan melihat kartu anggota (stempel masih 0). Admin tidak terpengaruh.

**File (urutan kirim):**

1. `database/migrations/*_members.php` (tabel `members`, bagian 5; `kode_member` unik).
2. `app/Models/member.php` (extends `Illuminate\Foundation\Auth\User`, `$primaryKey = 'id_member'`, `password` di-cast `hashed`).
3. `config/auth.php`: tambah guard `member` dan provider `members`:
    ```php
    'guards'    => ['web' => [...], 'member' => ['driver' => 'session', 'provider' => 'members']],
    'providers' => ['users' => [...], 'members' => ['driver' => 'eloquent', 'model' => App\Models\member::class]],
    ```
4. `app/Providers/AppServiceProvider.php`: limiter `ic-login` (per `email|ip`, 5/menit), `ic-daftar` (per ip, 5/menit).
5. `app/Http/Requests/RegisterMemberRequest.php` (nama maks 80, email unik, sandi min 8 + konfirmasi, centang syarat wajib; pesan Indonesia).
6. `app/Http/Controllers/InnerCircle/LandingController.php`, `AuthController.php`, `HomeController.php`.
    - Daftar: buat anggota, isi `kode_member` = `sprintf('IC-%05d', id_member)` di transaksi yang sama, login guard `member`, `session()->regenerate()`.
    - Masuk: hanya `status = aktif`, pesan error generik. Keluar: `Auth::guard('member')->logout()` + `regenerateToken()` (bukan `invalidate`).
7. `routes/web.php`: grup `/inner-circle` (bagian 6); area anggota `auth:member` dengan redirect tamu ke `ic.login`.
8. `vite.config.js` (tambah dua entry), `resources/css/inner-circle.css`, `resources/js/inner-circle.js`.
9. `resources/views/template/innerCircleLayout.blade.php`, `resources/views/inner-circle/landing.blade.php`, `daftar.blade.php`, `masuk.blade.php`, `app/home.blade.php`.
10. `resources/views/components/inner-circle-teaser.blade.php`, ubah `pages/home.blade.php` dan `components/navbar.blade.php` (link desktop + menu mobile).
11. `app/Http/Controllers/SeoController.php`: `/inner-circle` ke sitemap; `Disallow: /inner-circle/app` ke robots.

**Perintah:**

```bash
php artisan migrate
```

Membuat tabel `members` di database lokal.

```bash
npm run dev
```

Menjalankan Vite untuk pengembangan; biarkan jendela terminal ini terbuka. Buka terminal kedua (ikon `+` di panel terminal) untuk:

```bash
php artisan serve
```

Menjalankan situs di `http://127.0.0.1:8000` (lewati bila memakai Laragon/XAMPP yang sudah menyajikan situs).

**Cek (manual, desktop dan HP):**

- Daftar akun baru → langsung masuk, kartu anggota menampilkan `IC-00001` dan stempel 0.
- Keluar lalu masuk lagi; sandi salah menampilkan pesan generik.
- Buka `/inner-circle/app` tanpa login → dialihkan ke halaman masuk.
- Login admin di `/login` tetap jalan; sesi anggota **tidak** bisa membuka `/dashboard`.
- Link "Inner Circle" dan teaser tampil di home tanpa merusak tata letak; tiap halaman Inner Circle punya tepat satu `<h1>`.

**Commit:** `feat: add Inner Circle member accounts, landing, and layout`

## Fase IC-2: Event, check-in, dan stempel (L)

**Tujuan:** admin membuat event dan QR; anggota check-in dan mendapat stempel dengan aman.

**File (urutan kirim):**

1. Migrations: `*_ic_events.php`, `*_ic_checkins.php`, `*_ic_stempel_ledger.php` (unique key sesuai bagian 5).
2. Models: `ic_event.php` (cast `token` = `encrypted`, scope `bisaCheckin()`), `ic_checkin.php`, `ic_stempel_ledger.php`; ubah `member.php` (relasi `checkins`, `ledger`).
3. `config/inner-circle.php` (default jendela check-in, nilai `seed_contoh` dari `IC_SEED_CONTOH`).
4. `app/Services/QrService.php` (render SVG dari payload `WSIC|kode|token`) dan `app/Services/CheckinService.php` (alur 7.1 dalam satu transaksi; tangkap `QueryException` SQLSTATE 23000 sebagai "sudah check-in").
5. `app/Providers/AppServiceProvider.php`: limiter `ic-checkin` per anggota.
6. `app/Http/Requests/CheckinRequest.php`.
7. `database/seeders/innerCircleSeeder.php` + `database/seeders/data/ic_events_q4_2026.csv` (50 baris prototype; event tanpa venue `draft`, Private Event non-aktif untuk check-in, yang sudah lewat `selesai`). Ubah `DatabaseSeeder.php`: seeder event hanya jalan bila `IC_SEED_CONTOH=true`.
8. Controller member `InnerCircle/CheckinController.php` (daftar event + filter bulan/tipe/cari, `verify` balas JSON).
9. Controller admin `Dashboard/InnerCircle/EventController.php` (CRUD + impor CSV: kolom `kode,tanggal,kota,venue,tipe`, upsert berdasarkan `kode`, laporan baru/diperbarui/ditolak beserta alasan) dan `QrController.php` (tampil, buat ulang, halaman cetak).
10. View dashboard `pages/dashboard-pages/inner-circle/*` (hub bertab + event + QR) dan ubah `components/dashboard/navbar.blade.php` (1 entri), `routes/web.php`.
11. View member `inner-circle/app/checkin.blade.php`; JS di `inner-circle.js` (pemindai `qr-scanner`, filter, kirim verifikasi via `fetch`).
12. Ubah `inner-circle/app/home.blade.php` (progres stempel + aktivitas dari ledger).
13. Command penutup event `app/Console/Commands/TutupEventIc.php` dan jadwalnya di `routes/console.php` (tiap 5 menit).

**Perintah:**

```bash
php artisan migrate
```

Membuat tiga tabel baru di database lokal.

Tambahkan baris ini di berkas `.env` (buka lewat VS Code, di folder proyek), lalu simpan:

```
IC_SEED_CONTOH=true
```

Ini hanya untuk lokal: menyalakan data event contoh.

```bash
php artisan db:seed --class=innerCircleSeeder
```

Mengisi tabel event dengan 50 baris contoh dari prototype.

```bash
php artisan schedule:run
```

Menjalankan tugas terjadwal satu kali untuk mengecek penutup event otomatis.

**Cek:** semua skenario "Check-in" di bagian 12 lulus, plus:

- QR dari dashboard terbaca oleh kamera HP (butuh HTTPS atau `localhost`) dan oleh unggah gambar; input kode manual juga jalan.
- Kamera ditolak/tidak ada → pesan jelas dan kode manual tetap bisa dipakai.
- Event `selesai` / Private Event / tanpa venue tidak bisa dipilih.
- Impor CSV dengan baris salah menampilkan alasan per baris, bukan error umum.

**Commit:** `feat: add Inner Circle events, QR check-in, and stamp ledger`

## Fase IC-3: Hadiah dan klaim (M)

**Tujuan:** anggota membuka dan mengklaim hadiah; admin memprosesnya.

**File (urutan kirim):**

1. Migrations: `*_ic_rewards.php`, `*_ic_claims.php`, `*_ic_claim_logs.php`.
2. Models: `ic_reward.php`, `ic_claim.php`, `ic_claim_log.php` (cast `opsi` JSON, `data_pengiriman` `encrypted`); ubah `member.php` (relasi `claims`).
3. Seeder `innerCircleSeeder.php` (tambah 5 tahapan prototype; **aman** untuk produksi karena bisa diedit di CMS).
4. `app/Services/ClaimService.php`: transaksi + `lockForUpdate` anggota; cek `stempel_total >= ambang`; `opsi_dipilih` wajib bila hadiah punya `opsi`; alamat wajib bila `butuh_alamat`; peta transisi status satu arah, tiap perpindahan menulis `ic_claim_logs`; kosongkan `data_pengiriman` saat `selesai`.
5. `app/Http/Requests/ClaimRewardRequest.php`; `app/Policies/ClaimPolicy.php` + `Gate::policy` di `AppServiceProvider`.
6. Controller member `InnerCircle/RewardController.php`; controller admin `Dashboard/InnerCircle/RewardController.php` dan `ClaimController.php`.
7. View: `inner-circle/app/hadiah.blade.php` (kartu terkunci/terbuka, form klaim, pilihan A/B); dashboard hadiah (form kiri / preview kanan) dan antrean klaim (filter per status + catatan admin).
8. `routes/web.php`.

**Perintah:**

```bash
php artisan migrate
```

Membuat tiga tabel baru.

```bash
php artisan db:seed --class=innerCircleSeeder
```

Mengisi 5 hadiah awal (aman dijalankan ulang; seeder memakai `updateOrCreate`).

**Cek:** semua skenario "Hadiah" di bagian 12 lulus; tombol "Klaim" nonaktif di bawah ambang; status tidak bisa dilompati dari antarmuka maupun lewat request manual; riwayat status tampil di detail klaim.

**Commit:** `feat: add Inner Circle rewards and claim workflow`

## Fase IC-4: Komunitas (M)

**Tujuan:** pengumuman dan galeri kenangan dengan moderasi.

**File (urutan kirim):**

1. Migrations: `*_ic_broadcasts.php`, `*_ic_memories.php`, `*_ic_memory_likes.php`.
2. Models: `ic_broadcast.php`, `ic_memory.php`, `ic_memory_like.php`; `app/Policies/MemoryPolicy.php` + `Gate::policy`.
3. `app/Http/Requests/StoreMemoryRequest.php` (caption wajib maks 280, foto opsional maks 4 MB, kategori dari daftar).
4. Controller member `InnerCircle/CommunityController.php` (daftar `disetujui` dengan paginasi 12, unggah memakai `ImageProcessor` ke `ic/memories/{uuid}.webp`, like = toggle di transaksi dengan unique key, hapus milik sendiri beserta file fotonya).
5. Controller admin `Dashboard/InnerCircle/BroadcastController.php` (CTA hanya boleh ke route Inner Circle yang diizinkan atau URL `https://`) dan `MemoryController.php` (setujui/tolak/hapus).
6. View: `inner-circle/app/komunitas.blade.php` (tab pengumuman dan kenangan, filter kategori, modal unggah); dashboard broadcast dan moderasi; JS like (UI langsung berubah, kembali bila request gagal).
7. `routes/web.php`.

**Perintah:**

```bash
php artisan migrate
```

Membuat tiga tabel baru.

```bash
php artisan storage:link
```

Membuat tautan agar foto di `storage/app/public` bisa dibuka dari web (lewati bila sebelumnya sudah pernah dijalankan; pesan "link already exists" itu normal).

**Cek:** semua skenario "Komunitas" di bagian 12 lulus; foto dari HP tampil **tegak** (orientasi benar); berkas tersimpan berformat WebP tanpa data EXIF; kenangan `menunggu` tidak terlihat anggota lain.

**Commit:** `feat: add Inner Circle announcements and memory gallery`

## Fase IC-5: Profil, administrasi anggota, dan akun (S–M)

**Tujuan:** profil lengkap, pengelolaan anggota, dan hak hapus akun.

**File (urutan kirim):**

1. `app/Http/Controllers/InnerCircle/ProfileController.php`: unggah foto profil (crop tengah persegi di server lewat `ImageProcessor`), riwayat stempel (ledger berpaginasi) dan klaim, **hapus akun** (anonimkan `members`, hapus avatar dan foto kenangan, ledger/klaim tetap tanpa identitas).
2. View `inner-circle/app/profil.blade.php`.
3. `app/Http/Controllers/Dashboard/InnerCircle/MemberController.php`: daftar + pencarian, blokir/aktifkan, **koreksi stempel** (delta ±, keterangan wajib, masuk ledger `koreksi_admin`, total tidak boleh di bawah 0), buat **sandi sementara** (`wajib_ganti_sandi = true`).
4. Middleware kecil `app/Http/Middleware/WajibGantiSandi.php` (alias `icGantiSandi`) + halaman ganti sandi anggota; daftarkan alias di `bootstrap/app.php`.
5. View dashboard anggota; `routes/web.php`.
6. **Bila SMTP sudah ada (keputusan 7):** Mailable verifikasi email, lupa sandi (broker `members` di `config/auth.php`), dan status klaim, semuanya lewat antrean.

**Perintah:** tidak ada migrasi baru. Jalankan:

```bash
php artisan route:list --path=inner-circle
```

Menampilkan semua route Inner Circle untuk memastikan tidak ada yang hilang atau bertabrakan.

**Cek:** blokir anggota mengeluarkan dia dari sesi aktif berikutnya; koreksi stempel tampil di riwayat anggota; sandi sementara memaksa ganti sandi; hapus akun menghilangkan nama/email/foto dari seluruh halaman.

**Commit:** `feat: add Inner Circle profile, member admin, and account deletion`

## Fase IC-6: Polish, QA, dan rilis (M)

**Tujuan:** siap dipakai publik. Rilis **ke preview dulu**, lalu domain utama.

**File (urutan kirim):**

1. `resources/views/inner-circle/syarat.blade.php` dan `privasi.blade.php` + route + tautan di footer layout Inner Circle dan di form daftar.
2. Perbaikan hasil audit (aksesibilitas, kontras, target sentuh 44 px, fokus keyboard, `prefers-reduced-motion`).
3. `tests/Feature/InnerCircle/*` (skenario bagian 12).
4. Seeder demo (bila dibuat) dibungkus `app()->environment('local')`.

**Langkah sebelum rilis (lokal):**

```bash
php artisan test
```

Menjalankan seluruh tes; harus hijau semua.

```bash
npm run build
```

Membuat berkas aset produksi di `public/build/`.

**Rilis ke preview (`preview.whisnusantika.com`), berurutan:**

1. Unggah (FTP / alur deploy yang sudah ada) ke folder `laravel_preview/`: folder baru di `app/`, `config/`, `database/`, `resources/views/`, `routes/`, `bootstrap/app.php` bila berubah, `vendor/composer/` dan folder paket baru di `vendor/`.
2. Unggah isi `public/build/` ke `public_html/preview.whisnusantika.com/build/`.
3. phpMyAdmin server → pilih database **preview** → tab **Import** → pilih berkas SQL hasil ekspor tabel baru dari lokal (`members`, `ic_*`, termasuk data `ic_rewards`). Lalu tambahkan baris nama migrasi baru ke tabel `migrations`.
4. cPanel → **Cron Jobs** → tambah satu cron tiap menit. Perintahnya (sesuaikan alamat PHP dan folder dengan yang tertera di cPanel Anda):
    ```
    * * * * * /usr/local/bin/php /home/USERNAME/laravel_preview/artisan schedule:run >> /dev/null 2>&1
    ```
    Fungsinya menjalankan tugas terjadwal (penutup event otomatis, dan antrean email bila ada).
5. Di CMS preview: impor CSV event **asli** (bukan data contoh). **Jangan** isi `IC_SEED_CONTOH` di server.
6. Uji di HP asli: daftar, check-in dengan kamera, unggah foto, klaim hadiah. Uji beban ringan check-in (banyak anggota sekaligus untuk satu event) dan pastikan stempel tetap tepat satu per anggota.
7. Setelah lolos dan disetujui WSM, ulangi langkah 1–5 untuk domain utama (`laravel/` dan `public_html/`, database utama, cron utama).

**Cek akhir:** halaman `/inner-circle` ada di `sitemap.xml`; `robots.txt` memuat `Disallow: /inner-circle/app`; tidak ada akun/kredensial demo di server; folder `storage/` dapat ditulis; HTTPS aktif (kamera QR butuh).

**Commit:** `feat: finalize Inner Circle QA, legal pages, and release prep`

## Fase IC-7: Integrasi Mavnus (L)

**Prasyarat:** IC-1 sampai IC-6 sudah live. Kerjakan di **dua repo**: situs ini dan Mavnus. Mavnus memakai SSH + GitHub Actions, jadi `php artisan` bisa dijalankan di sana; situs ini tetap tanpa terminal. Uji di pasangan preview dulu: `preview.whisnusantika.com` ↔ `preview.mavnus.com`, dengan rahasia dan daftar callback masing-masing.

### Bagian A: repo situs ini (WS Talent Website)

1. `bootstrap/app.php`: tambah `api: __DIR__.'/../routes/api.php'` di `withRouting` dan daftarkan alias middleware `icSignature`.
2. `config/inner-circle.php`: `mavnus.secret` (`IC_MAVNUS_SECRET`), `mavnus.callbacks` (`IC_MAVNUS_CALLBACKS`, daftar URL callback yang diizinkan, dipisah koma), toleransi waktu tanda tangan 300 detik.
3. Migrations `*_ic_vouchers.php`, `*_ic_sso_codes.php`; models `ic_voucher.php`, `ic_sso_code.php`.
4. `app/Http/Middleware/VerifyIcSignature.php` (rumus tanda tangan di bagian 6; `hash_equals`; tolak timestamp basi).
5. `app/Services/VoucherService.php`: terbit (dari klaim `voucher_merch` saat `disetujui`), pakai (transaksi + `lockForUpdate`, **idempoten** berdasarkan `order_ref`), lepas, kedaluwarsa otomatis lewat scheduler.
6. `app/Http/Controllers/InnerCircle/SsoController.php` (`/inner-circle/sso/mavnus`: cek `kembali` ada di daftar putih, buat kode acak 60 detik simpan hash-nya, redirect ke callback) dan `app/Http/Controllers/Api/IcApiController.php` (lima endpoint bagian 6).
7. `ClaimService.php`: tambahkan penerbitan voucher untuk tipe `voucher_merch`.
8. Dashboard: tab voucher (`Dashboard/InnerCircle/VoucherController.php` + view). `routes/api.php`, `routes/web.php`.

**Perintah (lokal):**

```bash
php artisan migrate
```

Membuat dua tabel baru. Untuk server tanpa terminal, ekspor/impor via phpMyAdmin seperti di IC-6.

Tambahkan di `.env` (lokal dan server, nilai rahasia **berbeda** antara lokal, preview, dan utama):

```
IC_MAVNUS_SECRET=isi-string-acak-panjang
IC_MAVNUS_CALLBACKS=https://preview.mavnus.com/inner-circle/callback
```

### Bagian B: repo Mavnus

Nama tabel/kolom produk mengikuti skema Mavnus (kunci utama kustom seperti `id_product`); **cek dulu di repo Mavnus** sebelum menulis migrasi.

1. `config/services.php`: blok `inner_circle` (`base_url`, `secret`). `.env` Mavnus: `IC_BASE_URL`, `IC_SECRET` (sama dengan `IC_MAVNUS_SECRET` di server situs ini).
2. `app/Services/InnerCircleClient.php`: `Http::withHeaders(...)` bertanda tangan, timeout pendek (5 detik), satu kali ulang; semua kegagalan dikembalikan sebagai "tidak tersedia", bukan exception ke pembeli.
3. Route `GET /inner-circle/masuk` (redirect ke situs ini dengan `state` acak di sesi) dan `GET /inner-circle/callback` (cek `state`, tukar `code`, simpan ringkasan anggota di sesi: kode, nama, stempel, avatar; segarkan tiap ±10 menit).
4. Navbar Mavnus: tombol "Masuk dengan Inner Circle" / nama anggota + stempel; tampilan bilingual mengikuti sistem EN/ID yang sudah ada.
5. Checkout: kolom voucher → cek lewat API → hitung diskon **di server Mavnus** → saat pesanan dibuat panggil `pakai` dengan nomor pesanan; saat pesanan dibatalkan atau kedaluwarsa otomatis panggil `lepas`.
6. Akses awal: kolom baru di tabel produk terkait (`akses_awal_min_stempel`, `akses_awal_sampai`); produk tersembunyi/tidak bisa dibeli bagi non-anggota atau anggota di bawah batas hingga waktu akses awal berakhir.
7. Dokumentasikan variabel baru di `.env.production.example` Mavnus dan README Mavnus.

**Cek:**

- Alur masuk end-to-end: Mavnus → situs ini (login anggota) → kembali ke Mavnus dengan nama dan stempel benar.
- Skenario "Mavnus" di bagian 12 lulus.
- Matikan situs ini (atau salahkan rahasia): checkout Mavnus tetap bisa selesai tanpa voucher.
- Pesanan yang kedaluwarsa otomatis mengembalikan voucher menjadi `aktif`.

**Commit (situs ini):** `feat: add Inner Circle SSO, signed API, and vouchers for Mavnus`
**Commit (Mavnus):** `feat: add Inner Circle login, vouchers, and early access`

---

# 12. Skenario uji wajib (PHPUnit feature test)

**Check-in**

- Token benar → +1 stempel; ledger dan `stempel_total` konsisten.
- Token salah / sudah diganti admin → ditolak, stempel tetap.
- Check-in kedua untuk event yang sama → ditolak; dua request bersamaan tetap hanya 1 stempel.
- Event `private`, tanpa venue, `draft`, atau di luar jendela waktu → ditolak.
- Tamu → dialihkan ke halaman masuk. Anggota `diblokir` → ditolak.
- Limiter: dua anggota berbeda dari IP yang sama tidak saling memblokir.

**Hadiah**

- Stempel di bawah ambang → ditolak. Klaim ganda → ditolak.
- Tahap 50 tanpa pilihan A/B → validasi gagal.
- Loncat status (`diajukan` langsung `selesai`) → ditolak.
- `data_pengiriman` kosong setelah `selesai`.

**Komunitas**

- Kenangan `menunggu`/`ditolak` tidak muncul di galeri anggota lain.
- Anggota tidak bisa menghapus kenangan orang lain.
- Like ganda tidak menambah hitungan; caption berisi HTML tampil sebagai teks.
- Foto berorientasi miring (EXIF) tersimpan tegak; EXIF hilang dari file.

**Akses**

- Sesi anggota tidak bisa membuka `/dashboard`; sesi admin tidak otomatis jadi anggota.
- Logout anggota tidak mengeluarkan admin di browser yang sama.
- `/dashboard/inner-circle/*` tanpa login admin → ke halaman login.

**Mavnus (IC-7)**

- Kode SSO dipakai dua kali → kali kedua ditolak; kode lewat 60 detik → ditolak.
- `kembali` di luar daftar putih → ditolak.
- Tanda tangan salah, atau timestamp lebih dari 300 detik → 401.
- Voucher dipakai dua kali dengan `order_ref` berbeda → kedua ditolak; dengan `order_ref` sama → hasil sama (idempoten).
- `lepas` mengembalikan voucher menjadi `aktif`; voucher kedaluwarsa tidak bisa dipakai.
- Anggota `diblokir` tidak mendapat kode SSO maupun voucher aktif.
