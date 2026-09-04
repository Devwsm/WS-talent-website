@extends('template/dashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak: blob blur + dot grid, biar gak polos tapi tetap netral --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 z-0 opacity-5"
            style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 26px 26px;">
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-40 -right-32 w-96 h-96 rounded-full blur-3xl"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.5;"></div>
        <div aria-hidden="true" class="pointer-events-none absolute top-1/2 -left-40 w-80 h-80 rounded-full blur-3xl"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        @php
            $greeting = match (true) {
                now()->hour < 11 => 'Selamat pagi',
                now()->hour < 15 => 'Selamat siang',
                now()->hour < 18 => 'Selamat sore',
                default => 'Selamat malam',
            };
        @endphp

        <div
            class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6
            px-5 md:px-10 pt-8 pb-28 text-white">

            {{-- Greeting --}}
            <div class="relative rounded-3xl border border-white/10 bg-white/3 overflow-hidden p-8">
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">
                    {{ $greeting }}, {{ session('user', 'Admin') }} 👋
                </h1>
                <p class="text-white/50 mt-1">
                    Ini preview konten yang lagi tayang di website Whisnu Santika.
                </p>
            </div>

            {{-- Row 1: Header (hero) + Banner & Warna/Profil --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">

                {{-- Header --}}
                <div class="lg:col-span-2">
                    @component('components.dashboard.card.card', [
                        'title' => 'Header (' . $header->count() . ')',
                        'href' => route('headers'),
                        'icon' => 'bi-card-image',
                        'flush' => true,
                    ])
                        @if ($header->first())
                            @php $h = $header->first(); @endphp
                            <div class="relative min-h-80 lg:min-h-96 w-full overflow-hidden bg-black">
                                <img src="{{ Storage::url('header/img/' . $h->header_img) }}" alt="{{ $h->header_name }}"
                                    class="absolute inset-0 w-full h-full object-cover opacity-50">
                                <div class="absolute inset-0 bg-linear-to-t from-black via-black/40 to-black/10"></div>

                                <div class="relative z-10 h-full flex flex-col justify-end gap-3 p-6 lg:p-8">
                                    <span style="border-color: {{ $h->header_color }}99;"
                                        class="inline-flex items-center gap-2 w-fit bg-white/10 backdrop-blur border rounded-full px-3 py-1 text-xs">
                                        <span style="background-color: {{ $h->header_color }};" aria-hidden="true"
                                            class="w-2 h-2 rounded-full"></span>
                                        {{ $h->header_title }}
                                    </span>
                                    <h3 class="text-xl lg:text-2xl font-bold">{{ $h->header_name }}</h3>
                                    <p class="text-white/60 text-sm line-clamp-2 max-w-md">{{ $h->header_description }}</p>
                                    @if ($header->count() > 1)
                                        <p class="text-xs text-white/40">+{{ $header->count() - 1 }} header lainnya</p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-6">
                                @include('components/dashboard/card/empty-state', [
                                    'message' => 'Belum ada header yang diupload.',
                                ])
                            </div>
                        @endif
                    @endcomponent
                </div>

                {{-- Banner + Warna Web/Profil --}}
                <div class="flex flex-col gap-6 h-full">
                    {{-- Banner --}}
                    <div class="flex-1">
                        @component('components.dashboard.card.card', [
                            'title' => 'Banner (' . $banner->count() . ')',
                            'href' => route('banner'),
                            'icon' => 'bi-images',
                            'flush' => true,
                        ])
                            @if ($banner->first())
                                <div class="relative w-full h-40 lg:h-full min-h-40 overflow-hidden bg-black">
                                    <img src="{{ Storage::url('banner/' . $banner->first()->banner_cover) }}"
                                        alt="{{ $banner->first()->banner_name }}"
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/10 to-transparent">
                                    </div>
                                    <div class="relative z-10 h-full flex flex-col justify-end p-4">
                                        <p class="font-semibold text-sm line-clamp-1">{{ $banner->first()->banner_name }}</p>
                                        @if ($banner->count() > 1)
                                            <p class="text-xs text-white/40">+{{ $banner->count() - 1 }} lainnya</p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="p-6">
                                    @include('components/dashboard/card/empty-state', [
                                        'message' => 'Belum ada banner.',
                                    ])
                                </div>
                            @endif
                        @endcomponent
                    </div>

                    {{-- Warna Web & Profil --}}
                    <div
                        class="flex-1 rounded-3xl border border-white/10 bg-white/3 overflow-hidden flex divide-x divide-white/10">
                        <a href="{{ route('color_pages.index') }}"
                            class="flex-1 flex flex-col justify-between gap-3 p-5 hover:bg-white/4 transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wide text-white/60">Warna Web</span>
                                <i class="bi bi-palette-fill text-white/40"></i>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full border border-white/20 shrink-0"
                                    style="background-color: {{ $color_pages->color ?? '#000000' }};"></span>
                                <span class="text-sm font-mono text-white/70">{{ $color_pages->color ?? '—' }}</span>
                            </div>
                        </a>
                        <a href="{{ route('dashboard.profile') }}"
                            class="flex-1 flex flex-col justify-between gap-3 p-5 hover:bg-white/4 transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wide text-white/60">Profil</span>
                                <i class="bi bi-person-fill text-white/40"></i>
                            </div>
                            <span class="text-sm text-white/70">
                                {{ $highlightCount }} highlight · {{ $statistikCount }} statistik
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Row 2: News --}}
            @component('components.dashboard.card.card', [
                'title' => 'Berita Terbaru (' . $news->count() . ')',
                'href' => route('news'),
                'icon' => 'bi-newspaper',
            ])
                @if ($news->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada berita yang ditambahkan.',
                    ])
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach ($news->take(3) as $item)
                            <div class="rounded-2xl border border-white/10 overflow-hidden bg-white/2">
                                <img src="{{ Storage::url('news/' . $item->news_cover) }}" alt="{{ $item->news_title }}"
                                    class="w-full aspect-video object-cover">
                                <div class="p-4">
                                    <h4 class="font-semibold text-sm line-clamp-2">{{ $item->news_title }}</h4>
                                    <p class="text-xs text-white/50 mt-2">{{ $item->news_source }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($news->count() > 3)
                        <p class="text-xs text-white/40 mt-4">+{{ $news->count() - 3 }} berita lainnya</p>
                    @endif
                @endif
            @endcomponent

            {{-- Row 3: Album & Merchandise --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @component('components.dashboard.card.card', [
                    'title' => 'Album (' . $albums->count() . ')',
                    'href' => route('albums'),
                    'icon' => 'bi-disc-fill',
                ])
                    @if ($albums->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada album yang ditambahkan.',
                        ])
                    @else
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @foreach ($albums->take(8) as $item)
                                <div class="group/item">
                                    <div class="rounded-xl aspect-square overflow-hidden">
                                        <img src="{{ Storage::url('albums/' . $item->albums_cover) }}"
                                            alt="{{ $item->albums_name }}"
                                            class="w-full h-full object-cover transition-transform group-hover/item:scale-105">
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-center line-clamp-1">{{ $item->albums_name }}</p>
                                </div>
                            @endforeach
                        </div>
                        @if ($albums->count() > 8)
                            <p class="text-xs text-white/40 mt-4">+{{ $albums->count() - 8 }} album lainnya</p>
                        @endif
                    @endif
                @endcomponent

                @component('components.dashboard.card.card', [
                    'title' => 'Merchandise (' . $merchandise->count() . ')',
                    'href' => route('merchandise'),
                    'icon' => 'bi-basket-fill',
                ])
                    @if ($merchandise->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada merchandise yang ditambahkan.',
                        ])
                    @else
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @foreach ($merchandise->take(8) as $item)
                                <div class="group/item">
                                    <div class="rounded-xl aspect-square overflow-hidden">
                                        <img src="{{ Storage::url('merchandise/' . $item->merchandise_cover) }}"
                                            alt="{{ $item->merchandise_name }}"
                                            class="w-full h-full object-cover transition-transform group-hover/item:scale-105">
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-center line-clamp-1">
                                        {{ $item->merchandise_name }}</p>
                                </div>
                            @endforeach
                        </div>
                        @if ($merchandise->count() > 8)
                            <p class="text-xs text-white/40 mt-4">+{{ $merchandise->count() - 8 }} merchandise lainnya</p>
                        @endif
                    @endif
                @endcomponent
            </div>
        </div>
    </div>
@endsection
