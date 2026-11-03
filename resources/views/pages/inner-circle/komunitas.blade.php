@extends('template.innerCircleLayout')

@section('title', 'Our Community')

@section('content')
    <h1 class="ic-judul">Our Community<span>.</span></h1>
    <p class="ic-deskripsi">{{ ic_html('komunitas.deskripsi') }}</p>

    <x-inner-circle.panel-verifikasi />

    <section class="ic-banner" aria-labelledby="judul-banner">
        <div>
            <span class="ic-banner__stiker">{{ ic_teks('komunitas.banner.stiker') }}</span>
            <h2 id="judul-banner">{{ ic_html('komunitas.banner.judul') }}</h2>
            <p>{{ ic_html('komunitas.banner.deskripsi') }}</p>
            <button type="button" class="ic-tombol" data-ic-modal-buka="modal-kenangan"
                @disabled($member->email_verified_at === null)>{{ ic_teks('komunitas.banner.tombol') }}
                <i class="bi bi-arrow-up-right" aria-hidden="true"></i></button>
        </div>
        <div class="ic-banner__orbit" aria-hidden="true">
            <x-inner-circle.ikon nama="mirror-ball" kelas="ic-ikon--sampul" />
        </div>
    </section>

    <nav class="ic-tab-daftar" aria-label="Bagian komunitas">
        <a class="ic-tab {{ $tab === 'pengumuman' ? 'is-aktif' : '' }}"
            href="{{ route('ic.komunitas', ['tab' => 'pengumuman']) }}"
            @if ($tab === 'pengumuman') aria-current="page" @endif>
            <x-inner-circle.ikon nama="megaphone" kelas="ic-ikon--mini" /> Pengumuman WS
            @if ($jumlahPengumuman > 0)
                <span class="ic-tab__lencana">{{ $jumlahPengumuman > 99 ? '99+' : $jumlahPengumuman }}<span class="ic-sr-saja"> belum dibaca</span></span>
            @endif
        </a>
        <a class="ic-tab {{ $tab === 'galeri' ? 'is-aktif' : '' }}" href="{{ route('ic.komunitas', ['tab' => 'galeri']) }}"
            @if ($tab === 'galeri') aria-current="page" @endif>
            <x-inner-circle.ikon nama="camera" kelas="ic-ikon--mini" /> Galeri Kenangan
            @if ($jumlahKenangan > 0)
                <span class="ic-tab__lencana">{{ $jumlahKenangan > 99 ? '99+' : $jumlahKenangan }}<span class="ic-sr-saja"> belum dibaca</span></span>
            @endif
        </a>
    </nav>

    @if ($tab === 'pengumuman')
        <div class="ic-bagian-kepala">
            <div>
                <p class="ic-eyebrow">{{ ic_teks('komunitas.pengumuman.eyebrow') }}</p>
                <h2 class="ic-judul-bagian">{{ ic_teks('komunitas.pengumuman.judul') }}</h2>
            </div>
            <span class="ic-pil">{{ ic_teks('komunitas.pengumuman.pil') }}</span>
        </div>
        @if ($unggulan)
            <x-inner-circle.pengumuman-unggulan :pengumuman="$unggulan" />
        @endif
        <div class="ic-daftar ic-jarak-atas">
            @foreach ($pengumuman as $p)
                <x-inner-circle.item-pengumuman :pengumuman="$p" />
            @endforeach
        </div>
        @if (!$unggulan && $pengumuman->isEmpty())
            <div class="ic-panel ic-kosong">
                <b>{{ ic_teks('komunitas.pengumuman.kosong.judul') }}</b>
                <p class="ic-bantuan">{{ ic_html('komunitas.pengumuman.kosong.deskripsi') }}</p>
            </div>
        @endif
    @else
        <div class="ic-bagian-kepala">
            <div>
                <p class="ic-eyebrow">{{ ic_teks('komunitas.galeri.eyebrow') }}</p>
                <h2 class="ic-judul-bagian">{{ ic_teks('komunitas.galeri.judul') }}</h2>
            </div>
            <button type="button" class="ic-tombol" data-ic-modal-buka="modal-kenangan"
                @disabled($member->email_verified_at === null)>{{ ic_teks('komunitas.galeri.tombol') }}</button>
        </div>

        <p class="ic-intro-kenangan"><b>{{ ic_teks('komunitas.galeri.intro_tebal') }}</b> {{ ic_html('komunitas.galeri.intro') }}</p>

        <nav class="ic-chip-daftar" aria-label="Filter kategori kenangan">
            <a class="ic-chip {{ $kategori === 'all' ? 'is-aktif' : '' }}"
                href="{{ route('ic.komunitas', ['tab' => 'galeri']) }}"
                @if ($kategori === 'all') aria-current="true" @endif>Semua kenangan</a>
            @foreach (config('inner_circle.kategori_kenangan') as $kunci => $item)
                <a class="ic-chip {{ $kategori === $kunci ? 'is-aktif' : '' }}"
                    href="{{ route('ic.komunitas', ['tab' => 'galeri', 'kategori' => $kunci]) }}"
                    @if ($kategori === $kunci) aria-current="true" @endif>
                    <x-inner-circle.ikon :nama="$item['ikon']" kelas="ic-ikon--mini" /> {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="ic-grid ic-grid--2 ic-jarak-atas">
            @forelse ($kenangan as $k)
                <x-inner-circle.kartu-kenangan :kenangan="$k" />
            @empty
                <div class="ic-panel ic-kosong ic-grid__penuh">
                    <b>{{ ic_teks('komunitas.galeri.kosong.judul') }}</b>
                    <p class="ic-bantuan">{{ ic_html('komunitas.galeri.kosong.deskripsi') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Paginasi sederhana (tautan biasa, jalan tanpa JS). Tampilan bawaan Laravel memakai Tailwind, yang tidak dimuat di sini. --}}
        @if ($kenangan->hasPages())
            <nav class="ic-aksi-baris ic-jarak-atas" aria-label="Halaman galeri">
                @if ($kenangan->onFirstPage())
                    <span class="ic-tombol ic-tombol--kecil ic-tombol--terang" aria-disabled="true">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Lebih baru</span>
                @else
                    <a class="ic-tombol ic-tombol--kecil ic-tombol--terang" href="{{ $kenangan->previousPageUrl() }}"
                        rel="prev"><i class="bi bi-arrow-left" aria-hidden="true"></i> Lebih baru</a>
                @endif
                <span class="ic-bantuan">Halaman {{ $kenangan->currentPage() }} dari {{ $kenangan->lastPage() }}</span>
                @if ($kenangan->hasMorePages())
                    <a class="ic-tombol ic-tombol--kecil" href="{{ $kenangan->nextPageUrl() }}" rel="next">Lebih lama
                        <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                @else
                    <span class="ic-tombol ic-tombol--kecil ic-tombol--terang" aria-disabled="true">Lebih lama <i
                            class="bi bi-arrow-right" aria-hidden="true"></i></span>
                @endif
            </nav>
        @endif

        <p class="ic-catatan">{{ ic_html('komunitas.galeri.catatan') }}</p>
    @endif
@endsection

@push('modal')
    {{-- Galat validasi (mis. foto terlalu besar) membuka modal ini lagi dengan isian tadi; foto harus dipilih ulang. --}}
    <x-inner-circle.modal id="modal-kenangan" judul="Unggah kenangan ✦" eyebrow="Momenmu, ceritamu" :terbuka="$errors->any()">
        <form method="POST" action="{{ route('ic.kenangan.simpan') }}" enctype="multipart/form-data" data-ic-sekali>
            @csrf
            @foreach ($errors->all() as $galat)
                <p class="ic-galat" role="alert">{{ $galat }}</p>
            @endforeach

            <label class="ic-label" for="kenangan-caption">Apa ceritamu?</label>
            <textarea class="ic-input ic-input--area" id="kenangan-caption" name="caption" maxlength="240" rows="3"
                placeholder="Cerita singkat tentang momen paling berkesan…" required>{{ old('caption') }}</textarea>

            <div class="ic-baris-2">
                <div>
                    <label class="ic-label" for="kenangan-tempat">Acara / tempat</label>
                    <input class="ic-input" id="kenangan-tempat" name="tempat" maxlength="65" value="{{ old('tempat') }}"
                        placeholder="Contoh: WS Showcase">
                </div>
                <div>
                    <label class="ic-label" for="kenangan-kategori">Kategori</label>
                    <select class="ic-input" id="kenangan-kategori" name="kategori">
                        @foreach (config('inner_circle.kategori_kenangan') as $kunci => $item)
                            <option value="{{ $kunci }}" @selected(old('kategori') === $kunci)>{{ $item['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label class="ic-label" for="kenangan-foto">Tambahkan foto (opsional)</label>
            <input class="ic-input" type="file" id="kenangan-foto" name="foto"
                accept="image/jpeg,image/png,image/webp" data-ic-maks-mb="{{ $maksFotoMb }}">
            <p class="ic-bantuan">JPG, PNG, atau WebP. Maksimal {{ $maksFotoMb }} MB.</p>

            <div class="ic-aksi-form">
                <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Batal</button>
                <button type="submit" class="ic-tombol">Terbitkan kenangan <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></button>
            </div>
        </form>
    </x-inner-circle.modal>
@endpush
