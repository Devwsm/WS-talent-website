{{--
    Pengelola satu daftar berurutan di tab Konten (langkah, FAQ, poin teaser). Dipanggil dari pages/dashboard-pages/inner-circle/konten-bagian.blade.php
    di dalam bagian grupnya, dengan variabel: $kodeDaftar, $daftarDef, $butir, $ikonDaftar (sisanya turun dari halaman).

    Susunan responsif: tiap butir satu kartu. Teks di atas, tombol aksi di bawahnya (turun baris di HP, di samping dari
    layar sedang). Semua tombol setinggi 44 px; formulir ubah ada di dalam <details> sehingga daftar tetap ringkas.
--}}
@php
    $d = $daftarDef[$kodeDaftar];
    $isiDaftar = $butir[$kodeDaftar];
    $penuh = $isiDaftar->count() >= $d['maks_item'];
    $tampil = $isiDaftar->where('aktif', true)->count();
    $kodeBaru = 'item_baru_' . $kodeDaftar;
    $galatBaru = $errors->getBag($kodeBaru);
    $kelasTombol =
        'h-11 min-w-11 px-3 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors disabled:opacity-30 disabled:pointer-events-none';
@endphp

<div id="daftar-{{ $kodeDaftar }}" aria-labelledby="judul-daftar-{{ $kodeDaftar }}" role="group"
    class="scroll-mt-8 rounded-3xl border border-white/10 bg-white/3 p-5 sm:p-6 md:p-8 flex flex-col gap-5 min-w-0">
    <div class="flex flex-col gap-1 min-w-0">
        <h3 id="judul-daftar-{{ $kodeDaftar }}" class="font-bold uppercase tracking-wide text-sm wrap-break-word">
            {{ $d['label'] }}
            <span class="font-normal normal-case tracking-normal text-white/50">· {{ $isiDaftar->count() }} dari
                {{ $d['maks_item'] }} {{ $d['satuan'] }}</span>
        </h3>
        <p class="text-xs text-white/40">{{ $d['petunjuk'] }}</p>
    </div>

    @if ($tampil === 0)
        <p
            class="rounded-xl border border-amber-300/20 bg-amber-300/5 px-4 py-3 text-xs text-amber-100/80 flex items-start gap-2">
            <i class="bi bi-info-circle mt-0.5 shrink-0" aria-hidden="true"></i>
            <span>Belum ada {{ $d['satuan'] }} yang tampil, jadi bagian ini disembunyikan dari halaman.</span>
        </p>
    @endif

    @if ($isiDaftar->isNotEmpty())
        <ol class="flex flex-col gap-3 min-w-0">
            @foreach ($isiDaftar as $b)
                @php
                    $kodeItem = 'item_' . $b->id_item;
                    $galatItem = $errors->getBag($kodeItem);
                    $rute = fn(string $nama) => route('ic.admin.konten.item.' . $nama, [$kodeDaftar, $b->id_item]);
                @endphp
                <li id="item-{{ $b->id_item }}"
                    class="rounded-2xl border border-white/10 bg-white/2 min-w-0 scroll-mt-8 {{ $b->aktif ? '' : 'opacity-80' }}">
                    <div class="p-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0 flex gap-3">
                            <span aria-hidden="true"
                                class="shrink-0 w-8 h-8 rounded-full bg-white/10 grid place-items-center text-xs font-bold">{{ $loop->iteration }}</span>
                            <div class="min-w-0">
                                <p class="font-semibold wrap-break-word">
                                    @if ($b->ikon)
                                        <i class="bi bi-{{ $b->ikon }} text-white/50 mr-1" aria-hidden="true"></i>
                                    @endif
                                    {{ $b->judul }}
                                </p>
                                @if ($b->isi)
                                    <p class="text-sm text-white/60 mt-1 wrap-break-word line-clamp-2">
                                        {{ $b->isi }}</p>
                                @endif
                                @unless ($b->aktif)
                                    <span
                                        class="mt-2 inline-block rounded-full bg-white/10 text-white/70 px-2.5 py-0.5 text-[11px] font-bold">Disembunyikan</span>
                                @endunless
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 md:shrink-0 md:justify-end">
                            <form method="POST" action="{{ $rute('geser') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="arah" value="naik">
                                <button type="submit" class="{{ $kelasTombol }}" @disabled($loop->first)
                                    aria-label="Naikkan: {{ $b->judul }}" title="Naikkan"><i class="bi bi-arrow-up"
                                        aria-hidden="true"></i></button>
                            </form>
                            <form method="POST" action="{{ $rute('geser') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="arah" value="turun">
                                <button type="submit" class="{{ $kelasTombol }}" @disabled($loop->last)
                                    aria-label="Turunkan: {{ $b->judul }}" title="Turunkan"><i
                                        class="bi bi-arrow-down" aria-hidden="true"></i></button>
                            </form>
                            <form method="POST" action="{{ $rute('aktif') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="aktif" value="{{ $b->aktif ? 0 : 1 }}">
                                <button type="submit" class="{{ $kelasTombol }}">
                                    <i class="bi {{ $b->aktif ? 'bi-eye-slash' : 'bi-eye' }}" aria-hidden="true"></i>
                                    {{ $b->aktif ? 'Sembunyikan' : 'Tampilkan' }}</button>
                            </form>
                            <form method="POST" action="{{ $rute('hapus') }}" data-swal-confirm
                                data-confirm-message="Hapus &quot;{{ $b->judul }}&quot; dari daftar? Tidak bisa dibatalkan (kecuali dengan mengembalikan daftar bawaan).">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="{{ $kelasTombol }} border-red-300/40 text-red-300 hover:bg-red-950/30 hover:text-red-300"
                                    aria-label="Hapus: {{ $b->judul }}" title="Hapus"><i class="bi bi-trash-fill"
                                        aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </div>

                    <details class="border-t border-white/10" @if ($galatItem->any()) open @endif>
                        <summary
                            class="min-h-11 px-4 flex items-center gap-2 cursor-pointer text-xs font-semibold uppercase tracking-widest text-white/60 hover:text-white">
                            <i class="bi bi-pencil-square" aria-hidden="true"></i> Ubah isi
                        </summary>
                        <div class="px-4 pb-4 pt-2">
                            @include('components.dashboard.inner-circle.form-butir', [
                                'def' => $d,
                                'kode' => $kodeItem,
                                'aksi' => $rute('ubah'),
                                'metode' => 'PUT',
                                'butir' => $b,
                                'labelTombol' => 'Simpan perubahan',
                            ])
                        </div>
                    </details>
                </li>
            @endforeach
        </ol>
    @endif

    <div class="flex flex-col gap-3 min-w-0">
        @if ($penuh)
            <p class="text-xs text-white/50">Daftar sudah penuh ({{ $d['maks_item'] }} {{ $d['satuan'] }}). Hapus
                satu
                untuk menambah yang baru.</p>
        @else
            <details class="rounded-2xl border border-dashed border-white/20 min-w-0"
                @if ($galatBaru->any()) open @endif>
                <summary
                    class="min-h-11 px-4 flex items-center gap-2 cursor-pointer text-xs font-semibold uppercase tracking-widest text-white/70 hover:text-white">
                    <i class="bi bi-plus-circle" aria-hidden="true"></i> Tambah {{ $d['satuan'] }}
                </summary>
                <div class="px-4 pb-4 pt-2">
                    @include('components.dashboard.inner-circle.form-butir', [
                        'def' => $d,
                        'kode' => $kodeBaru,
                        'aksi' => route('ic.admin.konten.item.tambah', $kodeDaftar),
                        'metode' => 'POST',
                        'butir' => null,
                        'labelTombol' => 'Tambahkan',
                    ])
                </div>
            </details>
        @endif

        <form method="POST" action="{{ route('ic.admin.konten.item.bawaan', $kodeDaftar) }}" data-swal-confirm
            data-confirm-title="Kembalikan daftar bawaan?" data-confirm-button="Ya, kembalikan"
            data-confirm-loading="Mengembalikan..."
            data-confirm-message="Seluruh isi daftar &quot;{{ $d['label'] }}&quot; diganti dengan isi awalnya. Perubahan yang pernah kamu buat di daftar ini hilang.">
            @csrf
            <button type="submit"
                class="min-h-11 text-xs text-white/50 underline underline-offset-4 hover:text-white">Kembalikan daftar
                bawaan</button>
        </form>
    </div>
</div>
