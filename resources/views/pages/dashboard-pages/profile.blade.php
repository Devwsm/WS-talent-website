@extends('template/dashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div
            class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-10
            px-5 md:px-10 pt-8 pb-28 text-white">

            <div>
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">Profile</h1>
                <p class="text-white/50 mt-1">Kelola semua konten halaman Profile — Hero, Genre, Bio, Statistik,
                    Highlight, Kolaborasi, Media Coverage, Booking & Kontak, dan Media Sosial.</p>
            </div>

            @include('components/errors')
            @include('components/success')

            {{-- HERO --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-person-badge text-white/50"></i> Hero
                </h2>
                @include('components/profile/dashboard/profile-card')
            </section>

            {{-- GENRE --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-tags text-white/50"></i> Genre
                </h2>
                @include('components/profile/dashboard/genre')
            </section>

            {{-- BIO --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-file-text text-white/50"></i> Bio
                </h2>
                @include('components/profile/dashboard/bio')
            </section>

            {{-- STATISTIK --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-bar-chart text-white/50"></i> Statistik
                </h2>
                @include('components/profile/dashboard/statistik')
            </section>

            {{-- HIGHLIGHT --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-star text-white/50"></i> Highlight
                </h2>
                @include('components/profile/dashboard/highlight')
            </section>

            {{-- COLLAB --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-people text-white/50"></i> Kolaborasi
                </h2>
                @include('components/profile/dashboard/collab')
            </section>

            {{-- MEDIA COVERAGE --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-newspaper text-white/50"></i> Media Coverage
                </h2>
                @include('components/profile/dashboard/media-coverage')
            </section>

            {{-- BOOKING --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-envelope text-white/50"></i> Booking & Kontak
                </h2>
                @include('components/profile/dashboard/booking')
            </section>

            {{-- MEDIA SOSIAL --}}
            <section class="flex flex-col gap-4 w-full">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-share text-white/50"></i> Media Sosial
                </h2>
                @include('components/profile/dashboard/media-sosial')
            </section>

        </div>
    </div>
@endsection
