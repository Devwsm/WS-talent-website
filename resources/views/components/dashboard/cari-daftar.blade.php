{{-- Kolom cari untuk daftar CMS (GET ?q=). Hasil tetap dipaginasi dan q ikut di tautan halaman. --}}
@props(['q' => '', 'placeholder' => 'Cari...', 'label' => 'Cari di daftar'])
<form method="GET" action="{{ url()->current() }}" role="search" data-tanpa-jaga
    class="flex w-full items-center gap-2 sm:w-auto">
    <label for="cari-daftar" class="sr-only">{{ $label }}</label>
    <input type="search" id="cari-daftar" name="q" value="{{ $q }}" maxlength="60"
        placeholder="{{ $placeholder }}"
        class="w-full sm:w-64 bg-white/5 border border-white/15 text-white placeholder-white/30 px-3 py-2 text-sm rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
    <button type="submit"
        class="shrink-0 px-3 py-2 text-sm rounded-lg border border-white/15 text-white/70 hover:bg-white/5 transition"
        aria-label="Cari"><i class="bi bi-search" aria-hidden="true"></i></button>
    @if ($q !== '')
        <a href="{{ url()->current() }}"
            class="shrink-0 px-3 py-2 text-sm rounded-lg border border-white/15 text-white/70 hover:bg-white/5 transition">Reset</a>
    @endif
</form>
