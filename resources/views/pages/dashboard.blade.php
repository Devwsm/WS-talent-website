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
            px-4 sm:px-5 md:px-10 pt-6 md:pt-8 pb-28 text-white">

            {{-- Greeting + shortcut ke website --}}
            <div
                class="relative rounded-3xl border border-white/10 bg-white/3 overflow-hidden px-5 py-5 md:px-8 md:py-6
                flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold uppercase wrap-anywhere">
                        {{ $greeting }}, <span class="normal-case text-white/80">{{ session('user', 'Admin') }}</span>
                        👋
                    </h1>
                    <p class="text-white/50 mt-1 text-sm md:text-base">
                        Ini preview konten yang lagi tayang di website Whisnu Santika.
                    </p>
                </div>
                <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center justify-center gap-2 shrink-0 px-4 py-2 rounded-full border border-white/15
                    text-sm font-semibold text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Lihat website
                </a>
            </div>

            {{-- Row 1: Header (hero) + Warna Web & Profil --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">

                {{-- Header: preview meniru hero asli (background + logo kecil + judul) --}}
                <div class="lg:col-span-2 min-w-0">
                    @component('components.dashboard.card.card', [
                        'title' => 'Header (' . $header->count() . ')',
                        'href' => route('headers'),
                        'icon' => 'bi-card-image',
                        'flush' => true,
                    ])
                        @if ($header->first())
                            @php
                                $h = $header->first();
                                $hBgExt = strtolower(pathinfo($h->header_background, PATHINFO_EXTENSION));
                                $hIsVideo = in_array($hBgExt, ['mp4', 'webm', 'mov']);
                                $hBgUrl = Storage::url('header/background/' . $h->header_background);
                            @endphp
                            <div class="relative flex-1 min-h-72 sm:min-h-80 overflow-hidden bg-black">
                                @if ($hIsVideo)
                                    {{-- cuma frame pertama (tanpa autoplay) biar dashboard tetap ringan --}}
                                    <video muted playsinline preload="metadata" tabindex="-1" aria-hidden="true"
                                        src="{{ $hBgUrl }}#t=0.5"
                                        class="absolute inset-0 w-full h-full object-cover"></video>
                                @else
                                    <img src="{{ $hBgUrl }}" alt="" loading="lazy" decoding="async"
                                        class="absolute inset-0 w-full h-full object-cover">
                                @endif
                                <div class="absolute inset-0 bg-black/55"></div>

                                <div
                                    class="relative z-10 h-full min-h-72 sm:min-h-80 flex flex-col justify-center items-center
                                    text-center gap-3 px-5 py-6">
                                    <span style="border-color: {{ $h->header_color }}99;"
                                        class="inline-flex items-center gap-2 max-w-full bg-white/10 backdrop-blur border rounded-full px-3 py-1 text-xs">
                                        <span style="background-color: {{ $h->header_color }};" aria-hidden="true"
                                            class="w-2 h-2 rounded-full shrink-0"></span>
                                        <span class="truncate">{{ $h->header_title }}</span>
                                    </span>
                                    <img src="{{ Storage::url('header/img/' . $h->header_img) }}" alt="{{ $h->header_name }}"
                                        loading="lazy" decoding="async" class="w-28 sm:w-36 lg:w-44 max-h-20 object-contain">
                                    <h3 class="text-lg md:text-xl lg:text-2xl font-semibold wrap-anywhere">
                                        {{ $h->header_name }}</h3>
                                    <p class="text-white/70 text-sm line-clamp-2 max-w-md">{{ $h->header_description }}</p>
                                    @if ($header->count() > 1)
                                        <p class="text-xs text-white/50">+{{ $header->count() - 1 }} header lainnya</p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-6">
                                @include('components/dashboard/card/empty-state', [
                                    'message' => 'Belum ada header yang diupload.',
                                    'cta_href' => route('headers'),
                                    'cta_label' => 'Tambah header',
                                ])
                            </div>
                        @endif
                    @endcomponent
                </div>

                {{-- Warna Web & Profil: dua kartu ringkas (sejajar di tablet, ditumpuk di desktop) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-6 min-w-0">
                    @component('components.dashboard.card.card', [
                        'title' => 'Warna Web',
                        'href' => route('color_pages.index'),
                        'icon' => 'bi-palette-fill',
                    ])
                        <div class="h-full flex items-center gap-4">
                            <span class="w-14 h-14 rounded-2xl border border-white/20 shrink-0"
                                style="background-color: {{ $color_pages->color ?? '#000000' }};"></span>
                            <div class="min-w-0">
                                <p class="font-mono text-lg">{{ $color_pages->color ?? '#000000' }}</p>
                                <p class="text-xs text-white/40">Warna navbar &amp; footer website</p>
                            </div>
                        </div>
                    @endcomponent

                    @component('components.dashboard.card.card', [
                        'title' => 'Profil',
                        'href' => route('dashboard.profile'),
                        'icon' => 'bi-person-fill',
                    ])
                        <div class="h-full grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/4 p-4">
                                <p class="text-2xl font-bold">{{ $highlightCount }}</p>
                                <p class="text-xs text-white/50 mt-0.5">Highlight</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/4 p-4">
                                <p class="text-2xl font-bold">{{ $statistikCount }}</p>
                                <p class="text-xs text-white/50 mt-0.5">Statistik</p>
                            </div>
                        </div>
                    @endcomponent
                </div>
            </div>

            {{-- Row 2: Banner — selebar konten, gambar utuh (rasio asli) supaya preview nggak terpotong --}}
            @component('components.dashboard.card.card', [
                'title' => 'Banner (' . $banner->count() . ')',
                'href' => route('banner'),
                'icon' => 'bi-images',
                'flush' => true,
            ])
                @if ($banner->first())
                    <div class="bg-black">
                        <img src="{{ Storage::url('banner/' . $banner->first()->banner_cover) }}"
                            alt="{{ $banner->first()->banner_name }}" loading="lazy" decoding="async"
                            class="w-full h-auto max-h-72 object-contain mx-auto">
                    </div>
                    <div class="flex items-center justify-between gap-3 px-6 py-3 border-t border-white/10">
                        <p class="font-semibold text-sm truncate">{{ $banner->first()->banner_name }}</p>
                        @if ($banner->count() > 1)
                            <p class="text-xs text-white/40 shrink-0">+{{ $banner->count() - 1 }} lainnya</p>
                        @endif
                    </div>
                @else
                    <div class="p-6">
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada banner.',
                            'cta_href' => route('banner'),
                            'cta_label' => 'Tambah banner',
                        ])
                    </div>
                @endif
            @endcomponent

            {{-- Row 3: News --}}
            @component('components.dashboard.card.card', [
                'title' => 'Berita Terbaru (' . $news->count() . ')',
                'href' => route('news'),
                'icon' => 'bi-newspaper',
            ])
                @if ($news->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada berita yang ditambahkan.',
                        'cta_href' => route('news'),
                        'cta_label' => 'Tambah berita',
                    ])
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($news->take(3) as $item)
                            <div class="rounded-2xl border border-white/10 overflow-hidden bg-white/2 min-w-0">
                                <img src="{{ Storage::url('news/' . $item->news_cover) }}" alt="{{ $item->news_title }}"
                                    loading="lazy" decoding="async" class="w-full aspect-video object-cover">
                                <div class="p-4">
                                    <h4 class="font-semibold text-sm line-clamp-2">{{ $item->news_title }}</h4>
                                    <p class="text-xs text-white/50 mt-2 truncate">{{ $item->news_source }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($news->count() > 3)
                        <p class="text-xs text-white/40 mt-4">+{{ $news->count() - 3 }} berita lainnya</p>
                    @endif
                @endif
            @endcomponent

            {{-- Row 4: Album & Merchandise (tidak diubah) --}}
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
