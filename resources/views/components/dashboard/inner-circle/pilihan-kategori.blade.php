{{-- Kategori kenangan di tab Konten: tambah, ubah, urutkan, sembunyikan, hapus (hanya yang belum dipakai kenangan). --}}
@php
    $penuhKategori = $kategoriBaris->count() >= (int) config('inner_circle.kategori_kenangan_maks');
    $kodeBaru = 'pilihan_kategori_baru';
    $kelasIkonTombol =
        'h-11 min-w-11 px-3 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors disabled:opacity-30 disabled:pointer-events-none';
@endphp
<section id="pilihan-kategori" aria-labelledby="judul-pilihan-kategori" class="scroll-mt-8 flex flex-col gap-4">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <div class="min-w-0">
            <h2 id="judul-pilihan-kategori" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                <i class="bi bi-images text-white/50" aria-hidden="true"></i> Kategori kenangan
                <span class="font-normal normal-case tracking-normal text-white/50">· {{ $kategoriBaris->count() }} dari
                    {{ config('inner_circle.kategori_kenangan_maks') }}</span>
            </h2>
            <p class="text-xs text-white/40 mt-1">Pilihan kategori saat anggota membagikan kenangan, dan chip filter di
                galeri.
                Kategori yang sudah dipakai kenangan tidak bisa dihapus; sembunyikan saja supaya tidak bisa dipilih lagi
                (kenangan lamanya tetap tampil).</p>
        </div>
        <a href="{{ route('ic.komunitas', ['tab' => 'galeri']) }}" target="_blank" rel="noopener"
            class="{{ $kelasTombol }} self-start sm:self-auto shrink-0"><i class="bi bi-box-arrow-up-right"
                aria-hidden="true"></i> Lihat halaman</a>
    </div>

    <ol class="flex flex-col gap-3 min-w-0">
        @foreach ($kategoriBaris as $b)
            @php
                $kode = 'pilihan_kategori_' . $b->kunci;
                $dipakai = $pemakaianKategori[$b->kunci] ?? 0;
                $rute = fn(string $nama) => route('ic.admin.konten.pilihan.kategori.' . $nama, $b->kunci);
            @endphp
            <li class="rounded-2xl border border-white/10 bg-white/3 min-w-0 {{ $b->aktif ? '' : 'opacity-80' }}">
                <div class="p-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0 flex gap-3">
                        <span aria-hidden="true"
                            class="shrink-0 w-8 h-8 rounded-full bg-white/10 grid place-items-center text-xs font-bold">{{ $loop->iteration }}</span>
                        <div class="min-w-0">
                            <p class="font-semibold wrap-break-word">{{ $b->label }}</p>
                            <p class="text-xs text-white/40">Kunci: <code
                                    class="text-white/60">{{ $b->kunci }}</code> · {{ $dipakai }} kenangan</p>
                            @unless ($b->aktif)
                                <span
                                    class="mt-2 inline-block rounded-full bg-white/10 text-white/70 px-2.5 py-0.5 text-[11px] font-bold">Disembunyikan</span>
                            @endunless
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 md:shrink-0 md:justify-end">
                        @foreach (['naik' => ['bi-arrow-up', 'Naikkan', $loop->first], 'turun' => ['bi-arrow-down', 'Turunkan', $loop->last]] as $arah => [$ikon, $judul, $mati])
                            <form method="POST" action="{{ $rute('geser') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="arah" value="{{ $arah }}">
                                <button type="submit" class="{{ $kelasIkonTombol }}" @disabled($mati)
                                    aria-label="{{ $judul }}: {{ $b->label }}"
                                    title="{{ $judul }}"><i class="bi {{ $ikon }}"
                                        aria-hidden="true"></i></button>
                            </form>
                        @endforeach
                        <form method="POST" action="{{ $rute('aktif') }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="aktif" value="{{ $b->aktif ? 0 : 1 }}">
                            <button type="submit" class="{{ $kelasIkonTombol }}">
                                <i class="bi {{ $b->aktif ? 'bi-eye-slash' : 'bi-eye' }}" aria-hidden="true"></i>
                                {{ $b->aktif ? 'Sembunyikan' : 'Tampilkan' }}</button>
                        </form>
                        @if ($dipakai === 0)
                            <form method="POST" action="{{ $rute('hapus') }}" data-swal-confirm
                                data-confirm-message="Hapus kategori &quot;{{ $b->label }}&quot;? Tidak bisa dibatalkan.">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="{{ $kelasIkonTombol }} border-red-300/40 text-red-300 hover:bg-red-950/30 hover:text-red-300"
                                    aria-label="Hapus: {{ $b->label }}" title="Hapus"><i class="bi bi-trash-fill"
                                        aria-hidden="true"></i></button>
                            </form>
                        @endif
                    </div>
                </div>
                <details class="border-t border-white/10" @if ($errors->getBag($kode)->any()) open @endif>
                    <summary
                        class="min-h-11 px-4 flex items-center gap-2 cursor-pointer text-xs font-semibold uppercase tracking-widest text-white/60 hover:text-white">
                        <i class="bi bi-pencil-square" aria-hidden="true"></i> Ubah
                    </summary>
                    <div class="px-4 pb-4 pt-2 flex flex-col gap-3">
                        @include('components.dashboard.inner-circle.form-pilihan', [
                            'kode' => $kode,
                            'aksi' => $rute('ubah'),
                            'metode' => 'PUT',
                            'baris' => $b,
                            'ikonList' => $ikonKategori,
                            'warnaList' => $warnaKartu,
                            'denganSampul' => true,
                            'labelTombol' => 'Simpan',
                        ])
                        @if (config('inner_circle.kategori_kenangan.' . $b->kunci))
                            <form method="POST" action="{{ $rute('bawaan') }}">
                                @csrf
                                <button type="submit"
                                    class="min-h-11 text-xs text-white/50 underline underline-offset-4 hover:text-white">Kembalikan
                                    bawaan</button>
                            </form>
                        @endif
                    </div>
                </details>
            </li>
        @endforeach
    </ol>

    @if ($penuhKategori)
        <p class="text-xs text-white/50">Sudah penuh ({{ config('inner_circle.kategori_kenangan_maks') }} kategori).
            Hapus satu untuk menambah yang baru.</p>
    @else
        <details class="rounded-2xl border border-dashed border-white/20 min-w-0"
            @if ($errors->getBag($kodeBaru)->any()) open @endif>
            <summary
                class="min-h-11 px-4 flex items-center gap-2 cursor-pointer text-xs font-semibold uppercase tracking-widest text-white/70 hover:text-white">
                <i class="bi bi-plus-circle" aria-hidden="true"></i> Tambah kategori
            </summary>
            <div class="px-4 pb-4 pt-2">
                @include('components.dashboard.inner-circle.form-pilihan', [
                    'kode' => $kodeBaru,
                    'aksi' => route('ic.admin.konten.pilihan.kategori.tambah'),
                    'metode' => 'POST',
                    'baris' => null,
                    'ikonList' => $ikonKategori,
                    'warnaList' => $warnaKartu,
                    'denganSampul' => true,
                    'labelTombol' => 'Tambahkan',
                ])
            </div>
        </details>
    @endif
</section>
