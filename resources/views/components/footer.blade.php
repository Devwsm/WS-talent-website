@php
    // Ikon Bootstrap Icons per platform — dicocokkan dari nama platform yang diisi di dashboard.
    $iconMap = [
        'instagram' => 'bi-instagram',
        'tiktok' => 'bi-tiktok',
        'youtube' => 'bi-youtube',
        'spotify' => 'bi-spotify',
        'soundcloud' => 'bi-soundcloud',
        'apple' => 'bi-apple',
        'facebook' => 'bi-facebook',
        'twitter' => 'bi-twitter-x',
        'x.com' => 'bi-twitter-x',
        'whatsapp' => 'bi-whatsapp',
        'linkedin' => 'bi-linkedin',
    ];
    $iconFor = function (string $platform) use ($iconMap): string {
        $name = strtolower($platform);
        foreach ($iconMap as $key => $icon) {
            if (str_contains($name, $key)) {
                return $icon;
            }
        }
        return 'bi-link-45deg';
    };

    $socials = ($footerSosial ?? collect())->isNotEmpty()
        ? $footerSosial->map(fn($s) => ['platform' => $s->platform, 'url' => $s->url])->all()
        : [
            // Fallback kalau belum ada data media sosial di dashboard.
            ['platform' => 'Instagram', 'url' => 'https://www.instagram.com/whisnusantika'],
            ['platform' => 'TikTok', 'url' => 'https://www.tiktok.com/@whisnusantika'],
            ['platform' => 'YouTube', 'url' => 'https://youtube.com/@whisnusantika'],
            ['platform' => 'Spotify', 'url' => 'https://open.spotify.com/artist/6gvsmDZKW5wRvjKCPnbHDh'],
        ];
@endphp

<footer style="background-color: {{ $color }};"
    class="footer flex flex-col w-full text-white border-t border-white/10 px-6 py-10 md:px-12 md:py-14 gap-10">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">

        {{-- Booking & bisnis --}}
        @if (($footerBooking ?? collect())->isNotEmpty())
            <section aria-labelledby="footer-booking">
                <h2 id="footer-booking" class="text-xs text-white/50 uppercase tracking-widest mb-4">Booking & Bisnis</h2>
                <ul class="flex flex-col gap-4">
                    @foreach ($footerBooking as $item)
                        <li class="flex flex-col gap-0.5">
                            <span class="text-xs text-white/50">{{ $item->label }}</span>
                            <a href="mailto:{{ $item->email }}"
                                class="text-sm text-white/80 hover:text-white break-all transition-colors">{{ $item->email }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Genre --}}
        @if (($footerGenre ?? collect())->isNotEmpty())
            <section aria-labelledby="footer-genre">
                <h2 id="footer-genre" class="text-xs text-white/50 uppercase tracking-widest mb-4">Genre</h2>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($footerGenre as $item)
                        <li class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/70">
                            {{ $item->nama_genre }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Media coverage --}}
        @if (($footerMedia ?? collect())->isNotEmpty())
            <section aria-labelledby="footer-media">
                <h2 id="footer-media" class="text-xs text-white/50 uppercase tracking-widest mb-4">Media Coverage</h2>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($footerMedia as $item)
                        <li class="text-xs px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white/70">
                            {{ $item->nama_media }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Media sosial --}}
        <section aria-labelledby="footer-ikuti">
            <h2 id="footer-ikuti" class="text-xs text-white/50 uppercase tracking-widest mb-4">Ikuti</h2>
            <nav aria-label="Media sosial" class="social-media flex flex-wrap gap-3">
                @foreach ($socials as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                        aria-label="{{ $social['platform'] }} Whisnu Santika" title="{{ $social['platform'] }}"
                        class="flex items-center justify-center w-11 h-11 text-lg border-2 border-white/30 rounded-full transition duration-300 hover:-translate-y-1 hover:bg-white/15 hover:border-white active:scale-90">
                        <i class="bi {{ $iconFor($social['platform']) }}" aria-hidden="true"></i>
                    </a>
                @endforeach
            </nav>
        </section>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-6 border-t border-white/10">
        <p class="text-white/50 text-sm">Copyright &copy; {{ date('Y') }} wahsudahmonday. All rights reserved.</p>
        <a href="{{ route('profile') }}" class="text-sm text-white/60 hover:text-white transition-colors">Profil
            lengkap →</a>
    </div>
</footer>
