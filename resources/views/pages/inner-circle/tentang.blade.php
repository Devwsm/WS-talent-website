@extends('template.innerCirclePublikLayout')

@section('title', ic_teks('tentang.meta.judul'))
@section('deskripsi', ic_teks('tentang.meta.deskripsi'))

{{-- Semua teks di halaman ini bisa diubah dari CMS Inner Circle, tab Konten (bagian "Halaman Tentang"). Daftar hadiah dan
    jadwal acara diambil otomatis dari menu Hadiah dan Acara & QR; langkah dan FAQ dari daftar di tab Konten. --}}
@php
    $langkah = ic_daftar('langkah');
    $faq = ic_daftar('faq');
@endphp
@section('content')
    <section class="ic-hero ic-hero--publik" aria-labelledby="judul-hero">
        <p class="ic-eyebrow">{{ ic_teks('tentang.hero.eyebrow') }}</p>
        <h1 class="ic-judul" id="judul-hero">{{ ic_teks('tentang.hero.judul') }}<span>.</span></h1>
        <p class="ic-deskripsi">{{ ic_html('tentang.hero.deskripsi') }}</p>

        <div class="ic-aksi-baris">
            @if ($sudahMasuk)
                <a href="{{ route('ic.beranda') }}" class="ic-tombol">{{ ic_teks('tentang.tombol.buka') }} <i
                        class="bi bi-arrow-right" aria-hidden="true"></i></a>
            @else
                <a href="{{ route('ic.daftar') }}" class="ic-tombol">{{ ic_teks('tentang.tombol.daftar') }} <i
                        class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <a href="{{ route('ic.masuk') }}"
                    class="ic-tombol ic-tombol--garis">{{ ic_teks('tentang.tombol.masuk') }}</a>
            @endif
        </div>
        <span class="ic-hero__bintang" aria-hidden="true">✳</span>
        <span class="ic-hero__cakram" aria-hidden="true"><span><img
                    src="{{ asset('aset/inner-circle/logo/wsic-wordmark-kompak.webp') }}" alt="" width="420"
                    height="185" decoding="async"></span></span>
    </section>

    @if ($langkah->isNotEmpty())
        <div class="ic-bagian-kepala">
            <h2 class="ic-judul-bagian">{{ ic_teks('tentang.langkah.judul') }}</h2>
        </div>
        <ol class="ic-langkah">
            @foreach ($langkah as $butir)
                <li>
                    <h3>{{ $butir->judul }}</h3>
                    <p class="ic-bantuan">{{ ic_html_daftar('langkah', $butir->isi) }}</p>
                </li>
            @endforeach
        </ol>
    @endif

    @if ($hadiah->isNotEmpty())
        <div class="ic-bagian-kepala">
            <h2 class="ic-judul-bagian">{{ ic_teks('tentang.hadiah.judul') }}</h2>
        </div>
        <div class="ic-grid ic-grid--2">
            @foreach ($hadiah as $reward)
                <x-inner-circle.ubin-hadiah :reward="$reward" :lebar="$loop->last && $hadiah->count() % 2 === 1" :href="$sudahMasuk ? route('ic.hadiah') : route('ic.daftar')" :tampil-tag="false" />
            @endforeach
        </div>
    @endif

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">{{ ic_teks('tentang.acara.judul') }}</h2>
    </div>
    @if ($acara->isNotEmpty())
        <div class="ic-daftar">
            @foreach ($acara as $event)
                <x-inner-circle.kartu-event :event="$event" :info="true" />
            @endforeach
        </div>
    @else
        <div class="ic-panel">
            <p class="ic-bantuan">{{ ic_html('tentang.acara.kosong') }}</p>
        </div>
    @endif

    @if ($faq->isNotEmpty())
        <div class="ic-bagian-kepala">
            <h2 class="ic-judul-bagian">{{ ic_teks('tentang.faq.judul') }}</h2>
        </div>
        <div class="ic-faq">
            @foreach ($faq as $butir)
                <details>
                    <summary>{{ $butir->judul }}</summary>
                    <p>{{ ic_html_daftar('faq', $butir->isi) }}</p>
                </details>
            @endforeach
        </div>
    @endif

    @unless ($sudahMasuk)
        <section class="ic-cta-akhir" aria-labelledby="judul-cta">
            <h2 id="judul-cta">{{ ic_teks('tentang.cta.judul') }}</h2>
            <p>{{ ic_html('tentang.cta.deskripsi') }}</p>
            <div class="ic-aksi-baris">
                <a href="{{ route('ic.daftar') }}"
                    class="ic-tombol ic-tombol--terang">{{ ic_teks('tentang.tombol.daftar') }}</a>
                <a href="{{ route('ic.masuk') }}" class="ic-tombol ic-tombol--garis">{{ ic_teks('tentang.cta.masuk') }}</a>
            </div>
        </section>
    @endunless
@endsection
