{{--
    BOOKING & KONTAK (multi). Preview niru PERSIS markup blok Booking & Kontak di
    resources/views/components/profile/profile-full.blade.php (baris ~120-152).
    Expects: $booking (Illuminate\Support\Collection dari App\Models\booking)
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    {{-- FORM --}}
    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
        <h3 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
            <i class="bi bi-envelope text-white/50"></i> Tambah Booking & Kontak
        </h3>

        <form action="{{ route('booking.tambah') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="booking_label" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Label / Kategori <span class="text-red-400">*</span>
                </label>
                <input type="text" id="booking_label" name="label" placeholder="Contoh: Show & Touring"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="booking_email" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                    Email <span class="text-red-400">*</span>
                </label>
                <input type="email" id="booking_email" name="email" placeholder="nama@email.com"
                    class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
            </div>

            <button type="submit"
                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                Tambah Booking
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
            <h2 class="text-xs text-white/30 uppercase tracking-widest mb-3">Booking & Kontak</h2>
            <div id="bookingPreviewList" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse ($booking as $item)
                    <div class="flex flex-col gap-1">
                        <p class="text-xs uppercase tracking-widest text-white/40">{{ $item->label }}</p>
                        <p class="text-sm text-white/70">{{ $item->email }}</p>
                    </div>
                @empty
                @endforelse
            </div>
            <p id="bookingPreviewEmpty" class="text-xs text-white/30 {{ $booking->isNotEmpty() ? 'hidden' : '' }}">
                Belum ada data booking & kontak.
            </p>
        </div>
    </div>
</div>

{{-- List data booking tersimpan --}}
<div class="mt-6">
    <h3 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
        <i class="bi bi-envelope-fill text-white/50"></i> Booking & Kontak Tersimpan ({{ $booking->count() }})
    </h3>

    @if ($booking->isEmpty())
        @include('components/dashboard/card/empty-state', [
            'message' => 'Belum ada booking & kontak yang ditambahkan.',
        ])
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($booking as $item)
                <div class="rounded-2xl border border-white/10 bg-white/3 p-4 flex flex-col gap-2">
                    <p class="font-semibold text-sm">{{ $item->label }}</p>
                    <p class="text-xs text-white/40 break-all">{{ $item->email }}</p>
                    <div class="flex gap-2 pt-2">
                        @include('components.dashboard.profile.modal-edit-booking')
                        @include('components.dashboard.profile.btn-hapus-booking')
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    (() => {
        const label = document.getElementById('booking_label');
        const email = document.getElementById('booking_email');
        const list = document.getElementById('bookingPreviewList');
        const emptyEl = document.getElementById('bookingPreviewEmpty');
        let draft = null;

        const render = () => {
            const labelVal = label.value.trim();
            const emailVal = email.value.trim();

            if (!labelVal && !emailVal) {
                draft?.remove();
                draft = null;
                if (list.children.length === 0) emptyEl?.classList.remove('hidden');
                return;
            }

            if (!draft) {
                draft = document.createElement('div');
                draft.className = 'flex flex-col gap-1 opacity-50';
                list.appendChild(draft);
            }

            draft.innerHTML =
                `<p class="text-xs uppercase tracking-widest text-white/40">${labelVal || '—'}</p><p class="text-sm text-white/70">${emailVal || '—'}</p>`;
            emptyEl?.classList.add('hidden');
        };

        label?.addEventListener('input', render);
        email?.addEventListener('input', render);
    })();
</script>
