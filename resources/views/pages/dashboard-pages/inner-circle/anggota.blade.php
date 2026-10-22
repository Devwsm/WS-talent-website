@extends('template/dashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            {{-- Tab ini tidak punya preview bergaya anggota, jadi CSS Inner Circle tidak dimuat --}}
            @include('components.dashboard.inner-circle.tabs', [
                'aktif' => 'anggota',
                'judul' => 'Anggota',
                'deskripsi' => 'Cari anggota dan tambahkan stempel manual bila ada kehadiran yang perlu dicatat.',
                'pakaiCss' => false,
            ])

            @include('components/errors')
            @include('components/success')

            {{-- Cari: GET, jadi bisa dibagikan dan jalan tanpa JavaScript --}}
            <form method="GET" action="{{ route('ic.admin.anggota') }}" role="search"
                class="flex flex-col sm:flex-row gap-3 rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5">
                <div class="flex flex-col gap-1.5 flex-1 min-w-0">
                    <label for="cari-anggota" class="text-sm font-semibold uppercase tracking-widest text-white/60">
                        Cari anggota
                    </label>
                    <input type="search" id="cari-anggota" name="q" value="{{ $q }}" maxlength="60"
                        placeholder="Nama, email, atau kode (mis. IC-00248)"
                        class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="h-12 px-6 text-white font-bold uppercase tracking-widest bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                        Cari
                    </button>
                    @if ($q !== '')
                        <a href="{{ route('ic.admin.anggota') }}"
                            class="h-12 px-5 inline-flex items-center rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <div>
                <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                    <i class="bi bi-people-fill text-white/50" aria-hidden="true"></i>
                    @if ($q !== '')
                        Hasil pencarian ({{ $anggota->total() }} dari {{ $totalAnggota }})
                    @else
                        Anggota terdaftar ({{ $totalAnggota }})
                    @endif
                </h2>

                @if ($anggota->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' =>
                            $q !== ''
                                ? 'Tidak ada anggota yang cocok dengan pencarian ini.'
                                : 'Belum ada anggota yang mendaftar.',
                    ])
                @else
                    <ul class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                        @foreach ($anggota as $m)
                            <li
                                class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-white/10 bg-white/3 p-4 transition-colors hover:border-white/20 min-w-0">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <span aria-hidden="true"
                                        class="w-12 h-12 shrink-0 rounded-full bg-white/10 border border-white/10 flex items-center justify-center text-2xl">{{ $m->avatar_emoji }}</span>
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
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                                    <p class="text-right">
                                        <span class="text-2xl font-bold">{{ $m->stempel_total }}</span>
                                        <span class="block text-[11px] text-white/40">stempel</span>
                                    </p>
                                    <button type="button" data-ic-stempel data-nama="{{ $m->nama }}"
                                        data-kode="{{ $m->kode_member }}"
                                        data-aksi="{{ route('ic.admin.anggota.stempel', $m->id_member) }}"
                                        class="h-11 px-4 inline-flex items-center gap-2 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
                                        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Tambah stempel
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

    {{-- Dialog tambah stempel manual: satu dialog dipakai semua baris, nama anggota diisi lewat JS --}}
    <dialog id="dialogStempel" aria-labelledby="dialogStempelJudul"
        class="fixed inset-0 m-0 w-screen h-screen max-w-none max-h-none bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
        <div class="min-h-screen w-full flex items-center justify-center p-4">
            <div
                class="w-full max-w-md max-h-[calc(100vh-2rem)] flex flex-col min-w-0 rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
                <div class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                    <h3 id="dialogStempelJudul" class="font-bold uppercase tracking-wide text-sm text-white">Tambah stempel
                        manual</h3>
                    <button type="button" onclick="document.getElementById('dialogStempel').close()" aria-label="Tutup"
                        class="text-white/40 hover:text-white transition">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <form method="POST" action="#" id="formStempel" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    @csrf
                    <div class="flex-1 min-h-0 p-6 flex flex-col gap-4 overflow-y-auto">
                        <div class="flex flex-col gap-1.5">
                            <label for="stempel-anggota"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Anggota</label>
                            <input type="text" id="stempel-anggota" readonly
                                class="bg-white/5 border border-white/15 text-white/80 p-3 rounded-lg" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="stempel-jumlah"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Jumlah stempel <span
                                    class="text-red-400">*</span></label>
                            <input type="number" id="stempel-jumlah" name="jumlah" min="1" max="10"
                                value="1" required
                                class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                            <p class="text-xs text-white/30">Satu sampai sepuluh stempel per penambahan.</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="stempel-catatan"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Alasan <span
                                    class="text-red-400">*</span></label>
                            <textarea id="stempel-catatan" name="catatan" rows="3" maxlength="160" required
                                placeholder="Mis. kehadiran di acara privat yang diverifikasi tim WSM"
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition"></textarea>
                            <p class="text-xs text-white/30">Tercatat di riwayat stempel anggota beserta nama admin.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/3 shrink-0">
                        <button type="button" onclick="document.getElementById('dialogStempel').close()"
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
            // Buka dialog tambah stempel dan isi nama anggota dari baris yang diklik.
            const dialogStempel = document.getElementById('dialogStempel');
            document.addEventListener('click', (e) => {
                const tombol = e.target.closest('[data-ic-stempel]');
                if (!tombol) return;
                document.getElementById('stempel-anggota').value = `${tombol.dataset.nama} (${tombol.dataset.kode})`;
                document.getElementById('formStempel').action = tombol.dataset.aksi;
                dialogStempel.showModal();
                document.getElementById('stempel-jumlah').focus();
            });
        </script>
    @endpush
@endsection
