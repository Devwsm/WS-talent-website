@extends('template.innerCircleAuthLayout')

@section('title', 'Kata sandi baru')

@section('content')
    <h1 class="ic-auth__judul">Buat kata sandi baru</h1>
    <p class="ic-auth__intro">Pilih kata sandi baru untuk akun WS Inner Circle kamu.</p>

    <form method="POST" action="{{ route('ic.sandi.atur.proses') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label class="ic-label" for="email">Email</label>
        <input class="ic-input" type="email" id="email" name="email" value="{{ old('email', $email) }}"
            autocomplete="username" required @error('email') aria-invalid="true" aria-describedby="email-galat" @enderror>
        @error('email')
            <p class="ic-galat" id="email-galat" role="alert">{{ $message }}</p>
        @enderror

        <label class="ic-label" for="sandi">Kata sandi baru</label>
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

        <button type="submit" class="ic-tombol ic-tombol--penuh">Simpan kata sandi <i class="bi bi-arrow-right"
                aria-hidden="true"></i></button>
    </form>

    <p class="ic-auth__alih">Tautan bermasalah? <a href="{{ route('ic.sandi.lupa') }}">Minta tautan baru</a></p>
@endsection
