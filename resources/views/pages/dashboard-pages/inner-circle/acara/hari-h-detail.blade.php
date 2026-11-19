@extends('template/icDashboardLayout')
@section('content')
    @php
        $kelasTombol =
            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
        $kelasKartu = 'rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-4';
        $label = trim(($event->venue ?? $event->kode) . ($event->kota ? ', ' . $event->kota : ''));
        $total = $jumlahGl + $jumlahTiket;
        $statusUndian =
            [
                'belum' => 'Belum diundi',
                'ditunda' => 'Ditunda admin',
                'tertunda' => 'Tertunda (data GL belum lengkap)',
                'selesai' => 'Selesai',
            ][$event->status_undian] ?? $event->status_undian;
        $chipAktif = 'bg-white text-black border-white';
        $chipBiasa = 'border-white/15 text-white/70 hover:bg-white/10 hover:text-white';
    @endphp



    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'acara',
                'judul' => 'Hari H: ' . ($event->venue ?? $event->kode),
                'deskripsi' => $label . ' · ' . $event->tanggal->locale('id')->translatedFormat('D, j M Y'),
            ])

            @include('components.dashboard.inner-circle.tab-acara', ['aktifTab' => 'harih'])

            @include('components/errors')
            @include('components/success')

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('ic.admin.acara.harih') }}#daftar" class="{{ $kelasTombol }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Daftar acara</a>
                <a href="{{ route('ic.admin.acara.atur', $event->kode) }}" class="{{ $kelasTombol }}">
                    <i class="bi bi-gear-fill" aria-hidden="true"></i> Atur acara</a>
                @unless ($tokenRusak)
                    <a href="{{ route('ic.admin.acara.qr', $event->kode) }}" class="{{ $kelasTombol }}">
                        <i class="bi bi-download" aria-hidden="true"></i> Unduh QR</a>
                    <a href="{{ route('ic.admin.acara.cetak', $event->kode) }}" target="_blank" rel="noopener"
                        class="{{ $kelasTombol }}">
                        <i class="bi bi-printer" aria-hidden="true"></i> Cetak QR</a>
                @else
                    <a href="{{ route('ic.admin.acara.harih') }}" class="{{ $kelasTombol }} text-red-200!">
                        <i class="bi bi-exclamation-octagon" aria-hidden="true"></i> Token QR perlu diperbaiki</a>
                @endunless
            </div>

            {{-- ============ RINGKASAN ============ --}}
            <section aria-labelledby="judul-ringkas" class="flex flex-col gap-4">
                <h2 id="judul-ringkas" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-bar-chart text-white/50" aria-hidden="true"></i> Ringkasan kehadiran
                </h2>
                <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach (['Total hadir' => $total, 'GL gratis' => $jumlahGl, 'Beli tiket sendiri' => $jumlahTiket, 'Poin diberikan' => number_format($totalPoin, 0, ',', '.')] as $nama => $angka)
                        <div class="rounded-2xl border border-white/10 bg-white/3 p-5">
                            <dt class="text-xs font-semibold uppercase tracking-widest text-white/50">{{ $nama }}
                            </dt>
                            <dd class="mt-2 text-3xl font-bold">{{ $angka }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            {{-- ============ INFO GL ============ --}}
            <section aria-labelledby="judul-gl" class="{{ $kelasKartu }}">
                <h2 id="judul-gl" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-ticket-perforated text-white/50" aria-hidden="true"></i> GL gratis
                </h2>
                @if ($event->punya_gl)
                    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <dt class="text-white/50">Kuota</dt>
                            <dd class="font-semibold">{{ $event->kuota_gl }}</dd>
                        </div>
                        <div>
                            <dt class="text-white/50">Antre</dt>
                            <dd class="font-semibold">{{ $antrean }}</dd>
                        </div>
                        <div>
                            <dt class="text-white/50">Pemenang</dt>
                            <dd class="font-semibold">{{ $pemenang }}</dd>
                        </div>
                        <div>
                            <dt class="text-white/50">Status undian</dt>
                            <dd class="font-semibold">{{ $statusUndian }}</dd>
                        </div>
                    </dl>
                    <p class="text-xs text-white/40">Undian dijadwalkan
                        {{ $event->undian_efektif->locale('id')->translatedFormat('D, j M Y \p\u\k\u\l H.i') }} WIB. Panel
                        undian
                        (antrean, pemenang, undi sekarang) menyusul di fase berikutnya. Kehadiran pemenang GL dicatat lewat
                        pemindaian QR dan tampil di tab GL gratis.</p>
                @else
                    <p class="text-sm text-white/50">Acara ini belum punya kuota GL. Atur di halaman
                        <a class="underline underline-offset-4"
                            href="{{ route('ic.admin.acara.atur', $event->kode) }}">Atur acara</a>.
                    </p>
                @endif
            </section>

            {{-- ============ YANG HADIR ============ --}}
            <section id="hadir" aria-labelledby="judul-hadir" class="flex flex-col gap-4 scroll-mt-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="judul-hadir" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                        <i class="bi bi-people text-white/50" aria-hidden="true"></i> Yang hadir
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('ic.admin.acara.kehadiran', ['kode' => $event->kode, 'jenis' => $tab]) }}"
                            class="{{ $kelasTombol }}"><i class="bi bi-filetype-csv" aria-hidden="true"></i>
                            Unduh {{ $tab === 'gl' ? 'GL gratis' : 'beli tiket sendiri' }} (CSV)</a>
                        <a href="{{ route('ic.admin.acara.kehadiran', $event->kode) }}" class="{{ $kelasTombol }}"><i
                                class="bi bi-filetype-csv" aria-hidden="true"></i> Unduh semua (CSV)</a>
                    </div>
                </div>

                <nav aria-label="Jenis kehadiran" class="cms-chips">
                    <a href="{{ route('ic.admin.acara.harih.detail', ['kode' => $event->kode, 'tab' => 'tiket']) }}#hadir"
                        @if ($tab === 'tiket') aria-current="page" @endif
                        class="cms-chip rounded-full border text-sm font-semibold transition-colors {{ $tab === 'tiket' ? $chipAktif : $chipBiasa }}">
                        Beli tiket sendiri <span class="text-xs opacity-70">{{ $jumlahTiket }}</span></a>
                    <a href="{{ route('ic.admin.acara.harih.detail', ['kode' => $event->kode, 'tab' => 'gl']) }}#hadir"
                        @if ($tab === 'gl') aria-current="page" @endif
                        class="cms-chip rounded-full border text-sm font-semibold transition-colors {{ $tab === 'gl' ? $chipAktif : $chipBiasa }}">
                        GL gratis <span class="text-xs opacity-70">{{ $jumlahGl }}</span></a>
                </nav>

                @if ($hadir->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' =>
                            $tab === 'gl'
                                ? 'Belum ada pemenang GL yang tercatat hadir.'
                                : 'Belum ada yang check-in sendiri di acara ini.',
                    ])
                @else
                    <div class="overflow-x-auto rounded-2xl border border-white/10">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase tracking-widest text-white/50 bg-white/3">
                                <tr>
                                    <th scope="col" class="px-4 py-3">No</th>
                                    <th scope="col" class="px-4 py-3">Anggota</th>
                                    <th scope="col" class="px-4 py-3">Waktu check-in</th>
                                    <th scope="col" class="px-4 py-3">Jarak</th>
                                    <th scope="col" class="px-4 py-3 text-right">Poin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach ($hadir as $i => $p)
                                    <tr>
                                        <td class="px-4 py-3 text-white/50">{{ $hadir->firstItem() + $i }}</td>
                                        <td class="px-4 py-3">
                                            @if ($p->member)
                                                <a href="{{ route('ic.admin.anggota.detail', $p->member->id_member) }}"
                                                    class="font-semibold hover:underline">{{ $p->member->nama }}</a>
                                                <p class="text-xs text-white/50">{{ $p->member->kode_member }} ·
                                                    {{ $p->member->email }}</p>
                                            @else
                                                <span class="text-white/40">Anggota dihapus</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $p->created_at?->locale('id')->translatedFormat('D, j M Y H.i') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $p->jarak_m !== null ? $p->jarak_m . ' m' : '-' }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">
                                            {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @include('components.dashboard.pagination', ['paginator' => $hadir])
                @endif
            </section>
        </div>
    </div>
@endsection
