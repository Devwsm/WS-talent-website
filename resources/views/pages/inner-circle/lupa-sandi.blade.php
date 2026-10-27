@extends('template.innerCircleAuthLayout')

@section('title', 'Lupa kata sandi')

@section('content')
    <h1 class="ic-auth__judul">Lupa kata sandi?</h1>
    <p class="ic-auth__intro">Masukkan email akunmu. Kami kirim tautan untuk membuat kata sandi baru.</p>

    {{-- Jawaban sama untuk semua email (terdaftar atau tidak), supaya halaman ini tidak bisa dipakai menebak siapa yang punya akun. --}}
    @if (session('lupa_terkirim'))
        <p class="ic-bantuan" role="status">
            Kalau <b>{{ session('lupa_terkirim') }}</b> terdaftar, email berisi petunjuk sudah kami kirim.
            Cek juga folder spam. Tautannya berlaku 60 menit.
        </p>
    @endif

    <form method="POST" action="{{ route('ic.sandi.kirim') }}" novalidate>
        @csrf

        <label class="ic-label" for="email">Email</label>
        <input class="ic-input" type="email" id="email" name="email" value="{{ old('email') }}"
            placeholder="nama@email.com" autocomplete="email" required
            @error('email') aria-invalid="true" aria-describedby="email-galat" @enderror>
        @error('email')
            <p class="ic-galat" id="email-galat" role="alert">{{ $message }}</p>
        @enderror

        <button type="submit" class="ic-tombol ic-tombol--penuh">Kirim tautan <i class="bi bi-arrow-right"
                aria-hidden="true"></i></button>
    </form>

    <x-inner-circle.tombol-google teks="Atau masuk dengan Google" />

    <p class="ic-auth__alih">Sudah ingat? <a href="{{ route('ic.masuk') }}">Kembali ke Masuk</a></p>
@endsection
