{{-- Kebijakan privasi di tab Konten: editor teks kaya (Quill). Variabel: $privasiKustom (bool), $privasiHtml (isi awal editor),
     $privasiTanggal (YYYY-MM-DD). Tanpa isi sendiri, halaman /privasi memakai teks bawaan. --}}
@php
    $galat = $errors->getBag('privasi');
    $isiAwal = $galat->any() ? \App\Support\HtmlSanitizer::clean((string) old('isi', '')) : $privasiHtml;
    $tanggalTampil = \Illuminate\Support\Carbon::parse($privasiTanggal)->locale('id')->translatedFormat('j F Y');
@endphp
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<section id="privasi" aria-labelledby="judul-privasi" class="scroll-mt-8 flex flex-col gap-4">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <div class="min-w-0">
            <h2 id="judul-privasi" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                <i class="bi bi-shield-check text-white/50" aria-hidden="true"></i> Kebijakan privasi
            </h2>
            <p class="text-xs text-white/40 mt-1">Isi halaman /privasi (dipakai juga layar izin Google). Tanggal
                "Diperbarui" terisi otomatis saat disimpan. Sekarang:
                <span class="text-white/60">{{ $privasiKustom ? 'isi dari CMS' : 'teks bawaan' }}, diperbarui
                    {{ $tanggalTampil }}</span>.
            </p>
        </div>
        <a href="{{ route('privasi') }}" target="_blank" rel="noopener"
            class="h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors self-start sm:self-auto shrink-0">
            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Lihat halaman</a>
    </div>

    <form method="POST" id="form-privasi" action="{{ route('ic.admin.konten.privasi.simpan') }}"
        class="rounded-3xl border border-white/10 bg-white/3 p-5 sm:p-6 md:p-8 flex flex-col gap-4 min-w-0">
        @csrf
        @if ($galat->any())
            <div role="alert"
                class="rounded-xl border border-red-300/40 bg-red-950/20 p-3 text-xs text-red-300 flex flex-col gap-1">
                @foreach ($galat->all() as $pesan)
                    <p>{{ $pesan }}</p>
                @endforeach
            </div>
        @endif

        <div
            class="rounded-lg overflow-hidden border border-white/15 focus-within:border-red-900 focus-within:ring-1 focus-within:ring-red-900 transition">
            <div id="privasi-quill" class="bg-white/5 text-white" style="min-height: 360px;"></div>
        </div>
        <input type="hidden" name="isi" id="privasi-isi">

        <p class="text-xs text-white/40">Judul bagian (H2/H3), tebal, miring, daftar, dan tautan. Isian disaring: hanya
            format aman yang tersimpan.
            {{ $privasiKustom ? '' : 'Editor berisi teks bawaan; ubah sesukamu lalu simpan.' }}
        </p>

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit"
                class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                <i class="bi bi-check2-circle" aria-hidden="true"></i> Simpan kebijakan privasi</button>
            @if ($privasiKustom)
                <button type="submit" formaction="{{ route('ic.admin.konten.privasi.bawaan') }}" formnovalidate
                    class="min-h-11 text-xs text-white/50 underline underline-offset-4 hover:text-white">Kembalikan teks
                    bawaan</button>
            @endif
        </div>
    </form>
</section>

<style>
    /* Warna editor mengikuti tema CMS lewat variabel --color-white (tinta) dan --color-black (latar) milik body.cms-ic,
       jadi teks tidak lagi putih-di-atas-lavender. Nilai di belakang koma = cadangan bila variabel tidak ada. */
    #form-privasi .ql-toolbar.ql-snow {
        background-color: color-mix(in srgb, var(--color-white, #fff) 6%, transparent);
        border: none;
        border-bottom: 1px solid color-mix(in srgb, var(--color-white, #fff) 15%, transparent);
        border-radius: 0;
        padding: 8px 12px;
    }

    #form-privasi .ql-toolbar.ql-snow .ql-stroke {
        stroke: color-mix(in srgb, var(--color-white, #fff) 70%, transparent);
    }

    #form-privasi .ql-toolbar.ql-snow .ql-fill {
        fill: color-mix(in srgb, var(--color-white, #fff) 70%, transparent);
    }

    #form-privasi .ql-toolbar.ql-snow .ql-picker,
    #form-privasi .ql-toolbar.ql-snow .ql-picker-label {
        color: color-mix(in srgb, var(--color-white, #fff) 70%, transparent);
    }

    #form-privasi .ql-toolbar.ql-snow button:hover .ql-stroke,
    #form-privasi .ql-toolbar.ql-snow button.ql-active .ql-stroke {
        stroke: var(--color-red-900, #fff);
    }

    #form-privasi .ql-toolbar.ql-snow button:hover .ql-fill,
    #form-privasi .ql-toolbar.ql-snow button.ql-active .ql-fill {
        fill: var(--color-red-900, #fff);
    }

    #form-privasi .ql-container.ql-snow {
        border: none;
        font-size: 15px;
        font-family: inherit;
    }

    #form-privasi .ql-editor {
        color: var(--color-white, #f3f4f6);
        caret-color: var(--color-red-900, #fff);
        min-height: 360px;
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    #form-privasi .ql-editor.ql-blank::before {
        color: color-mix(in srgb, var(--color-white, #fff) 40%, transparent);
        font-style: normal;
    }

    /* Judul, daftar, dan kutipan di dalam editor: selalu tinta penuh (bukan pudar) dan berjarak teratur. */
    #form-privasi .ql-editor h2,
    #form-privasi .ql-editor h3 {
        color: var(--color-white, #fff);
        font-weight: 700;
        margin: 1.4em 0 0.5em;
    }

    #form-privasi .ql-editor h2 {
        font-size: 1.35rem;
    }

    #form-privasi .ql-editor h3 {
        font-size: 1.1rem;
    }

    #form-privasi .ql-editor p,
    #form-privasi .ql-editor li {
        color: var(--color-white, #f3f4f6);
    }

    #form-privasi .ql-editor ol,
    #form-privasi .ql-editor ul {
        padding-left: 0.5em;
    }

    #form-privasi .ql-editor a {
        color: #5a45b0;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    @media (max-width: 639px) {
        #form-privasi .ql-toolbar.ql-snow {
            padding: 6px 8px;
        }

        #form-privasi .ql-editor {
            min-height: 280px;
            padding: 12px 14px;
        }
    }
</style>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    (() => {
        const quill = new Quill('#privasi-quill', {
            theme: 'snow',
            placeholder: 'Tulis kebijakan privasi di sini…',
            modules: {
                toolbar: [
                    [{
                        header: 2
                    }, {
                        header: 3
                    }],
                    ['bold', 'italic', 'underline'],
                    [{
                        list: 'ordered'
                    }, {
                        list: 'bullet'
                    }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        quill.root.innerHTML = {!! json_encode($isiAwal, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};

        document.getElementById('form-privasi').addEventListener('submit', () => {
            document.getElementById('privasi-isi').value = quill.getText().trim() ? quill.root.innerHTML :
                '';
        });
    })();
</script>
