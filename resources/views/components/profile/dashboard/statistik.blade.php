{{--
    STATISTIK (multi). Preview niru PERSIS markup grid statistik di
    resources/views/components/profile/profile-full.blade.php (baris ~42-53).
    Expects: $statistik (Illuminate\Support\Collection dari App\Models\statistik)
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-bar-chart text-white/50"></i> Tambah Statistik
        </h3>

        <form action="{{ route('statistik.tambah') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="statistik_total" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Total <span class="text-red-400">*</span>
                </label>
                <input type="text" id="statistik_total" name="total" placeholder="Contoh: 593K+"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="statistik_platform" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Platform <span class="text-red-400">*</span>
                </label>
                <input type="text" id="statistik_platform" name="platform" placeholder="Contoh: YouTube Subscribers"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Tambah Statistik
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
            <div id="statistikPreviewList" class="grid grid-cols-2 gap-3">
                @forelse ($statistik as $item)
                    <div class="flex flex-col gap-1 p-3 rounded-lg bg-white/5 border border-white/10">
                        <span class="text-lg font-medium text-white">{{ $item->total }}</span>
                        <span class="text-xs text-white/50">{{ $item->platform }}</span>
                    </div>
                @empty
                @endforelse
            </div>
            <p id="statistikPreviewEmpty" class="text-xs text-white/30 {{ $statistik->isNotEmpty() ? 'hidden' : '' }}">
                Belum ada statistik.
            </p>
        </div>
    </div>
</div>

{{-- List data statistik tersimpan --}}
<div class="mt-6">
    <h3 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
        <i class="bi bi-bar-chart-fill text-white/50"></i> Statistik Tersimpan ({{ $statistik->count() }})
    </h3>

    @if ($statistik->isEmpty())
        @include('components/dashboard/card/empty-state', [
            'message' => 'Belum ada statistik yang ditambahkan.',
        ])
    @else
        <div class="flex flex-wrap gap-3">
            @foreach ($statistik as $item)
                <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/3 pl-4 pr-2 py-1.5">
                    <span class="text-sm"><strong class="text-white">{{ $item->total }}</strong>
                        <span class="text-white/50">·</span> {{ $item->platform }}</span>
                    <div class="flex gap-1 items-center">
                        @include('components.dashboard.profile.modal-edit-statistik')
                        @include('components.dashboard.profile.btn-hapus-statistik')
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    (() => {
        const total = document.getElementById('statistik_total');
        const platform = document.getElementById('statistik_platform');
        const list = document.getElementById('statistikPreviewList');
        const emptyEl = document.getElementById('statistikPreviewEmpty');
        let draft = null;

        const render = () => {
            const totalVal = total.value.trim();
            const platformVal = platform.value.trim();

            if (!totalVal && !platformVal) {
                draft?.remove();
                draft = null;
                if (list.children.length === 0) emptyEl?.classList.remove('hidden');
                return;
            }

            if (!draft) {
                draft = document.createElement('div');
                draft.className =
                    'flex flex-col gap-1 p-3 rounded-lg bg-white/5 border border-dashed border-white/30 opacity-60';
                list.appendChild(draft);
            }

            draft.innerHTML =
                `<span class="text-lg font-medium text-white">${totalVal || '—'}</span><span class="text-xs text-white/50">${platformVal || '—'}</span>`;
            emptyEl?.classList.add('hidden');
        };

        total?.addEventListener('input', render);
        platform?.addEventListener('input', render);
    })();
</script>
