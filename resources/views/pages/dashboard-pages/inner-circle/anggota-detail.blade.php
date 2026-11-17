@extends('template/icDashboardLayout')
@section('content')
    @php
        $statusKlaim = config('inner_circle.status_klaim');
        $inputKelas =
            'bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
        $labelKelas = 'text-sm font-semibold uppercase tracking-widest text-white/60';
        $sumberLabel = ['checkin' => 'Check-in', 'manual' => 'Stempel manual', 'koreksi' => 'Koreksi admin'];
    @endphp
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'anggota',
                'judul' => 'Detail anggota',
                'deskripsi' => 'Riwayat stempel, klaim hadiah, dan pengaturan akun satu anggota.',
                'pakaiCss' => false,
            ])

            @include('components/errors')
            @include('components/success')

            <a href="{{ route('ic.admin.anggota') }}"
                class="inline-flex w-fit items-center gap-2 text-sm text-white/60 hover:text-white transition-colors">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke daftar anggota
            </a>

            {{-- Akun dinonaktifkan: tampil jelas di atas, lengkap dengan tombol mengaktifkan kembali --}}
            @if ($anggota->diblokir())
                <div
                    class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-red-400/30 bg-red-400/10 px-5 py-4">
                    <i class="bi bi-slash-circle-fill text-red-300 text-xl shrink-0" aria-hidden="true"></i>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-semibold text-red-200">Akun ini dinonaktifkan</p>
                        <p class="text-red-100/70 wrap-anywhere">
                            Sejak {{ $anggota->diblokir_pada->locale('id')->translatedFormat('j F Y, H.i') }}
                            @if ($anggota->diblokir_oleh)
                                oleh {{ $anggota->diblokir_oleh }}
                            @endif
                            @if ($anggota->alasan_blokir)
                                · Alasan: {{ $anggota->alasan_blokir }}
                            @endif
                        </p>
                        <p class="text-red-100/50 text-xs mt-0.5">Anggota tidak bisa masuk. Alasan ini hanya terlihat oleh
                            admin.</p>
                    </div>
                    <form method="POST" action="{{ route('ic.admin.anggota.aktifkan', $anggota->id_member) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="h-11 px-5 inline-flex items-center gap-2 rounded-lg bg-white text-black text-xs font-semibold uppercase tracking-widest hover:bg-white/80 transition-colors">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Aktifkan kembali
                        </button>
                    </form>
                </div>
            @endif

            {{-- Profil ringkas --}}
            <div class="rounded-3xl border border-white/10 bg-white/3 p-5 md:p-6 flex flex-col gap-5">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <span aria-hidden="true"
                        class="w-16 h-16 shrink-0 rounded-full bg-white/10 border border-white/10 flex items-center justify-center text-3xl">{{ $anggota->avatar_emoji }}</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl font-bold wrap-anywhere">{{ $anggota->nama }}</h2>
                        <p class="text-sm text-white/50 wrap-anywhere">{{ $anggota->kode_member }} · {{ $anggota->email }}
                        </p>
                        <p class="flex flex-wrap items-center gap-1.5 mt-2">
                            @if ($anggota->email_verified_at)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-200"><i
                                        class="bi bi-patch-check-fill" aria-hidden="true"></i> Terverifikasi</span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-amber-300/30 bg-amber-300/10 px-2 py-0.5 text-[11px] font-semibold text-amber-200"><i
                                        class="bi bi-exclamation-circle-fill" aria-hidden="true"></i> Belum
                                    verifikasi</span>
                            @endif
                            <span
                                class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/5 px-2 py-0.5 text-[11px] font-semibold text-white/70">
                                @if ($anggota->daftar_via === \App\Models\member::VIA_GOOGLE)
                                    <i class="bi bi-google" aria-hidden="true"></i> Daftar via Google
                                @else
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Daftar manual
                                @endif
                            </span>
                            @unless ($anggota->diblokir())
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/5 px-2 py-0.5 text-[11px] font-semibold text-white/70">Akun
                                    aktif</span>
                            @endunless
                        </p>
                    </div>
                </div>

                <dl class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                    <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                        <dt class="text-xs uppercase tracking-widest text-white/40">Total stempel</dt>
                        <dd class="text-2xl font-bold mt-1">{{ $anggota->stempel_total }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                        <dt class="text-xs uppercase tracking-widest text-white/40">Check-in</dt>
                        <dd class="text-2xl font-bold mt-1">{{ $anggota->total_checkin }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                        <dt class="text-xs uppercase tracking-widest text-white/40">Bergabung</dt>
                        <dd class="mt-1 font-semibold">
                            {{ $anggota->bergabung_pada->locale('id')->translatedFormat('j M Y') }}</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                        <dt class="text-xs uppercase tracking-widest text-white/40">Check-in terakhir</dt>
                        <dd class="mt-1 font-semibold">
                            {{ $anggota->terakhir_checkin ? $anggota->terakhir_checkin->locale('id')->translatedFormat('j M Y') : '—' }}
                        </dd>
                    </div>
                </dl>

                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="document.getElementById('dialogTambah').showModal()"
                        class="h-11 px-5 inline-flex items-center gap-2 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
                        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Tambah stempel
                    </button>
                    <button type="button" onclick="document.getElementById('dialogKurangi').showModal()"
                        @disabled($anggota->stempel_total < 1)
                        class="h-11 px-5 inline-flex items-center gap-2 rounded-full border border-white/15 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors disabled:opacity-40 disabled:pointer-events-none">
                        <i class="bi bi-dash-circle-fill" aria-hidden="true"></i> Kurangi stempel
                    </button>
                    @unless ($anggota->diblokir())
                        <button type="button" onclick="document.getElementById('dialogNonaktif').showModal()"
                            class="h-11 px-5 inline-flex items-center gap-2 rounded-full border border-red-400/30 text-xs font-semibold text-red-300 hover:bg-red-400/10 transition-colors">
                            <i class="bi bi-slash-circle" aria-hidden="true"></i> Nonaktifkan akun
                        </button>
                    @endunless
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                {{-- Riwayat stempel: buku besar lengkap, termasuk koreksi --}}
                <div class="lg:col-span-2 min-w-0">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                        <i class="bi bi-stars text-white/50" aria-hidden="true"></i> Riwayat stempel
                        ({{ $riwayat->total() }})
                    </h2>
                    @if ($riwayat->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Anggota ini belum punya riwayat stempel.',
                        ])
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($riwayat as $s)
                                <li
                                    class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/3 p-4 min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-sm wrap-anywhere">{{ $s->judul }}</p>
                                        <p class="text-xs text-white/50">
                                            {{ $s->created_at->locale('id')->translatedFormat('j M Y, H.i') }} ·
                                            {{ $sumberLabel[$s->sumber] ?? $s->sumber }}
                                            @if ($s->event)
                                                · {{ $s->event->venue }}, {{ $s->event->kota }}
                                            @endif
                                            @if ($s->dicatat_oleh)
                                                · oleh {{ $s->dicatat_oleh }}
                                            @endif
                                        </p>
                                        @if ($s->catatan)
                                            <p class="text-xs text-white/40 mt-0.5 wrap-anywhere">Catatan:
                                                {{ $s->catatan }}</p>
                                        @endif
                                    </div>
                                    <span
                                        class="shrink-0 text-lg font-bold tabular-nums {{ $s->koreksi() ? 'text-red-300' : 'text-emerald-200' }}">
                                        {{ $s->jumlah > 0 ? '+' : '−' }}{{ abs($s->jumlah) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        @include('components.dashboard.pagination', ['paginator' => $riwayat])
                    @endif
                </div>

                {{-- Klaim hadiah anggota --}}
                <div class="min-w-0">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-4 flex items-center gap-2">
                        <i class="bi bi-gift-fill text-white/50" aria-hidden="true"></i> Klaim hadiah
                        ({{ $klaimAnggota->count() }})
                    </h2>
                    @if ($klaimAnggota->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada klaim hadiah.',
                        ])
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($klaimAnggota as $k)
                                <li class="rounded-2xl border border-white/10 bg-white/3 p-4 min-w-0">
                                    <p class="font-semibold text-sm wrap-anywhere">{{ $k->reward?->judul ?? 'Hadiah' }}</p>
                                    <p class="text-xs text-white/50">{{ $k->reward?->target_stempel }} stempel ·
                                        {{ $k->created_at->locale('id')->translatedFormat('j M Y') }} ·
                                        {{ $statusKlaim[$k->status]['label'] ?? $k->status }}</p>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('ic.admin.klaim') }}"
                            class="inline-block mt-3 text-xs text-white/50 hover:text-white transition-colors">Kelola di tab
                            Klaim →</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-dashboard.inner-circle.dialog-aksi id="dialogTambah" judul="Tambah stempel manual" :aksi="route('ic.admin.anggota.stempel', $anggota->id_member)" tombol="Tambah">
        <div class="flex flex-col gap-1.5">
            <label for="tambah-jumlah" class="{{ $labelKelas }}">Jumlah stempel <span
                    class="text-red-400">*</span></label>
            <input type="number" id="tambah-jumlah" name="jumlah" min="1" max="10" value="1"
                required class="{{ $inputKelas }}" />
            <p class="text-xs text-white/30">Satu sampai sepuluh stempel per penambahan.</p>
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="tambah-catatan" class="{{ $labelKelas }}">Alasan <span class="text-red-400">*</span></label>
            <textarea id="tambah-catatan" name="catatan" rows="3" maxlength="160" required
                placeholder="Mis. kehadiran di acara privat yang diverifikasi tim WSM" class="{{ $inputKelas }}"></textarea>
        </div>
    </x-dashboard.inner-circle.dialog-aksi>

    <x-dashboard.inner-circle.dialog-aksi id="dialogKurangi" judul="Kurangi stempel (koreksi)" :aksi="route('ic.admin.anggota.kurangi', $anggota->id_member)"
        tombol="Kurangi">
        <p class="text-sm text-white/60">Dipakai untuk memperbaiki stempel yang salah dicatat. Pengurangan ditulis sebagai
            baris
            koreksi di riwayat; baris lama tidak dihapus. Check-in yang sudah tercatat tetap ada, jadi anggota tidak bisa
            check-in ulang di acara yang sama.</p>
        <div class="flex flex-col gap-1.5">
            <label for="kurangi-jumlah" class="{{ $labelKelas }}">Jumlah dikurangi <span
                    class="text-red-400">*</span></label>
            <input type="number" id="kurangi-jumlah" name="jumlah" min="1"
                max="{{ max(1, min(100, $anggota->stempel_total)) }}" value="1" required
                class="{{ $inputKelas }}" />
            <p class="text-xs text-white/30">Anggota ini punya {{ $anggota->stempel_total }} stempel.</p>
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="kurangi-catatan" class="{{ $labelKelas }}">Alasan koreksi <span
                    class="text-red-400">*</span></label>
            <textarea id="kurangi-catatan" name="catatan" rows="3" maxlength="160" required
                placeholder="Mis. salah input, seharusnya 1 stempel bukan 3" class="{{ $inputKelas }}"></textarea>
        </div>
    </x-dashboard.inner-circle.dialog-aksi>

    <x-dashboard.inner-circle.dialog-aksi id="dialogNonaktif" judul="Nonaktifkan akun" method="PATCH" bahaya
        :aksi="route('ic.admin.anggota.nonaktifkan', $anggota->id_member)" tombol="Nonaktifkan">
        <p class="text-sm text-white/60">{{ $anggota->nama }} tidak akan bisa masuk (kata sandi maupun Google) dan sesi
            yang
            sedang berjalan berakhir. Stempel, kenangan, dan klaimnya tidak dihapus; akun bisa diaktifkan kembali kapan
            saja.</p>
        <div class="flex flex-col gap-1.5">
            <label for="nonaktif-alasan" class="{{ $labelKelas }}">Alasan <span class="text-red-400">*</span></label>
            <textarea id="nonaktif-alasan" name="alasan" rows="3" maxlength="160" required
                placeholder="Mis. akun ganda atau penyalahgunaan check-in" class="{{ $inputKelas }}"></textarea>
            <p class="text-xs text-white/30">Hanya terlihat oleh admin, tidak ditampilkan ke anggota.</p>
        </div>
    </x-dashboard.inner-circle.dialog-aksi>
@endsection
