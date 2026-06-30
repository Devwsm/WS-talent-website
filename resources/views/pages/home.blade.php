@extends('template.layout')
@section('content')
    <div class="hero-sectionflex flex-col w-full justify-center items-center">
        <div class="main-section flex flex-col">
            <div class="relative">
                @include('components/navbar')
                @include('components/banner')
            </div>
            <div class="relative">
                @include('components/videos')
            </div>
        </div>
    </div>
    <div id="profile" class="profile flex flex-col w-full">
        @include('components/profile/profile-teaser')
    </div>
    <div id="tour" class="schedule flex flex-col w-full">
        @include('components/schedule')
    </div>
    <div id="news" class="news flex flex-col w-full">
        @include('components/news')
    </div>
    <div class="follow bg-white flex flex-col w-full py-12 gap-2 justify-center items-center">
        <h1 class="capitalize text-md font-bold text-center">Get notified when new events are announced in your area</h1>
        <button class="bg-white w-fit border-2 border-black px-10 py-4 text-xs uppercase">folow whisnu santika</button>
    </div>
    <div class="product flex flex-col w-full">
        @include('components/albums')
        @include('components/merchandise')
    </div>
    @include('components/footer')

@endsection