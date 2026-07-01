@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">header</h1>
            @include('components/errors')
            @include('components/success')
            <form action="{{ route('headers.tambah') }}" method="POST" enctype="multipart/form-data"
                class="flex flex-col gap-2">
                @csrf
                {{-- header_color --}}
                <div class="flex flex-col gap-2">
                    <label for="header_color" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        Header Color
                    </label>

                    <input id="header_color" type="color" name="header_color" value="#5E0006"
                        oninput="document.getElementById('header_color_preview').style.backgroundColor = this.value"
                        class="h-12 w-full cursor-pointer rounded-lg border border-white/20 bg-white/10 p-1" />

                    <div class="flex items-center gap-3">
                        <div id="header_color_preview" class="h-8 w-8 rounded-md border border-white/20"
                            style="background-color: #5E0006;"></div>
                        <span class="text-sm text-gray-300" id="header_color_value">#5E0006</span>
                    </div>
                </div>

                <script>
                    const colorInput = document.getElementById('header_color');
                    const colorPreview = document.getElementById('header_color_preview');
                    const colorValue = document.getElementById('header_color_value');

                    colorInput.addEventListener('input', function() {
                        colorPreview.style.backgroundColor = this.value;
                        colorValue.textContent = this.value;
                    });
                </script>
                {{-- header_title --}}
                <div class="flex flex-col gap-2">
                    <label htmlFor="header_title" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        header title
                    </label>
                    <input type="text" name="header_title" placeholder="Masukan judul header..."
                        class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                </div>

                {{-- header header_img --}}
                <div class="flex flex-col gap-2" data-media-input>
                    <label for="header_img" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        header image
                    </label>
                    <input type="file" id="header_img" name="header_img" accept="image/jpeg,image/jpg,image/png"
                        data-preview-target="header_img"
                        class="w-full bg-white/10 border border-white/20 border-dashed text-gray-400 p-3 rounded-lg cursor-pointer
                            file:mr-4 file:py-1 file:px-3 file:rounded file:border-0
                            file:text-sm file:font-semibold file:bg-[#5E0006] file:text-white
                            hover:file:bg-[#5E0006]/80 transition"
                        onchange="new MediaPreview(this).handle(event)" />

                    <div id="preview-header_img" class="hidden mt-2">
                        <p class="text-xs text-gray-400 mb-1">Preview:</p>
                        <img class="preview-el max-h-48 rounded-lg border border-white/20 object-cover" />
                        <video class="preview-el max-h-48 rounded-lg border border-white/20 object-cover w-full hidden"
                            controls muted></video>
                    </div>
                    <p class="error-el hidden text-xs text-red-400 mt-1"></p>
                </div>
                {{-- header header_img end --}}

                {{-- header_name --}}
                <div class="flex flex-col gap-2">
                    <label htmlFor="header_name" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        header name
                    </label>
                    <input type="text" name="header_name" placeholder="Masukan nama header..."
                        class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                </div>
                {{-- header_description --}}
                <div class="flex flex-col gap-2">
                    <label htmlFor="header_description"
                        class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        header description
                    </label>
                    <input type="text" name="header_description" placeholder="Masukan deskripsi header..."
                        class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                </div>
                {{-- link_header --}}
                <div class="flex flex-col gap-2">
                    <label htmlFor="link_header" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        link header
                    </label>
                    <input type="text" name="link_header" placeholder="https://..."
                        class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                </div>

                {{-- header header_background --}}
                <div class="flex flex-col gap-2" data-media-input>
                    <label for="header_background" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        header background
                    </label>
                    <input type="file" id="header_background" name="header_background"
                        accept="image/jpeg,image/jpg,image/png,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm"
                        data-preview-target="header_background"
                        class="w-full bg-white/10 border border-white/20 border-dashed text-gray-400 p-3 rounded-lg cursor-pointer
                            file:mr-4 file:py-1 file:px-3 file:rounded file:border-0
                            file:text-sm file:font-semibold file:bg-[#5E0006] file:text-white
                            hover:file:bg-[#5E0006]/80 transition"
                        onchange="new MediaPreview(this).handle(event)" />
                    <p class="text-xs text-gray-500 mt-1">Gambar maks 1MB &middot; Video maks 25MB</p>

                    <div id="preview-header_background" class="hidden mt-2">
                        <p class="text-xs text-gray-400 mb-1">Preview:</p>
                        <img class="preview-el max-h-48 rounded-lg border border-white/20 object-cover" />
                        <video class="preview-el max-h-48 rounded-lg border border-white/20 object-cover w-full hidden"
                            controls muted></video>
                    </div>
                    <p class="error-el hidden text-xs text-red-400 mt-1"></p>
                </div>
                {{-- header header_background end --}}

                {{-- Submit Button --}}
                <button type="submit"
                    class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] hover:bg-[#5E0006]/70 active:scale-95 transition rounded-lg">
                    Upload header
                </button>
            </form>
        </div>

        {{-- Media Preview Script --}}
        <script>
            class MediaPreview {
                constructor(input) {
                    this.input = input;
                    this.wrapper = document.getElementById('preview-' + input.dataset.previewTarget);
                    this.img = this.wrapper.querySelector('img.preview-el');
                    this.video = this.wrapper.querySelector('video.preview-el');
                    this.errorEl = input.closest('[data-media-input]').querySelector('.error-el');
                }

                reset() {
                    this.wrapper.classList.add('hidden');
                    this.img.classList.add('hidden');
                    this.video.classList.add('hidden');
                    this.errorEl.classList.add('hidden');
                    this.errorEl.textContent = '';
                }

                handle(event) {
                    const file = event.target.files[0];
                    this.reset();
                    if (!file) return;

                    const isImage = file.type.startsWith('image/');
                    const isVideo = file.type.startsWith('video/');
                    const maxImage = 1 * 1024 * 1024;
                    const maxVideo = 25 * 1024 * 1024;

                    if (!isImage && !isVideo) return this.fail('Format file tidak didukung.');
                    if (isImage && file.size > maxImage) return this.fail('Ukuran gambar tidak boleh lebih dari 1MB.');
                    if (isVideo && file.size > maxVideo) return this.fail('Ukuran video tidak boleh lebih dari 25MB.');

                    this.wrapper.classList.remove('hidden');

                    if (isImage) {
                        const reader = new FileReader();
                        reader.onload = e => {
                            this.img.src = e.target.result;
                            this.img.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        this.video.src = URL.createObjectURL(file);
                        this.video.classList.remove('hidden');
                    }
                }

                fail(message) {
                    this.errorEl.textContent = message;
                    this.errorEl.classList.remove('hidden');
                    this.input.value = '';
                }
            }
        </script>


        <div class="bg-black/80 text-white p-6 md:p-8 mb-24 w-full rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">header list</h1>
            <div class="grid grid-cols-1 gap-4 w-full">
                @foreach ($header as $item)
                    <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center rounded-lg">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- header_color -->
                                <div class="text-center md:text-left">
                                    <h1 class="text-xs uppercase text-white/60">header color</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->header_color }}</h1>
                                </div>
                                <!-- header_title -->
                                <div class="text-center md:text-left">
                                    <h1 class="text-xs uppercase text-white/60">header title</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->header_title }}</h1>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- header_name -->
                                <div class="text-center md:text-left">
                                    <h1 class="text-xs uppercase text-white/60">header name</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->header_name }}</h1>
                                </div>
                                <!-- header_description -->
                                <div class="text-center md:text-left">
                                    <h1 class="text-xs uppercase text-white/60">header description</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->header_description }}</h1>
                                </div>
                                <!-- link_header -->
                                <div class="text-center md:text-left">
                                    <h1 class="text-xs uppercase text-white/60">link header</h1>
                                    <h1 class="text-xs md:text-lg font-semibold break-all">{{ $item->link_header }}</h1>
                                </div>
                            </div>
                            <!-- header img -->
                            <div class="flex flex-col gap-2 text-center justify-center items-center">
                                <h1 class="text-xs uppercase text-white/60">header img</h1>
                                <img src="{{ Storage::url('header/img/' . $item->header_img) }}"
                                    alt="{{ $item->header_name }}" loading="lazy" decoding="async"
                                    class="object-cover w-full h-full transition duration-300 rounded-lg">
                            </div>
                            <!-- header background -->
                            <div class="flex flex-col gap-2 text-center justify-center items-center">
                                <h1 class="text-xs uppercase text-white/60">header background</h1>
                                @php
                                    $bgExtension = strtolower(pathinfo($item->header_background, PATHINFO_EXTENSION));
                                    $videoExtensions = ['mp4', 'webm', 'mov'];
                                    $isVideo = in_array($bgExtension, $videoExtensions);
                                @endphp
                                @if ($isVideo)
                                    <video autoplay muted loop playsinline
                                        class="object-cover w-full h-full transition duration-300 rounded-lg">
                                        <source src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                            type="video/mp4">
                                    </video>
                                @else
                                    <img src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                        alt="{{ $item->header_name }}" loading="lazy" decoding="async"
                                        class="object-cover w-full h-full transition duration-300 rounded-lg">
                                @endif
                            </div>
                        </div>
                        {{-- action button --}}
                        <div class="flex flex-col w-full gap-4 mt-4 text-center">
                            <h1 class="text-xs uppercase text-white/60">action button</h1>
                            <div class="flex gap-2 justify-center items-center">
                                @include('components.dashboard.btn-hapus-header')
                                @include('components.dashboard.btn-edit-header')
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
