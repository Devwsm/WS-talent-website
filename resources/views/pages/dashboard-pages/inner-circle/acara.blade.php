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

            @include('components/errors')
            @include('components/success')

            {{-- Token terenkripsi yang tidak bisa dibuka (APP_KEY server berbeda dari saat token dibuat). --}}
            @if (($jumlahTokenRusak ?? 0) > 0)
                <div role="alert"
                    class="flex flex-col sm:flex-row sm:items-center gap-4 rounded-2xl border border-red-400/30 bg-red-950/30 px-5 py-4 text-sm text-red-100">
                    <i class="bi bi-exclamation-octagon text-lg shrink-0" aria-hidden="true"></i>
                    <p class="flex-1">Token QR <b>{{ $jumlahTokenRusak }} acara</b> tidak bisa dibaca, biasanya karena
                        <code>APP_KEY</code> di server berbeda dari saat token dibuat. QR acara itu belum bisa dipakai
                        check-in. Perbaiki dengan membuat token baru, lalu cetak ulang QR-nya.
                    </p>
                    <form method="POST" action="{{ route('ic.admin.acara.perbaiki') }}" data-ic-konfirmasi
                        data-judul="Perbaiki token?"
                        data-teks="Token yang tidak terbaca diganti baru. QR lama dari acara itu memang sudah tidak berfungsi."
                        data-tombol="Ya, perbaiki">
                        @csrf
                        <button type="submit"
                            class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            <i class="bi bi-wrench" aria-hidden="true"></i> Perbaiki token
                        </button>
                    </form>
                </div>
            @endif

            {{-- ============ 1. PEMBUAT QR ============ --}}
            <section id="qr" aria-labelledby="judul-qr" class="flex flex-col gap-4 scroll-mt-8">
                <h2 id="judul-qr" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-qr-code text-white/50" aria-hidden="true"></i> Pembuat QR check-in
                </h2>

                @if ($acaraDipilih)
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                        {{-- Kiri: pilih acara dan aksi --}}
                        <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-4">
                            <form method="GET" action="{{ route('ic.admin.acara') }}#qr" class="flex flex-col gap-1.5">
                                <label for="qr-acara"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Acara</label>
                                <select id="qr-acara" name="acara" onchange="this.form.submit()"
                                    class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark">
                                    @foreach ($acaraQr as $e)
                                        <option class="bg-neutral-900" value="{{ $e->kode }}"
                                            @selected($e->kode === $acaraDipilih->kode)>
                                            {{ $e->tanggal->locale('id')->translatedFormat('j M') }} · {{ $e->venue }} ·
                                            {{ $e->kota ?? 'Kota belum diumumkan' }}
                                        </option>
                                    @endforeach
                                </select>
                                <noscript>
                                    <button type="submit"
                                        class="self-start h-11 px-5 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70">Tampilkan</button>
                                </noscript>
                                <p class="text-xs text-white/30">Hanya acara yang bukan privat dan sudah punya venue yang
                                    bisa dipakai check-in.</p>
                            </form>

                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('ic.admin.acara.token', $acaraDipilih->kode) }}"
                                    data-ic-konfirmasi data-judul="Buat QR baru?"
                                    data-teks="QR lama langsung tidak berlaku. Cetakan QR yang sudah terpasang di venue harus diganti."
                                    data-tombol="Ya, buat QR baru">
                                    @csrf
                                    <button type="submit"
                                        class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Buat QR baru
                                    </button>
                                </form>
                                @unless ($acaraDipilih->tokenRusak())
                                    <a href="{{ route('ic.admin.acara.qr', $acaraDipilih->kode) }}" download
                                        class="h-11 px-5 inline-flex items-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                        <i class="bi bi-download" aria-hidden="true"></i> Unduh
                                    </a>
                                    <a href="{{ route('ic.admin.acara.cetak', $acaraDipilih->kode) }}" target="_blank"
                                        rel="noopener"
                                        class="h-11 px-5 inline-flex items-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                        <i class="bi bi-printer" aria-hidden="true"></i> Cetak
                                    </a>
                                @endunless
                            </div>

                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                                    <dt class="text-xs uppercase tracking-widest text-white/40">Check-in dibuka</dt>
                                    <dd id="qr-buka" class="mt-1 font-semibold">
                                        {{ $acaraDipilih->jendela_buka->locale('id')->translatedFormat('D, j M Y H.i') }}
                                        WIB</dd>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                                    <dt class="text-xs uppercase tracking-widest text-white/40">Check-in ditutup</dt>
                                    <dd id="qr-tutup" class="mt-1 font-semibold">
                                        {{ $acaraDipilih->jendela_tutup->locale('id')->translatedFormat('D, j M Y H.i') }}
                                        WIB</dd>
                                </div>
                            </dl>

                            <ul class="text-xs text-white/40 flex flex-col gap-1.5 list-disc pl-5">
                                <li>Satu stempel per anggota per acara.</li>
                                <li>Membuat QR baru mengganti token: QR lama langsung tidak berlaku.</li>
                                <li>Jendela check-in bisa diubah per acara lewat tombol ubah di jadwal.</li>
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
                                <div id="qr-gambar" role="img"
                                    aria-label="Kode QR check-in untuk {{ $acaraDipilih->venue }}"
                                    class="w-48 h-48 shrink-0 rounded-xl border border-black/10 bg-white overflow-hidden [&>svg]:w-full [&>svg]:h-full">
                                    {!! $qrSvg !!}
                                </div>
                                <div class="min-w-0 text-center sm:text-left">
                                    <p class="text-[11px] font-bold uppercase tracking-widest text-black/50">WS Inner Circle
                                        ·
                                        Tanda check-in</p>
                                    <p id="qr-venue" class="text-2xl font-bold mt-1 wrap-anywhere">
                                        {{ $acaraDipilih->venue }}</p>
                                    <p id="qr-meta" class="text-sm text-black/60 mt-1">
                                        {{ $acaraDipilih->kota ?? 'Kota belum diumumkan' }} ·
                                        {{ $acaraDipilih->tanggal->locale('id')->translatedFormat('D, j M Y') }} ·
                                        {{ $tipeEvent[$acaraDipilih->tipe]['label'] }}</p>
                                    <p id="qr-token"
                                        class="inline-block mt-3 rounded-xl bg-neutral-900 px-4 py-2 font-mono text-sm font-bold tracking-widest text-white">
                                        {{ $acaraDipilih->token ?? 'Token tidak terbaca' }}</p>
                                </div>
                            </div>
                            <p class="px-6 pb-6 text-xs text-black/50 break-all">
                                Tautan di dalam QR: <span id="qr-tautan">{{ $tautanQr }}</span>
                            </p>
                            <p class="px-6 py-3 border-t border-black/10 text-xs text-black/50 bg-black/3">
                                Kamera HP biasa pun bisa memindai QR ini: anggota akan dibawa ke halaman Check-in (setelah
                                masuk) dengan kode sudah terisi.
                            </p>
                        </div>
                    </div>

                    {{-- Lokasi check-in: titik + radius milik ACARA ini (bukan milik akun CMS), jadi banyak petugas boleh memakai satu akun. --}}
                    @php
                        $punyaLokasi = $acaraDipilih->lokasi_aktif;
                        $labelSumber = [
                            'petugas' => 'GPS HP petugas',
                            'alamat' => 'Hasil cari alamat',
                            'manual' => 'Koordinat diketik',
                        ];
                        $latIsi = old('lat', $punyaLokasi ? $acaraDipilih->lokasi_lat : '');
                        $lngIsi = old('lng', $punyaLokasi ? $acaraDipilih->lokasi_lng : '');
                        $kelasInput =
                            'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
                        $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
                        $kelasTombol =
                            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
                    @endphp
                    <div id="lokasi" data-lokasi-kartu
                        data-url-cari="{{ route('ic.admin.acara.lokasi.cari', $acaraDipilih->kode) }}"
                        data-titik-lat="{{ $punyaLokasi ? $acaraDipilih->lokasi_lat : '' }}"
                        data-titik-lng="{{ $punyaLokasi ? $acaraDipilih->lokasi_lng : '' }}"
                        class="scroll-mt-8 mt-6 rounded-3xl border border-white/10 bg-white/3 p-5 sm:p-6 md:p-8 flex flex-col gap-5">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                                    <i class="bi bi-geo-alt text-white/50" aria-hidden="true"></i> Lokasi check-in
                                </h3>
                                <p class="text-xs text-white/40 mt-1 max-w-prose">Anggota hanya bisa check-in bila HP-nya
                                    berada
                                    di dalam radius titik ini. Titik dan radius milik <b>acara</b>, bukan milik akun: banyak
                                    petugas boleh memakai satu akun CMS, dan yang berlaku hanya titik yang terakhir
                                    disimpan.</p>
                            </div>
                            @if ($punyaLokasi)
                                <span
                                    class="self-start shrink-0 rounded-full bg-emerald-500/15 text-emerald-200 px-3 py-1 text-xs font-bold">
                                    Aktif · radius {{ $acaraDipilih->radius_efektif }} m</span>
                            @else
                                <span
                                    class="self-start shrink-0 rounded-full bg-amber-500/15 text-amber-200 px-3 py-1 text-xs font-bold">
                                    Belum diatur</span>
                            @endif
                        </div>

                        @unless ($punyaLokasi)
                            <p class="rounded-2xl border border-amber-400/30 bg-amber-950/30 px-4 py-3 text-xs text-amber-100">
                                Selama titik belum diatur, QR acara ini bisa dipakai check-in dari mana saja (termasuk dari
                                foto QR yang dikirim ke orang yang tidak hadir). Atur titik di bawah, sebaiknya saat petugas
                                sudah berada di venue.</p>
                        @else
                            <p class="text-xs text-white/40">
                                Diatur {{ $labelSumber[$acaraDipilih->lokasi_sumber] ?? 'manual' }}
                                @if ($acaraDipilih->lokasi_diatur_pada)
                                    · {{ $acaraDipilih->lokasi_diatur_pada->locale('id')->translatedFormat('D, j M Y H.i') }}
                                    WIB
                                @endif
                                @if ($acaraDipilih->lokasi_diatur_oleh)
                                    · oleh {{ $acaraDipilih->lokasi_diatur_oleh }}
                                @endif
                            </p>
                        @endunless

                        <form method="POST" action="{{ route('ic.admin.acara.lokasi', $acaraDipilih->kode) }}"
                            class="flex flex-col gap-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="sumber" id="lokasi-sumber"
                                value="{{ old('sumber', $acaraDipilih->lokasi_sumber ?: 'manual') }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5 min-w-0">
                                    <label for="lokasi-lat" class="{{ $kelasLabel }}">Latitude</label>
                                    <input id="lokasi-lat" name="lat" inputmode="decimal" autocomplete="off"
                                        value="{{ $latIsi }}" placeholder="-6.2088" class="{{ $kelasInput }}"
                                        required>
                                    @error('lat')
                                        <p class="text-xs text-red-300" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1.5 min-w-0">
                                    <label for="lokasi-lng" class="{{ $kelasLabel }}">Longitude</label>
                                    <input id="lokasi-lng" name="lng" inputmode="decimal" autocomplete="off"
                                        value="{{ $lngIsi }}" placeholder="106.8456" class="{{ $kelasInput }}"
                                        required>
                                    @error('lng')
                                        <p class="text-xs text-red-300" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1.5 min-w-0">
                                    <label for="lokasi-radius" class="{{ $kelasLabel }}">Radius (meter)</label>
                                    <input id="lokasi-radius" name="radius" type="number" inputmode="numeric"
                                        min="{{ config('inner_circle.lokasi.radius_min_m') }}"
                                        max="{{ config('inner_circle.lokasi.radius_maks_m') }}"
                                        value="{{ old('radius', $acaraDipilih->lokasi_radius ?: config('inner_circle.lokasi.radius_bawaan_m')) }}"
                                        class="{{ $kelasInput }}" required>
                                    <p class="text-xs text-white/30">Klub: 100-200 m. Festival atau area luas: 300-800 m.
                                    </p>
                                    @error('radius')
                                        <p class="text-xs text-red-300" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1.5 min-w-0">
                                    <label for="lokasi-petugas" class="{{ $kelasLabel }}">Nama petugas
                                        (opsional)</label>
                                    <input id="lokasi-petugas" name="petugas" maxlength="40" autocomplete="off"
                                        value="{{ old('petugas') }}" placeholder="Siapa yang mengatur titik ini"
                                        class="{{ $kelasInput }}">
                                    <p class="text-xs text-white/30">Ikut tercatat di Log, karena akun CMS dipakai bersama.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-lokasi-hp class="{{ $kelasTombol }}">
                                    <i class="bi bi-phone" aria-hidden="true"></i> Pakai lokasi HP ini</button>
                                <button type="button" data-lokasi-cari class="{{ $kelasTombol }}">
                                    <i class="bi bi-search" aria-hidden="true"></i> Cari dari alamat venue</button>
                                @if ($punyaLokasi)
                                    <button type="button" data-lokasi-uji class="{{ $kelasTombol }}">
                                        <i class="bi bi-rulers" aria-hidden="true"></i> Uji jarak HP ini</button>
                                @endif
                                <a href="#" target="_blank" rel="noopener" id="lokasi-peta" hidden
                                    class="{{ $kelasTombol }}">
                                    <i class="bi bi-map" aria-hidden="true"></i> Cek di Google Maps</a>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label for="lokasi-alamat" class="sr-only">Alamat untuk dicari</label>
                                <input id="lokasi-alamat" maxlength="150" autocomplete="off"
                                    placeholder="Alamat venue (opsional)" class="{{ $kelasInput }}">
                            </div>

                            <div id="lokasi-hasil" role="status" aria-live="polite"
                                class="flex flex-col gap-2 text-sm text-white/70"></div>

                            @if ($punyaLokasi)
                                <label class="flex items-start gap-3 text-xs text-white/50 cursor-pointer">
                                    <input type="checkbox" name="konfirmasi_geser" value="1"
                                        class="mt-0.5 size-4 accent-red-700" @checked(old('konfirmasi_geser'))>
                                    <span>Saya sengaja memindahkan titik ini lebih dari
                                        {{ number_format(config('inner_circle.lokasi.geser_konfirmasi_m'), 0, ',', '.') }}
                                        m dari titik
                                        yang tersimpan. (Pengaman bila petugas yang sedang tidak di venue menekan tombol
                                        lokasi.)</span>
                                </label>
                            @endif

                            <div class="flex flex-wrap gap-2">
                                <button type="submit"
                                    class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                    <i class="bi bi-check2-circle" aria-hidden="true"></i> Simpan lokasi</button>
                            </div>
                        </form>

                        @if ($punyaLokasi)
                            <form method="POST" action="{{ route('ic.admin.acara.lokasi.hapus', $acaraDipilih->kode) }}"
                                data-ic-konfirmasi data-judul="Hapus titik lokasi?"
                                data-teks="QR acara ini kembali bisa dipakai check-in dari mana saja."
                                data-tombol="Ya, hapus titik">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-xs text-red-300 underline underline-offset-4 hover:text-red-200 min-h-11">
                                    Hapus titik lokasi (matikan pemeriksaan lokasi)</button>
                            </form>
                        @endif
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
                                <p class="text-xs text-white/30 mt-3">Kosongkan keduanya untuk memakai bawaan: tanggal show
                                    pukul 12.00 sampai hari berikutnya pukul 06.00 WIB.</p>
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
                        <p class="text-xs text-white/30">Kosongkan kedua jendela untuk memakai bawaan (tanggal show pukul
                            12.00
                            sampai hari berikutnya pukul 06.00 WIB). Token dan QR acara tidak berubah.</p>
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

            // ---------- 1. Konfirmasi sebelum membuat QR baru ----------
            // Pesan khusus (bukan "Hapus data?" bawaan konfirmasi hapus), karena QR lama langsung tidak berlaku.
            document.addEventListener('submit', async (e) => {
                const form = e.target.closest('form[data-ic-konfirmasi]');
                if (!form || form.dataset.icSudahKonfirmasi === 'true') return;
                e.preventDefault();
                const hasil = await window.Swal.fire({
                    title: form.dataset.judul || 'Lanjutkan?',
                    text: form.dataset.teks || '',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: form.dataset.tombol || 'Ya',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                });
                if (!hasil.isConfirmed) return;
                form.dataset.icSudahKonfirmasi = 'true';
                form.submit();
            });

            // ---------- 2. Dialog ubah acara ----------
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

            // ---------- 3. Preview form acara ----------
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

    @push('scripts')
        <script>
            // Lokasi check-in (panel admin): ambil titik dari GPS HP petugas, cari dari alamat, atau ketik koordinat.
            (function() {
                const kartu = document.querySelector('[data-lokasi-kartu]');
                if (!kartu) return;
                const $ = (id) => document.getElementById(id);
                const lat = $('lokasi-lat'),
                    lng = $('lokasi-lng'),
                    sumber = $('lokasi-sumber');
                const hasil = $('lokasi-hasil'),
                    peta = $('lokasi-peta');
                const tokenCsrf = kartu.querySelector('input[name=_token]')?.value ?? '';

                const jarak = (a, b, c, d) => {
                    const r = Math.PI / 180,
                        p = Math.sin((c - a) * r / 2) ** 2 +
                        Math.cos(a * r) * Math.cos(c * r) * Math.sin((d - b) * r / 2) ** 2;
                    return 2 * 6371008.8 * Math.asin(Math.min(1, Math.sqrt(p)));
                };
                const teks = (t) => {
                    hasil.replaceChildren(Object.assign(document.createElement('p'), {
                        textContent: t
                    }));
                };
                const perbaruiPeta = () => {
                    const a = parseFloat(String(lat.value).replace(',', '.')),
                        b = parseFloat(String(lng.value).replace(',', '.'));
                    peta.hidden = !(isFinite(a) && isFinite(b));
                    if (!peta.hidden) peta.href = 'https://www.google.com/maps?q=' + a + ',' + b;
                };

                // Koordinat dari Google Maps ("-6.2088, 106.8456") boleh ditempel di kolom latitude saja.
                lat.addEventListener('change', () => {
                    const m = lat.value.trim().match(/^(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)$/);
                    if (m && !lng.value.trim()) {
                        lat.value = m[1];
                        lng.value = m[2];
                    }
                    sumber.value = 'manual';
                    perbaruiPeta();
                });
                lng.addEventListener('change', () => {
                    sumber.value = 'manual';
                    perbaruiPeta();
                });
                perbaruiPeta();

                // Bacaan GPS terbaik dalam beberapa detik (bacaan pertama sering kasar).
                function bacaGps(progres) {
                    return new Promise((ok, gagal) => {
                        if (!window.isSecureContext || !navigator.geolocation) return gagal(new Error(
                            'Browser ini tidak bisa membaca lokasi (butuh HTTPS).'));
                        let terbaik = null,
                            id = null,
                            selesai = false;
                        const akhir = (f) => {
                            if (selesai) return;
                            selesai = true;
                            navigator.geolocation.clearWatch(id);
                            clearTimeout(t);
                            f();
                        };
                        const t = setTimeout(() => akhir(() => terbaik ? ok(terbaik) : gagal(new Error(
                            'Sinyal GPS belum terbaca. Coba di area terbuka.'))), 12000);
                        id = navigator.geolocation.watchPosition((p) => {
                            const c = p.coords,
                                k = {
                                    lat: c.latitude,
                                    lng: c.longitude,
                                    akurasi: Math.round(c.accuracy)
                                };
                            if (!terbaik || k.akurasi < terbaik.akurasi) terbaik = k;
                            progres(terbaik);
                            if (terbaik.akurasi <= 25) akhir(() => ok(terbaik));
                        }, (e) => akhir(() => terbaik && e.code !== 1 ? ok(terbaik) : gagal(new Error(e
                            .code === 1 ?
                            'Izin lokasi ditolak. Izinkan lokasi untuk situs ini lalu coba lagi.' :
                            'Lokasi tidak tersedia. Nyalakan GPS di HP.'))), {
                            enableHighAccuracy: true,
                            maximumAge: 0,
                            timeout: 12000
                        });
                    });
                }

                kartu.querySelector('[data-lokasi-hp]').addEventListener('click', async () => {
                    teks('Membaca lokasi HP…');
                    try {
                        const p = await bacaGps((k) => teks('Membaca lokasi HP… akurasi sekitar ' + k.akurasi +
                            ' m'));
                        lat.value = p.lat.toFixed(7);
                        lng.value = p.lng.toFixed(7);
                        sumber.value = 'petugas';
                        perbaruiPeta();
                        teks('Titik diisi dari HP ini (akurasi sekitar ' + p.akurasi + ' m).' +
                            (p.akurasi > 50 ?
                                ' Akurasi kurang baik: pindah ke area terbuka lalu ulangi untuk titik yang lebih tepat.' :
                                '') +
                            ' Tekan Simpan lokasi untuk memakainya.');
                    } catch (e) {
                        teks(e.message);
                    }
                });

                kartu.querySelector('[data-lokasi-uji]')?.addEventListener('click', async () => {
                    const a = parseFloat(kartu.dataset.titikLat),
                        b = parseFloat(kartu.dataset.titikLng);
                    const r = parseFloat($('lokasi-radius').value) || 0;
                    teks('Membaca lokasi HP…');
                    try {
                        const p = await bacaGps((k) => teks('Membaca lokasi HP… akurasi sekitar ' + k.akurasi +
                            ' m'));
                        const d = Math.round(jarak(a, b, p.lat, p.lng));
                        teks('HP ini berjarak sekitar ' + d + ' m dari titik tersimpan (akurasi ' + p.akurasi +
                            ' m). ' +
                            (d <= r ? 'Di dalam radius ' + r + ' m: check-in akan diterima.' :
                                'Di luar radius ' + r + ' m: check-in akan ditolak.') +
                            ' Ini hanya uji, tidak mengubah apa pun.');
                    } catch (e) {
                        teks(e.message);
                    }
                });

                kartu.querySelector('[data-lokasi-cari]').addEventListener('click', async () => {
                    teks('Mencari alamat…');
                    try {
                        const r = await fetch(kartu.dataset.urlCari, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': tokenCsrf
                            },
                            body: JSON.stringify({
                                alamat: $('lokasi-alamat').value
                            }),
                        });
                        const data = await r.json().catch(() => ({}));
                        if (!r.ok) return teks(data.pesan || 'Pencarian alamat gagal (status ' + r.status +
                            ').');
                        if (!data.hasil.length) return teks('Tidak ada hasil untuk "' + data.kueri +
                            '". Coba alamat yang lebih lengkap, atau pakai lokasi HP.');
                        const daftar = document.createElement('div');
                        daftar.className = 'flex flex-col gap-2';
                        daftar.appendChild(Object.assign(document.createElement('p'), {
                            textContent: 'Pilih yang benar (periksa lewat Google Maps sebelum menyimpan):'
                        }));
                        data.hasil.forEach((h) => {
                            const baris = document.createElement('div');
                            baris.className =
                                'flex flex-col sm:flex-row sm:items-center gap-2 rounded-xl border border-white/10 p-3';
                            const pilih = document.createElement('button');
                            pilih.type = 'button';
                            pilih.className =
                                'text-left text-sm text-white hover:underline min-w-0 flex-1 break-words';
                            pilih.textContent = h.nama;
                            pilih.addEventListener('click', () => {
                                lat.value = h.lat;
                                lng.value = h.lng;
                                sumber.value = 'alamat';
                                perbaruiPeta();
                                teks(
                                    'Titik diisi dari alamat. Periksa lewat Cek di Google Maps, lalu tekan Simpan lokasi.');
                            });
                            const tautan = document.createElement('a');
                            tautan.href = 'https://www.google.com/maps?q=' + h.lat + ',' + h.lng;
                            tautan.target = '_blank';
                            tautan.rel = 'noopener';
                            tautan.className = 'text-xs text-white/60 underline shrink-0';
                            tautan.textContent = 'Lihat di Maps';
                            baris.append(pilih, tautan);
                            daftar.appendChild(baris);
                        });
                        hasil.replaceChildren(daftar);
                    } catch {
                        teks('Pencarian alamat gagal. Periksa koneksi lalu coba lagi.');
                    }
                });
            })();
        </script>
    @endpush
@endsection
