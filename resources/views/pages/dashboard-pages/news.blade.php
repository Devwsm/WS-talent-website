@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">news</h1>
            @include('components/errors')
            @include('components/success')
            <form action="{{ route('news.tambah') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4"
                id="news-form">
                @csrf
                {{-- Row 1: Title & Source --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="news_title" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                            News Title <span class="text-red-400">*</span>
                        </label>
                        <input type="text" id="news_title" name="news_title" placeholder="Masukkan judul berita..."
                            value="{{ old('news_title') }}"
                            class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="news_source" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                            News Source <span class="text-red-400">*</span>
                        </label>
                        <input type="text" id="news_source" name="news_source"
                            placeholder="Contoh: Kompas, CNN Indonesia..." value="{{ old('news_source') }}"
                            class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                    </div>
                </div>

                {{-- Row 2: Date & Link --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="news_date" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                            News Date <span class="text-red-400">*</span>
                        </label>
                        <input type="date" id="news_date" name="news_date" value="{{ old('news_date') }}"
                            class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition scheme-dark" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="news_link" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                            Link Berita
                        </label>
                        <input type="text" id="news_link" name="news_link" placeholder="https://..."
                            value="{{ old('news_link') }}"
                            class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                    </div>
                </div>

                {{-- News Cover --}}
                <div class="flex flex-col gap-1">
                    <label for="news_cover" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        News Cover
                    </label>
                    <div class="relative">
                        <input type="file" id="news_cover" name="news_cover" accept="image/*"
                            class="w-full bg-white/10 border border-white/20 border-dashed text-gray-400 p-3 rounded-lg cursor-pointer
                            file:mr-4 file:py-1 file:px-3 file:rounded file:border-0
                            file:text-sm file:font-semibold file:bg-[#5E0006] file:text-white
                            hover:file:bg-[#5E0006]/80 transition"
                            onchange="previewImage(event)" />
                    </div>
                    {{-- Preview gambar --}}
                    <div id="image-preview-wrapper" class="hidden mt-2">
                        <p class="text-xs text-gray-400 mb-1">Preview:</p>
                        <img id="image-preview" src="#" alt="Preview"
                            class="max-h-48 rounded-lg border border-white/20 object-cover" />
                    </div>
                </div>

                {{-- Rich Text Editor: News Description --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        News Description <span class="text-red-400">*</span>
                    </label>

                    {{-- Quill Editor Container --}}
                    <div
                        class="rounded-lg overflow-hidden border border-white/20 focus-within:border-[#5E0006] focus-within:ring-1 focus-within:ring-[#5E0006] transition">
                        <div id="quill-editor" style="min-height: 280px; background: rgba(255,255,255,0.07); color: white;">
                        </div>
                    </div>

                    {{-- Hidden input untuk menyimpan konten HTML dari Quill --}}
                    <input type="hidden" name="news_description" id="news_description_input">

                    <p class="text-xs text-gray-500 mt-1">Gunakan toolbar di atas untuk memformat teks: <strong
                            class="text-gray-400">Bold</strong>, <em class="text-gray-400">Italic</em>, heading, list, link,
                        dan lainnya.</p>
                </div>

                {{-- Submit Button --}}
                <button type="submit"
                    class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] hover:bg-[#5E0006]/70 active:scale-95 transition rounded-lg">
                    Publish Berita
                </button>
            </form>
        </div>


        <div class="bg-black/80 text-white p-6 md:p-8 mb-24 w-full rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">news list</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                @foreach ($news as $item)
                    <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center rounded-lg">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="flex flex-col w-full gap-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- news_title -->
                                    <div class="text-center">
                                        <h1 class="text-xs uppercase text-white/60">news title</h1>
                                        <h1 class="text-xs md:text-lg font-semibold">{{ $item->news_title }}</h1>
                                    </div>
                                    <!-- news_description -->
                                    <div class="text-center">
                                        <h1 class="text-xs uppercase text-white/60">news description</h1>
                                        <h1 class="text-xs md:text-lg font-semibold">{{ $item->news_description }}</h1>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- news_source -->
                                    <div class="text-center">
                                        <h1 class="text-xs uppercase text-white/60">news source</h1>
                                        <h1 class="text-xs md:text-lg font-semibold">{{ $item->news_source }}</h1>
                                    </div>
                                    <!-- news_date -->
                                    <div class="text-center">
                                        <h1 class="text-xs uppercase text-white/60">news date</h1>
                                        <h1 class="text-xs md:text-lg font-semibold">{{ $item->news_date }}</h1>
                                    </div>
                                </div>
                                <!-- news_link -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">link berita</h1>
                                    <h1 class="text-xs md:text-lg font-semibold break-all">{{ $item->news_link }}</h1>
                                </div>
                            </div>
                            <!-- news cover -->
                            <div class="flex flex-col gap-2 text-center justify-center items-center">
                                <h1 class="text-xs uppercase text-white/60">news cover</h1>
                                <img src="{{ Storage::url('news/' . $item->news_cover) }}" alt="{{ $item->news_title }}"
                                    loading="lazy" decoding="async"
                                    class="object-cover aspect-square w-full max-w-xs transition duration-300 rounded-lg">
                            </div>
                        </div>
                        {{-- action button --}}
                        <div class="flex flex-col w-full gap-4 mt-4 text-center">
                            <h1 class="text-xs uppercase text-white/60">action button</h1>
                            <div class="flex gap-2 justify-center items-center">
                                @include('components.dashboard.btn-hapus-news')
                                @include('components.dashboard.modal-edit-news')
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Quill JS --}}
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <style>
        /* Override Quill toolbar agar cocok dengan tema gelap */
        #news-form .ql-toolbar.ql-snow {
            background-color: rgba(255, 255, 255, 0.12);
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0;
            padding: 8px 12px;
        }

        #news-form .ql-toolbar.ql-snow .ql-stroke {
            stroke: #d1d5db;
        }

        #news-form .ql-toolbar.ql-snow .ql-fill {
            fill: #d1d5db;
        }

        #news-form .ql-toolbar.ql-snow .ql-picker {
            color: #d1d5db;
        }

        #news-form .ql-toolbar.ql-snow button:hover .ql-stroke,
        #news-form .ql-toolbar.ql-snow button.ql-active .ql-stroke {
            stroke: #ffffff;
        }

        #news-form .ql-toolbar.ql-snow button:hover .ql-fill,
        #news-form .ql-toolbar.ql-snow button.ql-active .ql-fill {
            fill: #ffffff;
        }

        #news-form .ql-toolbar.ql-snow .ql-picker-label:hover,
        #news-form .ql-toolbar.ql-snow .ql-picker-item:hover {
            color: #ffffff;
        }

        #news-form .ql-container.ql-snow {
            border: none;
            font-size: 15px;
            font-family: inherit;
        }

        #news-form .ql-editor {
            color: #f3f4f6;
            caret-color: white;
        }

        #news-form .ql-editor.ql-blank::before {
            color: #6b7280;
            font-style: normal;
        }

        #news-form .ql-editor h1,
        #news-form .ql-editor h2,
        #news-form .ql-editor h3 {
            color: #ffffff;
        }

        #news-form .ql-editor a {
            color: #f87171;
        }

        #news-form .ql-editor blockquote {
            border-left: 3px solid #5E0006;
            color: #9ca3af;
            padding-left: 12px;
            margin: 8px 0;
        }

        /* Picker dropdown dark */
        #news-form .ql-snow .ql-picker-options {
            background-color: #1f2937;
            border-color: rgba(255, 255, 255, 0.15);
        }

        #news-form .ql-snow .ql-picker-item {
            color: #d1d5db;
        }
    </style>

    <script>
        // Inisialisasi Quill Editor
        const quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Tulis deskripsi berita di sini...',
            modules: {
                toolbar: [
                    [{
                        'header': [1, 2, 3, false]
                    }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{
                        'color': []
                    }, {
                        'background': []
                    }],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    [{
                        'indent': '-1'
                    }, {
                        'indent': '+1'
                    }],
                    [{
                        'align': []
                    }],
                    ['blockquote', 'code-block'],
                    ['link'],
                    ['clean']
                ]
            }
        });

        // Isi ulang konten jika ada old value (setelah validasi gagal)
        @if (old('news_description'))
            quill.root.innerHTML = {!! json_encode(old('news_description')) !!};
        @endif

        // Sebelum form di-submit, salin konten HTML dari Quill ke hidden input
        document.getElementById('news-form').addEventListener('submit', function() {
            document.getElementById('news_description_input').value = quill.root.innerHTML;
        });

        // Preview gambar cover sebelum upload
        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('image-preview').src = e.target.result;
                document.getElementById('image-preview-wrapper').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    </script>
@endsection
