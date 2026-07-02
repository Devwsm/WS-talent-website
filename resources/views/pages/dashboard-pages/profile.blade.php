@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')

        <div class="bg-black/80 text-white p-6 md:p-8 mb-24 w-full rounded-lg flex flex-col gap-10">

            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center">profile list</h1>

            @include('components/errors')
            @include('components/success')

            {{-- PROFILE CARD --}}
            {{-- <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">profile card</h2>
                    @include('components/profile/dashboard/profile-card')
                </div>
            </section> --}}

            {{-- GENRE --}}
            {{-- <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">genre</h2>
                    @include('components/profile/dashboard/genre')
                </div>
            </section> --}}

            {{-- BIO --}}
            {{-- <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">bio</h2>
                    @include('components/profile/dashboard/bio')
                </div>
            </section> --}}

            {{-- STATISTIK --}}
            <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">statistik</h2>
                    @include('components/profile/dashboard/statistik')
                </div>
            </section>

            {{-- HIGHLIGHT --}}
            {{-- <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">highlight</h2>
                    @include('components/profile/dashboard/highlight')
                </div>
            </section> --}}

            {{-- COLLAB --}}
            {{-- <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">collab</h2>
                    @include('components/profile/dashboard/collab')
                </div>
            </section> --}}

            {{-- MEDIA COVERAGE --}}
            {{-- <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">media coverage</h2>
                    @include('components/profile/dashboard/media-coverage')
                </div>
            </section> --}}

            {{-- BOOKING --}}
            {{-- <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">booking</h2>
                    @include('components/profile/dashboard/booking')
                </div>
            </section> --}}

            {{-- MEDIA SOSIAL --}}
            {{-- <section class="grid grid-cols-1 gap-4 w-full">
                <div class="flex flex-col gap-4 w-full">
                    <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">media sosial</h2>
                    @include('components/profile/dashboard/media-sosial')
                </div>
            </section> --}}

        </div>
    </div>
@endsection
