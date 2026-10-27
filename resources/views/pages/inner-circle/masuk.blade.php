@extends('template.innerCircleAuthLayout')

@section('title', 'Masuk')

@section('content')
    <h1 class="ic-auth__judul">Selamat datang kembali</h1>
    <p class="ic-auth__intro">Masuk dengan akun yang sudah terdaftar.</p>

    <x-inner-circle.tombol-google teks="Lanjutkan dengan Google" />

    <form method="POST" action="{{ route('ic.masuk.proses') }}" novalidate>
        @csrf

        <label class="ic-label" for="email">Email</label>
        <input class="ic-input" type="email" id="email" name="email" value="{{ old('email') }}"
            placeholder="nama@email.com" autocomplete="username" required
            @error('email') aria-invalid="true" aria-describedby="email-galat" @enderror>
        @error('email')
            <p class="ic-galat" id="email-galat" role="alert">{{ $message }}</p>
        @enderror

        <label class="ic-label" for="sandi">Kata sandi</label>
        <input class="ic-input" type="password" id="sandi" name="sandi" placeholder="Masukkan kata sandi"
            autocomplete="current-password" required
            @error('sandi') aria-invalid="true" aria-describedby="sandi-galat" @enderror>
        @error('sandi')
            <p class="ic-galat" id="sandi-galat" role="alert">{{ $message }}</p>
        @enderror

        <label class="ic-centang">
            <input type="checkbox" data-ic-tampil-sandi="#sandi">
            Tampilkan kata sandi
        </label>

        <button type="submit" class="ic-tombol ic-tombol--penuh">Masuk <i class="bi bi-arrow-right"
                aria-hidden="true"></i></button>
    </form>

    <p class="ic-auth__alih">Belum punya akun? <a href="{{ route('ic.daftar') }}">Daftar sekarang</a></p>
    <p class="ic-bantuan ic-auth__privasi">Dengan masuk, kamu menyetujui <a class="ic-tautan" href="{{ route('privasi') }}"
            target="_blank" rel="noopener">Kebijakan Privasi</a> kami.</p>
@endsection
