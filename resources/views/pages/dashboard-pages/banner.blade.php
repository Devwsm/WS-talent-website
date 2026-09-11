@extends('template/dashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman utama dashboard --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div
            class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6
            px-5 md:px-10 pt-8 pb-28 text-white">

            <div>
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">Banner</h1>
                <p class="text-white/50 mt-1">Kelola banner promo yang tampil di halaman utama website.</p>
            </div>

            @include('components/errors')
            @include('components/success')

            {{-- Kiri: form input · Kanan: preview real-time (niru tampilan asli, bukan mockup) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                {{-- FORM --}}
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-white/50"></i> Tambah Banner
                    </h2>

                    <form action="{{ route('banner.tambah') }}" method="POST" enctype="multipart/form-data"
                        class="flex flex-col gap-4">
                        @csrf

                        <div class="flex flex-col gap-1.5">
                            <label for="banner_name" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Nama Banner
                            </label>
                            <input type="text" id="banner_name" name="banner_name" placeholder="Masukkan nama banner..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="link_banner" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Link Banner
                            </label>
                            <input type="text" id="link_banner" name="link_banner" placeholder="https://..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="banner_cover" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Cover Banner
                            </label>
                            <input type="file" id="banner_cover" name="banner_cover" accept="image/*"
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition" />
                        </div>

                        <button type="submit"
                            class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Upload Banner
                        </button>
                    </form>
                </div>

                {{-- PREVIEW --}}
                <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-black overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                        <i class="bi bi-eye text-white/40"></i>
                        <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di homepage</span>
                    </div>

                    {{-- Replika PERSIS markup components/banner.blade.php, biar 1:1 sama kayak yang tayang --}}
                    <div class="p-5">
                        <div class="flex flex-col w-full justify-center items-center">
                            <div id="previewImgWrapper"
                                class="w-full rounded-lg overflow-hidden bg-white/5 border border-white/10">
                                <div id="previewImgEmpty"
                                    class="aspect-video flex flex-col items-center justify-center gap-2 text-white/30">
                                    <i class="bi bi-image text-3xl"></i>
                                    <p class="text-xs">Preview banner muncul di sini</p>
                                </div>
                                <img id="previewImg" src="" alt="Preview banner" class="hidden w-full rounded-lg">
                            </div>
                        </div>
                    </div>

                    {{-- Metadata bantu admin, ditandai jelas ini BUKAN yang tayang di layout banner-nya --}}
                    <div class="px-5 pb-5 pt-4 flex flex-col gap-3 border-t border-white/10">
                        <div class="flex flex-col gap-1">
                            <p class="text-xs uppercase text-white/40">Nama banner <span
                                    class="normal-case text-white/25">(metadata, tidak tampil di web)</span></p>
                            <p id="previewName" class="text-sm font-semibold text-white/80">—</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <p class="text-xs uppercase text-white/40">Link tujuan saat banner diklik</p>
                            <p id="previewLink" class="text-sm text-white/60 break-all">—</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- List data banner yang udah ditambahkan --}}
            <div>
                <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                    <i class="bi bi-images text-white/50"></i> Banner Tersimpan ({{ $banner->count() }})
                </h2>

                @if ($banner->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada banner yang ditambahkan.',
                    ])
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ($banner as $item)
                            <div class="rounded-2xl border border-white/10 bg-white/3 overflow-hidden flex flex-col">
                                <img src="{{ Storage::url('banner/' . $item->banner_cover) }}"
                                    alt="{{ $item->banner_name }}" loading="lazy" decoding="async"
                                    class="w-full aspect-video object-cover">
                                <div class="p-4 flex flex-col gap-1 flex-1">
                                    <p class="font-semibold text-sm line-clamp-1">{{ $item->banner_name }}</p>
                                    <p class="text-xs text-white/40 break-all line-clamp-1">{{ $item->link_banner }}</p>
                                </div>
                                <div class="flex gap-2 p-4 pt-0">
                                    @include('components.dashboard.modal-edit-banner')
                                    @include('components.dashboard.btn-hapus-banner')
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        const bannerName = document.getElementById('banner_name');
        const linkBanner = document.getElementById('link_banner');
        const bannerCover = document.getElementById('banner_cover');

        const previewName = document.getElementById('previewName');
        const previewLink = document.getElementById('previewLink');
        const previewImg = document.getElementById('previewImg');
        const previewImgEmpty = document.getElementById('previewImgEmpty');

        bannerName?.addEventListener('input', () => {
            previewName.textContent = bannerName.value.trim() || '—';
        });

        linkBanner?.addEventListener('input', () => {
            previewLink.textContent = linkBanner.value.trim() || '—';
        });

        bannerCover?.addEventListener('change', (event) => {
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
