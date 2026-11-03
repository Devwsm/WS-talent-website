{{-- Aturan bawaan check-in di tab Konten. Variabel: $aturan (efektif), $aturanBawaan, $aturanRingkasan. --}}
@php
    $galat = $errors->getBag('aturan');
    $pakaiOld = $galat->any();
    $v = fn(string $k) => $pakaiOld ? old($k, $aturan[$k]) : $aturan[$k];
    $cfg = config('inner_circle.lokasi');
    $kelasInput =
        'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark';
    $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
    $kelasBantu = 'text-xs text-white/40';
@endphp
<section id="aturan-checkin" aria-labelledby="judul-aturan-checkin" class="scroll-mt-8 flex flex-col gap-4">
    <div class="min-w-0">
        <h2 id="judul-aturan-checkin" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
            <i class="bi bi-sliders text-white/50" aria-hidden="true"></i> Aturan check-in
        </h2>
        <p class="text-xs text-white/40 mt-1">Aturan bawaan untuk acara baru. Acara yang punya jendela atau radius
            sendiri
            tidak terpengaruh, dan acara yang sudah ada tetap memakai aturan lamanya saat bawaan diubah.</p>
    </div>

    <form method="POST" action="{{ route('ic.admin.konten.aturan.simpan') }}"
        class="rounded-3xl border border-white/10 bg-white/3 p-5 sm:p-6 md:p-8 flex flex-col gap-6">
        @csrf
        @if ($galat->any())
            <div role="alert"
                class="rounded-xl border border-red-300/40 bg-red-950/20 p-3 text-xs text-red-300 flex flex-col gap-1">
                @foreach ($galat->all() as $pesan)
                    <p>{{ $pesan }}</p>
                @endforeach
            </div>
        @endif

        <p class="rounded-xl border border-white/10 bg-white/2 px-4 py-3 text-sm text-white/70">
            Sekarang: check-in dibuka {{ $aturanRingkasan }}.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="f-jendela-buka" class="{{ $kelasLabel }}">Dibuka (jam)</label>
                <input type="time" id="f-jendela-buka" name="jendela_buka" required value="{{ $v('jendela_buka') }}"
                    class="{{ $kelasInput }}">
                <p class="{{ $kelasBantu }}">Pada tanggal show. Bawaan {{ $aturanBawaan['jendela_buka'] }}.</p>
            </div>
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="f-jendela-tutup-hari" class="{{ $kelasLabel }}">Ditutup pada</label>
                <select id="f-jendela-tutup-hari" name="jendela_tutup_hari" class="{{ $kelasInput }}">
                    <option class="bg-neutral-900" value="1" @selected((string) $v('jendela_tutup_hari') === '1')>Hari berikutnya (H+1)
                    </option>
                    <option class="bg-neutral-900" value="0" @selected((string) $v('jendela_tutup_hari') === '0')>Hari yang sama</option>
                </select>
                <p class="{{ $kelasBantu }}">H+1 cocok untuk show klub yang selesai lewat tengah malam.</p>
            </div>
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="f-jendela-tutup" class="{{ $kelasLabel }}">Ditutup (jam)</label>
                <input type="time" id="f-jendela-tutup" name="jendela_tutup" required
                    value="{{ $v('jendela_tutup') }}" class="{{ $kelasInput }}">
                <p class="{{ $kelasBantu }}">Bawaan {{ $aturanBawaan['jendela_tutup'] }}.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="f-radius" class="{{ $kelasLabel }}">Radius bawaan (meter)</label>
                <input type="number" inputmode="numeric" id="f-radius" name="radius_bawaan_m" required
                    min="{{ $cfg['radius_min_m'] }}" max="{{ $cfg['radius_maks_m'] }}"
                    value="{{ $v('radius_bawaan_m') }}" class="{{ $kelasInput }}">
                <p class="{{ $kelasBantu }}">Dipakai acara yang punya titik lokasi tanpa radius sendiri.
                    {{ $cfg['radius_min_m'] }} sampai {{ $cfg['radius_maks_m'] }} m, bawaan
                    {{ $aturanBawaan['radius_bawaan_m'] }} m.</p>
            </div>
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="f-akurasi" class="{{ $kelasLabel }}">Akurasi GPS maksimal (meter)</label>
                <input type="number" inputmode="numeric" id="f-akurasi" name="akurasi_maks_m" required
                    min="{{ $cfg['akurasi_isian_min_m'] }}" max="{{ $cfg['akurasi_isian_maks_m'] }}"
                    value="{{ $v('akurasi_maks_m') }}" class="{{ $kelasInput }}">
                <p class="{{ $kelasBantu }}">HP yang melaporkan akurasi lebih kasar ditolak.
                    {{ $cfg['akurasi_isian_min_m'] }} sampai {{ $cfg['akurasi_isian_maks_m'] }} m, bawaan
                    {{ $aturanBawaan['akurasi_maks_m'] }} m. Berlaku langsung untuk semua acara.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit"
                class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                <i class="bi bi-check2-circle" aria-hidden="true"></i> Simpan aturan check-in</button>
            <button type="submit" formaction="{{ route('ic.admin.konten.aturan.bawaan') }}" formnovalidate
                class="min-h-11 text-xs text-white/50 underline underline-offset-4 hover:text-white">Kembalikan semua ke
                bawaan</button>
        </div>
    </form>
</section>
