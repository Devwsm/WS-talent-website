@extends('template.layout')

@section('title', ($hero->nama ?? 'Whisnu Santika') . ' — Profile | Whisnu Santika')
@section('meta_description',
    $hero->tagline ?? null ?:
    'Profile lengkap Whisnu Santika — genre, highlight, kolaborasi,
    media coverage, dan kontak booking.')
@section('og_image', $hero->foto ?? null ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto) :
    asset('aset/logo/whisnuSantika.jpg'))

    @push('schema')
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $hero->nama ?? 'Whisnu Santika',
            'url' => url('/profile'),
            'image' => ($hero->foto ?? null)
                ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto)
                : asset('aset/logo/whisnuSantika.jpg'),
            'jobTitle' => 'DJ & Produser Musik',
            'description' => isset($bio->konten) ? trim(strip_tags($bio->konten)) : null,
            'sameAs' => $mediaSosial->pluck('url')->values(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endpush

@section('content')
    <div class="flex flex-col w-full bg-black p-6 md:p-12 gap-8">
        @php
            $color = $color_pages->color ?? '#5E0006';
            $heroFoto =
                $hero->foto ?? null
                    ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto)
                    : asset('aset/logo/whisnuSantika.jpg');
        @endphp
        <div class="relative">
            @include('components/navbar')
        </div>

        {{-- Hero — ini elemen LCP di halaman profile, jangan di-lazy-load --}}
        <div class="head relative flex w-full justify-center">
            <img src="{{ $heroFoto }}" loading="eager" fetchpriority="high" decoding="async"
                alt="{{ $hero->nama ?? 'whisnu-santika' }}"
                class="object-cover object-center w-full aspect-4/5 sm:aspect-video rounded-lg">

            <div class="absolute inset-0 rounded-lg bg-linear-to-t from-black/80 via-black/30 to-transparent"></div>
            <div class="hero-in absolute bottom-4 left-4 md:bottom-8 md:left-8 text-left max-w-lg">
                <p class="text-xs text-white/60 uppercase tracking-widest mb-1">
                    {{ $hero->judul_singkat ?? 'DJ & Producer' }}</p>
                <h1 class="text-3xl md:text-5xl font-medium text-white mb-1">{{ $hero->nama ?? 'Whisnu Santika' }}</h1>
                <p class="text-sm text-white/60 leading-relaxed">{{ $hero->tagline ?? 'Pionir Indonesian Bounce — ' }}</p>
            </div>
        </div>

        {{-- Genre tags --}}
        <div class="flex flex-wrap gap-2" data-motion-stagger="0.06" data-motion-children="pop">
            @forelse ($genre as $item)
                <span
                    class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">{{ $item->nama_genre }}</span>
            @empty
                <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Indonesian Bounce</span>
            @endforelse
        </div>

        {{-- Bio lengkap --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Bio</h2>
            <div class="text-sm md:text-base text-white/70 leading-relaxed flex flex-col gap-3" data-motion="fade-up">
                {!! $bio->konten ??
                    '<p class="text-sm text-white/70 leading-relaxed">Whisnu Santika adalah DJ dan produser rekaman asal Jakarta yang dikenal sebagai pionir <span class="text-white font-medium">Indonesian Bounce</span> — sebuah pendekatan genre yang ia ciptakan sendiri dengan memadukan EDM, dancehall, moombahton, hip hop, bass, dan afrobeat.</p>' !!}
            </div>
        </div>

        {{-- Stats --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Pencapaian</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3" data-motion-stagger="0.08" data-motion-children="pop">
                @foreach ($statistik as $item)
                    <div class="spotlight-card flex flex-col gap-1 p-3 md:p-5 rounded-lg bg-white/5 border border-white/10">
                        <span class="text-lg md:text-3xl font-medium text-white" data-count>{{ $item->total }}</span>
                        <span class="text-xs text-white/50">{{ $item->platform }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Highlight penampilan --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Highlight Penampilan</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3" data-motion-stagger="0.1" data-motion-children="fade-up">
                @foreach ($highlight as $item)
                    <div
                        class="spotlight-card flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/10">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-sm font-medium text-white">{{ $item->place }}</span>
                            <span class="text-xs text-white/60">{{ $item->description }}</span>
                        </div>
                        <span class="text-xs text-white/20">{{ $item->year }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Kolaborasi artis --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Kolaborasi</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3" data-motion-stagger="0.08" data-motion-children="pop">
                @forelse ($collab as $item)
                    <div class="spotlight-card flex flex-col gap-0.5 p-4 rounded-lg bg-white/5 border border-white/10">
                        <span class="text-sm font-medium text-white">{{ $item->nama }}</span>
                        <span class="text-xs text-white/60">{{ $item->role }}</span>
                    </div>
                @empty
                    <span class="text-xs text-white/30">Belum ada data kolaborasi.</span>
                @endforelse
            </div>
        </div>

        {{-- Media coverage --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Media Coverage</h2>
            <div class="flex flex-wrap gap-2" data-motion-stagger="0.06" data-motion-children="pop">
                @forelse ($mediaCoverage as $item)
                    <span
                        class="text-xs px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/50">{{ $item->nama_media }}</span>
                @empty
                    <span class="text-xs text-white/30">Belum ada data media coverage.</span>
                @endforelse
            </div>
        </div>

        {{-- Booking & kontak --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Booking & Bisnis</h2>
            <div class="flex flex-col gap-3 p-5 rounded-lg bg-white/5 border border-white/10" data-motion="fade-up">
                <p class="text-xs text-white/60 leading-relaxed">
                    Untuk keperluan booking show, brand partnership, atau kolaborasi musik,
                    hubungi tim manajemen {{ $hero->nama ?? 'Whisnu Santika' }}.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1" data-motion-stagger="0.1"
                    data-motion-children="fade-up">
                    @forelse ($booking as $item)
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-white/50 uppercase tracking-widest">{{ $item->label }}</span>
                            <a href="mailto:{{ $item->email }}"
                                class="text-sm text-white/70 hover:text-white transition-colors">
                                {{ $item->email }}
                            </a>
                        </div>
                    @empty
                        <span class="text-xs text-white/30">Belum ada data booking & kontak.</span>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Social links --}}
        <div id="ikuti" class="flex flex-col gap-3">
            <h2 class="text-xs text-white/50 uppercase tracking-widest" data-motion="fade-right">Ikuti</h2>
            <div class="flex flex-wrap gap-2" data-motion-stagger="0.06" data-motion-children="pop">
                @forelse ($mediaSosial as $item)
                    <a href="{{ $item->url }}" target="_blank" rel="noopener"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/60 hover:text-white hover:bg-white/10 transition-colors text-xs">
                        {{ $item->platform }}
                    </a>
                @empty
                    <span class="text-xs text-white/30">Belum ada tautan media sosial.</span>
                @endforelse
            </div>
        </div>

    </div>
@endsection
