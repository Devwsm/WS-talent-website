{{--
    BIO (singleton, rich text via Quill). Preview niru PERSIS markup section Bio di
    resources/views/components/profile/profile-full.blade.php (baris 34-53).
    Expects: $bio (App\Models\bio|null)
--}}
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-file-text text-white/50"></i> Bio
        </h3>

        <form action="{{ route('bio.simpan') }}" method="POST" class="flex flex-col gap-4" id="bio-form">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Konten Bio <span class="text-red-400">*</span>
                </label>

                <div
                    class="rounded-lg overflow-hidden border border-white/15 focus-within:border-red-900 focus-within:ring-1 focus-within:ring-red-900 transition">
                    <div id="bio-quill-editor"
                        style="min-height: 220px; background: rgba(255,255,255,0.05); color: white;">
                    </div>
                </div>

                {{-- Hidden input untuk menyimpan konten HTML dari Quill --}}
                <input type="hidden" name="konten" id="bio_konten_input">

                <p class="text-xs text-white/30">Bisa multi paragraf, bold, italic, dll — persis kayak editor News.</p>
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Simpan Bio
            </button>
        </form>
    </div>

    {{-- PREVIEW --}}
    <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-black overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
            <i class="bi bi-eye text-white/40"></i>
            <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di halaman Profile</span>
        </div>

        {{-- Replika PERSIS markup section Bio --}}
        <div class="p-5 flex flex-col gap-3">
            <h2 class="text-xs text-white/30 uppercase tracking-widest">Bio</h2>
            <div id="bioPreviewContent" class="text-sm text-white/70 leading-relaxed flex flex-col gap-3">
                {!! $bio->konten ?? null ? $bio->konten : '<span class="text-white/30">—</span>' !!}
            </div>
        </div>
    </div>
</div>

<style>
    /* Override Quill toolbar agar cocok dengan tema gelap — sama kayak News */
    #bio-form .ql-toolbar.ql-snow {
        background-color: rgba(255, 255, 255, 0.08);
        border: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0;
        padding: 8px 12px;
    }

    #bio-form .ql-toolbar.ql-snow .ql-stroke {
        stroke: #d1d5db;
    }

    #bio-form .ql-toolbar.ql-snow .ql-fill {
        fill: #d1d5db;
    }

    #bio-form .ql-toolbar.ql-snow .ql-picker {
        color: #d1d5db;
    }

    #bio-form .ql-toolbar.ql-snow button:hover .ql-stroke,
    #bio-form .ql-toolbar.ql-snow button.ql-active .ql-stroke {
        stroke: #ffffff;
    }

    #bio-form .ql-toolbar.ql-snow button:hover .ql-fill,
    #bio-form .ql-toolbar.ql-snow button.ql-active .ql-fill {
        fill: #ffffff;
    }

    #bio-form .ql-container.ql-snow {
        border: none;
        font-size: 15px;
        font-family: inherit;
    }

    #bio-form .ql-editor {
        color: #f3f4f6;
        caret-color: white;
    }

    #bio-form .ql-editor.ql-blank::before {
        color: #6b7280;
        font-style: normal;
    }

    #bio-form .ql-editor a {
        color: #f87171;
    }

    /* Styling konten hasil Quill di panel preview, niru span putih tebal di profile-full.blade.php */
    #bioPreviewContent p {
        margin: 0;
    }

    #bioPreviewContent strong,
    #bioPreviewContent b {
        color: #ffffff;
        font-weight: 500;
    }
</style>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    (() => {
        const previewContent = document.getElementById('bioPreviewContent');

        const quill = new Quill('#bio-quill-editor', {
            theme: 'snow',
            placeholder: 'Tulis bio di sini, bisa beberapa paragraf...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        // Isi editor dengan data yang sudah tersimpan (atau old value kalau validasi gagal)
        @if (old('konten'))
            quill.root.innerHTML = {!! json_encode(old('konten')) !!};
        @elseif ($bio->konten ?? null)
            quill.root.innerHTML = {!! json_encode($bio->konten) !!};
        @endif

        // Update preview secara live tiap kali konten Quill berubah
        quill.on('text-change', () => {
            const html = quill.root.innerHTML;
            previewContent.innerHTML = quill.getText().trim() ? html :
                '<span class="text-white/30">—</span>';
        });

        // Sebelum form di-submit, salin konten HTML dari Quill ke hidden input
        document.getElementById('bio-form').addEventListener('submit', function() {
            document.getElementById('bio_konten_input').value = quill.root.innerHTML;
        });
    })();
</script>
