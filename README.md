# WS Talent Website

Website publik profil artis **Whisnu Santika** beserta dashboard CMS-nya.

- **Stack:** Laravel 13 · PHP 8.3 · Tailwind CSS 4 · Vite · Swiper · SweetAlert2 · SQLite (dev)
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

# Rencana Penerapan: WS Inner Circle

> Status: **rencana (belum ada kode yang diubah)**.
> Acuan desain dan alur: prototype `WS_Inner_Circle_Daftar_Anggota_Baru.html`. Rencana ini menerjemahkannya ke struktur Laravel repo ini.

## 1. Tujuan

Menanam komunitas **WS Inner Circle** ke dalam website ini: penggemar bisa mendaftar jadi anggota, mengumpulkan **stempel** dengan hadir di acara (check-in QR), membuka dan mengklaim **hadiah** per tahapan, membaca **pengumuman**, dan membagikan **kenangan** (galeri foto). Tim WSM mengelola semuanya dari dashboard CMS yang sudah ada.

## 2. Apa yang ada di prototype

Prototype adalah 1 file HTML, semua data hidup di `localStorage` (tanpa server). Isinya:

| Layar          | Fungsi                                                                                                                                                       | Data                             |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------- |
| Masuk / Daftar | Auth anggota dan admin dalam satu gerbang                                                                                                                    | `users[]` di localStorage        |
| Beranda        | Kartu anggota digital, progres stempel, aktivitas terbaru                                                                                                    | `state.stamps`, `state.activity` |
| Hadiah         | 5 tahapan: **10, 25, 50, 100, 250** stempel; klaim 1x per tahapan; tahapan 50 punya pilihan A/B                                                              | `definitions[]`, `state.claims`  |
| Check-in       | Jadwal Q4 2026 (**50 show**: 33 Club, 14 Festival, 3 Private Event) + filter bulan/tipe/cari; verifikasi lewat QR kamera, upload gambar QR, atau kode manual | `tourEvents[]`, token per event  |
| Komunitas      | Tab **Pengumuman WS** (3 item) dan **Galeri Kenangan** (upload foto, kategori, like, hapus milik sendiri)                                                    | `broadcasts[]`, `memories[]`     |
| Profil         | Foto profil, total stempel, riwayat stempel, pengajuan hadiah                                                                                                | `state`, avatar di localStorage  |
| Admin          | Pembuat QR per event (generate/unduh/cetak), antrean klaim hadiah, direktori anggota                                                                         | `tokens[]`, `state.claims`       |

Aturan bisnis yang sudah tersirat di prototype dan **dipertahankan**:

1. Stempel bersifat **kumulatif**, tidak berkurang saat klaim dan tidak hangus.
2. Tiap hadiah hanya bisa diklaim **1 kali** per anggota.
3. **1 stempel per anggota per event** (proteksi duplikat).
4. Event tipe **Private Event** dan event **tanpa venue** tidak bisa dipakai check-in.
5. Tiap event punya token/QR sendiri; admin bisa membuat ulang, dan token lama otomatis tidak berlaku.

## 3. Pemetaan prototype ke Laravel

Prototype dipakai sebagai **acuan tampilan dan alur**. Karena semua datanya hidup di browser, tiap bagiannya dipetakan ke komponen Laravel berikut:

| Di prototype (browser)                                                                                                          | Di Laravel                                                                                                      |
| ------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| `users[]` + login di `localStorage`                                                                                             | Model `Member`, guard `member`, tabel `members`, sandi `Hash::make` (min. 8 karakter)                           |
| ID anggota `max(id)+1`                                                                                                          | Auto-increment `id_member`, `kode_member` diformat `IC-00248` setelah insert                                    |
| `state.stamps` + `state.activity`                                                                                               | Tabel `ic_stempel_ledger` (sumber kebenaran) + cache `members.stempel_total`                                    |
| `definitions[]` (5 hadiah, hardcode)                                                                                            | Tabel `ic_rewards`, diseed dari prototype dan bisa diedit di CMS                                                |
| `state.claims` + `prompt()` pilihan A/B                                                                                         | Tabel `ic_claims` + field `opsi_dipilih` di form klaim                                                          |
| Status klaim `Requested/Review/Approved/Fulfilled` (di kode member tertulis `Diajukan`, jadi tidak sinkron dengan tombol admin) | Satu set status baku: `diajukan` → `ditinjau` → `disetujui` → `selesai` / `ditolak`, dicatat di `ic_claim_logs` |
| `tourEvents[]` (50 baris)                                                                                                       | Tabel `ic_events`, diseed dari prototype, CRUD + impor CSV di CMS                                               |
| Token QR dibuat dan dicek di browser (`tokens[]`)                                                                               | Token dibuat server (`encrypted` cast), dicek di `CheckinService`; QR dirender server                           |
| `selectedCheckins` (Set)                                                                                                        | Tabel `ic_checkins` dengan unique `(member_id, event_id)`                                                       |
| `broadcasts[]`                                                                                                                  | Tabel `ic_broadcasts`, CRUD di CMS                                                                              |
| `memories[]` + foto `dataURL`                                                                                                   | Tabel `ic_memories` + upload ke disk `public` (WebP) + moderasi                                                 |
| Like di array JS                                                                                                                | Tabel `ic_memory_likes`, endpoint JSON                                                                          |
| Avatar di `localStorage`                                                                                                        | Kolom `members.avatar` + upload server                                                                          |
| Angka admin hardcode (`128`, `7`), `isPastShow` hardcode `2026-10-13`                                                           | Query ke DB dan `now()`                                                                                         |
| Kredensial demo (`member123`, `admin123`)                                                                                       | Tidak dibawa. Akun demo hanya lewat seeder di environment `local`; admin tetap lewat login CMS yang sudah ada   |
| Satu gerbang login member/admin                                                                                                 | Dipisah: member `/inner-circle/masuk`, admin `/login` yang sudah ada                                            |
| 4 lapis CSS arah desain                                                                                                         | Hanya arah final (lavender) yang dibawa, dibersihkan jadi `inner-circle.css`                                    |
| Label campur Indonesia/Inggris                                                                                                  | Diseragamkan Bahasa Indonesia                                                                                   |

## 4. Keputusan arsitektur

### 4.1 Letak di aplikasi

- **Landing publik** `/inner-circle`: penjelasan program, daftar hadiah, tombol daftar. Masuk sitemap, bisa di-index.
- **Area anggota** `/inner-circle/app/*`: butuh login anggota. `noindex`, ditambahkan ke `Disallow` di `SeoController@robots`.
- **Teaser di home:** 1 komponen baru `inner-circle-teaser` di `pages/home.blade.php` + 1 link "Inner Circle" di `components/navbar.blade.php` (desktop dan menu mobile).
- Prototype adalah SPA satu halaman. Di Laravel dibuat **Blade per layar** (tiap layar = 1 route = 1 `<h1>`), sehingga sejalan dengan temuan audit SEO sebelumnya (jangan ada `<h1>` berulang dalam satu halaman).

### 4.2 Autentikasi: guard terpisah untuk anggota

Auth admin sekarang sengaja sederhana: tabel `accounts`, flag `session('login')`, middleware alias `cekLogin`. Itu **tidak disentuh**.

- Tambah model `Member` + guard **`member`** + provider **`members`** di `config/auth.php`.
- Jangan memakai `users`/`App\Models\User`: file `User.php` tidak ada di `app/Models`, sementara `config/auth.php` dan `DatabaseSeeder` masih menyebutnya.
- Middleware route anggota: `auth:member`. Route admin tetap `cekLogin`.
- Login anggota memakai `throttle` seperti login admin yang sudah ada (`throttle:5,1`) ditambah `RateLimiter` per kombinasi email+IP.
- Catatan: `loginController@logout` memanggil `session()->invalidate()`, jadi logout admin di browser yang sama ikut mengeluarkan sesi anggota. Dianggap wajar, dicatat saja.

### 4.3 Tampilan

Prototype punya identitas visual sendiri (lavender/editorial pop), berbeda dari tema gelap + `Warna Web` situs utama. **Rekomendasi:** area Inner Circle dipisah.

- Layout baru `template/innerCircleLayout.blade.php`, entry Vite baru `resources/css/inner-circle.css` + `resources/js/inner-circle.js`.
- Semua class diberi prefix `ic-` supaya CSS tidak bocor ke halaman publik utama.
- Teaser di home dan landing publik tetap mengikuti tema situs (`ThemeColorComposer`).
- Tanpa framework JS baru. Pola yang sama dengan sekarang: Blade + JS vanilla + SweetAlert2.

### 4.4 Sumber data event

Section **Tour** di home saat ini hanya widget **Bandsintown**; datanya tidak ada di database kita. Prototype memakai daftar event hasil impor dari tangkapan layar WSM. Karena check-in butuh data event milik sendiri:

- Event Inner Circle disimpan di tabel sendiri dan dikelola dari CMS (form manual + **impor CSV**).
- Seeder awal memuat 50 baris dari prototype (2 baris tanpa venue: `WS26-1023`, `WS26-1225`; dibiarkan non-aktif sampai venue diisi).
- Sinkronisasi otomatis dengan Bandsintown **tidak** masuk rencana awal (butuh akses API dan keputusan tim, lihat bagian 9).

### 4.5 Stempel = buku besar (ledger)

Total stempel tidak disimpan sebagai angka yang di-`++`. Sumber kebenaran adalah tabel ledger yang hanya bisa ditambah (append-only); `members.stamps_total` hanya cache yang diperbarui di transaksi yang sama. Ini memudahkan audit, koreksi admin, dan menjawab "kenapa stempel saya segini".

### 4.6 Struktur kode Laravel

- **Controller tipis, logika di service.** `CheckinService` (verifikasi + ledger), `ClaimService` (cek ambang + ubah status). Controller hanya validasi dan memanggil service.
- **Form Request** per aksi tulis (`RegisterMemberRequest`, `CheckinRequest`, `ClaimRewardRequest`, `StoreMemoryRequest`), pesan error Bahasa Indonesia seperti validasi yang sudah ada.
- **Policy** `MemoryPolicy` (hanya pemilik boleh hapus) dan `ClaimPolicy`.
- **Relasi Eloquent:** `Member` hasMany `checkins`, `ledger`, `claims`, `memories`; `ic_event` hasMany `checkins`; `ic_reward` hasMany `claims`.
- **Scope** `ic_event::bisaCheckin()` (bukan private, venue terisi, status aktif, di dalam jendela waktu) supaya aturan itu hanya ditulis sekali.
- **Scheduled command** (`schedule:run`) untuk menandai event `selesai` setelah jendela check-in tutup.
- **Blade component** untuk kartu anggota, kartu hadiah, kartu kenangan, dan kartu event, agar dipakai ulang di landing, beranda, dan preview CMS.
- **Seeder** terpisah: `innerCircleSeeder` (hadiah + event) dan demo seeder khusus `local`.

## 5. Skema database

Mengikuti konvensi repo: nama model huruf kecil snake*case, primary key `id*<nama>`, kolom Bahasa Indonesia. Semua tabel baru diberi prefix `ic\_`kecuali`members`.

| Tabel               | Kolom penting                                                                                                                                                                                                                                                                                      | Catatan                                                                                              |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `members`           | `id_member`, `kode_member` (unik, `IC-00248`), `nama`, `email` (unik), `password`, `avatar`, `stempel_total`, `status` (`aktif`/`diblokir`), `email_verified_at`, `remember_token`                                                                                                                 | Authenticatable untuk guard `member`                                                                 |
| `ic_events`         | `id_event`, `kode` (unik, `WS26-1001`), `tanggal`, `kota`, `venue`, `tipe` (`festival`/`club`/`private`), `checkin_buka_at`, `checkin_tutup_at`, `latitude`, `longitude`, `radius_m`, `token` (cast `encrypted`), `token_versi`, `status` (`draft`/`aktif`/`selesai`), `nilai_stempel` (default 1) | Event dianggap bisa check-in bila bukan `private`, venue terisi, status `aktif`                      |
| `ic_checkins`       | `id_checkin`, `member_id`, `event_id`, `metode` (`qr`/`kode`), `token_versi`, `lat`, `lng` (nullable), `created_at`                                                                                                                                                                                | **Unik `(member_id, event_id)`**. Itu yang menjamin 1 stempel per event, termasuk saat request ganda |
| `ic_stempel_ledger` | `id_ledger`, `member_id`, `delta`, `sumber` (`checkin`/`kegiatan`/`koreksi_admin`), `ref_type`, `ref_id`, `keterangan`, `dibuat_oleh`, `created_at`                                                                                                                                                | Append-only. Mewadahi "Ikut kegiatan resmi" di prototype dan koreksi manual admin                    |
| `ic_rewards`        | `id_reward`, `ambang`, `judul`, `deskripsi`, `ikon`, `tone`, `opsi` (JSON, mis. 2 pilihan untuk tahap 50), `urutan`, `aktif`                                                                                                                                                                       | Diseed dari 5 tahapan prototype, bisa diedit dari CMS                                                |
| `ic_claims`         | `id_claim`, `member_id`, `reward_id`, `opsi_dipilih`, `status`, `catatan_admin`, `judul_snapshot`, `ambang_snapshot`, `diajukan_at`, `diproses_at`                                                                                                                                                 | **Unik `(member_id, reward_id)`**. Snapshot supaya riwayat tetap utuh walau hadiah diedit            |
| `ic_claim_logs`     | `claim_id`, `dari_status`, `ke_status`, `oleh`, `created_at`                                                                                                                                                                                                                                       | Jejak audit perpindahan status                                                                       |
| `ic_broadcasts`     | `id_broadcast`, `judul`, `isi`, `tag`, `ikon`, `tone`, `cta_label`, `cta_tujuan`, `status`, `terbit_at`                                                                                                                                                                                            | Pengumuman komunitas. Terpisah dari tabel `news` karena audiensnya anggota                           |
| `ic_memories`       | `id_memory`, `member_id`, `event_id` (nullable), `label_acara`, `kategori` (`festival`/`club`/`pribadi`), `caption`, `foto` (nullable), `status` (`menunggu`/`disetujui`/`ditolak`), `jumlah_like`, `created_at`                                                                                   | Hanya `disetujui` yang tampil ke anggota lain                                                        |
| `ic_memory_likes`   | `member_id`, `memory_id`                                                                                                                                                                                                                                                                           | **Unik `(member_id, memory_id)`**                                                                    |

**Status klaim (baku):** `diajukan` → `ditinjau` → `disetujui` → `selesai`, plus `ditolak`. Hanya satu arah maju; setiap perpindahan tercatat di `ic_claim_logs`.

## 6. Route

### Publik dan anggota

| Method   | URL                                        | Nama                | Akses                                            |
| -------- | ------------------------------------------ | ------------------- | ------------------------------------------------ |
| GET      | `/inner-circle`                            | `ic.landing`        | publik                                           |
| GET/POST | `/inner-circle/daftar`                     | `ic.register`       | tamu, `throttle`                                 |
| GET/POST | `/inner-circle/masuk`                      | `ic.login`          | tamu, `throttle:5,1`                             |
| POST     | `/inner-circle/keluar`                     | `ic.logout`         | anggota (POST + CSRF, sama seperti logout admin) |
| GET      | `/inner-circle/app`                        | `ic.home`           | anggota                                          |
| GET      | `/inner-circle/app/hadiah`                 | `ic.rewards`        | anggota                                          |
| POST     | `/inner-circle/app/hadiah/{reward}/klaim`  | `ic.claim`          | anggota, `throttle:10,1`                         |
| GET      | `/inner-circle/app/check-in`               | `ic.checkin`        | anggota                                          |
| POST     | `/inner-circle/app/check-in`               | `ic.checkin.verify` | anggota, `throttle` ketat                        |
| GET      | `/inner-circle/app/komunitas`              | `ic.community`      | anggota                                          |
| POST     | `/inner-circle/app/kenangan`               | `ic.memory.store`   | anggota, `throttle`                              |
| POST     | `/inner-circle/app/kenangan/{memory}/like` | `ic.memory.like`    | anggota (JSON)                                   |
| DELETE   | `/inner-circle/app/kenangan/{memory}`      | `ic.memory.destroy` | pemilik saja                                     |
| GET      | `/inner-circle/app/profil`                 | `ic.profile`        | anggota                                          |
| POST     | `/inner-circle/app/profil/foto`            | `ic.profile.photo`  | anggota                                          |

### Admin (di dalam grup `/dashboard` yang sudah ada, middleware `cekLogin`)

| URL                                     | Fungsi                                                             |
| --------------------------------------- | ------------------------------------------------------------------ |
| `/dashboard/inner-circle`               | Ringkasan: anggota, check-in, klaim menunggu (angka nyata dari DB) |
| `/dashboard/inner-circle/event`         | CRUD event, impor CSV, atur jendela check-in dan lokasi            |
| `/dashboard/inner-circle/event/{id}/qr` | Tampilkan, buat ulang, unduh, dan cetak QR                         |
| `/dashboard/inner-circle/hadiah`        | CRUD tahapan hadiah (form kiri / preview kanan)                    |
| `/dashboard/inner-circle/klaim`         | Antrean klaim + ubah status + catatan                              |
| `/dashboard/inner-circle/broadcast`     | CRUD pengumuman                                                    |
| `/dashboard/inner-circle/kenangan`      | Moderasi galeri (setujui / tolak / hapus)                          |
| `/dashboard/inner-circle/anggota`       | Daftar anggota, blokir, koreksi stempel (masuk ledger)             |

Navbar dashboard sudah berisi 7 menu di dock bawah. **Jangan** menambah 7 menu lagi: cukup **1 menu "Inner Circle"** (`bi-people-fill`) yang membuka halaman hub dengan tab untuk sub-halaman di atas. Daftar `$navLinks` dan `$isKontenActive` di `components/dashboard/navbar.blade.php` ditambah 1 entri.

## 7. Alur inti

### 7.1 Check-in (hal paling sensitif)

```
Admin: event → "Buat QR"  → server membuat token acak, simpan terenkripsi, token_versi++
QR payload: WSIC|{kode_event}|{token}        (format sama dengan prototype)

Anggota: scan QR / unggah gambar QR / ketik kode  →  POST /check-in {kode_event, token}
Server (1 transaksi DB):
  1. event ada, status aktif, bukan private, venue terisi
  2. sekarang ada di dalam jendela check-in_buka..tutup
  3. hash_equals(token_tersimpan, token_kiriman)        → tolak jika beda / sudah diganti
  4. (opsional) jarak ke venue <= radius_m               → hanya bila lat/lng dikirim
  5. insert ic_checkins  → unique(member,event) menolak duplikat
  6. insert ic_stempel_ledger(+nilai_stempel) + update members.stempel_total
  7. balas JSON: stempel baru, total, sisa ke hadiah berikutnya
```

Catatan teknis:

- Pembacaan QR di prototype memakai `BarcodeDetector`. API ini **tidak ada di Safari iOS dan Firefox**. Sediakan fallback pustaka JS pemindai QR (di-bundle lewat npm/Vite, bukan CDN) dan **selalu** sediakan input kode manual.
- Kamera butuh **HTTPS** (atau `localhost`), pastikan domain produksi sudah HTTPS sebelum fase ini dirilis.
- QR dirender di server (SVG) supaya token tidak pernah dibuat di browser. Pilih paket QR yang kompatibel dengan Laravel 13/PHP 8.3 saat implementasi; cek `composer require` dulu, jangan asumsi versi.
- Lokasi (GPS) **opsional dan default mati**; bila dipakai, koordinat anggota tidak disimpan lebih lama dari yang perlu (lihat bagian 8).

### 7.2 Klaim hadiah

1. Hadiah terbuka bila `members.stempel_total >= ambang`.
2. Anggota mengisi form klaim (termasuk pilihan A/B untuk tahap 50) → insert `ic_claims`; unique key menolak klaim ganda.
3. Admin memindahkan status satu per satu; tiap langkah tercatat di `ic_claim_logs`.
4. Hadiah fisik butuh alamat kirim: diminta **hanya saat klaim**, bukan saat daftar (lihat bagian 9).

### 7.3 Galeri kenangan

1. Anggota unggah foto (opsional) + caption + kategori → status `menunggu`.
2. Admin moderasi dari dashboard → `disetujui` / `ditolak`.
3. Foto di-**re-encode ke WebP** di server; ini sekaligus membuang metadata EXIF (termasuk koordinat GPS bawaan foto ponsel).
4. Caption selalu di-escape Blade (`{{ }}`); jangan pakai `{!! !!}` untuk konten anggota.

## 8. Keamanan dan privasi

- Seluruh keputusan stempel dan klaim ada di server. Browser hanya menampilkan.
- Idempotensi lewat **unique constraint** + transaksi DB (`lockForUpdate` pada baris anggota saat menambah ledger), bukan sekadar cek di PHP.
- `throttle` di semua endpoint tulis; endpoint verifikasi check-in paling ketat. Tambahan: batasi percobaan token salah per anggota.
- Upload: validasi `mimes:jpg,jpeg,png,webp`, batas ukuran (prototype: foto kenangan 4 MB, avatar 5 MB), nama file acak (UUID), simpan di disk `public`.
- Token event disimpan terenkripsi (`encrypted` cast) dan tidak pernah ikut dikirim ke halaman anggota.
- Data pribadi minimal: nama, email, foto profil. Alamat/telepon hanya bila klaim hadiah fisik memerlukannya, dan dihapus/dianonimkan setelah klaim selesai.
- Sediakan halaman **Syarat & Ketentuan** dan **Kebijakan Privasi**, dengan persetujuan eksplisit saat daftar dan saat mengunggah foto. Soal kepatuhan UU PDP dan batas usia (venue klub) perlu ditinjau pihak WSM/legal; README ini bukan nasihat hukum.
- Aksesibilitas, mengikuti temuan audit yang sudah ada: satu `<h1>` per halaman, target sentuh minimal 44×44 px (termasuk dock navigasi), kontras teks memenuhi WCAG AA, hormati `prefers-reduced-motion` (prototype sudah punya aturannya).

## 9. Keputusan yang masih perlu dari tim WSM

Ini mengubah desain, jadi sebaiknya diputuskan sebelum fase terkait dimulai:

1. **Sumber jadwal event:** input manual/CSV dari CMS (rencana default) atau sinkron dari Bandsintown? Apakah ada akses API-nya?
2. **Aturan check-in:** kapan jendela dibuka dan ditutup (data prototype hanya punya tanggal, tidak ada jam), apakah GPS wajib, berapa radius.
3. **Verifikasi email dan reset kata sandi:** butuh penyedia SMTP. `MAIL_MAILER` sekarang `log`, dan prototype tidak punya alur lupa sandi.
4. **Moderasi galeri:** pra-moderasi (tampil setelah disetujui, rencana default) atau pasca-moderasi?
5. **Operasional hadiah:** siapa yang memenuhi klaim, lewat apa anggota diberi kabar (email/WA), dan data pengiriman apa yang boleh diminta.
6. **Kebijakan stempel:** tetap tidak hangus? Boleh ada koreksi manual oleh admin?
7. **Batas usia dan persetujuan** untuk anggota (acara klub).
8. **Database produksi:** `.env.example` memakai SQLite. Untuk lonjakan check-in saat acara, tulis bersamaan ke SQLite rawan antre/terkunci; pertimbangkan MySQL/PostgreSQL sebelum fase 3 rilis.

## 10. Fase pengerjaan

Ukuran: **S** kecil, **M** sedang, **L** besar. Tiap fase selesai berarti: migrate/seed jalan, route terdaftar, dites manual (desktop dan HP), lalu commit.

### Fase IC-1: Fondasi dan akun anggota (M)

- Migration `members`; model `Member`; guard/provider di `config/auth.php`.
- Layout `innerCircleLayout`, entry Vite `inner-circle`, port CSS final prototype dengan prefix `ic-`.
- Landing `/inner-circle`, daftar, masuk, keluar, kartu anggota di `ic.home` (stempel masih 0).
- Ekstrak `convertToWebP()` (saat ini diduplikasi privat di `dashboardController` dan `profileController`) ke `app/Support/ImageProcessor.php` supaya dipakai bersama.
- Link navbar publik + teaser home.
- **Selesai bila:** orang bisa daftar, masuk, keluar; admin tidak terpengaruh; `/inner-circle/app/*` mengalihkan tamu ke halaman masuk.

### Fase IC-2: Event, check-in, dan stempel (L)

- Migration `ic_events`, `ic_checkins`, `ic_stempel_ledger`; seeder 50 event dari prototype.
- Dashboard: CRUD event, impor CSV, halaman QR (buat ulang, unduh PNG, cetak).
- Halaman check-in anggota: daftar event + filter (bulan/tipe/cari) + pemindai QR + fallback kode manual.
- Service `CheckinService` (transaksi, unique key, ledger). Riwayat stempel di beranda dan profil.
- **Selesai bila:** semua kasus di bagian 11 untuk check-in lulus.

### Fase IC-3: Hadiah dan klaim (M)

- Migration `ic_rewards`, `ic_claims`, `ic_claim_logs`; seeder 5 tahapan.
- Halaman hadiah anggota (terkunci/terbuka, klaim, pilihan tahap 50).
- Dashboard: CRUD hadiah (form kiri/preview kanan sesuai pola CMS), antrean klaim + perubahan status.
- **Selesai bila:** klaim ganda ditolak, status hanya maju satu arah, riwayat tercatat.

### Fase IC-4: Komunitas (M)

- Migration `ic_broadcasts`, `ic_memories`, `ic_memory_likes`.
- Dashboard: CRUD broadcast, moderasi kenangan.
- Halaman komunitas: tab pengumuman dan galeri, filter kategori, unggah, like (JSON), hapus milik sendiri.
- **Selesai bila:** kenangan `menunggu` tidak terlihat anggota lain; EXIF hilang dari file tersimpan.

### Fase IC-5: Profil, notifikasi, dan administrasi anggota (S–M)

- Foto profil (crop persegi di klien, di-re-encode di server), halaman riwayat.
- Dashboard: daftar anggota, blokir, koreksi stempel via ledger.
- Email transaksional (verifikasi, status klaim) bila keputusan 3 dan 5 di bagian 9 sudah ada. Pakai `QUEUE_CONNECTION=database` yang sudah terpasang.

### Fase IC-6: Polish, QA, dan rilis (M)

- Audit aksesibilitas (bagian 8), uji di HP asli (kamera QR, layar kecil), uji beban check-in.
- Tambahkan `/inner-circle` ke `sitemap.xml`; `Disallow: /inner-circle/app` ke `robots.txt` (`SeoController`).
- Hapus seluruh data/kredensial demo; pastikan demo seeder hanya jalan di `local`.
- Halaman Syarat & Privasi; cek `storage:link` dan izin folder `storage/`.

## 11. Skenario uji wajib (PHPUnit feature test)

**Check-in**

- Token benar → +1 stempel, ledger dan `stempel_total` konsisten.
- Token salah / sudah diganti admin → ditolak, stempel tetap.
- Check-in kedua untuk event yang sama → ditolak; dua request bersamaan tetap hanya 1 stempel.
- Event `private`, tanpa venue, `draft`, atau di luar jendela waktu → ditolak.
- Tamu (belum login) → dialihkan ke halaman masuk.

**Hadiah**

- Stempel di bawah ambang → klaim ditolak.
- Klaim ganda untuk hadiah yang sama → ditolak.
- Tahap 50 tanpa pilihan A/B → validasi gagal.
- Loncat status (`diajukan` langsung `selesai`) → ditolak.

**Komunitas**

- Kenangan `menunggu`/`ditolak` tidak muncul di galeri anggota lain.
- Anggota tidak bisa menghapus kenangan milik orang lain.
- Like ganda tidak menambah hitungan; caption berisi HTML tampil sebagai teks.

**Akses**

- Sesi anggota tidak bisa membuka `/dashboard`; sesi admin tidak otomatis jadi anggota.
- `/dashboard/inner-circle/*` tanpa login admin → ke halaman login.

## 12. Daftar file yang akan dibuat atau diubah

**Baru**

- `app/Models/member.php`, `ic_event.php`, `ic_checkin.php`, `ic_stempel_ledger.php`, `ic_reward.php`, `ic_claim.php`, `ic_claim_log.php`, `ic_broadcast.php`, `ic_memory.php`
- `app/Http/Controllers/InnerCircle/` (auth, home, rewards, checkin, community, profile) dan `app/Http/Controllers/Dashboard/InnerCircle/` (event, hadiah, klaim, broadcast, kenangan, anggota)
- `app/Services/CheckinService.php`, `app/Services/ClaimService.php`, `app/Support/ImageProcessor.php`
- `app/Http/Requests/` (RegisterMember, Checkin, ClaimReward, StoreMemory), `app/Policies/` (Memory, Claim)
- `app/Console/Commands/` penutup event otomatis, dijadwalkan di `routes/console.php`
- `database/migrations/*` (9 tabel bagian 5), `database/seeders/innerCircleSeeder.php` (+ demo seeder khusus `local`)
- `resources/views/template/innerCircleLayout.blade.php`, `resources/views/inner-circle/*`, `resources/views/pages/dashboard-pages/inner-circle/*`, `resources/views/components/inner-circle-teaser.blade.php`
- `resources/css/inner-circle.css`, `resources/js/inner-circle.js`

**Diubah**

- `routes/web.php`, `routes/console.php`, `config/auth.php`, `vite.config.js`, `database/seeders/DatabaseSeeder.php`
- `resources/views/components/navbar.blade.php`, `resources/views/pages/home.blade.php`
- `resources/views/components/dashboard/navbar.blade.php` (1 menu baru)
- `app/Http/Controllers/SeoController.php` (sitemap + robots)
- `app/Http/Controllers/dashboardController.php` dan `profileController.php` (pakai `ImageProcessor`)
