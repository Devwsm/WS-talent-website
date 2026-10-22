@extends('template/dashboardLayout')
@section('content')
    @php
        $tipeEvent = config('inner_circle.tipe_event');
        $titikTipe = ['festival' => 'bg-orange-300', 'club' => 'bg-amber-300', 'private' => 'bg-fuchsia-300'];
        $labelWaktu = ['lewat' => 'Sudah lewat', 'hari_ini' => 'Hari ini', 'akan_datang' => 'Akan datang'];

        // Contoh awal untuk preview form (tanggal bawaan: seminggu dari hari ini, venue dan kota masih kosong).
        $contohAcara = (object) [
            'kode' => 'PRATINJAU',
            'tanggal' => \Carbon\Carbon::today()->addDays(7),
            'kota' => null,
            'venue' => null,
            'tipe' => 'club',
            'status_waktu' => 'akan_datang',
            'bisa_checkin' => false,
            'sudah_checkin' => false,
        ];

        // Data untuk panel QR (JavaScript): satu entri per acara yang bisa check-in.
        $dataQr = collect($acaraQr)->mapWithKeys(
            fn($e) => [
                $e->kode => [
                    'venue' => $e->venue,
                    'kota' => $e->kota,
                    'tanggal' => $e->tanggal->locale('id')->translatedFormat('D, j M Y'),
                    'tipe' => $tipeEvent[$e->tipe]['label'],
                    'token' => $e->token,
                    'buka' => $e->jendela_buka->locale('id')->translatedFormat('D, j M Y H.i') . ' WIB',
                    'tutup' => $e->jendela_tutup->locale('id')->translatedFormat('D, j M Y H.i') . ' WIB',
                ],
            ],
        );
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.tabs', [
                'aktif' => 'acara',
                'judul' => 'Acara & QR',
                'deskripsi' => 'Kelola jadwal pertunjukan dan buat QR check-in untuk tiap acara.',
            ])

            {{-- ============ 1. PEMBUAT QR ============ --}}
            <section id="qr" aria-labelledby="judul-qr" class="flex flex-col gap-4 scroll-mt-8">
                <h2 id="judul-qr" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-qr-code text-white/50" aria-hidden="true"></i> Pembuat QR check-in
                </h2>

                @if ($acaraDipilih)
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                        {{-- Kiri: pilih acara dan aksi --}}
                        <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="qr-acara"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Acara</label>
                                <select id="qr-acara"
                                    class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                                    @foreach ($acaraQr as $e)
                                        <option class="bg-neutral-900" value="{{ $e->kode }}"
                                            @selected($e->kode === $acaraDipilih->kode)>
                                            {{ $e->tanggal->locale('id')->translatedFormat('j M') }} · {{ $e->venue }} ·
                                            {{ $e->kota ?? 'Kota belum diumumkan' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-white/30">Hanya acara yang bukan privat dan sudah punya venue yang
                                    bisa dipakai check-in.</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" id="qr-baru"
                                    class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Buat QR baru
                                </button>
                                <button type="button" data-ic-segera="Unduh QR aktif di Fase 4."
                                    class="h-11 px-5 inline-flex items-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                    <i class="bi bi-download" aria-hidden="true"></i> Unduh
                                </button>
                                <button type="button" data-ic-segera="Cetak QR aktif di Fase 4."
                                    class="h-11 px-5 inline-flex items-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                    <i class="bi bi-printer" aria-hidden="true"></i> Cetak
                                </button>
                            </div>

                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                                    <dt class="text-xs uppercase tracking-widest text-white/40">Check-in dibuka</dt>
                                    <dd id="qr-buka" class="mt-1 font-semibold">—</dd>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                                    <dt class="text-xs uppercase tracking-widest text-white/40">Check-in ditutup</dt>
                                    <dd id="qr-tutup" class="mt-1 font-semibold">—</dd>
                                </div>
                            </dl>

                            <ul class="text-xs text-white/40 flex flex-col gap-1.5 list-disc pl-5">
                                <li>Satu stempel per anggota per acara.</li>
                                <li>Membuat QR baru mengganti token: QR lama langsung tidak berlaku.</li>
                                <li>Jendela check-in bisa diubah per acara.</li>
                            </ul>
                        </div>

                        {{-- Kanan: kertas QR (sticky) --}}
                        <div
                            class="lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-white overflow-hidden text-black">
                            <div class="flex items-center gap-2 px-5 py-3 border-b border-black/10 bg-black/3">
                                <i class="bi bi-eye text-black/40" aria-hidden="true"></i>
                                <span class="text-xs uppercase tracking-widest text-black/40">Kertas QR di venue</span>
                            </div>
                            <div class="p-6 flex flex-col sm:flex-row items-center gap-6">
                                <svg id="qr-gambar" role="img" aria-label="Pratinjau kode QR check-in"
                                    class="w-48 h-48 shrink-0 rounded-xl border border-black/10 bg-white"
                                    viewBox="0 0 33 33" shape-rendering="crispEdges"></svg>
                                <div class="min-w-0 text-center sm:text-left">
                                    <p class="text-[11px] font-bold uppercase tracking-widest text-black/50">WS Inner Circle
                                        ·
                                        Tanda check-in</p>
                                    <p id="qr-venue" class="text-2xl font-bold mt-1 wrap-anywhere">—</p>
                                    <p id="qr-meta" class="text-sm text-black/60 mt-1">—</p>
                                    <p id="qr-token"
                                        class="inline-block mt-3 rounded-xl bg-neutral-900 px-4 py-2 font-mono text-sm font-bold tracking-widest text-white">
                                        —</p>
                                </div>
                            </div>
                            <p class="px-6 pb-6 text-xs text-black/50 break-all">
                                Tautan di dalam QR: <span id="qr-tautan">—</span>
                            </p>
                            <p class="px-6 py-3 border-t border-black/10 text-xs text-black/50 bg-black/3">
                                Gambar di atas hanya pratinjau tata letak dan belum bisa dipindai. QR asli dibuat server di
                                Fase 4.
                            </p>
                        </div>
                    </div>
                @else
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada acara yang bisa dipakai check-in.',
                    ])
                @endif
            </section>

            {{-- ============ 2. FORM ACARA + PREVIEW ============ --}}
            <section aria-labelledby="judul-form-acara" class="flex flex-col gap-4">
                <h2 id="judul-form-acara" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-plus-circle text-white/50" aria-hidden="true"></i> Tambah acara
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    {{-- FORM --}}
                    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                        <form id="form-acara" data-ic-segera="Simpan acara aktif di Fase 4." class="flex flex-col gap-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="acara-tanggal"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Tanggal show
                                        <span class="text-red-400">*</span></label>
                                    <input type="date" id="acara-tanggal" name="tanggal" required
                                        value="{{ $contohAcara->tanggal->format('Y-m-d') }}"
                                        class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="acara-tipe"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Tipe <span
                                            class="text-red-400">*</span></label>
                                    <select id="acara-tipe" name="tipe"
                                        class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                                        @foreach ($tipeEvent as $kunci => $t)
                                            <option class="bg-neutral-900" value="{{ $kunci }}"
                                                @selected($kunci === $contohAcara->tipe)>{{ $t['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="acara-venue"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Venue</label>
                                    <input type="text" id="acara-venue" name="venue" maxlength="80"
                                        placeholder="Contoh: Superhouse Satrio"
                                        class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="acara-kota"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Kota</label>
                                    <input type="text" id="acara-kota" name="kota" maxlength="60"
                                        placeholder="Contoh: Jakarta"
                                        class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                                </div>
                            </div>
                            <p class="text-xs text-white/30">Acara privat dan acara tanpa venue tampil di jadwal anggota,
                                tetapi tidak bisa dipakai check-in.</p>

                            <button type="submit"
                                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                Simpan acara
                            </button>
                        </form>

                        {{-- Impor CSV --}}
                        <div class="mt-6 pt-6 border-t border-white/10 flex flex-col gap-2">
                            <label for="acara-csv"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Impor jadwal dari
                                CSV</label>
                            <input type="file" id="acara-csv" accept=".csv,text/csv"
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition" />
                            <p class="text-xs text-white/30">Kolom: <code class="text-white/50">kode, tanggal, kota,
                                    venue,
                                    tipe</code>. Tipe: festival, club, atau private.</p>
                            <button type="button" data-ic-segera="Impor CSV aktif di Fase 4."
                                class="self-start h-11 px-5 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                Impor
                            </button>
                        </div>
                    </div>

                    {{-- PREVIEW: kartu yang sama dengan yang dilihat anggota di Check-in --}}
                    <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                            <i class="bi bi-eye text-white/40" aria-hidden="true"></i>
                            <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di halaman
                                Check-in</span>
                        </div>
                        <div class="ic-app p-5" inert id="preview-acara">
                            <x-inner-circle.kartu-event :event="$contohAcara" href="#" />
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============ 3. DAFTAR JADWAL ============ --}}
            <section id="jadwal" aria-labelledby="judul-jadwal" class="flex flex-col gap-4 scroll-mt-8">
                <h2 id="judul-jadwal" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-calendar-event text-white/50" aria-hidden="true"></i> Jadwal acara
                    ({{ $jadwal->total() }} dari {{ $totalAcara }})
                </h2>

                <form method="GET" action="{{ route('ic.admin.acara') }}#jadwal" role="search"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.6fr_auto] gap-3 rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5 items-end">
                    <div class="flex flex-col gap-1.5">
                        <label for="f-bulan"
                            class="text-sm font-semibold uppercase tracking-widest text-white/60">Bulan</label>
                        <select id="f-bulan" name="bulan"
                            class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                            <option class="bg-neutral-900" value="all">Okt–Des 2026</option>
                            @foreach (config('inner_circle.bulan_event') as $no => $nama)
                                <option class="bg-neutral-900" value="{{ $no }}" @selected($filter['bulan'] === (string) $no)>
                                    {{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="f-tipe"
                            class="text-sm font-semibold uppercase tracking-widest text-white/60">Tipe</label>
                        <select id="f-tipe" name="tipe"
                            class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                            <option class="bg-neutral-900" value="all">Semua tipe</option>
                            @foreach ($tipeEvent as $kunci => $t)
                                <option class="bg-neutral-900" value="{{ $kunci }}" @selected($filter['tipe'] === $kunci)>
                                    {{ $t['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5 sm:col-span-2 lg:col-span-1">
                        <label for="f-q"
                            class="text-sm font-semibold uppercase tracking-widest text-white/60">Cari</label>
                        <input type="search" id="f-q" name="q" value="{{ $filter['q'] }}" maxlength="60"
                            placeholder="Kota atau venue"
                            class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                    </div>
                    <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                        <button type="submit"
                            class="h-12 px-6 text-white font-bold uppercase tracking-widest bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Terapkan
                        </button>
                        @if ($filter['bulan'] !== 'all' || $filter['tipe'] !== 'all' || $filter['q'] !== '')
                            <a href="{{ route('ic.admin.acara') }}#jadwal"
                                class="h-12 px-5 inline-flex items-center rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                @if ($jadwal->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Tidak ada acara yang cocok dengan filter ini.',
                    ])
                @else
                    <ul class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                        @foreach ($jadwal as $e)
                            <li
                                class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/3 p-4 transition-colors hover:border-white/20 min-w-0">
                                <span aria-hidden="true"
                                    class="w-3 h-3 shrink-0 rounded-full {{ $titikTipe[$e->tipe] }}"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-white/50">
                                        {{ $e->tanggal->locale('id')->translatedFormat('D, j M Y') }} ·
                                        {{ $tipeEvent[$e->tipe]['label'] }}</p>
                                    <p class="font-semibold truncate">{{ $e->venue ?? 'Venue belum diumumkan' }}</p>
                                    <p class="text-xs text-white/50 truncate">
                                        {{ $e->kota ?? 'Kota belum diumumkan' }} ·
                                        {{ $labelWaktu[$e->status_waktu] }}
                                        @if ($e->bisa_checkin && $e->jumlah_checkin > 0)
                                            · {{ $e->jumlah_checkin }} check-in
                                        @elseif (!$e->bisa_checkin)
                                            · tanpa check-in
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    @if ($e->bisa_checkin)
                                        <a href="{{ route('ic.admin.acara', array_merge(request()->query(), ['acara' => $e->kode])) }}#qr"
                                            class="h-11 px-3 inline-flex items-center gap-1.5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors"
                                            aria-label="Buka QR untuk {{ $e->venue }}">
                                            <i class="bi bi-qr-code" aria-hidden="true"></i> QR
                                        </a>
                                    @endif
                                    <button type="button" data-ic-segera="Ubah acara aktif di Fase 4."
                                        aria-label="Ubah acara {{ $e->venue ?? $e->kode }}"
                                        class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-white transition">
                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" data-ic-segera="Hapus acara aktif di Fase 4."
                                        aria-label="Hapus acara {{ $e->venue ?? $e->kode }}"
                                        class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-red-400 transition">
                                        <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @include('components.dashboard.pagination', ['paginator' => $jadwal])
                @endif
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            window.icIkonPeta = @json($ikonPeta);

            const TIPE = @json($tipeEvent);
            const DATA_QR = @json($dataQr);
            const URL_CHECKIN = @json(route('ic.checkin'));
            const $ = (id) => document.getElementById(id);

            // ---------- 1. Panel QR ----------
            // Gambar ini pola acak yang dibangun dari token supaya tata letak kertas QR bisa ditinjau.
            // Bukan QR sungguhan; QR asli dibuat server (bacon/bacon-qr-code) di Fase 4.
            function gambarQr(token) {
                const n = 25;
                let h = 2166136261;
                for (const c of token) {
                    h ^= c.charCodeAt(0);
                    h = Math.imul(h, 16777619) >>> 0;
                }
                const acak = () => {
                    h ^= h << 13;
                    h >>>= 0;
                    h ^= h >>> 17;
                    h ^= h << 5;
                    h >>>= 0;
                    return h / 4294967296;
                };
                const gelap = Array.from({
                    length: n
                }, () => Array(n).fill(false));
                const sudut = (x, y) => (x < 8 && y < 8) || (x >= n - 8 && y < 8) || (x < 8 && y >= n - 8);
                for (let y = 0; y < n; y++) {
                    for (let x = 0; x < n; x++) {
                        if (!sudut(x, y)) gelap[y][x] = acak() > 0.52;
                    }
                }
                const penanda = (ox, oy) => {
                    for (let dy = 0; dy < 7; dy++) {
                        for (let dx = 0; dx < 7; dx++) {
                            gelap[oy + dy][ox + dx] = Math.max(Math.abs(dx - 3), Math.abs(dy - 3)) !== 2;
                        }
                    }
                };
                penanda(0, 0);
                penanda(n - 7, 0);
                penanda(0, n - 7);

                let d = '';
                gelap.forEach((baris, y) => baris.forEach((g, x) => {
                    if (g) d += `M${x + 4} ${y + 4}h1v1h-1z`;
                }));
                $('qr-gambar').innerHTML = `<rect width="33" height="33" fill="#fff"/><path d="${d}" fill="#111322"/>`;
            }

            function tampilkanQr(kode) {
                const a = DATA_QR[kode];
                if (!a) return;
                $('qr-venue').textContent = a.venue;
                $('qr-meta').textContent = `${a.kota ?? 'Kota belum diumumkan'} · ${a.tanggal} · ${a.tipe}`;
                $('qr-token').textContent = a.token;
                $('qr-tautan').textContent = `${URL_CHECKIN}?kode=${a.token}`;
                $('qr-buka').textContent = a.buka;
                $('qr-tutup').textContent = a.tutup;
                gambarQr(a.token);
            }

            if ($('qr-acara')) {
                tampilkanQr($('qr-acara').value);

                $('qr-acara').addEventListener('change', (e) => {
                    tampilkanQr(e.target.value);
                    history.replaceState(null, '', `?acara=${encodeURIComponent(e.target.value)}#qr`);
                });

                $('qr-baru').addEventListener('click', () => {
                    const huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                    const acak = crypto.getRandomValues(new Uint8Array(12));
                    const kode = $('qr-acara').value;
                    DATA_QR[kode].token = 'WS-' + Array.from(acak, (b) => huruf[b % huruf.length]).join('');
                    tampilkanQr(kode);
                    window.icSegera('QR baru dibuat (contoh). Pada Fase 4 token lama langsung tidak berlaku.');
                });
            }

            // ---------- 2. Preview form acara ----------
            const kartu = $('preview-acara')?.querySelector('.ic-event');
            const hariIni = new Date().toISOString().slice(0, 10);

            function perbaruiKartu() {
                if (!kartu) return;
                const tanggal = $('acara-tanggal').value;
                const venue = $('acara-venue').value.trim();
                const kota = $('acara-kota').value.trim();
                const tipe = $('acara-tipe').value;
                const t = TIPE[tipe];
                const bisa = tipe !== 'private' && venue !== '';

                kartu.querySelector('.ic-event__tanggal').textContent = tanggal ?
                    new Date(tanggal + 'T00:00:00').toLocaleDateString('id-ID', {
                        weekday: 'short',
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    }) : '—';
                kartu.querySelector('.ic-event__venue').textContent = venue || 'Venue belum diumumkan';

                const elKota = kartu.querySelector('.ic-event__kota');
                elKota.replaceChildren(elKota.querySelector('i'), document.createTextNode(' ' + (kota ||
                    'Kota belum diumumkan')));

                const waktu = !tanggal ? '' : tanggal === hariIni ? 'Hari ini' : tanggal < hariIni ? 'Sudah lewat' :
                    'Akan datang';
                const catatan = tipe === 'private' ? 'Tidak untuk umum' : (!venue ? 'Venue belum diumumkan' : waktu);
                kartu.querySelector('.ic-event__lencana').textContent = `${t.label} · ${catatan}`;

                kartu.className = kartu.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${t.warna}`;
                kartu.classList.toggle('is-nonaktif', !bisa);
                kartu.querySelector('.ic-event__panah i').className = 'bi ' + (bisa ? 'bi-chevron-right' : 'bi-dash');
                kartu.querySelector('.ic-event__seni').replaceChildren(window.icBuatIkon(t.ikon, 'ic-ikon--event'));
            }

            ['acara-tanggal', 'acara-venue', 'acara-kota', 'acara-tipe'].forEach((id) => {
                $(id)?.addEventListener('input', perbaruiKartu);
            });
            perbaruiKartu();
        </script>
    @endpush
@endsection
