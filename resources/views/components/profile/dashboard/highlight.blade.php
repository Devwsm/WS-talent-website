{{--
    HIGHLIGHT (multi). Preview niru PERSIS markup grid highlight di
    resources/views/components/profile/profile-full.blade.php (baris ~55-69).
    Expects: $highlight (Illuminate\Support\Collection dari App\Models\highlight)
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-star text-white/50"></i> Tambah Highlight
        </h3>

        <form action="{{ route('highlight.tambah') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="highlight_place" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Place (nama event) <span class="text-red-400">*</span>
                </label>
                <input type="text" id="highlight_place" name="place" placeholder="Contoh: Tomorrowland 2026"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="highlight_description"
                    class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Description (lokasi) <span class="text-red-400">*</span>
                </label>
                <input type="text" id="highlight_description" name="description" placeholder="Contoh: Boom, Belgium"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="highlight_year" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Year <span class="text-red-400">*</span>
                </label>
                <input type="text" id="highlight_year" name="year" placeholder="Contoh: 2026"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Tambah Highlight
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
            <div id="highlightPreviewList" class="flex flex-col gap-3">
                @forelse ($highlight as $item)
                    <div class="flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/10">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-sm font-medium text-white">{{ $item->place }}</span>
                            <span class="text-xs text-white/40">{{ $item->description }}</span>
                        </div>
                        <span class="text-xs text-white/20">{{ $item->year }}</span>
                    </div>
                @empty
                @endforelse
            </div>
            <p id="highlightPreviewEmpty" class="text-xs text-white/30 {{ $highlight->isNotEmpty() ? 'hidden' : '' }}">
                Belum ada highlight.
            </p>
        </div>
    </div>
</div>

{{-- List data highlight tersimpan --}}
<div class="mt-6">
    <h3 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
        <i class="bi bi-star-fill text-white/50"></i> Highlight Tersimpan ({{ $highlight->count() }})
    </h3>

    @if ($highlight->isEmpty())
        @include('components/dashboard/card/empty-state', [
            'message' => 'Belum ada highlight yang ditambahkan.',
        ])
    @else
        <div class="flex flex-wrap gap-3">
            @foreach ($highlight as $item)
                <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/3 pl-4 pr-2 py-1.5">
                    <span class="text-sm"><strong class="text-white">{{ $item->place }}</strong>
                        <span class="text-white/50">·</span> {{ $item->description }}
                        <span class="text-white/50">·</span> {{ $item->year }}</span>
                    <div class="flex gap-1 items-center">
                        @include('components.dashboard.profile.modal-edit-highlight')
                        @include('components.dashboard.profile.btn-hapus-highlight')
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    (() => {
        const place = document.getElementById('highlight_place');
        const description = document.getElementById('highlight_description');
        const year = document.getElementById('highlight_year');
        const list = document.getElementById('highlightPreviewList');
        const emptyEl = document.getElementById('highlightPreviewEmpty');
        let draft = null;

        const render = () => {
            const placeVal = place.value.trim();
            const descVal = description.value.trim();
            const yearVal = year.value.trim();

            if (!placeVal && !descVal && !yearVal) {
                draft?.remove();
                draft = null;
                if (list.children.length === 0) emptyEl?.classList.remove('hidden');
                return;
            }

            if (!draft) {
                draft = document.createElement('div');
                draft.className =
                    'flex items-center justify-between p-4 rounded-lg bg-white/5 border border-dashed border-white/30 opacity-60';
                list.appendChild(draft);
            }

            draft.innerHTML =
                `<div class="flex flex-col gap-0.5"><span class="text-sm font-medium text-white">${placeVal || '—'}</span><span class="text-xs text-white/40">${descVal || '—'}</span></div><span class="text-xs text-white/20">${yearVal || '—'}</span>`;
            emptyEl?.classList.add('hidden');
        };

        place?.addEventListener('input', render);
        description?.addEventListener('input', render);
        year?.addEventListener('input', render);
    })();
</script>
