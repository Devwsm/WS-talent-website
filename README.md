Prinsip preview: **pakai ulang markup & class dari komponen publik yang
sudah ada** (`components/banner.blade.php`, `components/videos.blade.php`,
`components/albums.blade.php`, dst), cuma dibungkus kecil (mis. di dalam
"frame" browser mini kalau perlu konteks), lalu di-update via JS
(`input`/`change` listener + `FileReader` buat gambar) — pola JS-nya udah
ada contohnya di `banner.blade.php`, tinggal direplikasi & diperbaiki.

---

## 4. Rencana Eksekusi (Fase)

### ✅ Fase 1 — Perbaiki Banner (SELESAI)

- [x] Tukar posisi: form ke kiri, preview ke kanan.
- [x] Ganti isi panel preview dari mockup "browser chrome" jadi render ulang
      markup asli `components/banner.blade.php` (gambar full-width rounded,
      tanpa overlay teks/navbar palsu), supaya 1:1 sama kayak yang tampil di
      homepage. Nama & link banner sekarang ditandai jelas sebagai metadata
      admin (tidak ikut tayang di web).
- File yang diubah: `resources/views/pages/dashboard-pages/banner.blade.php`

### ✅ Fase 2 — Header (SELESAI)

- [x] Redesign halaman ke pola card `rounded-3xl border-white/10 bg-white/3`,
      form kiri / preview kanan (sticky), konsisten sama Banner.
- [x] Preview kanan niru `components/videos.blade.php`: badge warna + judul,
      gambar header di tengah, nama, deskripsi, tombol "Watch Video" dengan
      warna sesuai `header_color` — update live saat color picker/input diubah.
- [x] Preview background (image/video) ikut berubah saat file dipilih —
      diganti dari `MediaPreview` class lama (preview file mentah terpisah)
      jadi langsung nge-update panel preview utama, dengan validasi ukuran
      file tetap jalan (1MB gambar / 25MB video).
- [x] List data di bawah dirapikan pakai card grid (thumbnail + badge +
      nama + deskripsi), bukan list mentah lagi.
- File yang diubah: `resources/views/pages/dashboard-pages/header.blade.php`

### ✅ Fase 3 — Album (SELESAI)

- [x] Redesign ke pola form-kiri/preview-kanan, konsisten sama Banner & Header.
- [x] Preview niru `components/albums.blade.php` — cover kotak (aspect-square)
      di atas **background putih** (bukan hitam), karena section Album di
      homepage memang berlatar putih. Ini detail akurasi yang sebelumnya
      kelewat.
- [x] List data pakai card grid (mirip pola Album di Home dashboard).
- File yang diubah: `resources/views/pages/dashboard-pages/albums.blade.php`

### ✅ Fase 4 — Merchandise (SELESAI)

- [x] Sama seperti Album — pola form-kiri/preview-kanan, preview niru
      `components/merchandise.blade.php` (cover kotak di atas background putih).
- [x] List data pakai card grid.
- File yang diubah: `resources/views/pages/dashboard-pages/merchandise.blade.php`

### ✅ Fase 5 — News (SELESAI)

- [x] Redesign ke pola form-kiri/preview-kanan, konsisten sama modul lain.
- [x] Preview niru `components/news.blade.php` — 1 kartu berita persis
      (cover kotak, baris sumber+tanggal, judul besar, deskripsi, tombol
      "Read more"), di atas background putih/abu terang sesuai section asli.
- [x] Karena deskripsi pakai Quill (rich text), preview render **HTML asli**
      dari editor secara live (event `text-change`), bukan teks mentah — jadi
      bold/italic/list/dll ikut kelihatan di preview persis kayak yang bakal
      tayang.
- [x] List data di bawah dirapikan pakai card grid (thumbnail + sumber +
      tanggal + judul), bukan list mentah lagi.
- File yang diubah: `resources/views/pages/dashboard-pages/news.blade.php`

### Fase 6 — Profile (paling besar, dipecah jadi sub-fase)

1. ✅ **Data & backend (SELESAI)**: migration + model + extend
   `profileController` untuk field yang masih hardcode. 7 tabel baru,
   ngikutin pola project ini (custom PK `id_<nama_tabel>`, model =
   nama tabel, tanpa Form Request class, validasi inline kayak
   statistik/highlight):
    - `profile_hero` (singleton — foto, judul_singkat, nama, tagline).
      Upload foto pakai pola WebP yang sama kayak Banner
      (`convertToWebP` di-duplicate ke `profileController`, bukan
      di-extract ke trait, biar konsisten sama pola tiap controller
      berdiri sendiri di project ini).
    - `genre` (multi — nama_genre)
    - `bio` (singleton — konten, longText buat rich text Quill)
    - `collab` (multi — nama, role)
    - `media_coverage` (multi — nama_media)
    - `booking` (multi — label, email)
    - `media_sosial` (multi — platform, url)
    - _(Statistik & Highlight sudah ada duluan, tinggal dirapikan UI-nya)_
    - Route baru semua di-throttle `10,1` di request `tambah`, ngikutin
      pola statistik/highlight/banner.
    - `database/seeders/profileSeeder.php` di-extend: 7 tabel baru
      di-seed dari konten yang **saat ini hardcode** di
      `profile-full.blade.php`, supaya begitu UI Fase 6 langkah 2 jadi,
      datanya langsung ada isinya (bukan kosong).
    - Hero & Bio sengaja dibikin **singleton** (query pakai `::first()`,
      controller pakai `updateOrCreate`-style: ambil baris pertama atau
      bikin baru) — beda dari tabel lain yang emang multi-row. Alasannya:
      cuma ada 1 artis/1 bio, jadi nggak perlu tambah/hapus, cukup 1
      endpoint simpan (`hero.simpan`, `bio.simpan`).
    - File yang diubah/ditambah: 7 file migration baru di
      `database/migrations/`, 7 model baru di `app/Models/`,
      `app/Http/Controllers/profileController.php` (tambah import +
      method baru), `routes/web.php` (tambah route group Profile),
      `database/seeders/profileSeeder.php` (tambah seed data).
    - **Sudah dijalankan** via `migrate:fresh --seed` — aman, 7 tabel baru
      kebentuk dan ke-seed sesuai isi hardcode `profile-full.blade.php`.
      `optimize:clear` juga sudah dijalankan. **Manual E2E masih belum
      bisa** karena UI-nya belum ada (baru backend/API-level, endpoint
      cuma bisa dites lewat request langsung, belum ada form).
2. ✅ **UI dashboard (SELESAI)**: semua section di `profile.blade.php`
   sudah dibongkar dari comment dan dibangun — Hero (`profile-card`),
   Genre, Bio, Statistik, Highlight, Kolaborasi (`collab`), Media
   Coverage, Booking & Kontak, Media Sosial — masing-masing dengan
   komponen dashboard + modal edit + tombol hapus sendiri
   (`components/profile/dashboard/*.blade.php`,
   `components/dashboard/profile/modal-edit-*.blade.php`,
   `btn-hapus-*.blade.php`). Statistik & Highlight yang sebelumnya
   sudah ada UI-nya ikut dirapikan ulang biar konsisten sama pola
   section baru.
3. ✅ **Sinkronisasi publik (SELESAI)**: pas dicek, bukan cuma
   `profile-teaser.blade.php` yang masih hardcode — `profile-full.blade.php`
   (halaman `/profile` itu sendiri) juga **belum tersambung sama sekali**
   ke 7 tabel baru Fase 6, padahal ini justru halaman yang jadi acuan
   preview dashboard. Yang dikerjakan:
    - `homeController@index` & `homeController@profile` di-extend supaya
      fetch `profile_hero`, `genre`, `bio`, `collab`, `media_coverage`,
      `booking`, `media_sosial` dan dioper ke view (sebelumnya cuma
      `statistik`/`highlight`).
    - `profile-teaser.blade.php` (homepage): foto/judul/nama/tagline dari
      `profile_hero`, genre tags dari `genre`, bio singkat dipotong
      (`Str::limit` + `strip_tags`, 220 char) dari `bio.konten` biar tetap
      ringkas — bio lengkap (rich text Quill) sengaja nggak ditampilkan
      utuh di teaser.
    - `profile-full.blade.php` (halaman `/profile`): Hero, Genre, Bio
      (render HTML asli dari Quill), Kolaborasi, Media Coverage, Booking &
      Kontak (email jadi `mailto:`), dan Social links semua diganti dari
      hardcode ke data dinamis. Tiap list pakai `@forelse`/`@empty` dengan
      pesan "Belum ada data ..." kalau tabelnya kosong.
    - Foto Hero dipakai lewat `Storage::url('profile-hero/' . $hero->foto)`,
      konsisten sama pola yang dipakai Banner; ada fallback ke logo lama
      kalau `profile_hero` belum ada baris data.
    - File yang diubah: `app/Http/Controllers/homeController.php`,
      `resources/views/components/profile/profile-teaser.blade.php`,
      `resources/views/components/profile/profile-full.blade.php`.
    - Seeder (`profileSeeder.php`) sudah ngisi ketujuh tabel dari konten
      hardcode lama, jadi secara data harusnya tampil identik dengan
      sebelum revamp — fallback hardcode di Blade cuma jaga-jaga kalau
      tabel kosong. **Belum dites manual di browser**, cuma dicek lewat
      kode & seeder.

### Fase 7 — Polish & QA

- [x] Review konsistensi pola card (`rounded-3xl border-white/10 bg-white/3`,
      form kiri/preview kanan sticky, `grid-cols-1 lg:grid-cols-2`) di semua
      halaman CMS (Banner, Header, Album, Merchandise, News, tiap section
      Profile) — semua sudah konsisten, tidak ada penyimpangan.
- [x] Review validasi & flash message di `profileController` (7 modul baru
      Fase 6 + Statistik/Highlight lama) — pola `validate → save/update`,
      try/catch di delete, dan pesan sukses/error semuanya konsisten. Semua
      route sudah `throttle:10,1` di aksi tambah, nama route match sama yang
      dipanggil di view.
- [x] **Audit "preview = tampilan asli" di CMS Profile** (fokus permintaan
      user) — ditemukan & diperbaiki 3 preview yang MELENCENG dari tampilan
      publik: - **Statistik** & **Highlight**: preview-nya sebelumnya di-desain ulang
      jadi gaya "pill" (comment kode bilang "biar 1 tipe sama
      Genre/Media Coverage/Media Sosial"), padahal tampilan asli di
      `profile-full.blade.php` pakai **grid card** (Statistik: angka besar - label platform; Highlight: baris place/description di kiri, year
      di kanan). Preview sekarang diganti jadi replika markup card asli,
      termasuk live-draft JS-nya. - **Kolaborasi**: sama, preview pill diganti jadi grid card 2 kolom
      (nama bold + role) persis section Kolaborasi di halaman publik. - Genre, Media Coverage, Booking & Kontak, Media Sosial, Hero, Bio —
      dicek juga, semuanya sudah akurat (cuma Bio yang di-samain
      spacing-nya, `gap-2` → `gap-3`, biar match persis). - Catatan: "List data tersimpan" (bagian manajemen di bawah tiap
      form, dengan tombol edit/hapus) sengaja TETAP pakai gaya pill —
      itu bukan preview tampilan publik, itu UI manajemen data yang
      konsisten dipakai di semua modul. - File yang diubah: `resources/views/components/profile/dashboard/
statistik.blade.php`, `highlight.blade.php`, `collab.blade.php`,
      `bio.blade.php`.
- [x] Test end-to-end manual di browser: tambah/edit/hapus data di tiap
      modul — sudah dites user, aman.
- [x] Cek responsif mobile — pola `grid-cols-1 lg:grid-cols-2` sudah
      dites user, aman.
- [x] **Reskin semua modal "Edit"** (permintaan user): seluruh 11
      file `modal-edit-*.blade.php` (5 modul lama: Banner, Header,
      Merchandise, Album, News; 7 modul Profile: Genre, Statistik,
      Highlight, Kolaborasi, Media Coverage, Booking, Media Sosial) masih
      pakai tema lama peninggalan sebelum revamp — modal putih terang
      (`bg-white`, header `bg-blue-950`, input `border` abu-abu, tombol
      `bg-blue-800`/`bg-gray-400`) yang kontras banget sama tampilan dashboard
      sekarang yang serba gelap. Semua digantI jadi 1 tema gelap yang
      konsisten: - Panel: `rounded-3xl border border-white/10 bg-black`, header &
      footer beda lapisan (`bg-white/3 border-white/10`), ditambah
      tombol close (×) di header biar ada cara tutup lain selain
      "Batal". - Input/textarea: `bg-white/5 border-white/15 text-white`, focus
      state `border-red-900 ring-red-900` (brand accent, bukan biru
      lagi). - Input file: gaya dashed dengan tombol upload merah (`file:bg-red-950`),
      konsisten sama pola upload di Hero/Banner dashboard. - Tombol submit: `bg-red-950 hover:bg-red-900` (sebelumnya biru),
      tombol batal: outline putih transparan. - Tombol trigger "Edit" di Banner/Header/Merchandise/Album/News
      (sebelumnya `bg-blue-950`) disamain jadi `bg-[#5E0006]`, match
      sama tombol Hapus di sebelahnya yang emang udah pakai warna brand. - **Responsif**: panel modal sekarang `max-h-[90vh] flex flex-col`
      dengan body form `overflow-y-auto` — header & footer tetap
      nempel di atas/bawah, isi form (terutama modal Header yang
      isinya banyak: color picker + 4 field + 2 upload gambar/video)
      auto-scroll di dalam modal kalau kepanjangan buat layar pendek,
      gak bikin modal kepotong/overflow keluar viewport. - Nemu & sekalian dibenerin 2 bug kecil pas reskin: (1) modal edit
      Statistik ada karakter `\` nyasar sebelum tag `<button>` (bakal
      nongol sebagai teks aneh di halaman), (2) modal edit Header pakai
      `$item->id_highlight` (bukan `id_header`) buat semua DOM id color
      picker — bikin preview warna cuma jalan bener kalau baru ada 1
      header, rusak begitu ada 2+. - File yang diubah (12): `resources/views/components/dashboard/
modal-edit-{banner,header,merchandise,albums,news}.blade.php` dan
      `resources/views/components/dashboard/profile/modal-edit-
{genre,statistik,highlight,collab,media-coverage,booking,media-sosial}.blade.php`.
      **Belum dites manual di browser** — perlu dicoba buka tiap modal edit
      (termasuk di layar HP) buat pastikan tampilan & scroll-nya sesuai.

---

## 5. Status

- [x] Fase 1 — Banner (swap posisi + preview akurat) ✅
- [x] Fase 2 — Header (redesign + preview hero live) ✅
- [x] Fase 3 — Album (redesign + preview di background putih) ✅
- [x] Fase 4 — Merchandise (redesign + preview di background putih) ✅
- [x] Fase 5 — News (redesign + preview Quill HTML live) ✅
- [x] Fase 6 langkah 1 — Profile: migration + model + controller + route + seeder ✅
- [x] Fase 6 langkah 2 — Profile: UI dashboard (form kiri/preview kanan per section) ✅
- [x] Fase 6 langkah 3 — Profile: sinkronisasi publik (teaser + halaman full) ✅
- [x] Fase 7 — Polish & QA: konsistensi, audit preview, dan reskin semua modal edit selesai; E2E manual & cek mobile sudah dites user ✅
- [x] Fase 8 — SweetAlert2 ✅
- [x] Fase 9 — SEO (title/OG/Twitter/canonical/JSON-LD/sitemap dinamis) &
      Performance (LCP hero image, pagination dashboard, limit query News
      homepage) ✅ — belum dites manual, lihat checklist QA di Fase 9

_Login sudah oke, tidak masuk scope revamp ini._

---

## Fase 8 — SweetAlert2 (SELESAI)

### Tujuan

Mengganti browser-native `alert()`/`confirm()` dan flash message dashboard yang
sebelumnya berupa banner HTML menjadi feedback UI yang konsisten dengan tema
dashboard.

### Implementasi

- SweetAlert2 digunakan sebagai dependency frontend melalui Vite/NPM.
- Helper global dibuat di `resources/js/sweetalert.js`.
- `window.Swal` dipakai untuk dialog dan `window.Toast` untuk notifikasi ringan.
- Tema SweetAlert disamakan dengan dashboard: background gelap, border putih
  transparan, dan accent merah `#5E0006`.
- Flash `session('success')` sekarang tampil sebagai success toast.
- Validation errors Laravel (`$errors`) tampil sebagai dialog error.
- Flash `error`, `warning`, dan `info` juga didukung secara global.
- Semua tombol hapus dashboard menggunakan `data-swal-confirm` sehingga
  konfirmasi delete tidak lagi bergantung pada browser `confirm()`.
- Setelah user mengonfirmasi delete, muncul loading state `Menghapus...` sebelum
  form DELETE dikirim.
- Route, controller, validation, database operation, dan HTTP method CRUD tidak
  diubah; perubahan ini fokus pada feedback UI di sisi frontend.

### File utama

- `package.json`
- `resources/js/app.js`
- `resources/js/sweetalert.js`
- `resources/views/components/success.blade.php`
- `resources/views/components/errors.blade.php`
- `resources/views/components/dashboard/btn-hapus-*.blade.php`
- `resources/views/components/dashboard/profile/btn-hapus-*.blade.php`

### Catatan dependency

SweetAlert2 dipasang dengan `npm install sweetalert2`. Dependency ini tidak
memiliki dependency runtime tambahan dan digunakan melalui bundle Vite.

### QA

- Pastikan `npm install` sudah dijalankan setelah perubahan dependency.
- Jalankan `npm run dev` untuk development atau `npm run build` untuk production.
- Uji create/update: success toast muncul setelah redirect.
- Uji validation: error validation muncul sebagai dialog.
- Uji delete: confirmation SweetAlert muncul, tombol Batal tidak mengirim form,
  tombol Ya, hapus mengirim DELETE request dan menampilkan loading state.

---

## Fase 9 — SEO & Performance Improvements (SELESAI)

### Tujuan

Menindaklanjuti hasil review teknis (Lighthouse-style audit) di dua area:
SEO (meta tags, sitemap, structured data) dan Performance (LCP hero image,
query yang nggak dibatasi).

### A. SEO

- **Title & meta description dinamis per halaman.** Sebelumnya
  `<title>` hardcode "Whisnu Santika" di semua halaman termasuk `/profile`.
  Sekarang `template/layout.blade.php` punya default SEO (title, meta
  description, og:image) yang bisa di-override tiap halaman lewat
  `@section('title', ...)`, `@section('meta_description', ...)`,
  `@section('og_image', ...)`. Home & Profile sudah diisi masing-masing.
- **Open Graph & Twitter Card.** Ditambahkan di `<head>` layout
  (`og:title`, `og:description`, `og:image`, `og:url`, `og:site_name`,
  `twitter:card`, dst) — supaya link yang di-share ke WA/IG/Twitter
  nampilin preview card yang benar, bukan kosong/generic.
- **Canonical URL** (`<link rel="canonical">`) ditambahkan di layout,
  pakai `url()->current()`.
- **Structured data (JSON-LD).** Layout punya `@stack('schema')`; Home
  push schema `MusicGroup` (nama, genre, image, sameAs ke media sosial),
  Profile push schema `Person` (nama, jobTitle, deskripsi dari `bio.konten`
  yang di-`strip_tags` dulu, sameAs dari tabel `media_sosial`).
- **`robots.txt` & `sitemap.xml` jadi dinamis**, sebelumnya `robots.txt`
  cuma file statis kosong tanpa referensi sitemap. Sekarang keduanya
  digenerate lewat `SeoController` (routes `GET /robots.txt` dan
  `GET /sitemap.xml`), jadi otomatis ikut domain aktif (`APP_URL`) dan
  `lastmod` di sitemap ambil dari data yang paling baru diubah di tiap
  halaman. File statis lama `public/robots.txt` dihapus (kalau nggak,
  Apache bakal serve file statisnya duluan dan route baru nggak kepanggil
  — lihat `RewriteCond %{REQUEST_FILENAME} !-f` di `public/.htaccess`).
- **`APP_NAME`** diganti dari default `Laravel` jadi `Whisnu Santika` di
  `.env.example` (mempengaruhi antara lain `MAIL_FROM_NAME`). **Perlu
  disamain manual di `.env` asli di server** — file `.env` nggak ikut
  dikirim di batch ini karena isinya kredensial database, tinggal ubah
  1 baris: `APP_NAME=Laravel` → `APP_NAME="Whisnu Santika"`.

### B. Performance

- **Hero image `/profile` diganti dari `loading="lazy"` jadi
  `loading="eager" fetchpriority="high"`** — ini elemen LCP (Largest
  Contentful Paint) halaman itu, lazy-load malah nunda render elemen
  paling penting.
- **Homepage News dibatasi ke 8 item terbaru** (`news::latest()->take(8)`),
  sebelumnya `news::all()`. Section News di homepage render sebagai grid
  statis (bukan carousel kayak Album/Merchandise), jadi kalau dibiarkan
  `::all()` bakal makin berat seiring jumlah berita nambah. Album &
  Merchandise homepage TETAP `::all()` karena render-nya lewat Swiper
  carousel, jadi nggak masalah walau datanya banyak.
- **Pagination di dashboard admin** untuk 3 modul yang datanya paling
  cepat bertambah — News, Album, Merchandise — diganti dari `::all()`
  jadi `::latest()->paginate(9)->withQueryString()`. Komponen pagination
  baru: `components/dashboard/pagination.blade.php` (gaya konsisten sama
  card list lain, ada info "Halaman X dari Y" + tombol Sebelumnya/
  Selanjutnya). Badge jumlah data (`Berita Tersimpan (n)`, dst) diganti
  dari `->count()` (cuma hitung item di halaman aktif) jadi `->total()`
  (total keseluruhan data).
- **Belum dikerjakan** (di luar scope batch ini, butuh tooling
  kompresi video/ffmpeg yang nggak tersedia di shared hosting tanpa akses
  terminal): kompresi/adaptive bitrate untuk video header. Video sudah
  cukup dioptimasi dari sisi lazy-load per-slide (`preload="none"` untuk
  slide non-aktif), tapi ukuran file mentahnya sendiri belum dikompres —
  disarankan kompres manual sebelum upload (target di bawah ~5–8MB per
  video) pakai tool lokal (HandBrake/ffmpeg) sebelum upload ke dashboard.

### File yang diubah/ditambah

- **Baru**: `app/Http/Controllers/SeoController.php`,
  `resources/views/sitemap.blade.php`,
  `resources/views/components/dashboard/pagination.blade.php`
- **Diubah**: `resources/views/template/layout.blade.php`,
  `resources/views/pages/home.blade.php`,
  `resources/views/components/profile/profile-full.blade.php`,
  `app/Http/Controllers/homeController.php`,
  `app/Http/Controllers/dashboardController.php`,
  `resources/views/pages/dashboard-pages/{news,albums,merchandise}.blade.php`,
  `routes/web.php`, `.env.example`
- **Dihapus**: `public/robots.txt` (digantikan route dinamis)

### QA / yang masih perlu dites manual

- [ ] Buka `/robots.txt` dan `/sitemap.xml` di browser, pastikan XML/text-nya
      valid dan `Sitemap:` di robots.txt nunjuk ke URL yang bener (cek
      `APP_URL` di `.env` production sudah diisi domain asli, bukan
      `http://localhost`).
- [ ] Cek preview link share (paste URL home/profile ke chat WA/kolom
      compose Twitter) buat mastiin og:image & title muncul benar.
- [ ] Paste `/` dan `/profile` ke
      [Rich Results Test](https://search.google.com/test/rich-results)
      buat validasi JSON-LD.
- [ ] Dashboard News/Album/Merchandise: tambah data lebih dari 9 biar
      tombol pagination "Selanjutnya" muncul, cek tombol page berfungsi.
- [ ] Update `.env` production: `APP_NAME="Whisnu Santika"` dan pastikan
      `APP_URL` sudah domain asli (dipakai di canonical/OG/sitemap).
