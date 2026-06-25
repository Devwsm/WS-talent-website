@extends('template.layout')
@section('content')
    <div class="min-h-screen flex flex-col uppercase text-white items-center justify-center text-center px-4">
        <h1 class="text-8xl font-bold">404</h1>
        <p class="text-2xl font-semibold mt-4">Halaman tidak ditemukan</p>
        <p class="mt-2">Halaman yang kamu cari tidak ada atau sudah dipindahkan.</p>
        <a href="{{ route('home') }}"
            class="mt-6 px-4 py-2 bg-white text-black rounded-lg font-semibold hover:bg-gray-100 transition">
            Kembali ke Beranda
        </a>
    </div>
@endsection
