{{--
    MEDIA COVERAGE (multi). Preview niru PERSIS markup chip media coverage di
    resources/views/components/profile/profile-full.blade.php (baris ~107-118).
    Expects: $mediaCoverage (Illuminate\Support\Collection dari App\Models\media_coverage)
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-newspaper text-white/50"></i> Tambah Media Coverage
        </h3>

        <form action="{{ route('media_coverage.tambah') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="media_coverage_nama" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Nama Media <span class="text-red-400">*</span>
                </label>
                <input type="text" id="media_coverage_nama" name="nama_media" placeholder="Contoh: Suara.com"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Tambah Media
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
            <div id="mediaCoveragePreviewList" class="flex flex-wrap gap-2">
                @forelse ($mediaCoverage as $item)
                    <span
                        class="text-xs px-4 py-2 rounded-lg border border-white/10 bg-white/5 text-white/50">{{ $item->nama_media }}</span>
                @empty
                @endforelse
                <span id="mediaCoveragePreviewEmpty"
                    class="text-xs text-white/30 {{ $mediaCoverage->isNotEmpty() ? 'hidden' : '' }}">
                    Belum ada media coverage.
                </span>
            </div>
        </div>
    </div>
</div>

{{-- List data media coverage tersimpan --}}
<div class="mt-6">
    <h3 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
        <i class="bi bi-newspaper text-white/50"></i> Media Coverage Tersimpan ({{ $mediaCoverage->count() }})
    </h3>

    @if ($mediaCoverage->isEmpty())
        @include('components/dashboard/card/empty-state', [
            'message' => 'Belum ada media coverage yang ditambahkan.',
        ])
    @else
        <div class="flex flex-wrap gap-3">
            @foreach ($mediaCoverage as $item)
                <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/3 pl-4 pr-2 py-1.5">
                    <span class="text-sm">{{ $item->nama_media }}</span>
                    <div class="flex gap-1 items-center">
                        @include('components.dashboard.profile.modal-edit-media-coverage')
                        @include('components.dashboard.profile.btn-hapus-media-coverage')
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    (() => {
        const input = document.getElementById('media_coverage_nama');
        const list = document.getElementById('mediaCoveragePreviewList');
        const emptyEl = document.getElementById('mediaCoveragePreviewEmpty');
        let draft = null;

        input?.addEventListener('input', () => {
            const value = input.value.trim();

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
