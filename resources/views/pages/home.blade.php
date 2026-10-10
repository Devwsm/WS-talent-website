@extends('template.layout')

@section('title', 'Whisnu Santika — DJ & Produser Musik Indonesia | Pionir Indonesian Bounce')
@section('meta_description',
    'Whisnu Santika — DJ & produser asal Jakarta, pionir Indonesian Bounce. Info tur, rilisan
    musik, merchandise, dan berita terbaru.')
@section('og_image', $hero->foto ?? null ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto) :
    asset('aset/logo/Whisnu-Santika_Logo-2025-White.png'))

    @push('schema')
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'MusicGroup',
            'name' => 'Whisnu Santika',
            'url' => url('/'),
            'image' => ($hero->foto ?? null)
                ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto)
                : asset('aset/logo/Whisnu-Santika_Logo-2025-White.png'),
            'description' => isset($bio->konten)
                ? trim(strip_tags($bio->konten))
                : 'Whisnu Santika — DJ & produser asal Jakarta, pionir Indonesian Bounce.',
            'genre' => $genre->pluck('nama_genre')->values(),
            'sameAs' => [
                'https://www.instagram.com/whisnusantika',
                'https://www.tiktok.com/@whisnusantika',
                'https://youtube.com/@whisnusantika',
                'https://open.spotify.com/artist/6gvsmDZKW5wRvjKCPnbHDh',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endpush

@section('content')
    <div>
        {{-- Satu-satunya <h1> di halaman ini — bagian lain (hero carousel, teaser, dst)
             pakai heading level di bawahnya biar struktur halaman nggak flat buat
             screen reader & SEO. Disembunyikan visual (sr-only) karena nama artis
             sudah tampil besar di profile-teaser, cuma perlu ada di DOM. --}}
        <h1 class="sr-only">{{ $hero->nama ?? 'Whisnu Santika' }}</h1>

        <div class="grid grid-cols-1 w-full justify-center items-center">
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

        {{-- Marquee genre — pita teks berjalan, dekoratif (disembunyikan dari screen reader) --}}
        @php
            $marqueeWords = $genre->count()
                ? $genre->pluck('nama_genre')->all()
                : ['Indonesian Bounce', 'DJ & Producer'];
            // Cukup ±6 kata per grup — lebih panjang dari itu cuma bikin layer animasi raksasa & berat di HP.
            $reps = max(1, (int) ceil(6 / max(1, count($marqueeWords))));
        @endphp
        <div class="marquee bg-black border-y border-white/10 py-5 md:py-8" aria-hidden="true">
            <div class="marquee-track">
                @for ($g = 0; $g < 2; $g++)
                    <div class="marquee-group">
                        @for ($r = 0; $r < $reps; $r++)
                            @foreach ($marqueeWords as $word)
                                <span class="marquee-word">{{ $word }}</span>
                                <span class="marquee-dot">✦</span>
                            @endforeach
                        @endfor
                    </div>
                @endfor
            </div>
        </div>
        {{-- Jadwal tour — widget Bandsintown (sempat ikut terhapus di commit "remove unused schedule") --}}
        <div id="tour" class="schedule flex flex-col w-full">
            @include('components/tour')
        </div>

        @if ($news->isNotEmpty())
            <div id="news" class="news flex flex-col w-full">
                <h2 class="sr-only">Berita Terbaru</h2>
                @include('components/news')
            </div>
        @endif
        <div class="product flex flex-col w-full">
            @include('components/albums')
            @include('components/merchandise')
        </div>
        @include('components/footer')
    </div>
@endsection
