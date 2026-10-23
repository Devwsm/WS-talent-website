@extends('template.innerCircleLayout')

@section('title', 'Komunitas')

@section('content')
    <p class="ic-eyebrow">Lebih banyak musik • Teman yang sama</p>
    <h1 class="ic-judul">Komunitas kita<span>.</span></h1>
    <p class="ic-deskripsi">Semua cerita dimulai dari hadir. Update langsung dari WSM, lalu simpan momen yang layak
        diingat.</p>

    <section class="ic-banner" aria-labelledby="judul-banner">
        <div>
            <span class="ic-banner__stiker">✦ WS INNER CIRCLE ✦</span>
            <h2 id="judul-banner">Lebih banyak musik.<br>Teman yang sama.</h2>
            <p>Lebih dekat dengan musik. Lebih akrab satu sama lain.</p>
            <button type="button" class="ic-tombol" data-ic-modal-buka="modal-kenangan">+ Bagikan kenangan <i
                    class="bi bi-arrow-up-right" aria-hidden="true"></i></button>
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
            <span class="ic-tab__lencana">{{ $jumlahPengumuman }}</span>
        </a>
        <a class="ic-tab {{ $tab === 'galeri' ? 'is-aktif' : '' }}" href="{{ route('ic.komunitas', ['tab' => 'galeri']) }}"
            @if ($tab === 'galeri') aria-current="page" @endif>
            <x-inner-circle.ikon nama="camera" kelas="ic-ikon--mini" /> Galeri Kenangan
            <span class="ic-tab__lencana">{{ $jumlahKenangan }}</span>
        </a>
    </nav>

    @if ($tab === 'pengumuman')
        <div class="ic-bagian-kepala">
            <div>
                <p class="ic-eyebrow">Langsung dari komunitas</p>
                <h2 class="ic-judul-bagian">Kabar terbaru ✳</h2>
            </div>
            <span class="ic-pil">✦ Kabar resmi</span>
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
                <b>Belum ada pengumuman.</b>
                <p class="ic-bantuan">Kabar resmi dari WSM akan muncul di sini.</p>
            </div>
        @endif
    @else
        <div class="ic-bagian-kepala">
            <div>
                <p class="ic-eyebrow">Penggemar, foto &amp; cerita</p>
                <h2 class="ic-judul-bagian">Galeri kenangan ♡</h2>
            </div>
            <button type="button" class="ic-tombol" data-ic-modal-buka="modal-kenangan">+ Tambah kenangan</button>
        </div>

        <p class="ic-intro-kenangan"><b>Simpan momen terbaikmu.</b> Bagikan momen favorit, dukung sesama penggemar,
            dan kumpulkan kenangan seru.</p>

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
                    <b>Belum ada kenangan di kategori ini.</b>
                    <p class="ic-bantuan">Jadilah yang pertama membagikan momenmu.</p>
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

        <p class="ic-catatan">Tim WSM dapat menyembunyikan kenangan yang melanggar aturan komunitas.</p>
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
