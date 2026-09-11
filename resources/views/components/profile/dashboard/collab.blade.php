{{--
    KOLABORASI (multi). Preview niru PERSIS markup grid kolaborasi di
    resources/views/components/profile/profile-full.blade.php (baris ~84-105).
    Expects: $collab (Illuminate\Support\Collection dari App\Models\collab)
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-people text-white/50"></i> Tambah Kolaborasi
        </h3>

        <form action="{{ route('collab.tambah') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="collab_nama" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Nama <span class="text-red-400">*</span>
                </label>
                <input type="text" id="collab_nama" name="nama" placeholder="Contoh: Dipha Barus"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="collab_role" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Role / Deskripsi <span class="text-red-400">*</span>
                </label>
                <input type="text" id="collab_role" name="role" placeholder="Contoh: DJ / Producer"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Tambah Kolaborasi
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
            <div id="collabPreviewList" class="flex flex-wrap gap-2">
                @forelse ($collab as $item)
                    <span class="text-xs px-3 py-1 rounded-full border border-white/20 text-white/60">
                        <strong class="text-white">{{ $item->nama }}</strong> · {{ $item->role }}
                    </span>
                @empty
                @endforelse
                <span id="collabPreviewEmpty" class="text-xs text-white/30 {{ $collab->isNotEmpty() ? 'hidden' : '' }}">
                    Belum ada kolaborasi.
                </span>
            </div>
        </div>
    </div>
</div>

{{-- List data kolaborasi tersimpan --}}
<div class="mt-6">
    <h3 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
        <i class="bi bi-people-fill text-white/50"></i> Kolaborasi Tersimpan ({{ $collab->count() }})
    </h3>

    @if ($collab->isEmpty())
        @include('components/dashboard/card/empty-state', [
            'message' => 'Belum ada kolaborasi yang ditambahkan.',
        ])
    @else
        <div class="flex flex-wrap gap-3">
            @foreach ($collab as $item)
                <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/3 pl-4 pr-2 py-1.5">
                    <span class="text-sm"><strong class="text-white">{{ $item->nama }}</strong>
                        <span class="text-white/50">·</span> {{ $item->role }}</span>
                    <div class="flex gap-1 items-center">
                        @include('components.dashboard.profile.modal-edit-collab')
                        @include('components.dashboard.profile.btn-hapus-collab')
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    (() => {
        const nama = document.getElementById('collab_nama');
        const role = document.getElementById('collab_role');
        const list = document.getElementById('collabPreviewList');
        const emptyEl = document.getElementById('collabPreviewEmpty');
        let draft = null;

        const render = () => {
            const namaVal = nama.value.trim();
            const roleVal = role.value.trim();

            if (!namaVal && !roleVal) {
                draft?.remove();
                draft = null;
                if (list.children.length <= 1) emptyEl?.classList.remove('hidden');
                return;
            }

            if (!draft) {
                draft = document.createElement('span');
                draft.className =
                    'text-xs px-3 py-1 rounded-full border border-dashed border-white/30 text-white/40';
                list.appendChild(draft);
            }

            draft.innerHTML = `<strong class="text-white/60">${namaVal || '—'}</strong> · ${roleVal || '—'}`;
            emptyEl?.classList.add('hidden');
        };

        nama?.addEventListener('input', render);
        role?.addEventListener('input', render);
    })();
</script>
