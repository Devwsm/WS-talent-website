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
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">Header</h1>
                <p class="text-white/50 mt-1">Kelola hero/slide utama yang tampil di paling atas halaman utama website.</p>
            </div>

            @include('components/errors')
            @include('components/success')

            {{-- Kiri: form input · Kanan: preview real-time (niru tampilan asli hero) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                {{-- FORM --}}
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-white/50"></i> Tambah Header
                    </h2>

                    <form action="{{ route('headers.tambah') }}" method="POST" enctype="multipart/form-data"
                        class="flex flex-col gap-4">
                        @csrf

                        {{-- header_color --}}
                        <div class="flex flex-col gap-1.5">
                            <label for="header_color" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Warna Badge
                            </label>
                            <div class="flex items-center gap-3">
                                <input id="header_color" type="color" name="header_color" value="#5E0006"
                                    class="h-11 w-16 shrink-0 cursor-pointer rounded-lg border border-white/15 bg-white/5 p-1" />
                                <span id="header_color_value" class="text-sm font-mono text-white/50">#5E0006</span>
                            </div>
                        </div>

                        {{-- header_title (teks badge) --}}
                        <div class="flex flex-col gap-1.5">
                            <label for="header_title" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Judul Badge
                            </label>
                            <input type="text" id="header_title" name="header_title" placeholder="Misal: Rilisan Terbaru"
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        {{-- header_img --}}
                        <div class="flex flex-col gap-1.5">
                            <label for="header_img" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Gambar Header
                            </label>
                            <input type="file" id="header_img" name="header_img" accept="image/jpeg,image/jpg,image/png"
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                    file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                    file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                    hover:file:bg-red-900 transition" />
                            <p class="text-xs text-white/30">Gambar maks 1MB.</p>
                            <p id="error_header_img" class="hidden text-xs text-red-400"></p>
                        </div>

                        {{-- header_name --}}
                        <div class="flex flex-col gap-1.5">
                            <label for="header_name" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Nama
                            </label>
                            <input type="text" id="header_name" name="header_name" placeholder="Masukkan nama header..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        {{-- header_description --}}
                        <div class="flex flex-col gap-1.5">
                            <label for="header_description"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Deskripsi
                            </label>
                            <textarea id="header_description" name="header_description" rows="2" placeholder="Masukkan deskripsi header..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition resize-none"></textarea>
                        </div>

                        {{-- link_header --}}
                        <div class="flex flex-col gap-1.5">
                            <label for="link_header" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Link Header
                            </label>
                            <input type="text" id="link_header" name="link_header" placeholder="https://..."
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                        </div>

                        {{-- header_background --}}
                        <div class="flex flex-col gap-1.5">
                            <label for="header_background"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Cover Background (gambar/video)
                            </label>
                            <input type="file" id="header_background" name="header_background"
                                accept="image/jpeg,image/jpg,image/png,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm"
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                    file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                    file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                    hover:file:bg-red-900 transition" />
                            <p class="text-xs text-white/30">Gambar maks 1MB &middot; Video maks 25MB.</p>
                            <p id="error_header_background" class="hidden text-xs text-red-400"></p>
                        </div>

                        <button type="submit"
                            class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Upload Header
                        </button>
                    </form>
                </div>

                {{-- PREVIEW --}}
                <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-black overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                        <i class="bi bi-eye text-white/40"></i>
                        <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di homepage (hero)</span>
                    </div>

                    {{-- Replika niru components/videos.blade.php, dipadatkan jadi 1 slide di panel --}}
                    <div class="relative w-full aspect-3/4 sm:aspect-video overflow-hidden bg-white/5">
                        {{-- background --}}
                        <div id="previewBgEmpty"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-white/20">
                            <i class="bi bi-film text-3xl"></i>
                            <p class="text-xs">Background muncul di sini</p>
                        </div>
                        <img id="previewBgImg" src="" alt="Preview background"
                            class="hidden absolute inset-0 w-full h-full object-cover z-0 bg-black">
                        <video id="previewBgVideo" muted loop playsinline autoplay
                            class="hidden absolute inset-0 w-full h-full object-cover z-0 bg-black"></video>

                        {{-- overlay konten, sama kayak components/videos.blade.php --}}
                        <div
                            class="absolute inset-0 z-10 bg-black/50 flex flex-col justify-center items-center text-white text-center gap-3 px-4">
                            <span id="previewBadge"
                                class="inline-flex items-center gap-2 w-fit bg-white/15 backdrop-blur border rounded-full px-3 py-1 text-white text-xs"
                                style="border-color: #5E0006;">
                                <span id="previewColorDot" class="w-2 h-2 rounded-full"
                                    style="background-color: #5E0006;"></span>
                                <span id="previewBadgeTitle">—</span>
                            </span>

                            <div id="previewImgWrapper" class="hidden">
                                <img id="previewImg" src="" alt="Preview header image"
                                    class="object-cover w-28 sm:w-40 rounded-lg">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <h3 id="previewName" class="text-lg sm:text-xl font-semibold text-white">—</h3>
                                <p id="previewDesc" class="text-xs text-white/70">—</p>
                                <div class="flex gap-3 justify-center mt-1">
                                    <span id="previewWatchBtn"
                                        class="px-4 py-1.5 shadow-sm font-semibold rounded-full text-xs"
                                        style="background-color: #5E0006;">Watch Video</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Metadata bantu admin --}}
                    <div class="px-5 py-4 flex flex-col gap-1 border-t border-white/10">
                        <p class="text-xs uppercase text-white/40">Link tujuan tombol "Watch Video"</p>
                        <p id="previewLink" class="text-sm text-white/60 break-all">—</p>
                    </div>
                </div>
            </div>

            {{-- List data header yang udah ditambahkan --}}
            <div>
                <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                    <i class="bi bi-card-image text-white/50"></i> Header Tersimpan ({{ $header->count() }})
                </h2>

                @if ($header->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada header yang diupload.',
                    ])
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ($header as $item)
                            @php
                                $bgExtension = strtolower(pathinfo($item->header_background, PATHINFO_EXTENSION));
                                $isVideo = in_array($bgExtension, ['mp4', 'webm', 'mov']);
                            @endphp
                            <div class="rounded-2xl border border-white/10 bg-white/3 overflow-hidden flex flex-col">
                                <div class="relative w-full aspect-video bg-black">
                                    @if ($isVideo)
                                        <video autoplay muted loop playsinline
                                            class="absolute inset-0 w-full h-full object-cover opacity-60">
                                            <source
                                                src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                                type="video/mp4">
                                        </video>
                                    @else
                                        <img src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                            alt="{{ $item->header_name }}" loading="lazy" decoding="async"
                                            class="absolute inset-0 w-full h-full object-cover opacity-60">
                                    @endif
                                    <div class="absolute inset-0 bg-linear-to-t from-black via-black/30 to-transparent">
                                    </div>
                                    <div class="relative z-10 h-full flex flex-col justify-end gap-1 p-3">
                                        <span style="border-color: {{ $item->header_color }}99;"
                                            class="inline-flex items-center gap-1.5 w-fit bg-white/10 backdrop-blur border rounded-full px-2 py-0.5 text-[10px]">
                                            <span style="background-color: {{ $item->header_color }};"
                                                class="w-1.5 h-1.5 rounded-full"></span>
                                            {{ $item->header_title }}
                                        </span>
                                        <p class="font-semibold text-sm line-clamp-1">{{ $item->header_name }}</p>
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col gap-1 flex-1">
                                    <p class="text-xs text-white/50 line-clamp-2">{{ $item->header_description }}</p>
                                    <p class="text-xs text-white/30 break-all line-clamp-1">{{ $item->link_header }}</p>
                                </div>
                                <div class="flex gap-2 p-4 pt-0">
                                    @include('components.dashboard.modal-edit-header')
                                    @include('components.dashboard.btn-hapus-header')
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // ---- field refs ----
        const headerColor = document.getElementById('header_color');
        const headerTitle = document.getElementById('header_title');
        const headerImg = document.getElementById('header_img');
        const headerName = document.getElementById('header_name');
        const headerDescription = document.getElementById('header_description');
        const linkHeader = document.getElementById('link_header');
        const headerBackground = document.getElementById('header_background');

        // ---- preview refs ----
        const colorValueText = document.getElementById('header_color_value');
        const previewColorDot = document.getElementById('previewColorDot');
        const previewBadge = document.getElementById('previewBadge');
        const previewBadgeTitle = document.getElementById('previewBadgeTitle');
        const previewWatchBtn = document.getElementById('previewWatchBtn');
        const previewImgWrapper = document.getElementById('previewImgWrapper');
        const previewImg = document.getElementById('previewImg');
        const previewName = document.getElementById('previewName');
        const previewDesc = document.getElementById('previewDesc');
        const previewLink = document.getElementById('previewLink');
        const previewBgEmpty = document.getElementById('previewBgEmpty');
        const previewBgImg = document.getElementById('previewBgImg');
        const previewBgVideo = document.getElementById('previewBgVideo');

        headerColor?.addEventListener('input', () => {
            const val = headerColor.value;
            colorValueText.textContent = val;
            previewColorDot.style.backgroundColor = val;
            previewBadge.style.borderColor = val + '99';
            previewWatchBtn.style.backgroundColor = val;
        });

        headerTitle?.addEventListener('input', () => {
            previewBadgeTitle.textContent = headerTitle.value.trim() || '—';
        });

        headerName?.addEventListener('input', () => {
            previewName.textContent = headerName.value.trim() || '—';
        });

        headerDescription?.addEventListener('input', () => {
            previewDesc.textContent = headerDescription.value.trim() || '—';
        });

        linkHeader?.addEventListener('input', () => {
            previewLink.textContent = linkHeader.value.trim() || '—';
        });

        headerImg?.addEventListener('change', (event) => {
            const file = event.target.files[0];
            const errorEl = document.getElementById('error_header_img');
            errorEl.classList.add('hidden');
            if (!file) return;
            if (file.size > 1 * 1024 * 1024) {
                errorEl.textContent = 'Gambar tidak boleh lebih dari 1MB.';
                errorEl.classList.remove('hidden');
                headerImg.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                previewImgWrapper.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });

        headerBackground?.addEventListener('change', (event) => {
            const file = event.target.files[0];
            const errorEl = document.getElementById('error_header_background');
            errorEl.classList.add('hidden');
            if (!file) return;

            const isImage = file.type.startsWith('image/');
            const isVideo = file.type.startsWith('video/');
            const maxImage = 1 * 1024 * 1024;
            const maxVideo = 25 * 1024 * 1024;

            if (isImage && file.size > maxImage) {
                errorEl.textContent = 'Gambar background tidak boleh lebih dari 1MB.';
                errorEl.classList.remove('hidden');
                headerBackground.value = '';
                return;
            }
            if (isVideo && file.size > maxVideo) {
                errorEl.textContent = 'Video background tidak boleh lebih dari 25MB.';
                errorEl.classList.remove('hidden');
                headerBackground.value = '';
                return;
            }

            previewBgEmpty.classList.add('hidden');

            if (isImage) {
                previewBgVideo.classList.add('hidden');
                previewBgVideo.removeAttribute('src');
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewBgImg.src = e.target.result;
                    previewBgImg.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else if (isVideo) {
                previewBgImg.classList.add('hidden');
                previewBgImg.removeAttribute('src');
                previewBgVideo.src = URL.createObjectURL(file);
                previewBgVideo.classList.remove('hidden');
                previewBgVideo.play().catch(() => {});
            }
        });
    </script>
@endsection
