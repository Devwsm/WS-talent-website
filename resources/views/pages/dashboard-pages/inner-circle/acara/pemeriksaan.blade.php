@extends('template/icDashboardLayout')
@section('content')
    @php
        $kelasTombol =
            'h-10 px-4 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
        $kelasTombolUtama =
            'h-10 px-4 inline-flex items-center justify-center gap-2 rounded-lg bg-white text-black text-xs font-semibold uppercase tracking-widest hover:bg-white/80 transition-colors';
        $kelasKartu = 'rounded-3xl border border-white/10 bg-white/3 p-5 md:p-6 flex flex-col gap-4';
        $kelasIsian =
            'w-full rounded-lg border border-white/15 bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 focus:outline-none focus:border-white/50';
        $chipAktif = 'bg-white text-black border-white';
        $chipBiasa = 'border-white/15 text-white/70 hover:bg-white/10 hover:text-white';
        $parameterAcara = $acara ? ['acara' => $acara->kode] : [];
        $labelStatusPengajuan = [
            'menunggu' => ['Menunggu', 'border-amber-300/30 bg-amber-300/10 text-amber-100'],
            'disetujui' => ['Disetujui', 'border-emerald-300/30 bg-emerald-300/10 text-emerald-100'],
            'ditolak' => ['Ditolak', 'border-red-300/30 bg-red-300/10 text-red-100'],
        ];
        $labelStatusPenanda = [
            'baru' => ['Perlu ditinjau', 'border-amber-300/30 bg-amber-300/10 text-amber-100'],
            'aman' => ['Aman', 'border-emerald-300/30 bg-emerald-300/10 text-emerald-100'],
            'ditindak' => ['Sudah ditindak', 'border-sky-300/30 bg-sky-300/10 text-sky-100'],
        ];
        $filterStatus =
            $tab === 'pengajuan'
                ? ['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua']
                : ['baru' => 'Perlu ditinjau', 'aman' => 'Aman', 'ditindak' => 'Sudah ditindak', 'semua' => 'Semua'];
        $statusDipilih = $tab === 'pengajuan' ? $statusPengajuan : $statusPenanda;
        $daftar = $tab === 'pengajuan' ? $pengajuan : $penanda;
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'acara',
                'judul' => 'Pemeriksaan kehadiran',
                'deskripsi' =>
                    'Tinjau anggota yang GPS-nya gagal dan check-in yang tampak janggal. Check-in tidak pernah ditahan otomatis; keputusan ada di tim WSM.',
            ])

            @include('components.dashboard.inner-circle.tab-acara', ['aktifTab' => 'pemeriksaan'])

            @include('components/errors')
            @include('components/success')

            @if ($acara)
                <div
                    class="flex flex-wrap items-center gap-3 rounded-2xl border border-white/10 bg-white/3 px-4 py-3 text-sm">
                    <i class="bi bi-funnel text-white/50" aria-hidden="true"></i>
                    <span>Hanya acara <strong>{{ $acara->venue ?? $acara->kode }}</strong>
                        ({{ $acara->tanggal->locale('id')->translatedFormat('D, j M Y') }})</span>
                    <a href="{{ route('ic.admin.acara.pemeriksaan', ['tab' => $tab]) }}"
                        class="ml-auto underline underline-offset-4 text-white/70 hover:text-white">Lihat semua acara</a>
                </div>
            @endif

            <section aria-labelledby="judul-cara" class="{{ $kelasKartu }}">
                <h2 id="judul-cara" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-info-circle text-white/50" aria-hidden="true"></i> Cara kerjanya
                </h2>
                <ul class="grid gap-3 md:grid-cols-2 text-sm text-white/70">
                    <li><strong class="text-white">Pengajuan.</strong> Anggota yang berada di venue tetapi GPS-nya
                        gagal (izin ditolak, sinyal lemah, terbaca di luar radius) mengajukan pemeriksaan selama check-in
                        dibuka. Menyetujui memberi poin check-in yang sama seperti check-in biasa; menolak wajib disertai
                        alasan yang tampil ke anggota.</li>
                    <li><strong class="text-white">Penanda.</strong> Sistem menandai check-in yang tercatat tetapi janggal:
                        perangkat sama dipakai beberapa akun, lokasi terlalu sempurna, perjalanan antar acara yang tidak
                        masuk akal, atau banyak percobaan lokasi gagal. Penanda hanya isyarat, bukan bukti. Bila perlu
                        tindakan, kurangi poin atau nonaktifkan akun lewat detail anggota.</li>
                </ul>
            </section>

            <section id="daftar" aria-labelledby="judul-daftar" class="flex flex-col gap-4 scroll-mt-8">
                <h2 id="judul-daftar" class="sr-only">Daftar {{ $tab === 'pengajuan' ? 'pengajuan' : 'penanda' }}</h2>

                <nav aria-label="Jenis daftar" class="cms-chips">
                    <a href="{{ route('ic.admin.acara.pemeriksaan', ['tab' => 'pengajuan'] + $parameterAcara) }}#daftar"
                        @if ($tab === 'pengajuan') aria-current="page" @endif
                        class="cms-chip rounded-full border text-sm font-semibold transition-colors {{ $tab === 'pengajuan' ? $chipAktif : $chipBiasa }}">
                        Pengajuan <span class="text-xs opacity-70">{{ $jumlahPengajuanMenunggu }}</span></a>
                    <a href="{{ route('ic.admin.acara.pemeriksaan', ['tab' => 'penanda'] + $parameterAcara) }}#daftar"
                        @if ($tab === 'penanda') aria-current="page" @endif
                        class="cms-chip rounded-full border text-sm font-semibold transition-colors {{ $tab === 'penanda' ? $chipAktif : $chipBiasa }}">
                        Penanda <span class="text-xs opacity-70">{{ $jumlahPenandaBaru }}</span></a>
                </nav>

                <nav aria-label="Filter status" class="cms-chips">
                    @foreach ($filterStatus as $kunci => $label)
                        <a href="{{ route('ic.admin.acara.pemeriksaan', ['tab' => $tab, 'status' => $kunci] + $parameterAcara) }}#daftar"
                            @if ($statusDipilih === $kunci) aria-current="page" @endif
                            class="cms-chip rounded-full border text-xs font-semibold transition-colors {{ $statusDipilih === $kunci ? $chipAktif : $chipBiasa }}">{{ $label }}</a>
                    @endforeach
                </nav>

                @if ($daftar->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' =>
                            $tab === 'pengajuan'
                                ? 'Tidak ada pengajuan pada filter ini.'
                                : 'Tidak ada penanda pada filter ini. Itu kabar baik.',
                    ])
                @elseif ($tab === 'pengajuan')
                    <ul class="flex flex-col gap-4">
                        @foreach ($daftar as $p)
                            @php [$labelStatus, $kelasStatus] = $labelStatusPengajuan[$p->status] ?? [$p->status, 'border-white/15']; @endphp
                            <li class="{{ $kelasKartu }}">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        @if ($p->anggota)
                                            <a href="{{ route('ic.admin.anggota.detail', $p->anggota->id_member) }}"
                                                class="font-bold hover:underline">{{ $p->anggota->nama }}</a>
                                            <p class="text-xs text-white/50">{{ $p->anggota->kode_member }} ·
                                                {{ $p->anggota->email }}</p>
                                        @else
                                            <span class="text-white/40">Anggota dihapus</span>
                                        @endif
                                    </div>
                                    <span
                                        class="rounded-full border px-3 py-1 text-xs font-semibold {{ $kelasStatus }}">{{ $labelStatus }}</span>
                                </div>

                                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <dt class="text-white/50">Acara</dt>
                                        <dd class="font-semibold">
                                            <a href="{{ route('ic.admin.acara.harih.detail', $p->event->kode) }}"
                                                class="hover:underline">{{ $p->event->venue ?? $p->event->kode }}</a>
                                        </dd>
                                        <dd class="text-xs text-white/50">
                                            {{ $p->event->tanggal->locale('id')->translatedFormat('D, j M Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-white/50">Alasan</dt>
                                        <dd class="font-semibold">{{ $p->labelAlasan() }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-white/50">Terukur server</dt>
                                        <dd class="font-semibold">
                                            @if ($p->jarak_m !== null || $p->akurasi_m !== null)
                                                {{ $p->jarak_m !== null ? 'Jarak ' . $p->jarak_m . ' m' : '' }}
                                                {{ $p->akurasi_m !== null ? '· akurasi ' . $p->akurasi_m . ' m' : '' }}
                                                <span class="block text-xs font-normal text-white/50">Radius acara
                                                    {{ $p->event->radius_efektif }} m</span>
                                            @else
                                                <span class="font-normal text-white/50">Tidak ada (GPS tidak terbaca)</span>
                                            @endif
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-white/50">Diajukan</dt>
                                        <dd class="font-semibold">
                                            {{ $p->created_at?->locale('id')->translatedFormat('D, j M Y H.i') }}</dd>
                                    </div>
                                </dl>

                                @if (filled($p->catatan_anggota))
                                    <p class="rounded-2xl border border-white/10 bg-black/20 p-4 text-sm">
                                        <span class="block text-xs uppercase tracking-widest text-white/50 mb-1">Catatan
                                            anggota</span>{{ $p->catatan_anggota }}
                                    </p>
                                @endif

                                @if ($p->menunggu())
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <form method="POST"
                                            action="{{ route('ic.admin.acara.pemeriksaan.setujui', $p->id_pemeriksaan) }}"
                                            class="flex flex-col gap-3 rounded-2xl border border-white/10 p-4">
                                            @csrf
                                            <p class="text-sm font-semibold">Setujui: +{{ ic_angka($p->poin_akan) }} poin
                                                <span
                                                    class="font-normal text-white/50">({{ $p->pemenang_gl ? 'pemenang GL' : 'check-in mandiri' }})</span>
                                            </p>
                                            <label class="sr-only" for="catatan-setuju-{{ $p->id_pemeriksaan }}">Catatan
                                                (opsional)
                                            </label>
                                            <input id="catatan-setuju-{{ $p->id_pemeriksaan }}" name="catatan"
                                                maxlength="300" placeholder="Catatan untuk arsip (opsional)"
                                                class="{{ $kelasIsian }}">
                                            <div>
                                                <button type="submit" class="{{ $kelasTombolUtama }}"><i
                                                        class="bi bi-check2-circle" aria-hidden="true"></i> Setujui dan beri
                                                    poin</button>
                                            </div>
                                        </form>
                                        <form method="POST"
                                            action="{{ route('ic.admin.acara.pemeriksaan.tolak', $p->id_pemeriksaan) }}"
                                            class="flex flex-col gap-3 rounded-2xl border border-white/10 p-4">
                                            @csrf
                                            <p class="text-sm font-semibold">Tolak</p>
                                            <label class="sr-only" for="alasan-tolak-{{ $p->id_pemeriksaan }}">Alasan
                                                penolakan</label>
                                            <input id="alasan-tolak-{{ $p->id_pemeriksaan }}" name="alasan" required
                                                minlength="5" maxlength="300"
                                                placeholder="Alasan penolakan (tampil ke anggota)"
                                                class="{{ $kelasIsian }}">
                                            <div>
                                                <button type="submit" class="{{ $kelasTombol }}"><i
                                                        class="bi bi-x-circle" aria-hidden="true"></i> Tolak
                                                    pengajuan</button>
                                            </div>
                                        </form>
                                    </div>
                                @else
                                    <p class="text-xs text-white/50">
                                        Ditinjau oleh {{ $p->ditinjau_oleh ?? 'admin' }}
                                        · {{ $p->ditinjau_pada?->locale('id')->translatedFormat('D, j M Y H.i') }}
                                        @if (filled($p->catatan_admin))
                                            · “{{ $p->catatan_admin }}”
                                        @endif
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <ul class="flex flex-col gap-4">
                        @foreach ($daftar as $n)
                            @php [$labelStatus, $kelasStatus] = $labelStatusPenanda[$n->status] ?? [$n->status, 'border-white/15']; @endphp
                            <li class="{{ $kelasKartu }}">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold">{{ $n->labelJenis() }}</p>
                                        <p class="text-sm text-white/60">
                                            @if ($n->anggota)
                                                <a href="{{ route('ic.admin.anggota.detail', $n->anggota->id_member) }}"
                                                    class="font-semibold text-white hover:underline">{{ $n->anggota->nama }}</a>
                                                ({{ $n->anggota->kode_member }})
                                            @else
                                                Anggota dihapus
                                            @endif
                                            di
                                            <a href="{{ route('ic.admin.acara.harih.detail', $n->event->kode) }}"
                                                class="hover:underline">{{ $n->event->venue ?? $n->event->kode }}</a>
                                            · {{ $n->created_at?->locale('id')->translatedFormat('D, j M Y H.i') }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full border px-3 py-1 text-xs font-semibold {{ $kelasStatus }}">{{ $labelStatus }}</span>
                                </div>

                                <p class="text-sm text-white/80">{{ $n->detail }}</p>

                                @if ($n->status !== 'baru')
                                    <p class="text-xs text-white/50">
                                        Ditinjau oleh {{ $n->ditinjau_oleh ?? 'admin' }}
                                        · {{ $n->ditinjau_pada?->locale('id')->translatedFormat('D, j M Y H.i') }}
                                        @if (filled($n->catatan_admin))
                                            · “{{ $n->catatan_admin }}”
                                        @endif
                                    </p>
                                @endif

                                <form method="POST" action="{{ route('ic.admin.acara.penanda.ubah', $n->id_penanda) }}"
                                    class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    @csrf
                                    @method('PATCH')
                                    <label class="sr-only" for="catatan-penanda-{{ $n->id_penanda }}">Catatan
                                        (opsional)</label>
                                    <input id="catatan-penanda-{{ $n->id_penanda }}" name="catatan" maxlength="200"
                                        placeholder="Catatan (opsional)" class="{{ $kelasIsian }} sm:max-w-xs">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($n->status !== 'aman')
                                            <button type="submit" name="status" value="aman"
                                                class="{{ $kelasTombol }}"><i class="bi bi-shield-check"
                                                    aria-hidden="true"></i> Tandai aman</button>
                                        @endif
                                        @if ($n->status !== 'ditindak')
                                            <button type="submit" name="status" value="ditindak"
                                                class="{{ $kelasTombol }}"><i class="bi bi-check2-all"
                                                    aria-hidden="true"></i> Sudah ditindak</button>
                                        @endif
                                        @if ($n->status !== 'baru')
                                            <button type="submit" name="status" value="baru"
                                                class="{{ $kelasTombol }}"><i class="bi bi-arrow-counterclockwise"
                                                    aria-hidden="true"></i> Buka
                                                kembali</button>
                                        @endif
                                        @if ($n->anggota)
                                            <a href="{{ route('ic.admin.anggota.detail', $n->anggota->id_member) }}"
                                                class="{{ $kelasTombol }}"><i class="bi bi-person-gear"
                                                    aria-hidden="true"></i> Buka anggota</a>
                                        @endif
                                    </div>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (!$daftar->isEmpty())
                    @include('components.dashboard.pagination', ['paginator' => $daftar])
                @endif
            </section>
        </div>
    </div>
@endsection
