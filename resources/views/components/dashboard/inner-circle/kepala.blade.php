{{--
    Kepala bersama semua halaman CMS Inner Circle: judul halaman (satu <h1> per halaman), catatan
    "data contoh", dan skrip kecil yang dipakai bersama (notifikasi "segera aktif" dan pembuat ikon untuk
    preview). Navigasi antarhalaman ada di navbar bawah (components/dashboard/navbar), bukan di sini.

    Variabel:
    - $aktif       : kunci halaman yang sedang dibuka (lihat config('inner_circle.tab_admin'))
    - $judul       : teks <h1>
    - $deskripsi   : teks di bawah judul
    - $pakaiCss    : opsional (bawaan true). false bila halaman tidak punya preview bergaya anggota
--}}
@php
    $tabs = config('inner_circle.tab_admin');
    $fase = $tabs[$aktif]['fase'];
    // Tab yang fasenya sudah tersambung ke database tidak lagi memakai catatan "data contoh".
    $dataContoh = $fase > config('inner_circle.fase_tersambung');
@endphp

@if ($pakaiCss ?? true)
    @push('head')
        {{-- CSS area anggota hanya dimuat di tab yang punya preview. Semua class-nya di bawah .ic-app. --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">
        @vite('resources/css/inner-circle.css')
    @endpush
@endif

<div>
    <p class="text-xs font-semibold uppercase tracking-widest text-white/40 mb-1">{{ ic_program() }}</p>
    <h1 class="text-2xl lg:text-3xl font-bold uppercase">{{ $judul }}</h1>
    <p class="text-white/50 mt-1">{{ $deskripsi }}</p>
</div>

@if ($dataContoh)
    <div
        class="flex items-start gap-3 rounded-2xl border border-amber-300/20 bg-amber-300/5 px-4 py-3 text-sm text-amber-100/80">
        <i class="bi bi-info-circle mt-0.5 shrink-0" aria-hidden="true"></i>
        <p>Tampilan contoh: data di tab ini belum tersimpan dan baru tersambung ke database di <b>Fase
                {{ $fase }}</b>. Tombol simpan hanya menampilkan pemberitahuan.</p>
    </div>
@endif

@push('scripts')
    @once
        <script>
            // Notifikasi untuk tombol yang belum tersambung ke server (atribut data-ic-segera="pesan").
            window.icSegera = (teks) => window.Toast?.fire({
                icon: 'info',
                title: teks || 'Fitur ini segera aktif.'
            });

            document.addEventListener('click', (e) => {
                const tombol = e.target.closest('[data-ic-segera]:not(form)');
                if (!tombol) return;
                e.preventDefault();
                window.icSegera(tombol.dataset.icSegera);
            });

            document.addEventListener('submit', (e) => {
                const form = e.target.closest('form[data-ic-segera]');
                if (!form) return;
                e.preventDefault();
                window.icSegera(form.dataset.icSegera);
                form.closest('dialog')?.close();
            });

            // Ikon untuk preview: gambar WebP kalau ada, kalau tidak emoji. Peta diisi tiap halaman
            // lewat window.icIkonPeta (dihitung di adminController, logikanya sama dengan komponen ikon anggota).
            window.icBuatIkon = (nama, kelas) => {
                const peta = (window.icIkonPeta || {})[nama] || {};
                if (peta.url) {
                    const img = document.createElement('img');
                    img.src = peta.url;
                    img.alt = '';
                    img.width = 256;
                    img.height = 256;
                    img.decoding = 'async';
                    img.className = 'ic-ikon ' + kelas;
                    return img;
                }
                const span = document.createElement('span');
                span.className = 'ic-ikon ic-ikon--emoji ' + kelas;
                span.setAttribute('aria-hidden', 'true');
                span.textContent = peta.emoji || '✦';
                return span;
            };

            // Tanggal pendek bahasa Indonesia, mis. "22 Okt 2026".
            window.icTanggal = (nilai) => {
                if (!nilai) return '—';
                const d = new Date(nilai + 'T00:00:00');
                return isNaN(d) ? '—' : d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            };
        </script>
    @endonce
@endpush
