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
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">Merchandise</h1>
                <p class="text-white/50 mt-1">Kelola merchandise yang tampil di halaman utama website (link ke
                    marketplace/toko).</p>
            </div>

            @include('components/errors')
            @include('components/success')

            {{-- Kiri: form input · Kanan: preview real-time (niru tampilan asli di section putih) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                {{-- FORM --}}
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-white/50"></i> Tambah Merchandise
                    </h2>

                    <form action="{{ route('merchandise.tambah') }}" method="POST" enctype="multipart/form-data"
                        class="flex flex-col gap-4">
                        @csrf

                        <div class="flex flex-col gap-1.5">
                            <label for="merchandise_name"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Nama Merchandise
                            </label>
                            <input type="text" id="merchandise_name" name="merchandise_name"
                                placeholder="Masukkan nama merchandise..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="link_merchandise"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Link Merchandise
                            </label>
                            <input type="text" id="link_merchandise" name="link_merchandise" placeholder="https://..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="merchandise_cover"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Cover Merchandise
                            </label>
                            <input type="file" id="merchandise_cover" name="merchandise_cover" accept="image/*"
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition" />
                            <p class="text-xs text-white/30">Gambar maks 1MB &middot; rasio 1:1 (kotak) paling pas.</p>
                        </div>

                        <button type="submit"
                            class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Upload Merchandise
                        </button>
                    </form>
                </div>

                {{-- PREVIEW --}}
                <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-white overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-black/10 bg-black/3">
                        <i class="bi bi-eye text-black/40"></i>
                        <span class="text-xs uppercase tracking-widest text-black/40">Tampilan di homepage</span>
                    </div>

                    {{-- Replika PERSIS markup components/merchandise.blade.php: cover kotak di atas background putih --}}
                    <div class="p-8">
                        <div id="previewImgWrapper"
                            class="aspect-square w-full max-w-xs mx-auto rounded-lg overflow-hidden bg-black/5 border border-black/10">
                            <div id="previewImgEmpty"
                                class="w-full h-full flex flex-col items-center justify-center gap-2 text-black/25">
                                <i class="bi bi-image text-3xl"></i>
                                <p class="text-xs">Preview cover muncul di sini</p>
                            </div>
                            <img id="previewImg" src="" alt="Preview merchandise"
                                class="hidden w-full h-full object-cover">
                        </div>
                    </div>

                    {{-- Metadata bantu admin --}}
                    <div class="px-8 pb-8 pt-4 flex flex-col gap-3 border-t border-black/10">
                        <div class="flex flex-col gap-1">
                            <p class="text-xs uppercase text-black/40">Nama merchandise <span
                                    class="normal-case text-black/25">(metadata, tidak tampil di web)</span></p>
                            <p id="previewName" class="text-sm font-semibold text-black/80">—</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <p class="text-xs uppercase text-black/40">Link tujuan saat cover diklik</p>
                            <p id="previewLink" class="text-sm text-black/60 break-all">—</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- List data merchandise yang udah ditambahkan --}}
            <div>
                <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                    <i class="bi bi-basket-fill text-white/50"></i> Merchandise Tersimpan ({{ $merchandise->count() }})
                </h2>

                @if ($merchandise->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada merchandise yang ditambahkan.',
                    ])
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                        @foreach ($merchandise as $item)
                            <div class="rounded-2xl border border-white/10 bg-white/3 overflow-hidden flex flex-col">
                                <img src="{{ Storage::url('merchandise/' . $item->merchandise_cover) }}"
                                    alt="{{ $item->merchandise_name }}" loading="lazy" decoding="async"
                                    class="w-full aspect-square object-cover">
                                <div class="p-3 flex flex-col gap-1 flex-1">
                                    <p class="font-semibold text-xs line-clamp-1">{{ $item->merchandise_name }}</p>
                                    <p class="text-[11px] text-white/40 break-all line-clamp-1">
                                        {{ $item->link_merchandise }}</p>
                                </div>
                                <div class="flex gap-2 p-3 pt-0">
                                    @include('components.dashboard.modal-edit-merchandise')
                                    @include('components.dashboard.btn-hapus-merchandise')
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        const merchandiseName = document.getElementById('merchandise_name');
        const linkMerchandise = document.getElementById('link_merchandise');
        const merchandiseCover = document.getElementById('merchandise_cover');

        const previewName = document.getElementById('previewName');
        const previewLink = document.getElementById('previewLink');
        const previewImg = document.getElementById('previewImg');
        const previewImgEmpty = document.getElementById('previewImgEmpty');

        merchandiseName?.addEventListener('input', () => {
            previewName.textContent = merchandiseName.value.trim() || '—';
        });

        linkMerchandise?.addEventListener('input', () => {
            previewLink.textContent = linkMerchandise.value.trim() || '—';
        });

        merchandiseCover?.addEventListener('change', (event) => {
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
