@extends('template.layout')
@section('content')
    <div class="hero-sectionflex flex-col w-full justify-center items-center">
        <div class="relative z-10">
            @include('components/navbar')
        </div>
        <div class="relative">
            @include('components/hero-card')
        </div>
    </div>
    <div id="tour" class="schedule flex flex-col w-full px-6 lg:p-12">
        @include('components/schedule')
    </div>
    <div id="news" class="news flex flex-col w-full px-6 lg:p-12">
        @include('components/news')
    </div>
    <div class="follow flex flex-col w-full gap-2 p-12 justify-center items-center">
        <h1 class="capitalize text-md font-bold text-center">Get notified when new events are announced in your area</h1>
        <button class="bg-white w-fit border-2 border-black px-10 py-4 text-xs uppercase">folow whisnu santika</button>
    </div>
    <div class="product flex flex-col w-full px-6">
        @include('components/albums')
        @include('components/merchandise')
    </div>
    <div class="videos-section flex flex-col w-full px-6">
        @include('components/videos')
    </div>
    @include('components/footer')

@endsection