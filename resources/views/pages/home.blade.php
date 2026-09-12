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
        <div id="news" class="news flex flex-col w-full">
            <h2 class="sr-only">Berita Terbaru</h2>
            @include('components/news')
        </div>
        <div class="follow bg-white flex flex-col w-full py-12 gap-2 justify-center items-center">
            <h2 class="capitalize text-md font-bold text-center">Get notified when new events are announced in your area
            </h2>
            @php
                // Prioritaskan Instagram kalau ada, kalau nggak pakai link sosmed pertama yang tersimpan
                $followLink =
                    $mediaSosial->first(fn($item) => str_contains(strtolower($item->platform), 'instagram'))?->url ??
                    $mediaSosial->first()?->url;
            @endphp
            @if ($followLink)
                <a href="{{ $followLink }}" target="_blank" rel="noopener"
                    class="bg-white w-fit border-2 border-black px-10 py-4 text-xs uppercase text-center hover:bg-black hover:text-white transition-colors">follow
                    whisnu santika</a>
            @else
                <a href="{{ route('profile') }}#ikuti"
                    class="bg-white w-fit border-2 border-black px-10 py-4 text-xs uppercase text-center hover:bg-black hover:text-white transition-colors">follow
                    whisnu santika</a>
            @endif
        </div>
        <div class="product flex flex-col w-full">
            @include('components/albums')
            @include('components/merchandise')
        </div>
        @include('components/footer')
    </div>
@endsection
