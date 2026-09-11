@extends('template/dashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div
            class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6
            px-5 md:px-10 pt-8 pb-28 text-white">

            <div>
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">Album</h1>
                <p class="text-white/50 mt-1">Kelola cover album yang tampil di halaman utama website (link ke Spotify).</p>
            </div>

            @include('components/errors')
            @include('components/success')

            {{-- Kiri: form input · Kanan: preview real-time (niru tampilan asli di section putih) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                {{-- FORM --}}
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-white/50"></i> Tambah Album
                    </h2>

                    <form action="{{ route('albums.tambah') }}" method="POST" enctype="multipart/form-data"
                        class="flex flex-col gap-4">
                        @csrf

                        <div class="flex flex-col gap-1.5">
                            <label for="albums_name" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Nama Album
                            </label>
                            <input type="text" id="albums_name" name="albums_name" placeholder="Masukkan nama album..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="link_spotify" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Link Spotify
                            </label>
                            <input type="text" id="link_spotify" name="link_spotify"
                                placeholder="https://open.spotify.com/..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="albums_cover" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Cover Album
                            </label>
                            <input type="file" id="albums_cover" name="albums_cover" accept="image/*"
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition" />
                            <p class="text-xs text-white/30">Gambar maks 1MB &middot; rasio 1:1 (kotak) paling pas.</p>
                        </div>

                        <button type="submit"
                            class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Upload Album
                        </button>
                    </form>
                </div>

                {{-- PREVIEW --}}
                <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-white overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-black/10 bg-black/3">
                        <i class="bi bi-eye text-black/40"></i>
                        <span class="text-xs uppercase tracking-widest text-black/40">Tampilan di homepage</span>
                    </div>

                    {{-- Replika PERSIS markup components/albums.blade.php: cover kotak di atas background putih --}}
                    <div class="p-8">
                        <div id="previewImgWrapper"
                            class="aspect-square w-full max-w-xs mx-auto rounded-lg overflow-hidden bg-black/5 border border-black/10">
                            <div id="previewImgEmpty"
                                class="w-full h-full flex flex-col items-center justify-center gap-2 text-black/25">
                                <i class="bi bi-image text-3xl"></i>
                                <p class="text-xs">Preview cover muncul di sini</p>
                            </div>
                            <img id="previewImg" src="" alt="Preview album"
                                class="hidden w-full h-full object-cover">
                        </div>
                    </div>

                    {{-- Metadata bantu admin --}}
                    <div class="px-8 pb-8 pt-4 flex flex-col gap-3 border-t border-black/10">
                        <div class="flex flex-col gap-1">
                            <p class="text-xs uppercase text-black/40">Nama album <span
                                    class="normal-case text-black/25">(metadata, tidak tampil di web)</span></p>
                            <p id="previewName" class="text-sm font-semibold text-black/80">—</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <p class="text-xs uppercase text-black/40">Link Spotify saat cover diklik</p>
                            <p id="previewLink" class="text-sm text-black/60 break-all">—</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- List data album yang udah ditambahkan --}}
            <div>
                <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                    <i class="bi bi-disc-fill text-white/50"></i> Album Tersimpan ({{ $albums->count() }})
                </h2>

                @if ($albums->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada album yang ditambahkan.',
                    ])
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach ($albums as $item)
                            <div
                                class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/3 p-3 transition-colors hover:border-white/20 min-w-0">
                                <img src="{{ Storage::url('albums/' . $item->albums_cover) }}"
                                    alt="{{ $item->albums_name }}" loading="lazy" decoding="async"
                                    class="w-16 h-16 sm:w-20 sm:h-20 shrink-0 rounded-xl object-cover border border-white/10">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-sm text-white truncate">{{ $item->albums_name }}</p>
                                    <p class="text-xs text-white/40 break-all line-clamp-2 mt-0.5">{{ $item->link_spotify }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @include('components.dashboard.modal-edit-albums')
                                    @include('components.dashboard.btn-hapus-albums')
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        const albumsName = document.getElementById('albums_name');
        const linkSpotify = document.getElementById('link_spotify');
        const albumsCover = document.getElementById('albums_cover');

        const previewName = document.getElementById('previewName');
        const previewLink = document.getElementById('previewLink');
        const previewImg = document.getElementById('previewImg');
        const previewImgEmpty = document.getElementById('previewImgEmpty');

        albumsName?.addEventListener('input', () => {
            previewName.textContent = albumsName.value.trim() || '—';
        });

        linkSpotify?.addEventListener('input', () => {
            previewLink.textContent = linkSpotify.value.trim() || '—';
        });

        albumsCover?.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
                previewImgEmpty.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });
    </script>
@endsection
