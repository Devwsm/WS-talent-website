@extends('template.layout')
@section('content')
    <div class="min-h-screen flex flex-col uppercase text-white items-center justify-center text-center px-4">
        <h1 class="text-8xl font-bold">500</h1>
        <p class="text-2xl font-semibold mt-4">Internal Server Error</p>
        <p class="mt-2">Terjadi kesalahan pada server. Silakan coba lagi nanti.</p>
        <a href="{{ route('home') }}"
            class="mt-6 px-4 py-2 bg-white text-black rounded-lg font-semibold hover:bg-gray-100 transition">
            Kembali ke Beranda
        </a>
    </div>
@endsection
