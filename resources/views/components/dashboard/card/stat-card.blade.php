{{--
    Kartu statistik kecil buat ringkasan jumlah data per modul.
    Expects: $icon (nama class bootstrap-icons tanpa "bi "), $count, $label.
--}}
<div class="rounded-xl border border-white/15 bg-white/5 p-6">
    <i class="bi {{ $icon }} text-3xl text-white/60" aria-hidden="true"></i>
    <h2 class="mt-4 text-3xl font-bold">{{ $count }}</h2>
    <h3 class="text-sm text-white/50 uppercase tracking-wide">{{ $label }}</h3>
</div>
