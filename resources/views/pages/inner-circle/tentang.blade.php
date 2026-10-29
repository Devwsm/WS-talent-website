@extends('template.innerCirclePublikLayout')

@section('title', 'WS Inner Circle, komunitas penggemar')
@section('deskripsi',
    'Komunitas penggemar resmi DJ Whisnu Santika. Datang ke acara, kumpulkan stempel lewat check-in
    QR, dan buka hadiah eksklusif di setiap tahap.')

    {{-- Isi di bawah ini draf: ubah teksnya sesuai kebutuhan. Daftar hadiah dan jadwal acara diambil otomatis dari dashboard. --}}
@section('content')
    <section class="ic-hero ic-hero--publik" aria-labelledby="judul-hero">
        <p class="ic-eyebrow">Komunitas penggemar resmi</p>
        <h1 class="ic-judul" id="judul-hero">WS Inner Circle<span>.</span></h1>
        <p class="ic-deskripsi">Rumah bagi penggemar Whisnu Santika. Datang ke acaranya, kumpulkan stempel, dan buka
            hadiah eksklusif: dari merchandise sampai pengalaman personal.</p>

        <div class="ic-aksi-baris">
            @if ($sudahMasuk)
                <a href="{{ route('ic.beranda') }}" class="ic-tombol">Buka Inner Circle <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></a>
            @else
                <a href="{{ route('ic.daftar') }}" class="ic-tombol">Daftar sekarang <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></a>
                <a href="{{ route('ic.masuk') }}" class="ic-tombol ic-tombol--garis">Sudah punya akun? Masuk</a>
            @endif
        </div>
        <span class="ic-hero__bintang" aria-hidden="true">✳</span>
        <span class="ic-hero__cakram" aria-hidden="true"><span>WS</span></span>
    </section>

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">Cara kerjanya</h2>
    </div>
    <ol class="ic-langkah">
        <li>
            <h3>Daftar</h3>
            <p class="ic-bantuan">Buat akun dengan email dan kata sandi, atau langsung dengan akun Google.</p>
        </li>
        <li>
            <h3>Datang ke acara</h3>
            <p class="ic-bantuan">Pindai QR check-in di venue saat Whisnu Santika tampil.</p>
        </li>
        <li>
            <h3>Kumpulkan stempel</h3>
            <p class="ic-bantuan">Setiap acara yang kamu hadiri memberi satu stempel. Stempel tidak pernah berkurang.</p>
        </li>
        <li>
            <h3>Buka hadiah</h3>
            <p class="ic-bantuan">Di setiap tahap stempel, ajukan hadiahmu dan pantau statusnya sampai selesai.</p>
        </li>
    </ol>

    @if ($hadiah->isNotEmpty())
        <div class="ic-bagian-kepala">
            <h2 class="ic-judul-bagian">Hadiah di tiap tahap</h2>
        </div>
        <div class="ic-grid ic-grid--2">
            @foreach ($hadiah as $reward)
                <x-inner-circle.ubin-hadiah :reward="$reward" :lebar="$loop->last && $hadiah->count() % 2 === 1" :href="$sudahMasuk ? route('ic.hadiah') : route('ic.daftar')" :tampil-tag="false" />
            @endforeach
        </div>
    @endif

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">Acara berikutnya</h2>
    </div>
    @if ($acara->isNotEmpty())
        <div class="ic-daftar">
            @foreach ($acara as $event)
                <x-inner-circle.kartu-event :event="$event" :info="true" />
            @endforeach
        </div>
    @else
        <div class="ic-panel">
            <p class="ic-bantuan">Jadwal acara berikutnya akan diumumkan di sini dan di situs resmi.</p>
        </div>
    @endif

    <div class="ic-bagian-kepala">
        <h2 class="ic-judul-bagian">Pertanyaan umum</h2>
    </div>
    <div class="ic-faq">
        <details>
            <summary>Apa itu stempel?</summary>
            <p>Stempel adalah bukti kehadiranmu di sebuah acara. Setiap acara memberi satu stempel, dan jumlahnya tidak
                berkurang saat kamu mengajukan hadiah.</p>
        </details>
        <details>
            <summary>Bagaimana cara check-in di acara?</summary>
            <p>Di venue, pindai QR resmi acara dengan kamera HP, lalu masuk ke akunmu. Check-in dibuka pada hari acara dan
                hanya bisa dilakukan satu kali per acara.</p>
        </details>
        <details>
            <summary>Bagaimana cara mengajukan hadiah?</summary>
            <p>Saat stempelmu mencapai target sebuah tahap, buka halaman Hadiah lalu ajukan. Tim WSM akan meninjau dan
                memberi kabar lewat halaman Hadiah dan email.</p>
        </details>
        <details>
            <summary>Bagaimana data saya digunakan?</summary>
            <p>Kami hanya menyimpan data yang dibutuhkan untuk menjalankan komunitas ini. Penjelasan lengkapnya ada di <a
                    class="ic-tautan" href="{{ route('privasi') }}">Kebijakan Privasi</a>, dan kamu bisa menghapus
                akunmu kapan saja dari halaman Profil.</p>
        </details>
    </div>

    @unless ($sudahMasuk)
        <section class="ic-cta-akhir" aria-labelledby="judul-cta">
            <h2 id="judul-cta">Siap bergabung?</h2>
            <p>Buat akunmu dan mulai kumpulkan stempel di acara berikutnya.</p>
            <div class="ic-aksi-baris">
                <a href="{{ route('ic.daftar') }}" class="ic-tombol ic-tombol--terang">Daftar sekarang</a>
                <a href="{{ route('ic.masuk') }}" class="ic-tombol ic-tombol--garis">Masuk</a>
            </div>
        </section>
    @endunless
@endsection
