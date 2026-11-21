@extends('template.innerCircleAuthLayout')

@section('title', 'Daftar')

@section('content')
    <h1 class="ic-auth__judul">{{ ic_teks('daftar.judul') }}</h1>
    <p class="ic-auth__intro">{{ ic_html('daftar.intro') }}</p>

    {{-- Persetujuan Kebijakan Privasi: wajib untuk daftar dengan sandi maupun dengan Google. Kotaknya ada di luar form
         (atribut form="form-daftar") supaya satu centang berlaku untuk keduanya. --}}
    <label class="ic-centang ic-centang--setuju">
        <input type="checkbox" id="setuju-privasi" name="setuju_privasi" value="1" form="form-daftar"
            @checked(old('setuju_privasi')) required
            @error('setuju_privasi') aria-invalid="true" aria-describedby="setuju-privasi-galat" @enderror>
        <span>Saya sudah membaca dan menyetujui <a class="ic-tautan" href="{{ route('privasi') }}" target="_blank"
                rel="noopener">Kebijakan Privasi</a> WS Inner Circle, termasuk pengolahan data pribadiku untuk
            keanggotaan, check-in, poin, dan hadiah.</span>
    </label>
    @error('setuju_privasi')
        <p class="ic-galat" id="setuju-privasi-galat" role="alert">{{ $message }}</p>
    @enderror

    <x-inner-circle.tombol-google teks="Daftar dengan Google" butuh-setuju="#setuju-privasi" />

    <form method="POST" action="{{ route('ic.daftar.proses') }}" id="form-daftar" novalidate>
        @csrf

        <label class="ic-label" for="nama">Nama lengkap</label>
        <input class="ic-input" type="text" id="nama" name="nama" value="{{ old('nama') }}"
            placeholder="Nama lengkap" autocomplete="name" maxlength="100" required
            @error('nama') aria-invalid="true" aria-describedby="nama-galat" @enderror>
        @error('nama')
            <p class="ic-galat" id="nama-galat" role="alert">{{ $message }}</p>
        @enderror

        <label class="ic-label" for="email">Email</label>
        <input class="ic-input" type="email" id="email" name="email" value="{{ old('email') }}"
            placeholder="nama@email.com" autocomplete="email" required
            @error('email') aria-invalid="true" aria-describedby="email-galat" @enderror>
        @error('email')
            <p class="ic-galat" id="email-galat" role="alert">{{ $message }}</p>
        @enderror

        <label class="ic-label" for="sandi">Kata sandi</label>
        <input class="ic-input" type="password" id="sandi" name="sandi" placeholder="Minimal 8 karakter"
            autocomplete="new-password" minlength="8" required
            aria-describedby="sandi-bantuan{{ $errors->has('sandi') ? ' sandi-galat' : '' }}"
            @error('sandi') aria-invalid="true" @enderror>
        <p class="ic-bantuan" id="sandi-bantuan">Minimal 8 karakter, memuat huruf besar, huruf kecil, dan angka. Simbol
            boleh, emoji tidak.</p>
        @error('sandi')
            <p class="ic-galat" id="sandi-galat" role="alert">{{ $message }}</p>
        @enderror

        <label class="ic-label" for="sandi_confirmation">Ulangi kata sandi</label>
        <input class="ic-input" type="password" id="sandi_confirmation" name="sandi_confirmation"
            placeholder="Ulangi kata sandi" autocomplete="new-password" required>

        <label class="ic-centang">
            <input type="checkbox" data-ic-tampil-sandi="#sandi, #sandi_confirmation">
            Tampilkan kata sandi
        </label>

        <button type="submit" class="ic-tombol ic-tombol--penuh">Buat akun <i class="bi bi-arrow-right"
                aria-hidden="true"></i></button>
    </form>

    <p class="ic-auth__alih">Sudah punya akun? <a href="{{ route('ic.masuk') }}">Masuk</a></p>
@endsection
