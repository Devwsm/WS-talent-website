@extends('template.innerCircleLayout')

@section('title', 'Beranda')

@section('content')
    <x-inner-circle.panel-verifikasi />

    <div class="ic-beranda">
        <section class="ic-hero" aria-labelledby="judul-beranda">
            <p class="ic-eyebrow">{{ ic_teks('beranda.eyebrow') }}</p>
            <h1 class="ic-judul" id="judul-beranda">Beranda<span>.</span></h1>
            <h2 class="ic-hero__salam">
                {{ ic_teks('beranda.salam', ['nama' => \Illuminate\Support\Str::of($member->nama)->before(' ')]) }}</h2>
            <div class="ic-hero__tangan" aria-hidden="true">
                <x-inner-circle.ikon nama="ok-hand" kelas="ic-ikon--tangan" />
                <x-inner-circle.ikon nama="ok-hand" kelas="ic-ikon--tangan" />
            </div>
            <p class="ic-hero__teks">{{ ic_html('beranda.terima_kasih') }}</p>
            <a class="ic-hero__stiker" href="{{ route('ic.checkin') }}">{{ ic_teks('beranda.stiker') }}</a>
            <span class="ic-hero__bintang" aria-hidden="true">✳</span>
            <span class="ic-hero__cakram" aria-hidden="true"><span><img
                        src="{{ asset('aset/inner-circle/logo/wsic-wordmark-kompak.webp') }}" alt="" width="420"
                        height="185" decoding="async"></span></span>
        </section>

        <div class="ic-beranda__sisi">
            <x-inner-circle.kartu-anggota :member="$member" />
            <x-inner-circle.bar-poin :member="$member" :berikutnya="$berikutnya" />
        </div>
    </div>

    @if (app(\App\Services\InnerCircle\IcGame::class)->aktif())
        {{-- Ajakan main game: kartu ringkas satu baris, tanpa kueri (batas poin dari pengaturan CMS). --}}
        <section class="ic-ajakan-game" aria-labelledby="judul-game-beranda">
            <a class="ic-ajakan-game__tautan" href="{{ route('ic.komunitas', ['tab' => 'game']) }}">
                <span class="ic-ajakan-game__ikon" aria-hidden="true">
                    <img src="{{ asset('aset/game-mambo/pineapple.webp') }}" width="21" height="26" alt=""
                        loading="lazy" decoding="async">
                </span>
                <span class="ic-ajakan-game__isi">
                    <span class="ic-ajakan-game__label">{{ ic_teks('beranda.game.stiker') }}</span>
                    <b id="judul-game-beranda" class="ic-ajakan-game__judul">{{ ic_teks('beranda.game.judul') }}</b>
                    <span
                        class="ic-ajakan-game__teks">{{ ic_teks('beranda.game.deskripsi', app(\App\Services\InnerCircle\IcGame::class)->untukTeks()) }}</span>
                </span>
                <span class="ic-ajakan-game__tombol">{{ ic_teks('beranda.game.tombol') }} <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></span>
            </a>
        </section>
    @endif

    @if ($glSaya->isNotEmpty())
        <div class="ic-bagian-kepala">
            <h2 class="ic-judul-bagian">GL gratis saya</h2>
            <a class="ic-tautan" href="{{ route('ic.checkin') }}">Semua acara →</a>
        </div>
        <div class="ic-panel ic-daftar ic-daftar--rapat">
            @foreach ($glSaya as $gl)
                <a class="ic-gl-saya" href="{{ route('ic.acara.detail', $gl->event->kode) }}">
                    <span>
                        <b>{{ $gl->event->venue }}</b>
                        <span
                            class="ic-bantuan">{{ $gl->event->tanggal->locale('id')->translatedFormat('D, j M Y') }}{{ $gl->event->kota ? ' · ' . $gl->event->kota : '' }}</span>
                    </span>
                    <span
                        class="ic-gl-saya__status {{ $gl->status === 'menang' ? 'is-menang' : '' }}">{{ $gl->status === 'menang' ? 'Menang · lihat kartu' : 'Dalam antrean' }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">{{ ic_teks('beranda.aktivitas.judul') }}</h2>
        <a class="ic-tautan" href="{{ route('ic.profil') }}#riwayat">Lihat semua →</a>
    </div>
    <div class="ic-panel ic-daftar ic-daftar--rapat">
        @forelse ($aktivitas as $a)
            <x-inner-circle.baris-aktivitas :aktivitas="$a" />
        @empty
            <p class="ic-bantuan">{{ ic_html('beranda.aktivitas.kosong') }}</p>
        @endforelse
    </div>
@endsection
