{{--
    GENRE tags (multi). Preview niru PERSIS markup genre tags di
    resources/views/components/profile/profile-full.blade.php (baris 25-32).
    Expects: $genre (Illuminate\Support\Collection dari App\Models\genre)
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-tags text-white/50"></i> Tambah Genre
        </h3>

        <form action="{{ route('genre.tambah') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="genre_nama" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Nama Genre <span class="text-red-400">*</span>
                </label>
                <input type="text" id="genre_nama" name="nama_genre" placeholder="Contoh: Indonesian Bounce"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Tambah Genre
            </button>
        </form>
    </div>

    {{-- PREVIEW --}}
    <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-black overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
            <i class="bi bi-eye text-white/40"></i>
            <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di halaman Profile</span>
        </div>

        <div class="p-5">
            <div id="genrePreviewList" class="flex flex-wrap gap-2">
                @forelse ($genre as $item)
                    <span
                        class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">{{ $item->nama_genre }}</span>
                @empty
                @endforelse
                <span id="genrePreviewEmpty" class="text-xs text-white/30 {{ $genre->isNotEmpty() ? 'hidden' : '' }}">
                    Belum ada genre.
                </span>
            </div>
        </div>
    </div>
</div>

{{-- List data genre tersimpan --}}
<div class="mt-6">
    <h3 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
        <i class="bi bi-tags-fill text-white/50"></i> Genre Tersimpan ({{ $genre->count() }})
    </h3>

    @if ($genre->isEmpty())
        @include('components/dashboard/card/empty-state', [
            'message' => 'Belum ada genre yang ditambahkan.',
        ])
    @else
        <div class="flex flex-wrap gap-3">
            @foreach ($genre as $item)
                <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/3 pl-4 pr-2 py-1.5">
                    <span class="text-sm">{{ $item->nama_genre }}</span>
                    <div class="flex gap-1 items-center">
                        @include('components.dashboard.profile.modal-edit-genre')
                        @include('components.dashboard.profile.btn-hapus-genre')
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    (() => {
        const input = document.getElementById('genre_nama');
        const list = document.getElementById('genrePreviewList');
        const emptyEl = document.getElementById('genrePreviewEmpty');
        let draft = null;

        input?.addEventListener('input', () => {
            const value = input.value.trim();

            if (!draft) {
                draft = document.createElement('span');
                draft.className =
                    'text-xs px-3 py-1 rounded-full border border-dashed border-white/30 text-white/40';
                list.appendChild(draft);
            }

            if (value) {
                draft.textContent = value;
                emptyEl?.classList.add('hidden');
            } else {
                draft.remove();
                draft = null;
                if (list.children.length <= 1) emptyEl?.classList.remove('hidden');
            }
        });
    })();
</script>
