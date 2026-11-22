@extends('template.innerCircleLayout')

@section('title', 'Kenangan dari ' . $kenangan->nama_member)

@php
    $kategori = ic_kategori_kenangan(true)[$kenangan->kategori] ?? ['label' => 'Kenangan', 'ikon' => 'sparkles'];
    $terverifikasi = $member->email_verified_at !== null;
@endphp

@section('content')
    <p class="ic-eyebrow"><a class="ic-tautan" href="{{ route('ic.komunitas', ['tab' => 'galeri']) }}"><i
                class="bi bi-arrow-left" aria-hidden="true"></i> Galeri Kenangan</a></p>

    <article class="ic-kenangan ic-kenangan--detail">
        <div class="ic-kenangan__sampul ic-warna-{{ $kenangan->warna }}">
            @if ($kenangan->foto)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($kenangan->foto) }}"
                    alt="Foto kenangan dari {{ $kenangan->nama_member }}" width="640" height="800" decoding="async"
                    fetchpriority="high">
            @else
                <x-inner-circle.ikon :nama="$kenangan->ikon" kelas="ic-ikon--sampul" />
                <span class="ic-kenangan__bintang" aria-hidden="true">✳</span>
            @endif
        </div>
        <div class="ic-kenangan__isi">
            <div class="ic-kenangan__atas">
                <x-inner-circle.avatar :member="$kenangan->member" kelas="ic-avatar--kecil" />
                <div>
                    <b>{{ $kenangan->nama_member }}</b>
                    <small>{{ $kenangan->tempat ? $kenangan->tempat . ' · ' : '' }}{{ $kenangan->tanggal->locale('id')->translatedFormat('j M Y') }}</small>
                </div>
            </div>
            <p class="ic-kenangan__caption">{{ $kenangan->caption }}</p>
            <x-inner-circle.penyuka :kenangan="$kenangan" />
            <div class="ic-kenangan__kaki">
                <span class="ic-kenangan__tag"><x-inner-circle.ikon :nama="$kategori['ikon']" kelas="ic-ikon--mini" />
                    {{ $kategori['label'] }}</span>
                <span class="ic-kenangan__aksi">
                    <button type="button" class="ic-suka {{ $kenangan->disukai_saya ? 'is-suka' : '' }}"
                        aria-pressed="{{ $kenangan->disukai_saya ? 'true' : 'false' }}"
                        data-ic-suka="{{ $kenangan->id_memory }}"
                        data-ic-suka-url="{{ route('ic.kenangan.suka', $kenangan->id_memory) }}">
                        <i class="bi {{ $kenangan->disukai_saya ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
                        <span>{{ $kenangan->jumlah_like }}</span>
                    </button>
                    @if ($kenangan->milik_saya)
                        <form method="POST" action="{{ route('ic.kenangan.hapus', $kenangan->id_memory) }}"
                            data-ic-konfirmasi data-ic-sekali data-ic-konfirmasi-judul="Hapus kenangan ini?"
                            data-ic-konfirmasi-tombol="Ya, hapus"
                            data-judul="Foto, cerita, dan komentarnya akan dihapus dari galeri dan tidak bisa dikembalikan.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ic-hapus">Hapus</button>
                        </form>
                    @endif
                </span>
            </div>
        </div>
    </article>

    <section class="ic-panel ic-jarak-atas ic-komentar" id="komentar" aria-labelledby="judul-komentar">
        <h2 class="ic-panel__judul" id="judul-komentar">Komentar ({{ $kenangan->jumlah_komentar }})</h2>

        <div class="ic-daftar ic-daftar--rapat">
            @forelse ($komentar as $k)
                <div class="ic-komentar__baris">
                    <x-inner-circle.avatar :member="$k->member" kelas="ic-avatar--kecil" />
                    <div class="ic-komentar__isi">
                        <p><b>{{ $k->nama_member }}</b>
                            <small>{{ $k->created_at->locale('id')->translatedFormat('j M Y, H.i') }}</small>
                        </p>
                        <p>{{ $k->isi }}</p>
                        @if ($k->member_id === $member->getKey() || $kenangan->milik_saya)
                            <form method="POST" action="{{ route('ic.kenangan.komentar.hapus', $k->id_komentar) }}"
                                data-ic-konfirmasi data-ic-sekali data-ic-konfirmasi-judul="Hapus komentar ini?"
                                data-ic-konfirmasi-tombol="Ya, hapus" data-judul="Komentar akan dihapus permanen.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ic-hapus">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="ic-bantuan">Belum ada komentar. Jadilah yang pertama menyapa.</p>
            @endforelse
        </div>

        @if ($komentar->hasPages())
            <nav class="ic-aksi-baris ic-jarak-atas" aria-label="Halaman komentar">
                @if ($komentar->onFirstPage())
                    <span class="ic-tombol ic-tombol--kecil ic-tombol--terang" aria-disabled="true">Sebelumnya</span>
                @else
                    <a class="ic-tombol ic-tombol--kecil ic-tombol--terang"
                        href="{{ $komentar->previousPageUrl() }}#komentar" rel="prev">Sebelumnya</a>
                @endif
                <span class="ic-bantuan">Halaman {{ $komentar->currentPage() }} dari {{ $komentar->lastPage() }}</span>
                @if ($komentar->hasMorePages())
                    <a class="ic-tombol ic-tombol--kecil" href="{{ $komentar->nextPageUrl() }}#komentar"
                        rel="next">Berikutnya</a>
                @else
                    <span class="ic-tombol ic-tombol--kecil ic-tombol--terang" aria-disabled="true">Berikutnya</span>
                @endif
            </nav>
        @endif

        @if ($terverifikasi)
            <form method="POST" action="{{ route('ic.kenangan.komentar', $kenangan->id_memory) }}" data-ic-sekali
                novalidate class="ic-komentar__form">
                @csrf
                <label class="ic-label" for="komentar-isi">Tulis komentar</label>
                <textarea class="ic-input ic-input--area" id="komentar-isi" name="isi" rows="2"
                    maxlength="{{ $maksKomentar }}" placeholder="Sapa dengan sopan ya…" required
                    @error('isi') aria-invalid="true" @enderror>{{ old('isi') }}</textarea>
                @error('isi')
                    <p class="ic-galat" role="alert">{{ $message }}</p>
                @enderror
                <p class="ic-bantuan">Maksimal {{ $maksKomentar }} karakter. Komentar tampil ke sesama anggota dengan nama
                    dan foto profilmu.</p>
                <button type="submit" class="ic-tombol">Kirim komentar <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></button>
            </form>
        @else
            <p class="ic-bantuan">Verifikasi emailmu dulu untuk menulis komentar.</p>
        @endif
    </section>
@endsection

@push('modal')
    <x-inner-circle.modal-penyuka />
@endpush
