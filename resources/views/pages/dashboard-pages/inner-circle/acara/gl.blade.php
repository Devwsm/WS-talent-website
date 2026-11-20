@extends('template/icDashboardLayout')
@section('content')
    @php
        $kelasTombol =
            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
        $kelasTombolUtama =
            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg bg-white text-black text-xs font-semibold uppercase tracking-widest hover:bg-white/80 transition-colors';
        $kelasKartu = 'rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-4';
        $kelasIsian =
            'w-full rounded-lg border border-white/15 bg-black/30 px-4 py-3 text-sm text-white placeholder:text-white/30 focus:outline-none focus:border-white/50';
        $chipAktif = 'bg-white text-black border-white';
        $chipBiasa = 'border-white/15 text-white/70 hover:bg-white/10 hover:text-white';
        $labelStatus = ['antri' => 'Antre', 'menang' => 'Menang', 'tidak_terpilih' => 'Tidak terpilih'];
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'acara',
                'judul' => 'Undian GL: ' . ($event->venue ?? $event->kode),
                'deskripsi' => $namaAcara . ' · ' . $event->tanggal->locale('id')->translatedFormat('D, j M Y'),
            ])

            @include('components.dashboard.inner-circle.tab-acara', ['aktifTab' => 'harih'])

            @include('components/errors')
            @include('components/success')

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('ic.admin.acara.harih.detail', $event->kode) }}" class="{{ $kelasTombol }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Detail Hari H</a>
                <a href="{{ route('ic.admin.acara.atur', $event->kode) }}" class="{{ $kelasTombol }}">
                    <i class="bi bi-gear-fill" aria-hidden="true"></i> Atur acara</a>
            </div>

            {{-- ============ RINGKASAN ============ --}}
            <section aria-labelledby="judul-ringkas" class="flex flex-col gap-4">
                <h2 id="judul-ringkas" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-ticket-perforated text-white/50" aria-hidden="true"></i> Ringkasan GL
                </h2>
                <dl class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach (['Kuota' => (int) $event->kuota_gl, 'Pendaftar' => $jumlahSemua, 'Pemenang' => $jumlahMenang, 'Tidak terpilih' => $jumlahKalah, 'Antre' => $jumlahAntri] as $nama => $angka)
                        <div class="rounded-2xl border border-white/10 bg-white/3 p-5">
                            <dt class="text-xs font-semibold uppercase tracking-widest text-white/50">{{ $nama }}
                            </dt>
                            <dd class="mt-2 text-3xl font-bold">{{ $angka }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            {{-- ============ UNDIAN ============ --}}
            <section id="undian" aria-labelledby="judul-undian" class="{{ $kelasKartu }} scroll-mt-8">
                <h2 id="judul-undian" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-shuffle text-white/50" aria-hidden="true"></i> Undian
                </h2>
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <dt class="text-white/50">Status</dt>
                        <dd class="font-semibold">{{ $statusLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-white/50">Waktu undian terjadwal</dt>
                        <dd class="font-semibold">
                            {{ $event->undian_efektif->locale('id')->translatedFormat('D, j M Y \p\u\k\u\l H.i') }} WIB</dd>
                    </div>
                    @if ($selesai)
                        <div>
                            <dt class="text-white/50">Diundi pada</dt>
                            <dd class="font-semibold">
                                {{ $event->undian_selesai_pada?->locale('id')->translatedFormat('D, j M Y \p\u\k\u\l H.i') }}
                                WIB</dd>
                        </div>
                    @endif
                </dl>

                @if ($selesai)
                    <p class="text-sm text-white/60">Hasil undian sudah terkunci dan tidak bisa diundi ulang.
                        Kartu GL terkirim lewat email ke {{ $jumlahKartuTerkirim }} dari {{ $jumlahMenang }} pemenang.</p>
                @else
                    @if ($event->status_undian === 'tertunda')
                        <p class="rounded-2xl border border-amber-300/30 bg-amber-300/10 p-4 text-sm text-amber-100">
                            Undian belum jalan karena data GL belum lengkap saat waktunya tiba; antrean sudah ditutup.
                            @if ($dataKurang)
                                Yang masih kurang: <strong>{{ implode(', ', $dataKurang) }}</strong>.
                            @endif
                            Lengkapi di <a class="underline underline-offset-4"
                                href="{{ route('ic.admin.acara.atur', $event->kode) }}">Atur acara</a>, lalu undi
                            sekarang dengan alasan.
                        </p>
                    @elseif ($dataKurang)
                        <p class="rounded-2xl border border-amber-300/30 bg-amber-300/10 p-4 text-sm text-amber-100">
                            Data GL belum lengkap: <strong>{{ implode(', ', $dataKurang) }}</strong>. Undian otomatis
                            tidak akan jalan sebelum ini diisi di <a class="underline underline-offset-4"
                                href="{{ route('ic.admin.acara.atur', $event->kode) }}">Atur acara</a>.
                        </p>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        @if ($bisaTunda)
                            <form method="POST" action="{{ route('ic.admin.acara.gl.tunda', $event->kode) }}">
                                @csrf
                                <button type="submit" class="{{ $kelasTombol }}"><i class="bi bi-pause-circle"
                                        aria-hidden="true"></i> Tunda undian</button>
                            </form>
                        @endif
                        @if ($bisaLanjutkan)
                            <form method="POST" action="{{ route('ic.admin.acara.gl.lanjutkan', $event->kode) }}">
                                @csrf
                                <button type="submit" class="{{ $kelasTombol }}"><i class="bi bi-play-circle"
                                        aria-hidden="true"></i> Lanjutkan sesuai jadwal</button>
                            </form>
                        @endif
                    </div>

                    @if ($bisaUndi)
                        <form method="POST" action="{{ route('ic.admin.acara.gl.undi', $event->kode) }}"
                            class="flex flex-col gap-3 rounded-2xl border border-white/10 p-5">
                            @csrf
                            <label for="alasan-undi" class="text-sm font-semibold">Undi sekarang</label>
                            <p class="text-xs text-white/50">Mengundi di luar jadwal biasa atau menyusul undian yang
                                tertunda. Hasilnya langsung terkunci dan email kartu GL dikirim ke pemenang. Alasan wajib
                                diisi dan tercatat di Log.</p>
                            <textarea id="alasan-undi" name="alasan" rows="2" maxlength="200" required
                                placeholder="Contoh: sandi acara baru diterima dari tim Show" class="{{ $kelasIsian }}">{{ old('alasan') }}</textarea>
                            <div>
                                <button type="submit" class="{{ $kelasTombolUtama }}"><i class="bi bi-shuffle"
                                        aria-hidden="true"></i> Undi sekarang</button>
                            </div>
                        </form>
                    @endif
                @endif
            </section>

            {{-- ============ ANTREAN DAN PEMENANG ============ --}}
            <section id="daftar" aria-labelledby="judul-daftar" class="flex flex-col gap-4 scroll-mt-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="judul-daftar" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                        <i class="bi bi-people text-white/50" aria-hidden="true"></i> Antrean dan pemenang
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @if ($selesai)
                            <a href="{{ route('ic.admin.acara.gl.ekspor', ['kode' => $event->kode, 'jenis' => 'pemenang']) }}"
                                class="{{ $kelasTombol }}"><i class="bi bi-filetype-csv" aria-hidden="true"></i> Unduh
                                pemenang (CSV)</a>
                        @endif
                        <a href="{{ route('ic.admin.acara.gl.ekspor', ['kode' => $event->kode, 'jenis' => 'antrean']) }}"
                            class="{{ $kelasTombol }}"><i class="bi bi-filetype-csv" aria-hidden="true"></i> Unduh
                            semua pendaftar (CSV)</a>
                    </div>
                </div>

                <nav aria-label="Jenis daftar GL" class="cms-chips">
                    <a href="{{ route('ic.admin.acara.gl', ['kode' => $event->kode, 'tab' => 'antrean']) }}#daftar"
                        @if ($tab === 'antrean') aria-current="page" @endif
                        class="cms-chip rounded-full border text-sm font-semibold transition-colors {{ $tab === 'antrean' ? $chipAktif : $chipBiasa }}">
                        Semua pendaftar <span class="text-xs opacity-70">{{ $jumlahSemua }}</span></a>
                    <a href="{{ route('ic.admin.acara.gl', ['kode' => $event->kode, 'tab' => 'pemenang']) }}#daftar"
                        @if ($tab === 'pemenang') aria-current="page" @endif
                        class="cms-chip rounded-full border text-sm font-semibold transition-colors {{ $tab === 'pemenang' ? $chipAktif : $chipBiasa }}">
                        Pemenang <span class="text-xs opacity-70">{{ $jumlahMenang }}</span></a>
                </nav>

                @if ($daftar->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' =>
                            $tab === 'pemenang'
                                ? 'Belum ada pemenang. Pemenang muncul setelah undian selesai.'
                                : 'Belum ada yang mendaftar antrean GL acara ini.',
                    ])
                @else
                    <div class="overflow-x-auto rounded-2xl border border-white/10">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase tracking-widest text-white/50 bg-white/3">
                                <tr>
                                    <th scope="col" class="px-4 py-3">No</th>
                                    <th scope="col" class="px-4 py-3">Anggota</th>
                                    <th scope="col" class="px-4 py-3">Daftar</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                    @if ($tab === 'pemenang')
                                        <th scope="col" class="px-4 py-3">Kartu GL</th>
                                        <th scope="col" class="px-4 py-3">Tim Show</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach ($daftar as $i => $b)
                                    <tr>
                                        <td class="px-4 py-3 text-white/50">
                                            {{ $tab === 'pemenang' ? $b->urutan_undi : $daftar->firstItem() + $i }}</td>
                                        <td class="px-4 py-3">
                                            @if ($b->anggota)
                                                <a href="{{ route('ic.admin.anggota.detail', $b->anggota->id_member) }}"
                                                    class="font-semibold hover:underline">{{ $b->anggota->nama }}</a>
                                                <p class="text-xs text-white/50">{{ $b->anggota->kode_member }} ·
                                                    {{ $b->anggota->email }}</p>
                                            @else
                                                <span class="text-white/40">Anggota dihapus</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $b->created_at?->locale('id')->translatedFormat('D, j M Y H.i') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $labelStatus[$b->status] ?? $b->status }}
                                        </td>
                                        @if ($tab === 'pemenang')
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                {{ $b->kartu_dikirim_pada ? 'Terkirim ' . $b->kartu_dikirim_pada->locale('id')->translatedFormat('j M H.i') : 'Belum terkirim' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                {{ $b->diterima_show_pada ? 'Diterima ' . $b->diterima_show_pada->locale('id')->translatedFormat('j M H.i') : '-' }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @include('components.dashboard.pagination', ['paginator' => $daftar])
                @endif
            </section>

            {{-- ============ UNTUK TIM SHOW ============ --}}
            @if ($selesai)
                <section id="tim-show" aria-labelledby="judul-show" class="{{ $kelasKartu }} scroll-mt-8">
                    <h2 id="judul-show" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                        <i class="bi bi-whatsapp text-white/50" aria-hidden="true"></i> Daftar untuk tim Show
                    </h2>
                    <p class="text-xs text-white/50">Berisi nama dan Member ID pemenang saja. Username dan sandi acara
                        sengaja tidak ikut; sandi tidak boleh dibagikan lewat chat.</p>
                    <textarea id="teks-whatsapp" readonly rows="{{ min(14, 5 + $jumlahMenang) }}"
                        class="{{ $kelasIsian }} font-mono">{{ $teksWhatsapp }}</textarea>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" id="salin-whatsapp" class="{{ $kelasTombolUtama }}"><i
                                class="bi bi-clipboard" aria-hidden="true"></i> <span>Salin untuk WhatsApp</span></button>
                        <form method="POST" action="{{ route('ic.admin.acara.gl.diterima', $event->kode) }}">
                            @csrf
                            <button type="submit" class="{{ $kelasTombol }}"><i class="bi bi-check2-circle"
                                    aria-hidden="true"></i> Tandai sudah diterima tim Show</button>
                        </form>
                        <span class="text-xs text-white/50">Diterima tim Show: {{ $jumlahDiterima }} dari
                            {{ $jumlahMenang }} pemenang</span>
                    </div>
                </section>
            @endif
        </div>
    </div>

    @if ($selesai)
        @push('scripts')
            <script>
                (function() {
                    var tombol = document.getElementById('salin-whatsapp');
                    var area = document.getElementById('teks-whatsapp');
                    if (!tombol || !area) return;
                    var label = tombol.querySelector('span');
                    var asal = label.textContent;

                    function selesai(ok) {
                        label.textContent = ok ? 'Tersalin' : 'Gagal menyalin, blok teks lalu salin manual';
                        setTimeout(function() {
                            label.textContent = asal;
                        }, 2500);
                    }

                    tombol.addEventListener('click', function() {
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(area.value).then(function() {
                                selesai(true);
                            }, function() {
                                selesai(false);
                            });
                            return;
                        }
                        area.focus();
                        area.select();
                        try {
                            selesai(document.execCommand('copy'));
                        } catch (e) {
                            selesai(false);
                        }
                    });
                })();
            </script>
        @endpush
    @endif
@endsection
