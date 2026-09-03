@extends('template/dashboardLayout')
@section('content')
    <div class="w-full flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="w-full grid grid-cols-1 gap-4 mb-24 p-6 md:p-8 text-white">

            {{-- Header --}}
            <div class="rounded-lg border border-white/15 bg-white/5 gap-2 p-6">
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">
                    Halo, {{ session('user', 'Admin') }} 👋
                </h1>
                <p class="text-white/50">
                    Kelola seluruh konten website Whisnu Santika dari satu tempat.
                </p>
            </div>

            {{-- Statistik ringkas --}}
            @php
                $stats = [
                    ['icon' => 'bi-images', 'count' => $banner->count(), 'label' => 'Banner'],
                    ['icon' => 'bi-window-fullscreen', 'count' => $header->count(), 'label' => 'Header'],
                    ['icon' => 'bi-newspaper', 'count' => $news->count(), 'label' => 'News'],
                    ['icon' => 'bi-disc-fill', 'count' => $albums->count(), 'label' => 'Albums'],
                    ['icon' => 'bi-bag-fill', 'count' => $merchandise->count(), 'label' => 'Merchandise'],
                ];
            @endphp
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                @foreach ($stats as $stat)
                    @include('components.dashboard.card.stat-card', $stat)
                @endforeach
            </div>

            {{-- Banner & Header --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                @component('components.dashboard.card.card', ['title' => 'Banner', 'href' => route('banner')])
                    @if ($banner->first())
                        <img src="{{ Storage::url('banner/' . $banner->first()->banner_cover) }}"
                            alt="{{ $banner->first()->banner_name }}" class="rounded-xl w-full aspect-video object-cover">
                        <h3 class="mt-4 font-bold">
                            {{ $banner->first()->banner_name }}
                        </h3>
                        @if ($banner->count() > 1)
                            <p class="text-sm text-white/40 mt-1">+{{ $banner->count() - 1 }} banner lainnya</p>
                        @endif
                    @else
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada banner yang diupload.',
                        ])
                    @endif
                @endcomponent

                @component('components.dashboard.card.card', ['title' => 'Header', 'href' => route('headers')])
                    @if ($header->first())
                        <img src="{{ Storage::url('header/img/' . $header->first()->header_img) }}"
                            alt="{{ $header->first()->header_name }}" class="rounded-xl w-full aspect-video object-cover">
                        <h4 class="mt-4 font-bold">
                            {{ $header->first()->header_name }}
                        </h4>
                        <p class="text-white/50 mt-1 text-sm">
                            {{ $header->first()->header_title }}
                        </p>
                        @if ($header->count() > 1)
                            <p class="text-sm text-white/40 mt-1">+{{ $header->count() - 1 }} header lainnya</p>
                        @endif
                    @else
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada header yang diupload.',
                        ])
                    @endif
                @endcomponent
            </div>

            {{-- News --}}
            @component('components.dashboard.card.card', ['title' => 'Latest News', 'href' => route('news')])
                @if ($news->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada berita yang ditambahkan.',
                    ])
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach ($news->take(3) as $item)
                            <div class="rounded-xl border border-white/10 overflow-hidden">
                                <img src="{{ Storage::url('news/' . $item->news_cover) }}" alt="{{ $item->news_title }}"
                                    class="w-full aspect-video object-cover">
                                <div class="p-4">
                                    <h4 class="font-semibold line-clamp-2">
                                        {{ $item->news_title }}
                                    </h4>
                                    <p class="text-sm text-white/50 mt-2">
                                        {{ $item->news_source }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endcomponent

            {{-- Album & Merchandise --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                @component('components.dashboard.card.card', ['title' => 'Latest Albums', 'href' => route('albums')])
                    @if ($albums->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada album yang ditambahkan.',
                        ])
                    @else
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ($albums->take(4) as $item)
                                <div>
                                    <img src="{{ Storage::url('albums/' . $item->albums_cover) }}"
                                        alt="{{ $item->albums_name }}" class="rounded-lg aspect-square object-cover w-full">
                                    <p class="mt-2 text-sm font-semibold text-center line-clamp-1">
                                        {{ $item->albums_name }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endcomponent

                @component('components.dashboard.card.card', ['title' => 'Merchandise', 'href' => route('merchandise')])
                    @if ($merchandise->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada merchandise yang ditambahkan.',
                        ])
                    @else
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ($merchandise->take(4) as $item)
                                <div>
                                    <img src="{{ Storage::url('merchandise/' . $item->merchandise_cover) }}"
                                        alt="{{ $item->merchandise_name }}"
                                        class="rounded-lg aspect-square object-cover w-full">
                                    <p class="mt-2 text-sm font-semibold text-center line-clamp-1">
                                        {{ $item->merchandise_name }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endcomponent
            </div>
        </div>
    </div>
@endsection
