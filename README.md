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

- Review ulang semua halaman CMS biar konsisten (spacing, warna, ukuran
  card, empty-state).
- Test end-to-end: tambah/edit/hapus data di tiap modul, cek preview
  match dengan tampilan asli di homepage/`/profile`.
- Cek responsif mobile buat layout form-kiri/preview-kanan (kemungkinan
  preview pindah ke bawah form di layar kecil, sama seperti pola Banner
  sekarang: `grid-cols-1 lg:grid-cols-2`).

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
- [ ] Fase 7 — Polish & QA

_Login sudah oke, tidak masuk scope revamp ini._
