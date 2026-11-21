@extends('template/icDashboardLayout')
@section('content')
    @php
        $tipeEvent = ic_tipe_event();
        $kelasInput =
            'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark';
        $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
        $kelasTombol =
            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
        $kelasKartu = 'rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-5';
        $kelasSimpan =
            'h-12 px-6 inline-flex items-center justify-center gap-2 bg-white text-black font-bold uppercase tracking-widest text-xs rounded-lg hover:bg-white/80 active:scale-95 transition';
        $dt = fn($v) => $v?->format('Y-m-d\TH:i');
        $label = trim(($event->venue ?? $event->kode) . ($event->kota ? ', ' . $event->kota : ''));
        $punyaLokasi = $event->lokasi_aktif;
        $latIsi = old('lat', $punyaLokasi ? $event->lokasi_lat : '');
        $lngIsi = old('lng', $punyaLokasi ? $event->lokasi_lng : '');
        $statusUndian =
            [
                'belum' => 'Belum diundi',
                'ditunda' => 'Ditunda admin',
                'tertunda' => 'Tertunda (data GL belum lengkap)',
                'selesai' => 'Selesai (hasil terkunci)',
            ][$event->status_undian] ?? $event->status_undian;
        $kunciUndian = $event->status_undian === 'selesai';
    @endphp

    @push('head')
        @vite('resources/js/peta-acara.js')
    @endpush

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'acara',
                'judul' => 'Atur acara',
                'deskripsi' => $label . ' · ' . $event->tanggal->locale('id')->translatedFormat('D, j M Y'),
            ])

            @include('components.dashboard.inner-circle.tab-acara', ['aktifTab' => 'pengaturan'])

            @include('components/errors')
            @include('components/success')

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('ic.admin.acara') }}#jadwal" class="{{ $kelasTombol }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke jadwal</a>
                @if ($event->bisa_checkin)
                    <a href="{{ route('ic.admin.acara.harih', ['acara' => $event->kode]) }}#qr" class="{{ $kelasTombol }}">
                        <i class="bi bi-qr-code" aria-hidden="true"></i> QR dan Hari H</a>
                @endif
            </div>

            @if ($event->bisa_checkin && !$event->lokasi_aktif)
                <div role="alert"
                    class="flex items-start gap-3 rounded-2xl border border-amber-300/20 bg-amber-300/5 px-4 py-3 text-sm text-amber-100/80">
                    <i class="bi bi-geo-alt mt-0.5 shrink-0" aria-hidden="true"></i>
                    <p>Acara ini <b>belum punya titik lokasi</b>. Pada aturan check-in yang baru, acara tanpa titik lokasi
                        tidak
                        membuka check-in. Tentukan titik dan radiusnya di bagian Lokasi di bawah.</p>
                </div>
            @endif

            {{-- ============ PENGATURAN ACARA ============ --}}
            <form method="POST" action="{{ route('ic.admin.acara.atur.simpan', $event->kode) }}"
                enctype="multipart/form-data" class="flex flex-col gap-8">
                @csrf
                @method('PUT')

                <section aria-labelledby="judul-dasar" class="{{ $kelasKartu }}">
                    <h2 id="judul-dasar" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                        <i class="bi bi-calendar-event text-white/50" aria-hidden="true"></i> Data acara
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="at-tanggal" class="{{ $kelasLabel }}">Tanggal show <span
                                    class="text-red-400">*</span></label>
                            <input type="date" id="at-tanggal" name="tanggal" required
                                value="{{ old('tanggal', $event->tanggal->format('Y-m-d')) }}" class="{{ $kelasInput }}">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-tipe" class="{{ $kelasLabel }}">Tipe acara <span
                                    class="text-red-400">*</span></label>
                            <select id="at-tipe" name="tipe" required class="{{ $kelasInput }}">
                                @foreach ($tipeEvent as $kunci => $t)
                                    <option value="{{ $kunci }}" @selected(old('tipe', $event->tipe) === $kunci)>{{ $t['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-venue" class="{{ $kelasLabel }}">Venue</label>
                            <input type="text" id="at-venue" name="venue" maxlength="80"
                                value="{{ old('venue', $event->venue) }}" class="{{ $kelasInput }}">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-kota" class="{{ $kelasLabel }}">Kota</label>
                            <input type="text" id="at-kota" name="kota" maxlength="60"
                                value="{{ old('kota', $event->kota) }}" class="{{ $kelasInput }}">
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="at-deskripsi" class="{{ $kelasLabel }}">Deskripsi singkat</label>
                        <textarea id="at-deskripsi" name="deskripsi" rows="3" maxlength="500" class="{{ $kelasInput }}"
                            placeholder="Tampil di halaman detail acara anggota (maksimal 500 karakter).">{{ old('deskripsi', $event->deskripsi) }}</textarea>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="at-poster" class="{{ $kelasLabel }}">Poster</label>
                        @if ($posterUrl)
                            <div class="flex items-center gap-4">
                                <img src="{{ $posterUrl }}" alt="Poster {{ $label }}" width="96"
                                    height="128" loading="lazy"
                                    class="w-24 aspect-9/16 object-cover rounded-lg border border-white/10">
                                <label class="flex items-center gap-2 text-sm text-white/70 min-h-11">
                                    <input type="checkbox" name="hapus_poster" value="1" class="w-4 h-4"> Hapus poster
                                    ini
                                </label>
                            </div>
                        @endif
                        <input type="file" id="at-poster" name="poster" accept="image/jpeg,image/png,image/webp"
                            class="{{ $kelasInput }}">
                        <p class="text-xs text-white/40">JPG, PNG, atau WebP, maksimal 1 MB. Otomatis diubah ke WebP dan
                            dipotong ke rasio 9:16 (tegak, seperti story); pakai gambar 9:16 supaya tidak terpotong. Bila
                            kosong,
                            halaman detail memakai ikon dan warna tipe acara.</p>
                    </div>
                </section>

                <section aria-labelledby="judul-gl" class="{{ $kelasKartu }}">
                    <h2 id="judul-gl" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                        <i class="bi bi-ticket-perforated text-white/50" aria-hidden="true"></i> GL gratis
                    </h2>
                    <p class="text-sm text-white/50">Status undian: <b class="text-white/80">{{ $statusUndian }}</b>.
                        Undian dijalankan admin dari halaman Hari H (fase berikutnya). Kuota dan waktu undian
                        @if ($kunciUndian)
                            <b>terkunci</b> karena undian sudah selesai.
                        @else
                            bisa diubah sampai undian dijalankan.
                        @endif
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="at-kuota" class="{{ $kelasLabel }}">Kuota GL</label>
                            <input type="number" inputmode="numeric" min="0"
                                max="{{ config('inner_circle.gl.kuota_maks') }}" id="at-kuota" name="kuota_gl"
                                value="{{ old('kuota_gl', $event->kuota_gl) }}" @disabled($kunciUndian)
                                class="{{ $kelasInput }}">
                            <p class="text-xs text-white/40">Kosong atau 0 = acara ini tidak punya antrean GL.</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-undian" class="{{ $kelasLabel }}">Waktu undian</label>
                            <input type="datetime-local" id="at-undian" name="undian_pada"
                                value="{{ old('undian_pada', $dt($event->undian_pada)) }}" @disabled($kunciUndian)
                                class="{{ $kelasInput }}">
                            <p class="text-xs text-white/40">Kosong = bawaan:
                                {{ $event->undian_efektif->locale('id')->translatedFormat('D, j M Y \p\u\k\u\l H.i') }} WIB
                                (hari H
                                pukul 18.00).</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-username" class="{{ $kelasLabel }}">Username acara</label>
                            <input type="text" id="at-username" name="username_acara" maxlength="60"
                                autocomplete="off" value="{{ old('username_acara', $event->username_acara) }}"
                                class="{{ $kelasInput }}">
                            <p class="text-xs text-white/40">Boleh memakai spasi, mis. “Whisnu Santika VIP”.</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-sandi" class="{{ $kelasLabel }}">Sandi acara (kode)</label>
                            {{-- Kode terlihat langsung (bukan titik-titik) supaya tim bisa membacanya di sini dan di halaman Undian GL. --}}
                            <div class="flex gap-2">
                                <input type="text" id="at-sandi" name="sandi_acara" maxlength="60"
                                    autocomplete="off" spellcheck="false" autocapitalize="off"
                                    value="{{ old('sandi_acara', $event->sandi_acara) }}"
                                    placeholder="{{ $event->sandiRusak() ? 'Tidak terbaca, isi ulang' : 'Belum diisi' }}"
                                    class="{{ $kelasInput }} font-mono tracking-wider">
                                <button type="button" data-buat-kode="#at-sandi"
                                    class="shrink-0 min-h-11 px-4 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                    Buat kode</button>
                            </div>
                            <p class="text-xs text-white/40">Huruf, angka, dan simbol tanpa spasi (minimal 4 karakter).
                                "Buat kode" mengisi kode acak 8 karakter.</p>
                            @if ($event->sandi_acara !== null)
                                <label class="flex items-center gap-2 text-xs text-white/50 min-h-11"><input
                                        type="checkbox" name="hapus_sandi" value="1" class="w-4 h-4"> Hapus
                                    sandi</label>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="at-petunjuk" class="{{ $kelasLabel }}">Petunjuk masuk untuk pemenang</label>
                        <textarea id="at-petunjuk" name="petunjuk_masuk" rows="2" maxlength="300" class="{{ $kelasInput }}"
                            placeholder="Mis. tunjukkan kartu GL di pintu VIP sebelum pukul 22.00.">{{ old('petunjuk_masuk', $event->petunjuk_masuk) }}</textarea>
                    </div>
                    @unless ($event->glLengkap())
                        @if ($event->punya_gl)
                            <p class="text-xs text-amber-200/80">Data GL belum lengkap: isi username dan sandi acara agar
                                undian bisa jalan.</p>
                        @endif
                    @endunless
                </section>

                <section aria-labelledby="judul-checkin" class="{{ $kelasKartu }}">
                    <h2 id="judul-checkin" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                        <i class="bi bi-clock-history text-white/50" aria-hidden="true"></i> Check-in dan poin
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="at-mulai" class="{{ $kelasLabel }}">Check-in dibuka</label>
                            <input type="datetime-local" id="at-mulai" name="jendela_mulai"
                                value="{{ old('jendela_mulai', $dt($event->jendela_mulai)) }}"
                                class="{{ $kelasInput }}">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-selesai" class="{{ $kelasLabel }}">Check-in ditutup</label>
                            <input type="datetime-local" id="at-selesai" name="jendela_selesai"
                                value="{{ old('jendela_selesai', $dt($event->jendela_selesai)) }}"
                                class="{{ $kelasInput }}">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-poin-gl" class="{{ $kelasLabel }}">Poin GL (scan QR)</label>
                            <input type="number" inputmode="numeric" min="1" max="1000000" id="at-poin-gl"
                                name="poin_gl" value="{{ old('poin_gl', $event->poin_gl) }}"
                                class="{{ $kelasInput }}">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="at-poin-mandiri" class="{{ $kelasLabel }}">Poin check-in mandiri</label>
                            <input type="number" inputmode="numeric" min="1" max="1000000"
                                id="at-poin-mandiri" name="poin_mandiri"
                                value="{{ old('poin_mandiri', $event->poin_mandiri) }}" class="{{ $kelasInput }}">
                        </div>
                    </div>
                    <p class="text-xs text-white/40">Kosongkan semuanya untuk memakai bawaan
                        ({{ app(\App\Services\InnerCircle\IcAturan::class)->ringkasan() }}). Nilai poin per acara mulai
                        dipakai
                        setelah aturan check-in baru diaktifkan (Fase 5).</p>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="{{ $kelasSimpan }}"><i class="bi bi-check2" aria-hidden="true"></i>
                        Simpan
                        pengaturan</button>
                </div>
            </form>

            {{-- ============ LOKASI + PETA ============ --}}
            <section id="lokasi" aria-labelledby="judul-lokasi" class="scroll-mt-8 flex flex-col gap-4">
                <h2 id="judul-lokasi" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-geo-alt text-white/50" aria-hidden="true"></i> Lokasi check-in
                </h2>
                @if ($event->bisa_checkin)
                    <div data-peta-kartu data-url-cari="{{ route('ic.admin.acara.lokasi.cari', $event->kode) }}"
                        class="{{ $kelasKartu }}">
                        <div id="peta-acara"
                            class="w-full h-80 rounded-2xl overflow-hidden border border-white/10 bg-white/5"
                            role="application" aria-label="Peta titik check-in"></div>
                        <p class="text-xs text-white/40">Klik peta untuk menaruh titik. Radius menentukan seberapa jauh
                            dari titik
                            anggota masih boleh check-in ({{ config('inner_circle.lokasi.radius_min_m') }} sampai
                            {{ number_format(config('inner_circle.lokasi.radius_maks_m'), 0, ',', '.') }} m).</p>

                        <form method="POST" action="{{ route('ic.admin.acara.lokasi', $event->kode) }}"
                            class="flex flex-col gap-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="kembali" value="atur">
                            <input type="hidden" name="sumber" id="lokasi-sumber"
                                value="{{ old('sumber', $event->lokasi_sumber ?: 'manual') }}">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                                </div>
                                <div class="flex flex-col gap-1.5 min-w-0">
                                    <label for="lokasi-radius" class="{{ $kelasLabel }}">Radius (m)</label>
                                    <input id="lokasi-radius" name="radius" type="number" inputmode="numeric"
                                        min="{{ config('inner_circle.lokasi.radius_min_m') }}"
                                        max="{{ config('inner_circle.lokasi.radius_maks_m') }}"
                                        value="{{ old('radius', $event->lokasi_radius ?: app(\App\Services\InnerCircle\IcAturan::class)->radiusBawaan()) }}"
                                        class="{{ $kelasInput }}" required>
                                    @error('radius')
                                        <p class="text-xs text-red-300" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5 min-w-0">
                                <label for="lokasi-petugas" class="{{ $kelasLabel }}">Nama petugas (opsional)</label>
                                <input id="lokasi-petugas" name="petugas" maxlength="40" autocomplete="off"
                                    value="{{ old('petugas', $event->lokasi_diatur_oleh) }}"
                                    class="{{ $kelasInput }}">
                            </div>
                            @if ($punyaLokasi)
                                <label class="flex items-start gap-2 text-sm text-white/60 min-h-11">
                                    <input type="checkbox" name="konfirmasi_geser" value="1" class="w-4 h-4 mt-1">
                                    <span>Saya yakin memindahkan titik yang sudah tersimpan bila jaraknya lebih dari
                                        {{ number_format(config('inner_circle.lokasi.geser_konfirmasi_m'), 0, ',', '.') }}
                                        m.</span>
                                </label>
                            @endif
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="{{ $kelasSimpan }}"><i class="bi bi-geo-alt-fill"
                                        aria-hidden="true"></i>
                                    Simpan lokasi</button>
                                <button type="button" data-lokasi-hp class="{{ $kelasTombol }}"><i class="bi bi-phone"
                                        aria-hidden="true"></i> Pakai lokasi HP</button>
                            </div>
                        </form>

                        <div class="flex flex-col gap-2">
                            <label for="lokasi-alamat" class="{{ $kelasLabel }}">Cari dari alamat</label>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <input id="lokasi-alamat" maxlength="150" autocomplete="off"
                                    placeholder="{{ trim(($event->venue ?? '') . ', ' . ($event->kota ?? ''), ', ') }}"
                                    class="{{ $kelasInput }}">
                                <button type="button" data-lokasi-cari class="{{ $kelasTombol }} shrink-0"><i
                                        class="bi bi-search" aria-hidden="true"></i> Cari</button>
                            </div>
                            <div id="lokasi-hasil" role="status" aria-live="polite" class="text-sm text-white/60">
                            </div>
                        </div>

                        @if ($punyaLokasi)
                            <form method="POST" action="{{ route('ic.admin.acara.lokasi.hapus', $event->kode) }}"
                                data-ic-konfirmasi data-judul="Hapus titik lokasi?"
                                data-teks="Acara ini tidak lagi punya titik lokasi." data-tombol="Ya, hapus">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="kembali" value="atur">
                                <button type="submit"
                                    class="text-xs text-red-300 underline underline-offset-4 hover:text-red-200 min-h-11">Hapus
                                    titik
                                    lokasi</button>
                            </form>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-white/50">Acara privat atau tanpa venue tidak memakai check-in, jadi tidak punya
                        titik lokasi.
                        Isi venue di atas bila acara ini perlu check-in.</p>
                @endif
            </section>
        </div>
    </div>
    @push('scripts')
        <script>
            // Konfirmasi sebelum hapus titik lokasi.
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
        </script>
    @endpush
@endsection
