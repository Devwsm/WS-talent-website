@extends('template.layout')
@section('content')
    <div class="flex flex-col w-full bg-black p-6 md:p-12 gap-8">
        <div class="relative">
            @include('components/navbar')
        </div>

        {{-- Hero --}}
        <div class="head relative flex w-full justify-center">
            <img src="{{ asset('aset/logo/whisnuSantika.jpg') }}" loading="lazy" decoding="async" alt="whisnu-santika"
                class="object-cover object-center w-full rounded-lg">

            <div class="absolute inset-0 rounded-lg bg-linear-to-t from-black/80 via-black/30 to-transparent"></div>
            <div class="absolute bottom-4 left-4 text-left max-w-lg">
                <h1 class="text-xs text-white/40 uppercase tracking-widest mb-1">DJ & Producer</h1>
                <h1 class="text-3xl font-medium text-white mb-1">Whisnu Santika</h1>
                <h1 class="text-sm text-white/60 leading-relaxed">Pionir Indonesian Bounce — </h1>
            </div>
        </div>

        {{-- Genre tags --}}
        <div class="flex flex-wrap gap-2">
            <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Indonesian Bounce</span>
            <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">EDM</span>
            <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Dancehall</span>
            <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Afrobeat</span>
            <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Moombahton</span>
            <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">Bass Music</span>
        </div>

        {{-- Bio lengkap --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/30 uppercase tracking-widest">Bio</h2>
            <h1 class="text-sm text-white/70 leading-relaxed">
                Whisnu Santika adalah DJ dan produser rekaman asal Jakarta yang dikenal sebagai pionir
                <span class="text-white font-medium">Indonesian Bounce</span> — sebuah pendekatan genre yang ia ciptakan
                sendiri dengan memadukan EDM, dancehall, moombahton, hip hop, bass, dan afrobeat.
            </h1>
            <h1 class="text-sm text-white/70 leading-relaxed">
                Memulai karier pada 2012, ia konsisten membangun identitas suara yang khas dan relevan
                di skena musik elektronik Indonesia maupun internasional. Hits seperti
                <span class="text-white">"Tequila"</span>, <span class="text-white">"Cartel"</span>,
                <span class="text-white">"Yalla Habibi"</span>, dan <span class="text-white">"Sahara"</span>
                meraih jutaan stream dan dimainkan di panggung festival dunia.
            </h1>
            <h1 class="text-sm text-white/70 leading-relaxed">
                Pada 2024, ia tampil di <span class="text-white font-medium">Tomorrowland Belgium</span> —
                festival EDM terbesar di dunia — menjadi salah satu DJ Indonesia dengan jangkauan panggung global.
            </h1>
        </div>

        {{-- Stats --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/30 uppercase tracking-widest">Pencapaian</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="flex flex-col gap-1 p-3 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-lg font-medium text-white">590K+</span>
                    <span class="text-xs text-white/50">YouTube subscribers</span>
                </div>
                <div class="flex flex-col gap-1 p-3 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-lg font-medium text-white">2.9M+</span>
                    <span class="text-xs text-white/50">Spotify Montly Listeners</span>
                </div>
                <div class="flex flex-col gap-1 p-3 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-lg font-medium text-white">426K+</span>
                    <span class="text-xs text-white/50">Instagram Followers</span>
                </div>
                <div class="flex flex-col gap-1 p-4 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-xl font-medium text-white">Global</span>
                    <span class="text-xs text-white/50">Festival internasional</span>
                </div>
            </div>
        </div>

        {{-- Highlight penampilan --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/30 uppercase tracking-widest">Highlight Penampilan</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/10">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-medium text-white">Tomorrowland 2024</span>
                        <span class="text-xs text-white/40">Boom, Belgium</span>
                    </div>
                    <span class="text-xs text-white/20">2024</span>
                </div>
                <div class="flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/10">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-medium text-white">Djakarta Warehouse Project</span>
                        <span class="text-xs text-white/40">Jakarta, Indonesia</span>
                    </div>
                    <span class="text-xs text-white/20">2024</span>
                </div>
                <div class="flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/10">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-medium text-white">Sahara 1st Anniversary Tour</span>
                        <span class="text-xs text-white/40">Multishow, Indonesia</span>
                    </div>
                    <span class="text-xs text-white/20">2023</span>
                </div>
                <div class="flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/10">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-medium text-white">Borderland Music Festival</span>
                        <span class="text-xs text-white/40">Malaysia & Asia</span>
                    </div>
                    <span class="text-xs text-white/20">2024</span>
                </div>
            </div>
        </div>

        {{-- Kolaborasi artis --}}
        <div class="flex flex-col gap-3">
            <h1 class="text-xs text-white/30 uppercase tracking-widest">Kolaborasi</h1>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="flex flex-col gap-0.5 p-4 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-sm font-medium text-white">Dipha Barus</span>
                    <span class="text-xs text-white/40">DJ / Producer</span>
                </div>
                <div class="flex flex-col gap-0.5 p-4 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-sm font-medium text-white">Cinta Laura Kiehl</span>
                    <span class="text-xs text-white/40">Penyanyi</span>
                </div>
                <div class="flex flex-col gap-0.5 p-4 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-sm font-medium text-white">Souljah</span>
                    <span class="text-xs text-white/40">Band</span>
                </div>
                <div class="flex flex-col gap-0.5 p-4 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-sm font-medium text-white">Amy B</span>
                    <span class="text-xs text-white/40">Singer · Australia</span>
                </div>
            </div>
        </div>

        {{-- Media coverage --}}
        <div class="flex flex-col gap-3">
            <h1 class="text-xs text-white/30 uppercase tracking-widest">Media Coverage</h1>
            <div class="flex flex-wrap gap-2">
                <span class="text-xs px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/50">Suara.com</span>
                <span class="text-xs px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/50">iNews.ID</span>
                <span class="text-xs px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/50">Spotify
                    Editorial</span>
                <span class="text-xs px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/50">Wikipedia
                    ID</span>
            </div>
        </div>

        {{-- Booking & kontak --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/30 uppercase tracking-widest">Booking & Bisnis</h2>
            <div class="flex flex-col gap-3 p-5 rounded-lg bg-white/5 border border-white/10">
                <h1 class="text-xs text-white/40 leading-relaxed">
                    Untuk keperluan booking show, brand partnership, atau kolaborasi musik,
                    hubungi tim manajemen Whisnu Santika.
                </h1>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs text-white/30 uppercase tracking-widest">Show & Touring</span>
                        <a href="jeannita.kirana@gmail.com"
                            class="text-sm text-white/70 hover:text-white transition-colors">
                            jeannita.kirana@gmail.com
                        </a>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs text-white/30 uppercase tracking-widest">Brand & Partnership</span>
                        <a href="partnership@whisnusantika.com"
                            class="text-sm text-white/70 hover:text-white transition-colors">
                            partnership@whisnusantika.com
                        </a>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs text-white/30 uppercase tracking-widest">Media & Press</span>
                        <a href="management@whisnusantika.com"
                            class="text-sm text-white/70 hover:text-white transition-colors">
                            management@whisnusantika.com
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Social links --}}
        <div class="flex flex-col gap-3">
            <h2 class="text-xs text-white/30 uppercase tracking-widest">Ikuti</h2>
            <div class="flex flex-wrap gap-2">
                <a href="https://www.instagram.com/whisnusantika" target="_blank" rel="noopener"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/60 hover:text-white hover:bg-white/10 transition-colors text-xs">
                    Instagram
                </a>
                <a href="https://www.tiktok.com/@whisnusantika" target="_blank" rel="noopener"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/60 hover:text-white hover:bg-white/10 transition-colors text-xs">
                    TikTok
                </a>
                <a href="https://youtube.com/@whisnusantika" target="_blank" rel="noopener"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/60 hover:text-white hover:bg-white/10 transition-colors text-xs">
                    YouTube
                </a>
                <a href="https://open.spotify.com/artist/6gvsmDZKW5wRvjKCPnbHDh" target="_blank" rel="noopener"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/60 hover:text-white hover:bg-white/10 transition-colors text-xs">
                    Spotify
                </a>
            </div>
        </div>

    </div>
@endsection
