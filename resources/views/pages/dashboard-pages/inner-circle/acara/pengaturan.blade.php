@extends('template/icDashboardLayout')
@section('content')
    @php
        $tipeEvent = ic_tipe_event();
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
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'acara',
                'judul' => 'Acara: Pengaturan',
                'deskripsi' => 'Tambah dan ubah jadwal pertunjukan, impor CSV, dan sinkron dari Bandsintown.',
            ])

            @include('components.dashboard.inner-circle.tab-acara', ['aktifTab' => 'pengaturan'])

            @include('components/errors')
            @include('components/success')

            {{-- ============ 2. FORM ACARA + PREVIEW ============ --}}
            <section aria-labelledby="judul-form-acara" class="flex flex-col gap-4">
                <h2 id="judul-form-acara" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-plus-circle text-white/50" aria-hidden="true"></i> Tambah acara
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    {{-- FORM --}}
                    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                        <form id="form-acara" method="POST" action="{{ route('ic.admin.acara.simpan') }}"
                            class="flex flex-col gap-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="acara-tanggal"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Tanggal show
                                        <span class="text-red-400">*</span></label>
                                    <input type="date" id="acara-tanggal" name="tanggal" required
                                        value="{{ old('tanggal', $contohAcara->tanggal->format('Y-m-d')) }}"
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
                                                @selected($kunci === old('tipe', $contohAcara->tipe))>{{ $t['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="acara-venue"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Venue</label>
                                    <input type="text" id="acara-venue" name="venue" maxlength="80"
                                        value="{{ old('venue') }}" placeholder="Contoh: Superhouse Satrio"
                                        class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="acara-kota"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Kota</label>
                                    <input type="text" id="acara-kota" name="kota" maxlength="60"
                                        value="{{ old('kota') }}" placeholder="Contoh: Jakarta"
                                        class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                                </div>
                            </div>
                            <p class="text-xs text-white/30">Acara privat dan acara tanpa venue tampil di jadwal anggota,
                                tetapi tidak bisa dipakai check-in.</p>

                            <details class="rounded-2xl border border-white/10 bg-white/2 p-4"
                                @if (old('jendela_mulai') || old('jendela_selesai')) open @endif>
                                <summary class="cursor-pointer text-sm font-semibold text-white/70">Atur jendela check-in
                                    (opsional)</summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label for="acara-mulai"
                                            class="text-sm font-semibold uppercase tracking-widest text-white/60">Dibuka</label>
                                        <input type="datetime-local" id="acara-mulai" name="jendela_mulai"
                                            value="{{ old('jendela_mulai') }}"
                                            class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark" />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label for="acara-selesai"
                                            class="text-sm font-semibold uppercase tracking-widest text-white/60">Ditutup</label>
                                        <input type="datetime-local" id="acara-selesai" name="jendela_selesai"
                                            value="{{ old('jendela_selesai') }}"
                                            class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark" />
                                    </div>
                                </div>
                                <p class="text-xs text-white/30 mt-3">Kosongkan keduanya untuk memakai bawaan:
                                    {{ app(\App\Services\InnerCircle\IcAturan::class)->ringkasan() }}.</p>
                            </details>

                            <button type="submit"
                                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                Simpan acara
                            </button>
                        </form>

                        {{-- Bandsintown --}}
                        <div class="mt-6 pt-6 border-t border-white/10 flex flex-col gap-2">
                            <p class="text-sm font-semibold uppercase tracking-widest text-white/60">Jadwal dari
                                Bandsintown
                            </p>
                            @if ($bitTersedia)
                                <p class="text-xs text-white/30">Menarik jadwal tour yang sama dengan widget Tour di situs
                                    publik. Acara baru dibuat dengan QR sendiri, acara yang sudah ada tidak ditimpa dan
                                    tidak dihapus. Tipe acara tebakan (festival atau club), ubah bila perlu.
                                    @if ($bitTerakhir)
                                        Terakhir sinkron:
                                        {{ $bitTerakhir->locale('id')->translatedFormat('j M Y H.i') }} WIB.
                                    @endif
                                </p>
                                <form method="POST" action="{{ route('ic.admin.acara.bandsintown') }}">
                                    @csrf
                                    <button type="submit"
                                        class="h-11 px-5 inline-flex items-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Sinkron sekarang
                                    </button>
                                </form>
                            @else
                                <p class="text-xs text-white/40">Belum aktif. Isi <code
                                        class="text-white/60">BANDSINTOWN_APP_ID</code> di <code
                                        class="text-white/60">.env</code> server untuk menarik jadwal otomatis (lihat
                                    README, bagian Bandsintown).</p>
                            @endif
                        </div>

                        {{-- Impor CSV --}}
                        <form method="POST" action="{{ route('ic.admin.acara.impor') }}" enctype="multipart/form-data"
                            class="mt-6 pt-6 border-t border-white/10 flex flex-col gap-2">
                            @csrf
                            <label for="acara-csv"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Impor jadwal dari
                                CSV</label>
                            <input type="file" id="acara-csv" name="csv" accept=".csv,text/csv" required
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition" />
                            <p class="text-xs text-white/30">Kolom: <code class="text-white/50">kode, tanggal, kota,
                                    venue,
                                    tipe</code>. Tanggal berformat YYYY-MM-DD. Tipe: festival, club, atau private. Kode yang
                                sudah ada diperbarui (QR-nya tetap), kode kosong dibuat otomatis. Satu baris salah
                                membatalkan seluruh impor.</p>
                            <button type="submit"
                                class="self-start h-11 px-5 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                Impor
                            </button>
                        </form>
                    </div>

                    {{-- PREVIEW: kartu yang sama dengan yang dilihat anggota di Check-in --}}
                    <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                            <i class="bi bi-eye text-white/40" aria-hidden="true"></i>
                            <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di halaman
                                Check-in</span>
                        </div>
                        <div class="ic-app ic-ponsel p-2 sm:p-5" inert id="preview-acara">
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
                    class="grid grid-cols-2 lg:grid-cols-[1fr_1fr_1.6fr_auto] gap-3 rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5 items-end">
                    <div class="flex flex-col gap-1.5">
                        <label for="f-bulan"
                            class="text-sm font-semibold uppercase tracking-widest text-white/60">Bulan</label>
                        <select id="f-bulan" name="bulan"
                            class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                            <option class="bg-neutral-900" value="all">Semua bulan</option>
                            @foreach ($bulanPilihan as $ym => $nama)
                                <option class="bg-neutral-900" value="{{ $ym }}" @selected($filter['bulan'] === $ym)>
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
                    <div class="flex flex-col gap-1.5 col-span-2 lg:col-span-1">
                        <label for="f-q"
                            class="text-sm font-semibold uppercase tracking-widest text-white/60">Cari</label>
                        <input type="search" id="f-q" name="q" value="{{ $filter['q'] }}" maxlength="60"
                            placeholder="Kota atau venue"
                            class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                    </div>
                    <div class="flex gap-2 col-span-2 lg:col-span-1 *:flex-1 lg:*:flex-none *:justify-center">
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
                                        @if ($e->punya_gl)
                                            · GL {{ $e->kuota_gl }}
                                        @endif
                                        @if ($e->bisa_checkin && !$e->lokasi_aktif)
                                            · belum ada lokasi
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    @if ($e->bisa_checkin)
                                        <a href="{{ route('ic.admin.acara.harih', ['acara' => $e->kode]) }}#qr"
                                            class="h-11 px-3 inline-flex items-center gap-1.5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors"
                                            aria-label="Buka QR untuk {{ $e->venue }}">
                                            <i class="bi bi-qr-code" aria-hidden="true"></i> QR
                                        </a>
                                    @endif
                                    <a href="{{ route('ic.admin.acara.atur', $e->kode) }}"
                                        class="h-11 px-3 inline-flex items-center gap-1.5 rounded-full border border-white/20 text-white text-xs font-semibold hover:bg-white/10 transition-colors"
                                        aria-label="Atur acara {{ $e->venue ?? $e->kode }}">
                                        <i class="bi bi-gear-fill" aria-hidden="true"></i> Atur
                                    </a>
                                    <button type="button" data-ic-acara-ubah
                                        data-aksi="{{ route('ic.admin.acara.ubah', $e->kode) }}"
                                        data-tanggal="{{ $e->tanggal->format('Y-m-d') }}"
                                        data-tipe="{{ $e->tipe }}" data-venue="{{ $e->venue }}"
                                        data-kota="{{ $e->kota }}"
                                        data-mulai="{{ $e->jendela_mulai?->format('Y-m-d\TH:i') }}"
                                        data-selesai="{{ $e->jendela_selesai?->format('Y-m-d\TH:i') }}"
                                        aria-label="Ubah acara {{ $e->venue ?? $e->kode }}"
                                        class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-white transition">
                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                    </button>
                                    <form method="POST" action="{{ route('ic.admin.acara.hapus', $e->kode) }}"
                                        class="inline-flex" data-swal-confirm
                                        data-confirm-message="Acara {{ $e->venue ?? $e->kode }} akan dihapus dari jadwal.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus acara {{ $e->venue ?? $e->kode }}"
                                            class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-red-400 transition">
                                            <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @include('components.dashboard.pagination', ['paginator' => $jadwal])
                @endif
            </section>
        </div>
    </div>

    {{-- Dialog ubah acara: satu dialog dipakai semua baris, isinya diisi lewat JS dari tombol yang diklik --}}
    <dialog id="dialogAcara" aria-labelledby="dialogAcaraJudul"
        class="fixed inset-0 m-0 w-screen h-screen max-w-none max-h-none bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
        <div class="min-h-screen w-full flex items-center justify-center p-4">
            <div
                class="w-full max-w-lg max-h-[calc(100vh-2rem)] flex flex-col min-w-0 rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
                <div
                    class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                    <h3 id="dialogAcaraJudul" class="font-bold uppercase tracking-wide text-sm text-white">Ubah acara</h3>
                    <button type="button" onclick="document.getElementById('dialogAcara').close()" aria-label="Tutup"
                        class="text-white/40 hover:text-white transition">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <form method="POST" action="#" id="formUbahAcara"
                    class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    @csrf
                    @method('PUT')
                    <div class="flex-1 min-h-0 p-6 flex flex-col gap-4 overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="ubah-tanggal"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Tanggal show
                                    <span class="text-red-400">*</span></label>
                                <input type="date" id="ubah-tanggal" name="tanggal" required
                                    class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="ubah-tipe"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Tipe <span
                                        class="text-red-400">*</span></label>
                                <select id="ubah-tipe" name="tipe"
                                    class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                                    @foreach ($tipeEvent as $kunci => $t)
                                        <option class="bg-neutral-900" value="{{ $kunci }}">{{ $t['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="ubah-venue"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Venue</label>
                                <input type="text" id="ubah-venue" name="venue" maxlength="80"
                                    class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="ubah-kota"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Kota</label>
                                <input type="text" id="ubah-kota" name="kota" maxlength="60"
                                    class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="ubah-mulai"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Check-in
                                    dibuka</label>
                                <input type="datetime-local" id="ubah-mulai" name="jendela_mulai"
                                    class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="ubah-selesai"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Check-in
                                    ditutup</label>
                                <input type="datetime-local" id="ubah-selesai" name="jendela_selesai"
                                    class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark" />
                            </div>
                        </div>
                        <p class="text-xs text-white/30">Kosongkan kedua jendela untuk memakai bawaan
                            ({{ app(\App\Services\InnerCircle\IcAturan::class)->ringkasan() }}). Token dan QR acara tidak
                            berubah.</p>
                    </div>
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/3 shrink-0">
                        <button type="button" onclick="document.getElementById('dialogAcara').close()"
                            class="h-11 px-5 rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="h-11 px-5 text-white font-bold uppercase tracking-widest text-sm bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </dialog>

    @push('scripts')
        <script>
            window.icIkonPeta = @json($ikonPeta);

            const TIPE = @json($tipeEvent);
            const $ = (id) => document.getElementById(id);

            // ---------- 1. Dialog ubah acara ----------
            const dialogAcara = $('dialogAcara');
            document.addEventListener('click', (e) => {
                const tombol = e.target.closest('[data-ic-acara-ubah]');
                if (!tombol || !dialogAcara) return;
                const d = tombol.dataset;
                $('formUbahAcara').action = d.aksi;
                $('ubah-tanggal').value = d.tanggal || '';
                $('ubah-tipe').value = d.tipe || 'club';
                $('ubah-venue').value = d.venue || '';
                $('ubah-kota').value = d.kota || '';
                $('ubah-mulai').value = d.mulai || '';
                $('ubah-selesai').value = d.selesai || '';
                dialogAcara.showModal();
                $('ubah-venue').focus();
            });

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
