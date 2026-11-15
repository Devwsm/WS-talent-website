@extends('template/dashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak: blob blur + dot grid, biar gak polos tapi tetap netral --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 z-0 opacity-5"
            style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 26px 26px;">
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-40 -right-32 w-96 h-96 rounded-full blur-3xl"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.5;"></div>
        <div aria-hidden="true" class="pointer-events-none absolute top-1/2 -left-40 w-80 h-80 rounded-full blur-3xl"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        @php
            $lencana = [
                'hijau' => 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200',
                'merah' => 'border-red-400/30 bg-red-400/10 text-red-300',
                'kuning' => 'border-amber-300/30 bg-amber-300/10 text-amber-200',
            ];
            $profilTotal = array_sum($profil->jumlah);
            $kartuAngka = [
                ['ikon' => 'bi-card-image', 'angka' => $headerTotal, 'label' => 'Header', 'href' => route('headers')],
                ['ikon' => 'bi-images', 'angka' => $bannerTotal, 'label' => 'Banner', 'href' => route('banner')],
                ['ikon' => 'bi-newspaper', 'angka' => $newsTotal, 'label' => 'Berita', 'href' => route('news')],
                ['ikon' => 'bi-disc-fill', 'angka' => $albumsTotal, 'label' => 'Album', 'href' => route('albums')],
                [
                    'ikon' => 'bi-basket-fill',
                    'angka' => $merchandiseTotal,
                    'label' => 'Merchandise',
                    'href' => route('merchandise'),
                ],
                [
                    'ikon' => 'bi-person-fill',
                    'angka' => $profilTotal,
                    'label' => 'Isi profil',
                    'href' => route('dashboard.profile'),
                ],
            ];
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

            {{-- Angka ringkas: jumlah isi tiap bagian, masing-masing menuju halaman kelolanya --}}
            <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach ($kartuAngka as $k)
                    <li class="min-w-0">
                        <a href="{{ $k['href'] }}"
                            class="block h-full rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5 transition-colors hover:border-white/20 hover:bg-white/5">
                            <i class="bi {{ $k['ikon'] }} text-white/40 text-lg" aria-hidden="true"></i>
                            <p class="text-2xl md:text-3xl font-bold mt-2">{{ number_format($k['angka'], 0, ',', '.') }}</p>
                            <p class="text-xs text-white/50 mt-0.5">{{ $k['label'] }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Row 1: Header (hero) + Warna Web & Log --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">

                {{-- Header: preview meniru hero asli (background + logo kecil + judul) --}}
                <div class="lg:col-span-2 min-w-0">
                    @component('components.dashboard.card.card', [
                        'title' => 'Header (' . $headerTotal . ')',
                        'href' => route('headers'),
                        'icon' => 'bi-card-image',
                        'flush' => true,
                    ])
                        @if ($headerPertama)
                            @php
                                $h = $headerPertama;
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
                                    @if ($headerTotal > 1)
                                        <p class="text-xs text-white/50">+{{ $headerTotal - 1 }} header lainnya</p>
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

                {{-- Warna Web & Log: dua kartu ringkas (sejajar di tablet, ditumpuk di desktop) --}}
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
                        'title' => 'Log',
                        'href' => route('log.index'),
                        'icon' => 'bi-journal-text',
                    ])
                        <a href="{{ route('log.index') }}"
                            class="flex items-center gap-3 rounded-2xl border p-3 {{ $log->error_terbuka > 0 ? $lencana['merah'] : $lencana['hijau'] }}">
                            <i class="bi {{ $log->error_terbuka > 0 ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"
                                aria-hidden="true"></i>
                            <span class="text-sm font-semibold">
                                {{ $log->error_terbuka > 0 ? $log->error_terbuka . ' masalah masih terbuka' : 'Tidak ada masalah yang terbuka' }}
                            </span>
                        </a>
                        @if ($log->login_gagal > 0)
                            <a href="{{ route('log.index', ['tab' => 'aktivitas', 'kategori' => 'akses']) }}"
                                class="flex items-center gap-3 rounded-2xl border p-3 mt-2 {{ $lencana['kuning'] }}">
                                <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
                                <span class="text-sm font-semibold">{{ $log->login_gagal }}x login gagal dalam 24 jam
                                    terakhir</span>
                            </a>
                        @endif
                        @if ($log->aktivitas->isNotEmpty())
                            <ul class="flex flex-col gap-2 mt-4">
                                @foreach ($log->aktivitas as $a)
                                    <li class="min-w-0">
                                        <p class="text-sm font-semibold truncate">{{ $a->aksi }}</p>
                                        <p class="text-xs text-white/50 truncate">
                                            {{ $a->created_at->locale('id')->translatedFormat('j M Y, H.i') }} ·
                                            {{ $a->pelaku ?? 'tidak dikenal' }}@if ($a->perangkat)
                                                · {{ $a->perangkat }}
                                            @endif
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-white/40 mt-4">Belum ada perubahan yang tercatat.</p>
                        @endif
                    @endcomponent
                </div>
            </div>

            {{-- Row 2: Banner — selebar konten, gambar utuh (rasio asli) supaya preview nggak terpotong --}}
            @component('components.dashboard.card.card', [
                'title' => 'Banner (' . $bannerTotal . ')',
                'href' => route('banner'),
                'icon' => 'bi-images',
                'flush' => true,
            ])
                @if ($bannerPertama)
                    <div class="bg-black">
                        <img src="{{ Storage::url('banner/' . $bannerPertama->banner_cover) }}"
                            alt="{{ $bannerPertama->banner_name }}" loading="lazy" decoding="async"
                            class="w-full h-auto max-h-72 object-contain mx-auto">
                    </div>
                    <div class="flex items-center justify-between gap-3 px-6 py-3 border-t border-white/10">
                        <p class="font-semibold text-sm truncate">{{ $bannerPertama->banner_name }}</p>
                        @if ($bannerTotal > 1)
                            <p class="text-xs text-white/40 shrink-0">+{{ $bannerTotal - 1 }} lainnya</p>
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

            {{-- Row 3: Profil — hero, bio, dan jumlah tiap daftar --}}
            @component('components.dashboard.card.card', [
                'title' => 'Profil',
                'href' => route('dashboard.profile'),
                'icon' => 'bi-person-fill',
            ])
                @php
                    $hero = $profil->hero;
                    $bioTeks = $profil->bio
                        ? \Illuminate\Support\Str::limit(
                            trim(html_entity_decode(strip_tags((string) $profil->bio->konten))),
                            220,
                        )
                        : '';
                @endphp
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="flex items-center gap-4 min-w-0">
                        @if ($hero && $hero->foto)
                            <img src="{{ Storage::url('profile-hero/' . $hero->foto) }}" alt="{{ $hero->nama }}"
                                loading="lazy" decoding="async" class="w-20 h-20 rounded-2xl object-cover shrink-0">
                        @else
                            <span aria-hidden="true"
                                class="w-20 h-20 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white/30 text-3xl shrink-0"><i
                                    class="bi bi-person-fill"></i></span>
                        @endif
                        <div class="min-w-0">
                            @if ($hero)
                                <p class="text-xs uppercase tracking-widest text-white/40 truncate">{{ $hero->judul_singkat }}
                                </p>
                                <p class="font-bold text-lg truncate">{{ $hero->nama }}</p>
                                <p class="text-sm text-white/60 line-clamp-2">{{ $hero->tagline }}</p>
                            @else
                                <p class="text-sm text-white/50">Hero profil belum diisi.</p>
                                <a href="{{ route('dashboard.profile') }}"
                                    class="text-xs font-semibold text-white/70 underline underline-offset-4 hover:text-white">Isi
                                    sekarang</a>
                            @endif
                        </div>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-widest text-white/40 mb-1">Bio</p>
                        @if ($bioTeks !== '')
                            <p class="text-sm text-white/70 wrap-anywhere">{{ $bioTeks }}</p>
                        @else
                            <p class="text-sm text-white/50">Bio belum diisi.</p>
                        @endif
                    </div>
                </div>
                <ul class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mt-6">
                    @foreach ($profil->jumlah as $label => $jumlah)
                        <li class="rounded-2xl border border-white/10 bg-white/4 p-4 min-w-0">
                            <p class="text-2xl font-bold">{{ number_format($jumlah, 0, ',', '.') }}</p>
                            <p class="text-xs text-white/50 mt-0.5 truncate">{{ $label }}</p>
                        </li>
                    @endforeach
                </ul>
            @endcomponent

            {{-- Row 4: News --}}
            @component('components.dashboard.card.card', [
                'title' => 'Berita Terbaru (' . $newsTotal . ')',
                'href' => route('news'),
                'icon' => 'bi-newspaper',
            ])
                @if ($newsTotal === 0)
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada berita yang ditambahkan.',
                        'cta_href' => route('news'),
                        'cta_label' => 'Tambah berita',
                    ])
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($newsTampil as $item)
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
                    @if ($newsTotal > 3)
                        <p class="text-xs text-white/40 mt-4">+{{ $newsTotal - 3 }} berita lainnya</p>
                    @endif
                @endif
            @endcomponent

            {{-- Row 5: Album & Merchandise --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @component('components.dashboard.card.card', [
                    'title' => 'Album (' . $albumsTotal . ')',
                    'href' => route('albums'),
                    'icon' => 'bi-disc-fill',
                ])
                    @if ($albumsTotal === 0)
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada album yang ditambahkan.',
                        ])
                    @else
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @foreach ($albumsTampil as $item)
                                <div class="group/item">
                                    <div class="rounded-xl aspect-square overflow-hidden">
                                        <img src="{{ Storage::url('albums/' . $item->albums_cover) }}"
                                            alt="{{ $item->albums_name }}"
                                            class="w-full h-full object-cover transition-transform group-hover/item:scale-105">
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-center line-clamp-1">{{ $item->albums_name }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                        @if ($albumsTotal > 8)
                            <p class="text-xs text-white/40 mt-4">+{{ $albumsTotal - 8 }} album lainnya</p>
                        @endif
                    @endif
                @endcomponent

                @component('components.dashboard.card.card', [
                    'title' => 'Merchandise (' . $merchandiseTotal . ')',
                    'href' => route('merchandise'),
                    'icon' => 'bi-basket-fill',
                ])
                    @if ($merchandiseTotal === 0)
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada merchandise yang ditambahkan.',
                        ])
                    @else
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @foreach ($merchandiseTampil as $item)
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
                        @if ($merchandiseTotal > 8)
                            <p class="text-xs text-white/40 mt-4">+{{ $merchandiseTotal - 8 }} merchandise lainnya</p>
                        @endif
                    @endif
                @endcomponent
            </div>
        </div>
    </div>
@endsection
