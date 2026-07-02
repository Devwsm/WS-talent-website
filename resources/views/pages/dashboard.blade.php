@extends('template/dashboardLayout')
@section('content')
    <div class="w-full flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="w-full grid grid-cols-1 gap-4 mb-24 p-6 md:p-8 text-white">

            {{-- Header --}}
            <div class="rounded-lg border border-[#5E0006] bg-black/80 gap-2 p-6">
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">
                    Dashboard
                </h1>
                <h1 class="text-white/60">
                    Kelola seluruh konten website Whisnu Santika dari satu tempat.
                </h1>
            </div>

            {{-- Statistik --}}
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                <div class="rounded-xl border border-[#5E0006] bg-black/80 p-6">
                    <i class="bi bi-images text-3xl text-[#5E0006]"></i>
                    <h1 class="mt-4 text-3xl font-bold">
                        {{ $banner->count() }}
                    </h1>
                    <h1 class="text-sm text-white/60 uppercase">
                        Banner
                    </h1>
                </div>

                <div class="rounded-xl border border-[#5E0006] bg-black/80 p-6">
                    <i class="bi bi-window-fullscreen text-3xl text-[#5E0006]"></i>
                    <h1 class="mt-4 text-3xl font-bold">
                        {{ $header->count() }}
                    </h1>
                    <h1 class="text-sm text-white/60 uppercase">
                        Header
                    </h1>
                </div>

                <div class="rounded-xl border border-[#5E0006] bg-black/80 p-6">
                    <i class="bi bi-newspaper text-3xl text-[#5E0006]"></i>
                    <h1 class="mt-4 text-3xl font-bold">
                        {{ $news->count() }}
                    </h1>
                    <h1 class="text-sm text-white/60 uppercase">
                        News
                    </h1>
                </div>

                <div class="rounded-xl border border-[#5E0006] bg-black/80 p-6">
                    <i class="bi bi-disc-fill text-3xl text-[#5E0006]"></i>
                    <h1 class="mt-4 text-3xl font-bold">
                        {{ $albums->count() }}
                    </h1>
                    <h1 class="text-sm text-white/60 uppercase">
                        Albums
                    </h1>
                </div>

                <div class="rounded-xl border border-[#5E0006] bg-black/80 p-6">
                    <i class="bi bi-bag-fill text-3xl text-[#5E0006]"></i>
                    <h1 class="mt-4 text-3xl font-bold">
                        {{ $merchandise->count() }}
                    </h1>
                    <h1 class="text-sm text-white/60 uppercase">
                        Merchandise
                    </h1>
                </div>
            </div>

            {{-- Preview Section --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                {{-- Banner --}}
                <div class="rounded-lg border border-[#5E0006] bg-black/80 overflow-hidden">
                    <div class="flex justify-between items-center p-6 border-b border-[#5E0006]">
                        <h1 class="font-bold uppercase">
                            Banner
                        </h1>
                        <a href="{{ route('banner') }}"
                            class="px-4 py-2 rounded-lg bg-[#5E0006] hover:bg-[#7D0008] transition">
                            Kelola
                        </a>
                    </div>
                    <div class="p-6">
                        @if ($banner->first())
                            <img src="{{ Storage::url('banner/' . $banner->first()->banner_cover) }}"
                                class="rounded-xl w-full object-cover">
                            <h3 class="mt-4 font-bold">
                                {{ $banner->first()->banner_name }}
                            </h3>
                        @endif
                    </div>

                </div>

                {{-- Header --}}
                <div class="rounded-lg border border-[#5E0006] bg-black/80 overflow-hidden">
                    <div class="flex justify-between items-center p-6 border-b border-[#5E0006]">
                        <h1 class="font-bold uppercase">
                            Header
                        </h1>
                        <a href="{{ route('headers') }}"
                            class="px-4 py-2 rounded-lg bg-[#5E0006] hover:bg-[#7D0008] transition">
                            Kelola
                        </a>
                    </div>
                    <div class="p-6">
                        @if ($header->first())
                            <img src="{{ Storage::url('header/img/' . $header->first()->header_img) }}"
                                class="rounded-xl w-full object-cover">
                            <h1 class="mt-4 font-bold">
                                {{ $header->first()->header_name }}
                            </h1>
                            <h1 class="text-white/60 mt-1">
                                {{ $header->first()->header_title }}
                            </h1>
                        @endif
                    </div>
                </div>
            </div>

            {{-- News --}}
            <div class="rounded-lg border border-[#5E0006] bg-black/80">
                <div class="flex justify-between items-center p-6 border-b border-[#5E0006]">
                    <h1 class="font-bold uppercase">
                        Latest News
                    </h1>
                    <a href="{{ route('news') }}" class="px-4 py-2 rounded-lg bg-[#5E0006] hover:bg-[#7D0008] transition">
                        Kelola
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6">
                    @foreach ($news->take(3) as $item)
                        <div class="rounded-xl bg-black/40 overflow-hidden">
                            <img src="{{ Storage::url('news/' . $item->news_cover) }}"
                                class="w-full object-cover">
                            <div class="p-4">
                                <h1 class="font-semibold line-clamp-2">
                                    {{ $item->news_title }}
                                </h1>
                                <h1 class="text-sm text-white/60 mt-2">
                                    {{ $item->news_source }}
                                </h1>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Album & Merchandise --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                {{-- Album --}}
                <div class="rounded-lg border border-[#5E0006] bg-black/80">
                    <div class="flex justify-between items-center p-6 border-b border-[#5E0006]">
                        <h1 class="font-bold uppercase">
                            Latest Albums
                        </h1>
                        <a href="{{ route('albums') }}" class="px-4 py-2 rounded-lg bg-[#5E0006] hover:bg-[#7D0008]">
                            Kelola
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-6">
                        @foreach ($albums->take(4) as $item)
                            <div>
                                <img src="{{ Storage::url('albums/' . $item->albums_cover) }}"
                                    class="rounded-lg aspect-square object-cover">
                                <h1 class="mt-2 text-sm font-semibold text-center">
                                    {{ $item->albums_name }}
                                </h1>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Merchandise --}}
                <div class="rounded-lg border border-[#5E0006] bg-black/80">
                    <div class="flex justify-between items-center p-6 border-b border-[#5E0006]">
                        <h1 class="font-bold uppercase">
                            Merchandise
                        </h1>
                        <a href="{{ route('merchandise') }}" class="px-4 py-2 rounded-lg bg-[#5E0006] hover:bg-[#7D0008]">
                            Kelola
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-6">
                        @foreach ($merchandise->take(4) as $item)
                            <div>
                                <img src="{{ Storage::url('merchandise/' . $item->merchandise_cover) }}"
                                    class="rounded-lg aspect-square object-cover">
                                <h1 class="mt-2 text-sm font-semibold text-center">
                                    {{ $item->merchandise_name }}
                                </h1>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection