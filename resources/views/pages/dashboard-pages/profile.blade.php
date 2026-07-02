@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')

        <div class="bg-black/80 text-white p-6 md:p-8 mb-24 w-full rounded-lg flex flex-col gap-10">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center">profile list</h1>

            {{-- PROFILE CARD --}}
            {{--
            <section class="flex flex-col gap-4 w-full">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center">profile card</h2>
                @include('components/profile/dashboard/profile-card')
            </section>
            --}}

            {{-- GENRE --}}
            {{--
            <section class="flex flex-col gap-4 w-full">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center">genre</h2>
                @include('components/profile/dashboard/genre')
            </section>
            --}}

            {{-- BIO --}}
            {{--
            <section class="flex flex-col gap-4 w-full">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center">bio</h2>
                @include('components/profile/dashboard/bio')
            </section>
            --}}

            {{-- STATISTIK --}}
            <section class="flex flex-col gap-4 w-full pt-6 border-t border-white/10">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">statistik</h2>
                @include('components/profile/dashboard/statistik')
            </section>

            {{-- HIGHLIGHT --}}
            <section class="flex flex-col gap-4 w-full pt-6 border-t border-white/10">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">highlight</h2>
                @include('components/profile/dashboard/highlight')
            </section>

            {{-- COLLAB --}}
            {{--
            <section class="flex flex-col gap-4 w-full pt-6 border-t border-white/10">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">collab</h2>
                @include('components/profile/dashboard/collab')
            </section>
            --}}

            {{-- MEDIA COVERAGE --}}
            {{--
            <section class="flex flex-col gap-4 w-full pt-6 border-t border-white/10">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">media coverage</h2>
                @include('components/profile/dashboard/media-coverage')
            </section>
            --}}

            {{-- BOOKING --}}
            {{--
            <section class="flex flex-col gap-4 w-full pt-6 border-t border-white/10">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">booking</h2>
                @include('components/profile/dashboard/booking')
            </section>
            --}}

            {{-- MEDIA SOSIAL --}}
            {{--
            <section class="flex flex-col gap-4 w-full pt-6 border-t border-white/10">
                <h2 class="text-xl lg:text-2xl font-bold uppercase text-center mb-2">media sosial</h2>
                @include('components/profile/dashboard/media-sosial')
            </section>
            --}}

        </div>
    </div>
@endsection
