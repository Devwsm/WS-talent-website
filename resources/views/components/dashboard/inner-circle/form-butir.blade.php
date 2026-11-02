{{--
    Form satu butir daftar konten (tambah atau ubah) di tab Konten. Dipakai untuk langkah, FAQ, dan poin teaser.

    Variabel:
    - $kodeDaftar : nama daftar (langkah, faq, teaser_poin)
    - $def        : definisi daftar dari config('ic_konten.daftar')
    - $kode       : nama form (item_<id> atau item_baru_<daftar>): kantong galat dan penanda old()
    - $aksi       : alamat tujuan form
    - $metode     : POST (tambah) atau PUT (ubah)
    - $butir      : butir yang diubah, atau null untuk form tambah
    - $ikonDaftar : pilihan ikon
    - $labelTombol: tulisan tombol kirim
    Galat tampil di dalam form ini saja (kantong bernama), jadi form lain di halaman tidak ikut tertular.
--}}
@php
    $galat = $errors->getBag($kode);
    $pakaiOld = old('_form') === $kode;
    $nilai = fn(string $kolom, string $awal = '') => $pakaiOld ? old($kolom, $awal) : $awal;
    $awalIkon = $butir?->ikon ?: array_key_first($ikonDaftar);
    $idDasar = 'f-' . $kode;
    $kelasInput =
        'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
    $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
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
        <label for="{{ $idDasar }}-judul" class="{{ $kelasLabel }}">{{ $def['judul']['label'] }}</label>
        <input id="{{ $idDasar }}-judul" name="judul" maxlength="{{ $def['judul']['maks'] }}" required
            autocomplete="off" value="{{ $nilai('judul', $butir?->judul ?? '') }}" data-hitung
            class="{{ $kelasInput }}">
        <p class="text-xs text-white/40 text-right"><span
                data-hitung-untuk="{{ $idDasar }}-judul">{{ mb_strlen($nilai('judul', $butir?->judul ?? '')) }}</span>/{{ $def['judul']['maks'] }}
        </p>
    </div>

    @if (!empty($def['isi']))
        <div class="flex flex-col gap-1.5 min-w-0">
            <label for="{{ $idDasar }}-isi" class="{{ $kelasLabel }}">{{ $def['isi']['label'] }}</label>
            <textarea id="{{ $idDasar }}-isi" name="isi" rows="3" maxlength="{{ $def['isi']['maks'] }}" required
                data-hitung class="{{ $kelasInput }}">{{ $nilai('isi', $butir?->isi ?? '') }}</textarea>
            <p class="text-xs text-white/40 text-right"><span
                    data-hitung-untuk="{{ $idDasar }}-isi">{{ mb_strlen($nilai('isi', $butir?->isi ?? '')) }}</span>/{{ $def['isi']['maks'] }}
            </p>
        </div>
    @endif

    @if (!empty($def['ikon']))
        <div class="flex flex-col gap-1.5 min-w-0">
            <label for="{{ $idDasar }}-ikon" class="{{ $kelasLabel }}">Ikon</label>
            <div class="flex items-center gap-3">
                <span aria-hidden="true"
                    class="shrink-0 w-11 h-11 grid place-items-center rounded-lg border border-white/15 bg-white/5 text-lg">
                    <i class="bi bi-{{ $nilai('ikon', $awalIkon) }}" data-ikon-pratinjau></i>
                </span>
                <select id="{{ $idDasar }}-ikon" name="ikon" data-ikon-pilih
                    class="{{ $kelasInput }} scheme-dark">
                    @foreach ($ikonDaftar as $namaIkon => $labelIkon)
                        <option class="bg-neutral-900" value="{{ $namaIkon }}" @selected($namaIkon === $nilai('ikon', $awalIkon))>
                            {{ $labelIkon }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <div>
        <button type="submit"
            class="h-11 px-5 w-full sm:w-auto inline-flex items-center justify-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
            <i class="bi bi-check2-circle" aria-hidden="true"></i> {{ $labelTombol }}</button>
    </div>
</form>
