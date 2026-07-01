@extends('template/dashboardLayout')
@section('content')
    <div class="w-full flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class=" text-white p-6 md:p-8 w-full mb-24 rounded-2xl">
            <div class="flex flex-col gap-4 justify-center">

                <div class="flex w-full gap-4 justify-center">
                    <div class="flex flex-col w-full p-4 bg-black/80 gap-4 rounded-xl border-2 border-[#5E0006]">
                        <h1 class="text-xl lg:text-2xl font-bold uppercase text-center my-3">banner list</h1>
                        <div class="grid grid-cols-1 gap-4 justify-center">
                            @foreach ($banner as $item)
                                <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center">
                                    <div class="grid grid-cols-1 gap-4">
                                        <!-- banner_name -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">banner name</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->banner_name }}
                                            </h1>
                                        </div>
                                        <!-- link_banner -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">link_banner</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->link_banner }}
                                            </h1>
                                        </div>
                                        <!-- banner cover -->
                                        <div class="flex flex-col gap-2 text-center justify-center items-center">
                                            <h1 class="text-xs uppercase text-white/60">banner cover</h1>
                                            <img src="{{ Storage::url('banner/' . $item->banner_cover) }}"
                                                alt="{{ $item->banner_name }}" loading="lazy" decoding="async"
                                                class="object-cover w-full h-full transition duration-300 rounded-lg">
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <a href="{{ route('banner') }}"
                                            class="w-full flex items-center justify-center text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
                                            <i class="bi bi-calendar-check me-2"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex w-full gap-4 justify-center">
                    <div class="flex flex-col w-full p-4 bg-black/80 gap-4 rounded-xl border-2 border-[#5E0006]">
                        <h1 class="text-xl lg:text-2xl font-bold uppercase text-center my-3">header list</h1>
                        <div class="grid grid-cols-1 gap-4 justify-center">
                            @foreach ($header as $item)
                                <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- header_title -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">header title</h1>
                                            <h1 class="text-xs md:text-lg font-semibold">
                                                {{ $item->header_title }}</h1>
                                        </div>
                                        <!-- header_name -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">header name</h1>
                                            <h1 class="text-xs md:text-lg font-semibold">{{ $item->header_name }}
                                            </h1>
                                        </div>
                                        <!-- header_description -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">header description</h1>
                                            <h1 class="text-xs md:text-lg font-semibold">
                                                {{ $item->header_description }}</h1>
                                        </div>
                                        <!-- link_header -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">link header</h1>
                                            <h1 class="text-xs md:text-lg font-semibold break-all">
                                                {{ $item->link_header }}</h1>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4">
                                        <!-- header img -->
                                        <div class="flex flex-col gap-2 text-center justify-center items-center">
                                            <h1 class="text-xs uppercase text-white/60">header img</h1>
                                            <img src="{{ Storage::url('header/img/' . $item->header_img) }}"
                                                alt="{{ $item->header_name }}" loading="lazy" decoding="async"
                                                class="object-cover w-full h-fit transition duration-300 rounded-lg">
                                        </div>
                                        <!-- header background -->
                                        <div class="flex flex-col gap-2 text-center justify-center items-center">
                                            <h1 class="text-xs uppercase text-white/60">header background</h1>
                                            <img src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                                alt="{{ $item->header_name }}" loading="lazy" decoding="async"
                                                class="object-cover w-full h-fit transition duration-300 rounded-lg">
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <a href="{{ route('headers') }}"
                                            class="w-full flex items-center justify-center text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
                                            <i class="bi bi-calendar-check me-2"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex w-full gap-4 justify-center">
                    <div class="flex flex-col w-full p-4 bg-black/80 gap-4 rounded-xl border-2 border-[#5E0006]">
                        <h1 class="text-xl lg:text-2xl font-bold uppercase text-center my-3">news list</h1>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 justify-center">
                            @foreach ($news as $item)
                                <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center">
                                    <div class="grid grid-cols-1 md:grid-cols-2">
                                        <!-- news_title -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">news title</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->news_title }}
                                            </h1>
                                        </div>
                                        <!-- news_description -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">news_description</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold">
                                                {{ $item->news_description }}
                                            </h1>
                                        </div>
                                        <!-- news_source -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">news_source</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold">
                                                {{ $item->news_source }}
                                            </h1>
                                        </div>
                                        <!-- news_date -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">news_date</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->news_date }}
                                            </h1>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4">
                                        <!-- news_link -->
                                        <div class="text-center md:text-left">
                                            <h1 class="text-xs uppercase text-white/60">news_link</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->news_link }}
                                            </h1>
                                        </div>
                                        <!-- news cover -->
                                        <div class="flex flex-col gap-2 text-center justify-center items-center">
                                            <h1 class="text-xs uppercase text-white/60">news cover</h1>
                                            <img src="{{ Storage::url('news/' . $item->news_cover) }}"
                                                alt="{{ $item->news_title }}" loading="lazy" decoding="async"
                                                class="object-cover aspect-square w-full max-w-xs transition duration-300 rounded-lg">
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <a href="{{ route('news') }}"
                                            class="w-full flex items-center justify-center text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
                                            <i class="bi bi-calendar-check me-2"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 justify-center">
                    <div class="flex flex-col h-fit gap-4 p-4 bg-black/80 rounded-xl border-2 border-[#5E0006]">
                        <h1 class="text-xl lg:text-2xl font-bold uppercase text-center my-3">albums list</h1>
                        @foreach ($albums as $item)
                            <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center">
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- albums_name -->
                                        <div class="text-center">
                                            <h1 class="text-xs uppercase text-white/60">albums name</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold">
                                                {{ $item->albums_name }}
                                            </h1>
                                        </div>
                                        <!-- link_spotify -->
                                        <div class="text-center">
                                            <h1 class="text-xs uppercase text-white/60">link spotify</h1>
                                            <h1 class="text-xs md:text-md lg:text-lg font-semibold break-all">
                                                {{ $item->link_spotify }}</h1>
                                        </div>
                                    </div>
                                    <!-- album cover -->
                                    <div class="flex flex-col gap-2 text-center justify-center items-center">
                                        <h1 class="text-xs uppercase text-white/60">album cover</h1>
                                        <img src="{{ Storage::url('albums/' . $item->albums_cover) }}"
                                            alt="{{ $item->albums_name }}" loading="lazy" decoding="async"
                                            class="object-cover aspect-square w-full max-w-xs transition duration-300 rounded-lg">
                                    </div>
                                </div>
                                {{-- action button --}}
                                <div class="flex flex-col w-full gap-4 mt-4 text-center">
                                    <h1 class="text-xs uppercase text-white/60">action button</h1>
                                    <div class="flex gap-2 justify-center items-center">
                                        <a href="{{ route('albums') }}"
                                            class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
                                            <i class="bi bi-disc-fill me-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-col h-fit gap-4 p-4 bg-black/80 rounded-xl border-2 border-[#5E0006]">
                        <h1 class="text-xl lg:text-2xl font-bold uppercase text-center my-3">merchandise list</h1>
                        @foreach ($merchandise as $item)
                            <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center">
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- merchandise_name -->
                                        <div class="text-center">
                                            <h1 class="text-xs uppercase text-white/60">merchandise name</h1>
                                            <h1 class="text-xs md:text-lg font-semibold">{{ $item->merchandise_name }}
                                            </h1>
                                        </div>
                                        <!-- link_merchandise -->
                                        <div class="text-center">
                                            <h1 class="text-xs uppercase text-white/60">link merchandise</h1>
                                            <h1 class="text-xs md:text-lg font-semibold break-all">
                                                {{ $item->link_merchandise }}</h1>
                                        </div>
                                    </div>
                                    <!-- merchandise cover -->
                                    <div class="flex flex-col gap-2 text-center justify-center items-center">
                                        <h1 class="text-xs uppercase text-white/60">merchandise cover</h1>
                                        <img src="{{ Storage::url('merchandise/' . $item->merchandise_cover) }}"
                                            alt="{{ $item->merchandise_name }}" loading="lazy" decoding="async"
                                            class="object-cover aspect-square w-full max-w-xs transition duration-300 rounded-lg">
                                    </div>
                                </div>
                                {{-- action button --}}
                                <div class="flex flex-col w-full gap-4 mt-4 text-center">
                                    <h1 class="text-xs uppercase text-white/60">action button</h1>
                                    <div class="flex gap-2 justify-center items-center">
                                        <a href="{{ route('merchandise') }}"
                                            class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
                                            <i class="bi bi-basket-fill"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
