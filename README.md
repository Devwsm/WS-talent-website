# WS Talent Website — CMS Revamp

Website & CMS internal untuk Whisnu Santika (Laravel). Dokumen ini berisi
hasil audit kondisi CMS saat ini dan rencana revamp dashboard sampai selesai.

---

## 1. Tujuan Revamp

1. **Konsistensi tampilan** — semua halaman CMS pakai 1 pola visual yang sama
   (dashboard home & Banner udah lebih rapi, sisanya masih gaya lama).
2. **Form kiri, Preview kanan** — setiap halaman tambah/edit data nampilin
   preview real-time di kanan, dan preview-nya harus **niru tampilan asli**
   di halaman utama (bukan mockup kasar), supaya staff langsung tahu hasil
   akhirnya kayak gimana sebelum submit.
3. **Selesaikan modul Profile** — bagian yang paling belum lengkap, banyak
   section masih di-comment dan datanya hardcode di blade, bukan dari CMS.

---

## 2. Audit Kondisi Saat Ini

### Pola desain yang sudah ada (jadi acuan/basis)

- `pages/dashboard.blade.php` (Home dashboard) & `components/dashboard/card/card.blade.php`
  — sudah pakai bahasa desain baru: `rounded-3xl border border-white/10 bg-white/3`,
  dekorasi blob blur, komponen `card` reusable dengan tombol "Kelola", empty-state komponen.
- `pages/dashboard-pages/banner.blade.php` — satu-satunya halaman CRUD yang
  sudah punya panel **preview**, tapi ada 2 masalah:
    1. **Posisi kebalik** — preview ada di **kiri** (sticky), form di **kanan**.
       Maunya form di kiri, preview di kanan.
    2. **Preview-nya "sakah"/nggak akurat** — dibuat mockup palsu (browser chrome
        - tiruan navbar), padahal tampilan asli banner di homepage cuma gambar
          full-width `rounded-lg` polos di atas video header
          (`components/banner.blade.php`). Jadi preview yang ada sekarang
          **menyesatkan**, bukan representasi asli.

### Halaman yang MASIH gaya lama (belum ada preview sama sekali)

Semua di bawah ini masih pakai `bg-black/80 ... rounded-lg` dan list data
mentah tanpa card/preview:

| Halaman                               | Controller            | Komponen publik yang harus ditiru                                                             | Catatan                                                            |
| ------------------------------------- | --------------------- | --------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| Header (`header.blade.php`)           | `dashboardController` | `components/videos.blade.php` (hero full-screen swiper + overlay teks + tombol "Watch Video") | Paling kompleks: ada color picker, image, & background image/video |
| Albums (`albums.blade.php`)           | `dashboardController` | `components/albums.blade.php` (swiper cover kotak, link ke Spotify)                           | Simple                                                             |
| News (`news.blade.php`)               | `dashboardController` | `components/news.blade.php` (card gambar 1:1 + judul besar + tombol "Read more")              | Pakai Quill editor buat deskripsi, perlu preview render HTML       |
| Merchandise (`merchandise.blade.php`) | `dashboardController` | `components/merchandise.blade.php` (swiper cover kotak, link ke marketplace)                  | Simple, mirip Albums                                               |

### Halaman Profile — paling belum selesai

`pages/dashboard-pages/profile.blade.php` cuma aktif untuk **Statistik** dan
**Highlight**. Section lain ada di file tapi **di-comment semua**:
`profile-card`, `genre`, `bio`, `collab`, `media-coverage`, `booking`,
`media-sosial`.

Setelah dicek ke `components/profile/profile-full.blade.php` (halaman publik
`/profile`), ternyata semua section itu **memang tampil di web**, tapi
kontennya **hardcode langsung di blade**, bukan dari database:

- Hero (foto, judul "DJ & Producer", nama, tagline)
- Genre tags (Indonesian Bounce, EDM, dst)
- Bio (3 paragraf)
- Kolaborasi (Dipha Barus, Cinta Laura, dst)
- Media coverage (Suara.com, iNews, dst)
- Booking & kontak (3 email)
- Social links (Instagram, TikTok, YouTube, Spotify)

Dicek juga ke `database/migrations/` — **memang belum ada tabel** untuk
genre/bio/kolaborasi/media coverage/booking/social links. Cuma ada tabel
`statistik` dan `highlight`. Jadi ini bukan cuma soal UI CMS yang belum
jadi, tapi datanya sendiri belum punya model/migration/controller.

---

## 3. Pola Baru yang Dipakai di Semua Halaman CRUD

Supaya konsisten, dari Banner sampai Profile pakai kerangka yang sama:

```
┌─────────────────────────────────────────────────────┐
│  Judul halaman + deskripsi singkat                   │
├───────────────────────────┬───────────────────────────┤
│  FORM (kiri)               │  PREVIEW (kanan, sticky)  │
│  - input sesuai field      │  - render ulang komponen  │
│  - validasi & error inline │    publik asli (bukan     │
│                             │    mockup baru), diisi    │
│                             │    dari nilai form secara │
│                             │    real-time via JS       │
├───────────────────────────┴───────────────────────────┤
│  List data tersimpan (card grid, pakai card.blade.php) │
└─────────────────────────────────────────────────────┘
```

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

### Fase 3 — Albums

- Redesign ke pola form-kiri/preview-kanan.
- Preview niru `components/albums.blade.php` (cover kotak 1:1, hover
  scale, link Spotify).
- List data pakai card grid (mirip pola Album/Merchandise di Home dashboard
  yang sudah ada).

### Fase 4 — Merchandise

- Sama seperti Albums (paling mirip & paling cepat dikerjakan).

### Fase 5 — News

- Redesign ke pola form-kiri/preview-kanan.
- Preview niru `components/news.blade.php` (card gambar 1:1 + sumber +
  tanggal + judul besar + deskripsi + tombol "Read more").
- Karena deskripsi pakai Quill (rich text), preview harus render HTML-nya
  (bukan teks mentah) supaya representatif.

### Fase 6 — Profile (paling besar, dipecah jadi sub-fase)

1. **Data & backend dulu**: bikin migration + model + controller (atau
   extend `profileController`) untuk field yang masih hardcode:
    - Hero: foto, judul singkat, nama, tagline
    - Genre tags (bisa multi, simpan sebagai list)
    - Bio (rich text, bisa multi paragraf)
    - Kolaborasi (nama, role/deskripsi)
    - Media coverage (nama media)
    - Booking & kontak (label + email, per kategori)
    - Social links (platform + URL)
    - _(Statistik & Highlight sudah ada, tinggal dirapikan UI-nya)_
2. **UI dashboard**: bongkar section yang di-comment, bangun form kiri /
   preview kanan buat tiap section, preview niru
   `components/profile/profile-full.blade.php` per bagian (Hero, Genre,
   Bio, Stats, Highlight, Kolaborasi, Media Coverage, Booking, Social).
3. Pastikan `profile-teaser.blade.php` (versi ringkas di homepage) ikut
   konsisten kalau ada field yang dipakai bareng (foto, tagline, genre,
   bio singkat, stats).

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
- [ ] Fase 3 — Albums
- [ ] Fase 4 — Merchandise
- [ ] Fase 5 — News
- [ ] Fase 6 — Profile (backend + UI)
- [ ] Fase 7 — Polish & QA

_Login sudah oke, tidak masuk scope revamp ini._
