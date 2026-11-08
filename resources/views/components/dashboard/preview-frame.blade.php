{{--
    Bingkai preview CMS (form kiri, preview kanan) — dipakai semua section di halaman Profile.

    - Kartu kanan SELALU setinggi kartu form (grid induk memakai items-stretch), jadi tidak ada ruang kosong di bawahnya.
    - Isi preview tampil di "layar" selebar maksimal 390px (skala mobile) yang rata tengah dan ikut menempel (sticky)
      saat form di-scroll. Sisa ruang jadi latar panggung yang sengaja, bukan lubang kosong.
    - overflow-clip (bukan overflow-hidden) supaya sticky di dalamnya tetap bekerja.

    Pakai: <x-dashboard.preview-frame> ...isi preview... </x-dashboard.preview-frame>
--}}
@props(['label' => 'Tampilan di halaman Profile'])

<div class="flex flex-col rounded-3xl border border-white/10 bg-black overflow-clip">
    <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
        <i class="bi bi-eye text-white/40" aria-hidden="true"></i>
        <span class="min-w-0 truncate text-xs uppercase tracking-widest text-white/40">{{ $label }}</span>
        <span
            class="ml-auto hidden sm:inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full border border-white/10 px-2 py-0.5 text-[10px] uppercase tracking-widest text-white/40">
            <i class="bi bi-phone" aria-hidden="true"></i> Skala mobile
        </span>
    </div>

    {{-- Panggung: mengisi sisa tinggi kartu --}}
    <div
        class="flex-1 p-3 sm:p-6 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.05)_1px,transparent_1px)] bg-size-[18px_18px]">
        {{-- Layar: lebar tetap ala HP, menempel saat form di-scroll --}}
        <div
            class="lg:sticky lg:top-8 mx-auto w-full max-w-97.5 rounded-4xl border border-white/15 bg-black p-3 sm:p-4 shadow-2xl shadow-black/60">
            {{ $slot }}
        </div>
    </div>
</div>
