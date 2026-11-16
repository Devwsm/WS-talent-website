{{-- Isian "Jadwalkan tayang" (berita dan album). Kosong = langsung tampil. Nilai: Y-m-d\TH:i (zona waktu aplikasi, WIB). --}}
@props(['id' => 'terbit_pada', 'nilai' => null, 'target' => null])
<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" class="text-sm font-semibold uppercase tracking-widest text-white/60">
        Jadwalkan tayang <span class="normal-case tracking-normal text-white/30">(opsional)</span>
    </label>
    <input type="datetime-local" id="{{ $id }}" name="terbit_pada" value="{{ $nilai }}"
        @if ($target) data-jadwal-target="{{ $target }}" @endif
        class="w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark" />
    <p class="text-xs text-white/30">Kosongkan agar langsung tampil. Jadwal memakai waktu WIB; sebelum waktunya, belum
        tampil di website.</p>
</div>
