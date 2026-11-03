{{--
    Form satu pilihan (tipe acara atau kategori kenangan) di tab Konten.
    Variabel: $kode (nama form: kantong galat + penanda old()), $aksi, $metode (POST|PUT), $baris (ic_pilihan|null),
    $ikonList, $warnaList, $denganSampul (bool), $labelTombol. Galat tampil di form ini saja.
--}}
@php
    $galat = $errors->getBag($kode);
    $pakaiOld = old('_form') === $kode;
    $nilai = fn(string $kolom, $awal = '') => $pakaiOld ? old($kolom, $awal) : $awal;
    $id = 'f-' . $kode;
    $kelasInput =
        'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
    $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
    $awalIkon = $baris?->ikon ?? array_key_first($ikonList);
    $awalSampul = $baris?->sampul ?? array_key_first($ikonList);
    $awalWarna = $baris?->warna ?? array_key_first($warnaList);
@endphp

<form method="POST" action="{{ $aksi }}" class="flex flex-col gap-4 min-w-0">
    @csrf
    @if ($metode !== 'POST')
        @method($metode)
    @endif
    <input type="hidden" name="_form" value="{{ $kode }}">

    @if ($galat->any())
        <div role="alert"
            class="rounded-xl border border-red-300/40 bg-red-950/20 p-3 text-xs text-red-300 flex flex-col gap-1">
            @foreach ($galat->all() as $pesan)
                <p>{{ $pesan }}</p>
            @endforeach
        </div>
    @endif

    <div class="flex flex-col gap-1.5 min-w-0">
        <label for="{{ $id }}-label" class="{{ $kelasLabel }}">Nama</label>
        <input id="{{ $id }}-label" name="label" maxlength="40" required autocomplete="off"
            value="{{ $nilai('label', $baris?->label ?? '') }}" class="{{ $kelasInput }}">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex flex-col gap-1.5 min-w-0">
            <label for="{{ $id }}-ikon" class="{{ $kelasLabel }}">Ikon</label>
            <select id="{{ $id }}-ikon" name="ikon" class="{{ $kelasInput }} scheme-dark">
                @foreach ($ikonList as $k => $l)
                    <option class="bg-neutral-900" value="{{ $k }}" @selected($k === $nilai('ikon', $awalIkon))>
                        {{ $l }}</option>
                @endforeach
            </select>
        </div>

        @if ($denganSampul)
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="{{ $id }}-sampul" class="{{ $kelasLabel }}">Ikon sampul (kartu tanpa
                    foto)</label>
                <select id="{{ $id }}-sampul" name="sampul" class="{{ $kelasInput }} scheme-dark">
                    @foreach ($ikonList as $k => $l)
                        <option class="bg-neutral-900" value="{{ $k }}" @selected($k === $nilai('sampul', $awalSampul))>
                            {{ $l }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex flex-col gap-1.5 min-w-0">
            <label for="{{ $id }}-warna" class="{{ $kelasLabel }}">Warna kartu</label>
            <select id="{{ $id }}-warna" name="warna" class="{{ $kelasInput }} scheme-dark">
                @foreach ($warnaList as $k => $l)
                    <option class="bg-neutral-900" value="{{ $k }}" @selected($k === $nilai('warna', $awalWarna))>
                        {{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <button type="submit"
            class="h-11 px-5 w-full sm:w-auto inline-flex items-center justify-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
            <i class="bi bi-check2-circle" aria-hidden="true"></i> {{ $labelTombol }}</button>
    </div>
</form>
