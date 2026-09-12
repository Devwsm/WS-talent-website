@extends('template.layout')

@section('title', 'Whisnu Santika — DJ & Produser Musik Indonesia | Pionir Indonesian Bounce')
@section('meta_description', 'Whisnu Santika — DJ & produser asal Jakarta, pionir Indonesian Bounce. Info tur, rilisan
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
            @include('components/news')
        </div>
        <div class="follow bg-white flex flex-col w-full py-12 gap-2 justify-center items-center">
            <h1 class="capitalize text-md font-bold text-center">Get notified when new events are announced in your area
            </h1>
            <button class="bg-white w-fit border-2 border-black px-10 py-4 text-xs uppercase">follow whisnu santika</button>
        </div>
        <div class="product flex flex-col w-full">
            @include('components/albums')
            @include('components/merchandise')
        </div>
        @include('components/footer')
    </div>
@endsection
