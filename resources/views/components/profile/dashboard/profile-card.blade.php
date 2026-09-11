{{--
    HERO (singleton) — foto, judul singkat, nama, tagline.
    Preview niru PERSIS markup section Hero di
    resources/views/components/profile/profile-full.blade.php (baris 11-22).
    Expects: $hero (App\Models\profile_hero|null)
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-person-badge text-white/50"></i> Hero
        </h3>

        <form action="{{ route('hero.simpan') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label for="hero_foto" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Foto
                </label>
                <input type="file" id="hero_foto" name="foto" accept="image/*"
                    class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                    file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                    file:text-sm file:font-semibold file:bg-red-950 file:text-white
                    hover:file:bg-red-900 transition" />
                <p class="text-xs text-white/30">Kosongkan kalau tidak ingin mengganti foto yang sekarang.</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="hero_judul_singkat" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Judul Singkat <span class="text-red-400">*</span>
                </label>
                <input type="text" id="hero_judul_singkat" name="judul_singkat" placeholder="Contoh: DJ & Producer"
                    value="{{ old('judul_singkat', $hero->judul_singkat ?? '') }}"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="hero_nama" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Nama <span class="text-red-400">*</span>
                </label>
                <input type="text" id="hero_nama" name="nama" placeholder="Nama artis..."
                    value="{{ old('nama', $hero->nama ?? '') }}"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="hero_tagline" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Tagline <span class="text-red-400">*</span>
                </label>
                <textarea id="hero_tagline" name="tagline" rows="2" placeholder="Kalimat singkat di bawah nama..."
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition resize-none">{{ old('tagline', $hero->tagline ?? '') }}</textarea>
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Simpan Hero
            </button>
        </form>
    </div>

    {{-- PREVIEW --}}
    <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-black overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
            <i class="bi bi-eye text-white/40"></i>
            <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di halaman Profile</span>
        </div>

        {{-- Replika PERSIS markup section Hero --}}
        <div class="p-5">
            <div class="head relative flex w-full justify-center">
                <div id="heroPreviewImgWrapper"
                    class="w-full aspect-video rounded-lg overflow-hidden bg-white/5 border border-white/10">
                    <div id="heroPreviewImgEmpty"
                        class="w-full h-full flex flex-col items-center justify-center gap-2 text-white/30 {{ $hero->foto ?? null ? 'hidden' : '' }}">
                        <i class="bi bi-image text-3xl"></i>
                        <p class="text-xs">Preview foto muncul di sini</p>
                    </div>
                    <img id="heroPreviewImg"
                        src="{{ $hero->foto ?? null ? Storage::url('profile-hero/' . $hero->foto) : '' }}"
                        alt="Preview foto hero"
                        class="w-full h-full object-cover object-center {{ $hero->foto ?? null ? '' : 'hidden' }}">
                </div>

                <div class="absolute inset-0 rounded-lg bg-linear-to-t from-black/80 via-black/30 to-transparent"></div>
                <div class="absolute bottom-4 left-4 text-left max-w-lg">
                    <h1 id="heroPreviewJudul" class="text-xs text-white/40 uppercase tracking-widest mb-1">
                        {{ $hero->judul_singkat ?? '—' }}
                    </h1>
                    <h1 id="heroPreviewNama" class="text-3xl font-medium text-white mb-1">
                        {{ $hero->nama ?? '—' }}
                    </h1>
                    <h1 id="heroPreviewTagline" class="text-sm text-white/60 leading-relaxed">
                        {{ $hero->tagline ?? '—' }}
                    </h1>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const foto = document.getElementById('hero_foto');
        const judul = document.getElementById('hero_judul_singkat');
        const nama = document.getElementById('hero_nama');
        const tagline = document.getElementById('hero_tagline');

        const previewImg = document.getElementById('heroPreviewImg');
        const previewImgEmpty = document.getElementById('heroPreviewImgEmpty');
        const previewJudul = document.getElementById('heroPreviewJudul');
        const previewNama = document.getElementById('heroPreviewNama');
        const previewTagline = document.getElementById('heroPreviewTagline');

        judul?.addEventListener('input', () => {
            previewJudul.textContent = judul.value.trim() || '—';
        });

        nama?.addEventListener('input', () => {
            previewNama.textContent = nama.value.trim() || '—';
        });

        tagline?.addEventListener('input', () => {
            previewTagline.textContent = tagline.value.trim() || '—';
        });

        foto?.addEventListener('change', (event) => {
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
    })();
</script>
