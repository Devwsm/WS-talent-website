{{--
    MEDIA SOSIAL (multi). Preview niru PERSIS markup social links di
    resources/views/components/profile/profile-full.blade.php (baris ~154-175).
    Expects: $mediaSosial (Illuminate\Support\Collection dari App\Models\media_sosial)
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-share text-white/50"></i> Tambah Media Sosial
        </h3>

        <form action="{{ route('media_sosial.tambah') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="media_sosial_platform" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Platform <span class="text-red-400">*</span>
                </label>
                <input type="text" id="media_sosial_platform" name="platform" placeholder="Contoh: Instagram"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="media_sosial_url" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    URL <span class="text-red-400">*</span>
                </label>
                <input type="text" id="media_sosial_url" name="url" placeholder="https://..."
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Tambah Social Link
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
            <div id="mediaSosialPreviewList" class="flex flex-wrap gap-2">
                @forelse ($mediaSosial as $item)
                    <span
                        class="text-xs px-4 py-2 rounded-lg border border-white/10 bg-white/5 text-white/60">{{ $item->platform }}</span>
                @empty
                @endforelse
                <span id="mediaSosialPreviewEmpty"
                    class="text-xs text-white/30 {{ $mediaSosial->isNotEmpty() ? 'hidden' : '' }}">
                    Belum ada media sosial.
                </span>
            </div>
        </div>
    </div>
</div>

{{-- List data media sosial tersimpan --}}
<div class="mt-6">
    <h3 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
        <i class="bi bi-share-fill text-white/50"></i> Media Sosial Tersimpan ({{ $mediaSosial->count() }})
    </h3>

    @if ($mediaSosial->isEmpty())
        @include('components/dashboard/card/empty-state', [
            'message' => 'Belum ada media sosial yang ditambahkan.',
        ])
    @else
        <div class="flex flex-wrap gap-3">
            @foreach ($mediaSosial as $item)
                <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/3 pl-4 pr-2 py-1.5">
                    <span class="text-sm">{{ $item->platform }}</span>
                    <span class="text-xs text-white/30 break-all max-w-40 truncate">{{ $item->url }}</span>
                    <div class="flex gap-1 items-center">
                        @include('components.dashboard.profile.modal-edit-media-sosial')
                        @include('components.dashboard.profile.btn-hapus-media-sosial')
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    (() => {
        const platform = document.getElementById('media_sosial_platform');
        const list = document.getElementById('mediaSosialPreviewList');
        const emptyEl = document.getElementById('mediaSosialPreviewEmpty');
        let draft = null;

        platform?.addEventListener('input', () => {
            const value = platform.value.trim();

            if (!draft) {
                draft = document.createElement('span');
                draft.className =
                    'text-xs px-4 py-2 rounded-lg border border-dashed border-white/30 text-white/40';
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
