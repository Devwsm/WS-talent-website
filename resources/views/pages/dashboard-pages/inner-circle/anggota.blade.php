@extends('template/icDashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            {{-- Tab ini tidak punya preview bergaya anggota, jadi CSS Inner Circle tidak dimuat --}}
            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'anggota',
                'judul' => 'Anggota',
                'deskripsi' =>
                    'Cari anggota, buka profilnya untuk melihat riwayat poin, lalu tambah atau kurangi poin, nonaktifkan akun, atau unduh daftar anggota sebagai CSV.',
                'pakaiCss' => false,
            ])

            @include('components/errors')
            @include('components/success')

            @php($adaFilter = $q !== '' || $status !== '' || $via !== '' || $akun !== '')

            @if (request()->query('pengaduan') === 'terbuka')
                <p class="rounded-2xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">
                    <i class="bi bi-chat-left-text-fill" aria-hidden="true"></i>
                    Menampilkan anggota yang punya pengaduan belum selesai. Buka profil anggota untuk menindaklanjuti.
                    <a class="underline" href="{{ route('ic.admin.anggota') }}">Tampilkan semua anggota</a>
                </p>
            @endif

            {{-- Cari dan filter: GET, jadi bisa dibagikan dan jalan tanpa JavaScript --}}
            <form method="GET" action="{{ route('ic.admin.anggota') }}" role="search"
                class="flex flex-col lg:flex-row gap-3 rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5">
                <div class="flex flex-col gap-1.5 flex-1 min-w-0">
                    <label for="cari-anggota" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                        Cari anggota
                    </label>
                    <input type="search" id="cari-anggota" name="q" value="{{ $q }}" maxlength="60"
                        placeholder="Nama, email, atau kode (mis. IC-00248)"
                        class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                </div>
                <div class="flex flex-col gap-1.5 lg:w-52">
                    <label for="filter-status" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                        Status email
                    </label>
                    <select id="filter-status" name="status"
                        class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                        <option class="bg-neutral-900" value="" @selected($status === '')>Semua</option>
                        <option class="bg-neutral-900" value="terverifikasi" @selected($status === 'terverifikasi')>Terverifikasi
                        </option>
                        <option class="bg-neutral-900" value="belum" @selected($status === 'belum')>Belum verifikasi</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5 lg:w-52">
                    <label for="filter-via" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                        Cara daftar
                    </label>
                    <select id="filter-via" name="via"
                        class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                        <option class="bg-neutral-900" value="" @selected($via === '')>Semua</option>
                        <option class="bg-neutral-900" value="google" @selected($via === 'google')>Google</option>
                        <option class="bg-neutral-900" value="manual" @selected($via === 'manual')>Manual (sandi)</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5 lg:w-52">
                    <label for="filter-akun" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                        Status akun
                    </label>
                    <select id="filter-akun" name="akun"
                        class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                        <option class="bg-neutral-900" value="" @selected($akun === '')>Semua</option>
                        <option class="bg-neutral-900" value="aktif" @selected($akun === 'aktif')>Aktif</option>
                        <option class="bg-neutral-900" value="nonaktif" @selected($akun === 'nonaktif')>Dinonaktifkan</option>
                    </select>
                </div>
                <div class="flex items-end gap-2 *:flex-1 sm:*:flex-none *:justify-center">
                    <button type="submit"
                        class="h-12 px-6 text-white font-bold uppercase tracking-widest bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                        Cari
                    </button>
                    @if ($adaFilter)
                        <a href="{{ route('ic.admin.anggota') }}"
                            class="h-12 px-5 inline-flex items-center rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <div>
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                        <i class="bi bi-people-fill text-white/50" aria-hidden="true"></i>
                        @if ($adaFilter)
                            Hasil pencarian ({{ $anggota->total() }} dari {{ $totalAnggota }})
                        @else
                            Anggota terdaftar ({{ $totalAnggota }})
                        @endif
                    </h2>
                    {{-- Unduhan mengikuti pencarian dan filter yang sedang dipakai (halaman diabaikan: semua hasil ikut). --}}
                    <a href="{{ route('ic.admin.anggota.ekspor', request()->except('page')) }}"
                        class="h-11 px-5 inline-flex items-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                        <i class="bi bi-filetype-csv" aria-hidden="true"></i>
                        {{ $adaFilter ? 'Unduh hasil (CSV)' : 'Unduh semua (CSV)' }}
                    </a>
                </div>

                @if ($anggota->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => $adaFilter
                            ? 'Tidak ada anggota yang cocok dengan pencarian atau filter ini.'
                            : 'Belum ada anggota yang mendaftar.',
                    ])
                @else
                    <ul class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                        @foreach ($anggota as $m)
                            <li
                                class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-white/10 bg-white/3 p-4 transition-colors hover:border-white/20 min-w-0">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <x-dashboard.inner-circle.foto-anggota :member="$m" ukuran="w-12 h-12 text-2xl" />
                                    <div class="min-w-0">
                                        <p class="font-semibold truncate">{{ $m->nama }}</p>
                                        <p class="text-xs text-white/50 truncate">{{ $m->kode_member }} ·
                                            {{ $m->email }}</p>
                                        <p class="text-[11px] text-white/40 mt-0.5">
                                            Bergabung {{ $m->bergabung_pada->locale('id')->translatedFormat('j M Y') }}
                                            ·
                                            @if ($m->terakhir_checkin)
                                                check-in terakhir
                                                {{ $m->terakhir_checkin->locale('id')->translatedFormat('j M Y') }}
                                            @else
                                                belum pernah check-in
                                            @endif
                                        </p>
                                        {{-- Status email dan cara daftar pertama kali. Teks selalu ada (warna hanya pelengkap). --}}
                                        <p class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                            @if ($m->diblokir())
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full border border-red-400/30 bg-red-400/10 px-2 py-0.5 text-[11px] font-semibold text-red-300">
                                                    <i class="bi bi-slash-circle-fill" aria-hidden="true"></i> Dinonaktifkan
                                                </span>
                                            @endif
                                            @if ($m->email_verified_at)
                                                <span
                                                    title="Diverifikasi {{ $m->email_verified_at->locale('id')->translatedFormat('j M Y') }}"
                                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-200">
                                                    <i class="bi bi-patch-check-fill" aria-hidden="true"></i> Terverifikasi
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full border border-amber-300/30 bg-amber-300/10 px-2 py-0.5 text-[11px] font-semibold text-amber-200">
                                                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i> Belum
                                                    verifikasi
                                                </span>
                                            @endif
                                            @if ($m->daftar_via === \App\Models\member::VIA_GOOGLE)
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/5 px-2 py-0.5 text-[11px] font-semibold text-white/70">
                                                    <i class="bi bi-google" aria-hidden="true"></i> Daftar via Google
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/5 px-2 py-0.5 text-[11px] font-semibold text-white/70">
                                                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Daftar manual
                                                </span>
                                                @if ($m->google_id)
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/5 px-2 py-0.5 text-[11px] font-semibold text-white/70">
                                                        <i class="bi bi-google" aria-hidden="true"></i> Google terhubung
                                                    </span>
                                                @endif
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                                    <p class="text-right">
                                        <span class="text-2xl font-bold">{{ ic_angka($m->poin_total) }}</span>
                                        <span class="block text-[11px] text-white/40">poin</span>
                                    </p>
                                    <a href="{{ route('ic.admin.anggota.detail', $m->id_member) }}"
                                        class="h-11 px-4 inline-flex items-center gap-2 rounded-full border border-white/15 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                        <i class="bi bi-person-lines-fill" aria-hidden="true"></i> Detail
                                    </a>
                                    <button type="button" data-ic-poin data-nama="{{ $m->nama }}"
                                        data-kode="{{ $m->kode_member }}"
                                        data-aksi="{{ route('ic.admin.anggota.poin', $m->id_member) }}"
                                        class="h-11 px-4 inline-flex items-center gap-2 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
                                        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Tambah poin
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @include('components.dashboard.pagination', ['paginator' => $anggota])
                @endif
            </div>
        </div>
    </div>

    {{-- Dialog tambah poin manual: satu dialog dipakai semua baris, nama anggota diisi lewat JS --}}
    <dialog id="dialogPoin" aria-labelledby="dialogPoinJudul"
        class="fixed inset-0 m-0 w-screen h-dvh max-w-none max-h-none bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
        <div class="min-h-dvh w-full flex items-center justify-center p-4">
            <div
                class="w-full max-w-md max-h-[calc(100dvh-2rem)] flex flex-col min-w-0 rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
                <div
                    class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                    <h3 id="dialogPoinJudul" class="font-bold uppercase tracking-wide text-sm text-white">Tambah
                        poin
                        manual</h3>
                    <button type="button" onclick="document.getElementById('dialogPoin').close()" aria-label="Tutup"
                        class="text-white/40 hover:text-white transition">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <form method="POST" action="#" id="formPoin" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    @csrf
                    <div class="flex-1 min-h-0 p-6 flex flex-col gap-4 overflow-y-auto">
                        <div class="flex flex-col gap-1.5">
                            <label for="poin-anggota"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Anggota</label>
                            <input type="text" id="poin-anggota" readonly
                                class="bg-white/5 border border-white/15 text-white/80 p-3 rounded-lg" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="poin-jumlah"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Jumlah poin <span
                                    class="text-red-400">*</span></label>
                            <input type="number" id="poin-jumlah" name="jumlah" min="1"
                                max="{{ config('inner_circle.poin.manual_maks') }}" value="1" required
                                class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                            <p class="text-xs text-white/30">Maksimal
                                {{ ic_angka(config('inner_circle.poin.manual_maks')) }} poin per penambahan.</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="poin-catatan"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Alasan <span
                                    class="text-red-400">*</span></label>
                            <textarea id="poin-catatan" name="catatan" rows="3" maxlength="160" required
                                placeholder="Mis. kehadiran di acara privat yang diverifikasi tim WSM"
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition"></textarea>
                            <p class="text-xs text-white/30">Tercatat di riwayat poin anggota beserta nama admin.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/3 shrink-0">
                        <button type="button" onclick="document.getElementById('dialogPoin').close()"
                            class="h-11 px-5 rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="h-11 px-5 text-white font-bold uppercase tracking-widest text-sm bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Tambah
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </dialog>

    @push('scripts')
        <script>
            // Buka dialog tambah poin dan isi nama anggota dari baris yang diklik.
            const dialogPoin = document.getElementById('dialogPoin');
            document.addEventListener('click', (e) => {
                const tombol = e.target.closest('[data-ic-poin]');
                if (!tombol) return;
                document.getElementById('poin-anggota').value = `${tombol.dataset.nama} (${tombol.dataset.kode})`;
                document.getElementById('formPoin').action = tombol.dataset.aksi;
                dialogPoin.showModal();
                document.getElementById('poin-jumlah').focus();
            });
        </script>
    @endpush
@endsection
