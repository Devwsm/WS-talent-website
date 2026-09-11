<div class="flex flex-col w-full bg-black p-6 md:p-12 gap-4">

    @php
        $heroFoto =
            $hero->foto ?? null
                ? \Illuminate\Support\Facades\Storage::url('profile-hero/' . $hero->foto)
                : asset('aset/logo/whisnuSantika.jpg');
    @endphp

    {{-- desktop --}}
    <div class="head relative hidden lg:flex w-full justify-start items-center gap-4">
        <img src="{{ $heroFoto }}" loading="lazy" decoding="async" alt="{{ $hero->nama ?? 'whisnu-santika' }}"
            class="object-cover object-center w-42 aspect-square rounded-full">

        <div class="flex flex-col">
            <h1 class="text-sm text-white/60">{{ $hero->judul_singkat ?? 'DJ & Producer' }}</h1>
            <h1 class="text-2xl font-medium text-white">{{ $hero->nama ?? 'Whisnu Santika' }}</h1>
            <h1 class="text-sm text-white/60 leading-relaxed">{{ $hero->tagline ?? 'Pionir Indonesian Bounce — ' }}</h1>
        </div>
    </div>
    {{-- mobile --}}
    <div class="head relative flex lg:hidden w-full justify-center">
        <img src="{{ $heroFoto }}" loading="lazy" decoding="async" alt="{{ $hero->nama ?? 'whisnu-santika' }}"
            class="object-cover object-center w-full rounded-lg">

        <div class="absolute inset-0 rounded-lg bg-linear-to-t from-black/75 via-black/25 to-transparent"></div>
        <div class="absolute bottom-4 left-4 text-left">
            <h1 class="text-sm text-white/60">{{ $hero->judul_singkat ?? 'DJ & Producer' }}</h1>
            <h1 class="text-2xl font-medium text-white">{{ $hero->nama ?? 'Whisnu Santika' }}</h1>
            <h1 class="text-sm text-white/60 leading-relaxed">{{ $hero->tagline ?? 'Pionir Indonesian Bounce — ' }}</h1>
        </div>
    </div>

    {{-- Body: video --}}
    <div class="flex flex-col gap-4">

        {{-- Genre tags --}}
        <div class="flex flex-wrap gap-2 px-1">
            @forelse ($genre as $item)
                <span
                    class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">{{ $item->nama_genre }}</span>
            @empty
                <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Indonesian
                    Bounce</span>
            @endforelse
        </div>

        {{-- Bio singkat: potongan plain-text dari Bio lengkap, biar teaser tetap ringkas --}}
        <h1 class="text-sm text-white/70 leading-relaxed px-1">
            {{ $bio->konten ?? null ? \Illuminate\Support\Str::limit(strip_tags($bio->konten), 220) : 'Pionir Indonesian Bounce — memadukan EDM, dancehall, hip hop, dan afrobeat dalam satu identitas bunyi yang khas. Tampil di Tomorrowland Belgium 2024 dan Djakarta Warehouse Project, menjadikan Whisnu Santika salah satu DJ Indonesia dengan jangkauan global.' }}
        </h1>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-3">
            @foreach ($statistik as $item)
                <div class="flex flex-col gap-1 p-3 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-lg font-medium text-white">{{ $item->total }}</span>
                    <span class="text-xs text-white/50">{{ $item->platform }}</span>
                </div>
            @endforeach
        </div>

        {{-- CTA --}}
        <a href="{{ route('profile') }}"
            class="flex items-center justify-between w-full px-4 py-3 rounded-lg border border-white/20 text-white/80 hover:bg-white/5 transition-colors group">
            <span class="text-sm font-medium">Lihat profil lengkap</span>
            <span class="text-white/40 group-hover:text-white/70 transition-colors">→</span>
        </a>

    </div>
</div>
