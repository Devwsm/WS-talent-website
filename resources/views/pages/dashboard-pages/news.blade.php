@extends('template/dashboardLayout')
@section('content')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div
            class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6
            px-5 md:px-10 pt-8 pb-28 text-white">

            <div>
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">News</h1>
                <p class="text-white/50 mt-1">Kelola berita yang tampil di halaman utama website.</p>
            </div>

            @include('components/errors')
            @include('components/success')

            {{-- Kiri: form input · Kanan: preview real-time (niru card berita asli, bg putih) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                {{-- FORM --}}
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-white/50"></i> Tambah Berita
                    </h2>

                    <form action="{{ route('news.tambah') }}" method="POST" enctype="multipart/form-data"
                        class="flex flex-col gap-4" id="news-form">
                        @csrf

                        {{-- Row 1: Title & Source --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="news_title"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                    Judul Berita <span class="text-red-400">*</span>
                                </label>
                                <input type="text" id="news_title" name="news_title"
                                    placeholder="Masukkan judul berita..." value="{{ old('news_title') }}"
                                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="news_source"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                    Sumber Berita <span class="text-red-400">*</span>
                                </label>
                                <input type="text" id="news_source" name="news_source"
                                    placeholder="Contoh: Kompas, CNN Indonesia..." value="{{ old('news_source') }}"
                                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                            </div>
                        </div>

                        {{-- Row 2: Date & Link --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="news_date"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                    Tanggal Berita <span class="text-red-400">*</span>
                                </label>
                                <input type="date" id="news_date" name="news_date" value="{{ old('news_date') }}"
                                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="news_link"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                    Link Berita
                                </label>
                                <input type="text" id="news_link" name="news_link" placeholder="https://..."
                                    value="{{ old('news_link') }}"
                                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                            </div>
                        </div>

                        {{-- News Cover --}}
                        <div class="flex flex-col gap-1.5">
                            <label for="news_cover" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Cover Berita
                            </label>
                            <input type="file" id="news_cover" name="news_cover" accept="image/*"
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition" />
                            <p class="text-xs text-white/30">Gambar maks 1MB &middot; rasio 1:1 (kotak) paling pas.</p>
                        </div>

                        {{-- Rich Text Editor: News Description --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Deskripsi Berita <span class="text-red-400">*</span>
                            </label>

                            <div
                                class="rounded-lg overflow-hidden border border-white/15 focus-within:border-red-900 focus-within:ring-1 focus-within:ring-red-900 transition">
                                <div id="quill-editor"
                                    style="min-height: 220px; background: rgba(255,255,255,0.05); color: white;">
                                </div>
                            </div>

                            {{-- Hidden input untuk menyimpan konten HTML dari Quill --}}
                            <input type="hidden" name="news_description" id="news_description_input">

                            <p class="text-xs text-white/30">Gunakan toolbar di atas untuk memformat teks: bold, italic,
                                heading, list, link, dan lainnya.</p>
                        </div>

                        <button type="submit"
                            class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Publish Berita
                        </button>
                    </form>
                </div>

                {{-- PREVIEW --}}
                <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-white overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-black/10 bg-black/3">
                        <i class="bi bi-eye text-black/40"></i>
                        <span class="text-xs uppercase tracking-widest text-black/40">Tampilan di homepage</span>
                    </div>

                    {{-- Replika PERSIS markup components/news.blade.php (1 card, bg abu terang) --}}
                    <div class="p-6">
                        <div class="card flex flex-col bg-gray-100 gap-2 rounded-lg overflow-hidden max-w-sm mx-auto">
                            <div id="previewImgWrapper" class="w-full aspect-square overflow-hidden bg-black/5">
                                <div id="previewImgEmpty"
                                    class="w-full h-full flex flex-col items-center justify-center gap-2 text-black/25">
                                    <i class="bi bi-image text-3xl"></i>
                                    <p class="text-xs">Preview cover muncul di sini</p>
                                </div>
                                <img id="previewImg" src="" alt="Preview berita"
                                    class="hidden w-full h-full object-cover object-center">
                            </div>

                            <div class="flex flex-col p-4 gap-2">
                                <div class="header flex items-center gap-2">
                                    <h1 id="previewSource" class="font-bold text-lg line-clamp-2 text-black">—</h1>
                                    <h1 id="previewDate" class="font-semibold text-sm text-[#5E0006]">—</h1>
                                </div>
                                <div class="body flex flex-col">
                                    <h1 id="previewTitle" class="font-bold text-3xl line-clamp-2 text-black">—</h1>
                                    <div id="previewDesc" class="font-light text-sm text-gray-600 line-clamp-3">—</div>
                                    <span
                                        class="w-full text-center text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] transition rounded-lg">
                                        Read more
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Metadata bantu admin --}}
                    <div class="px-6 pb-6 pt-2 flex flex-col gap-3 border-t border-black/10">
                        <div class="flex flex-col gap-1">
                            <p class="text-xs uppercase text-black/40">Link berita saat "Read more" diklik</p>
                            <p id="previewLink" class="text-sm text-black/60 break-all">—</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- List data berita yang udah ditambahkan --}}
            <div>
                <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                    <i class="bi bi-newspaper text-white/50"></i> Berita Tersimpan ({{ $news->count() }})
                </h2>

                @if ($news->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada berita yang ditambahkan.',
                    ])
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ($news as $item)
                            <div class="rounded-2xl border border-white/10 bg-white/3 overflow-hidden flex flex-col">
                                <img src="{{ Storage::url('news/' . $item->news_cover) }}" alt="{{ $item->news_title }}"
                                    loading="lazy" decoding="async" class="w-full aspect-video object-cover">
                                <div class="p-4 flex flex-col gap-1 flex-1">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="text-white/50 line-clamp-1">{{ $item->news_source }}</span>
                                        <span class="text-red-400">{{ $item->news_date }}</span>
                                    </div>
                                    <p class="font-semibold text-sm line-clamp-2">{{ $item->news_title }}</p>
                                    <p class="text-xs text-white/40 break-all line-clamp-1">{{ $item->news_link }}</p>
                                </div>
                                <div class="flex gap-2 p-4 pt-0">
                                    @include('components.dashboard.modal-edit-news')
                                    @include('components.dashboard.btn-hapus-news')
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quill JS --}}
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <style>
        /* Override Quill toolbar agar cocok dengan tema gelap */
        #news-form .ql-toolbar.ql-snow {
            background-color: rgba(255, 255, 255, 0.08);
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
        // ---- field refs ----
        const newsTitle = document.getElementById('news_title');
        const newsSource = document.getElementById('news_source');
        const newsDate = document.getElementById('news_date');
        const newsLink = document.getElementById('news_link');
        const newsCover = document.getElementById('news_cover');

        // ---- preview refs ----
        const previewTitle = document.getElementById('previewTitle');
        const previewSource = document.getElementById('previewSource');
        const previewDate = document.getElementById('previewDate');
        const previewLink = document.getElementById('previewLink');
        const previewImg = document.getElementById('previewImg');
        const previewImgEmpty = document.getElementById('previewImgEmpty');
        const previewDesc = document.getElementById('previewDesc');

        newsTitle?.addEventListener('input', () => {
            previewTitle.textContent = newsTitle.value.trim() || '—';
        });

        newsSource?.addEventListener('input', () => {
            previewSource.textContent = newsSource.value.trim() || '—';
        });

        newsDate?.addEventListener('input', () => {
            previewDate.textContent = newsDate.value || '—';
        });

        newsLink?.addEventListener('input', () => {
            previewLink.textContent = newsLink.value.trim() || '—';
        });

        newsCover?.addEventListener('change', (event) => {
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
            previewDesc.innerHTML = quill.root.innerHTML;
        @endif

        // Update preview deskripsi secara live tiap kali konten Quill berubah
        quill.on('text-change', () => {
            const html = quill.root.innerHTML;
            previewDesc.innerHTML = quill.getText().trim() ? html : '—';
        });

        // Sebelum form di-submit, salin konten HTML dari Quill ke hidden input
        document.getElementById('news-form').addEventListener('submit', function() {
            document.getElementById('news_description_input').value = quill.root.innerHTML;
        });
    </script>
@endsection
