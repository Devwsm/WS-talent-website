# WS Talent Website

Website publik profil artis **Whisnu Santika** beserta dashboard CMS-nya.

- **Stack:** Laravel 13 · PHP 8.3 · Tailwind CSS 4 · Vite · Swiper · SweetAlert2 · Bootstrap Icons
- **Database:** MySQL di `.env` lokal (`.env.example` masih bawaan SQLite, ganti `DB_CONNECTION=mysql`)
- **Publik:** `/` (home) dan `/profile`
- **CMS:** `/dashboard` (Banner, Headers, Profile, Warna Web, Albums, News, Merchandise)
- **Sesi, antrean, cache:** driver `database`

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run dev
```

---

# Rencana Penerapan: WS Inner Circle

> Status: **Fase 1 sampai 4 selesai**. Fase 1 sampai 3: kerangka UI, 7 layar anggota, dan panel admin 7 tab. Fase 4: akun anggota, check-in QR, dan stempel sudah tersambung ke database (Hadiah, Komunitas, dan tab admin Klaim, Hadiah, Pengumuman, Kenangan masih data contoh). Flag fitur mati secara bawaan. Berikutnya: Fase 5.
> Dibuat: 21 Oktober 2026.
> Acuan: `WS_Inner_Circle.html` (prototype native, satu file, data di `localStorage`) dan 19 screenshot UI di `inner-circle-ui-prototype.zip`.

## 1. Tujuan dan cara kerja

Menanam komunitas **WS Inner Circle** di website ini: penggemar mendaftar, mengumpulkan **stempel** dengan hadir di acara (check-in QR), membuka dan mengklaim **hadiah**, membaca **pengumuman**, dan berbagi **kenangan** (galeri foto). Tim WSM mengelolanya dari dashboard CMS yang sudah ada.

Urutan kerja: **UI dulu dengan data dummy, backend menyusul per fitur.**

| Fase | Isi                                                | Data     |
| ---- | -------------------------------------------------- | -------- |
| 1    | Fondasi UI (layout, CSS, komponen, flag fitur)     | Dummy    |
| 2    | UI anggota: 6 layar                                | Dummy    |
| 3    | UI admin di dashboard CMS                          | Dummy    |
| 4    | Anggota, check-in, dan stempel sungguhan (selesai) | Database |
| 5    | Hadiah dan klaim                                   | Database |
| 6    | Komunitas (pengumuman, kenangan) dan foto profil   | Database |
| 7    | Teaser di home, SEO, QA, rilis                     | Database |

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

Fase 1 sampai 3 berisi data dummy. Supaya tidak ikut aktif di produksi ketika kodenya naik (deploy manual pun):

- `config/inner_circle.php` dengan `aktif` = `env('INNER_CIRCLE_AKTIF', false)`.
- Middleware `icAktif` (didaftarkan sebagai alias di `bootstrap/app.php`, seperti `cekLogin`) mengembalikan 404 untuk semua route Inner Circle bila flag mati.
- Link di dashboard dan navbar publik hanya muncul bila flag menyala.
- `.env` produksi tetap `INNER_CIRCLE_AKTIF=false` sampai Fase 4 selesai dan dites. Lokal dan preview boleh `true`.

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

Selama Fase 1 sampai 3, `app/Support/InnerCircleDummy.php` mengembalikan objek (`(object) [...]`) dengan nama properti yang sama dengan kolom database nanti. Blade memakai `$x->properti` dan tidak tahu datanya dari mana. Mulai Fase 4, hanya controller yang berganti ke Eloquent; view tidak diubah.

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

### Fase 1: Fondasi UI (selesai)

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

### Fase 2: UI anggota (data dummy) (selesai)

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

### Fase 3: UI admin di dashboard CMS (data dummy) (selesai)

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

### Fase 4: Anggota, check-in, dan stempel sungguhan (selesai)

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
- Tab Klaim di dashboard masih memuat data contoh, tetapi lencana dan angka Ringkasan menampilkan 0 klaim sampai Fase 5.
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

### Fase 5: Hadiah dan klaim

File (urut): migration `ic_rewards`, `ic_claims`, `ic_claim_logs`; model; `ClaimService`; seeder 5 hadiah dari prototype; controller Hadiah dan Profil (bagian klaim); admin tab Hadiah dan Klaim; modal pilihan A/B.

Aturan:

- Klaim hanya bila `stempel_total >= target_stempel`, **sekali** per anggota per tahap, dalam transaksi dengan kunci baris supaya klik ganda tidak membuat dua klaim.
- Tahap 50 wajib memilih opsi; tersimpan di `opsi_dipilih`.
- Alur status: `diajukan` → `ditinjau` → `disetujui` → `selesai`, atau `ditolak`. Tiap perubahan dicatat di `ic_claim_logs` (siapa, kapan, catatan).
- Pemenuhan hadiah merchandise (diskon, akses awal) dilakukan manual: admin mengisi `catatan_admin` (mis. kode voucher) yang tampil di Profil anggota.

Cek hasil: tombol "Klaim" aktif hanya saat cukup stempel; klaim dobel ditolak; antrean admin menggerakkan status dan anggota melihat perubahannya.

### Fase 6: Komunitas dan foto profil

File (urut): migration `ic_broadcasts`, `ic_memories`, `ic_memory_likes`; model; `app/Support/ImageProcessor.php`; `MemoryPolicy` (didaftarkan manual lewat `Gate::policy` karena nama model huruf kecil); controller Komunitas dan Profil (foto); admin tab Pengumuman dan Kenangan; endpoint JSON untuk suka.

Aturan:

- Pengumuman: satu bisa ditandai **unggulan** (kartu besar "Kabar pilihan"), sisanya daftar. CTA mengarah ke `checkin`, `hadiah`, atau `komunitas`.
- Kenangan: caption maks 240 karakter, tempat maks 65, kategori Festival / Klub / Pribadi, foto opsional maks 4 MB (WebP). Tanpa foto, kartu memakai sampul berwarna + ikon sesuai kategori seperti di prototype.
- Suka bersifat toggle per anggota (`unique` pada pasangan anggota dan kenangan). Hanya pemilik yang bisa menghapus kenangannya; admin bisa menyembunyikan.
- Foto profil JPG/PNG/WebP maks 5 MB; tombol Simpan aktif setelah memilih foto, tombol Hapus mengembalikan avatar bawaan.
- Teks pengguna di-escape (prototype sudah memakai `escapeText`; di Blade cukup `{{ }}`). Tidak ada HTML bebas dari anggota.

Cek hasil: unggah kenangan dengan foto HP (termasuk yang miring/EXIF) tampil benar; suka dan hapus berfungsi; kenangan yang disembunyikan admin hilang dari galeri.

### Fase 7: Integrasi situs publik, SEO, QA, rilis

- Komponen teaser `inner-circle-teaser` di `pages/home.blade.php` dan link "Inner Circle" di `components/navbar.blade.php` (desktop dan menu mobile), hanya bila flag menyala. Teaser mengikuti tema situs (`ThemeColorComposer`).
- `SeoController@robots`: tambahkan `Disallow: /inner-circle`.
- QA lintas perangkat: kamera di Safari iOS dan Chrome Android, foto HP, layar 375 px, `prefers-reduced-motion`, kontras, target sentuh 44 px.
- Pastikan langkah deploy menjalankan `npm run build` sehingga dua entry Vite baru ikut ter-build.
- Rilis: set `INNER_CIRCLE_AKTIF=true` di produksi setelah event asli dan hadiah terisi, lalu `php artisan migrate`, `config:clear`, `view:clear`.

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
- **Verifikasi email dan "lupa kata sandi":** tidak ada di prototype dan butuh SMTP (`config/mail.php`). Default: ditunda sampai setelah Fase 6.
- **Avatar bawaan** sebelum anggota unggah foto: emoji 😎 seperti prototype, atau inisial nama.
- **Jendela check-in:** default D 12.00 sampai D+1 06.00 WIB. Sesuaikan bila jam show WSM berbeda.
- **Halaman `/inner-circle` untuk pengunjung belum login:** default dialihkan ke `/inner-circle/masuk`. Landing publik penuh (penjelasan program, daftar hadiah) belum ada di prototype; bisa jadi fase tambahan.
- **Ikon:** pakai set gambar berlisensi terbuka (bagian 3.2) atau ilustrasi buatan tim WSM.
- **Data event:** 50 baris di prototype adalah data contoh tentatif (33 Club, 14 Festival, 3 Private Event; 2 tanpa venue). Jadwal asli diisi tim WSM lewat CMS atau impor CSV.

## 9. Di luar cakupan

- Sinkron otomatis dengan Bandsintown (section Tour di home tetap widget).
- QR yang berputar otomatis dan verifikasi lokasi (GPS). Mitigasi awal: jendela waktu, token per event, limiter per anggota.
- Notifikasi (email, WhatsApp, Instagram) dan pesan antar anggota.
- Integrasi akun atau voucher dengan Mavnus. Hadiah merchandise dipenuhi manual lewat antrean klaim.
- Perubahan pada login admin, controller CMS yang sudah ada, dan tema situs publik.
