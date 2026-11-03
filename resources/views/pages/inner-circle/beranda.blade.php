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
            <x-inner-circle.bar-stempel :member="$member" :berikutnya="$berikutnya" />
        </div>
    </div>

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
