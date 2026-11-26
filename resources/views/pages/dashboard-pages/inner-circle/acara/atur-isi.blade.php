{{--
    Isi popup "Atur acara" (CMS Inner Circle > Acara > Pengaturan). Tampil di dalam <dialog id="dialogAtur"> di halaman Pengaturan.
    Dimuat lewat fetch saat tombol Atur ditekan (acaraController@atur), atau dirender langsung bila form ini gagal
    divalidasi, supaya isian dan pesan galat tidak hilang. Empat bagian dalam satu popup: Data acara, GL gratis,
    Check-in dan poin (satu form), dan Lokasi check-in (form sendiri, karena simpan dan validasinya terpisah).
    Variabel: $event, $posterUrl. Perilaku (tab, peta, saran alamat, penjaga "belum disimpan") ada di resources/js/atur-acara.js.
--}}
@php
    $tipeEvent = ic_tipe_event();
    $kelasInput =
        'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark aria-invalid:border-red-400';
    $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
    $kelasBantu = 'text-xs text-white/60';
    $kelasTombol =
        'min-h-11 px-4 inline-flex items-center justify-center gap-2 rounded-lg border border-white/20 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
    $kelasSimpan =
        'min-h-11 px-5 inline-flex items-center justify-center gap-2 bg-red-950 text-white font-bold uppercase tracking-widest text-xs rounded-lg hover:bg-red-900 active:scale-95 transition';
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
    $tab = [
        'data' => ['Data acara', 'bi-calendar-event'],
        'gl' => ['GL gratis', 'bi-ticket-perforated'],
        'checkin' => ['Check-in dan poin', 'bi-clock-history'],
        'lokasi' => ['Lokasi check-in', 'bi-geo-alt'],
    ];
@endphp

<div data-atur-akar data-kode="{{ $event->kode }}"
    data-url-cari="{{ route('ic.admin.acara.lokasi.cari', $event->kode) }}"
    data-url-saran="{{ route('ic.admin.acara.lokasi.saran', $event->kode) }}" data-csrf="{{ csrf_token() }}"
    class="flex flex-col min-h-0 flex-1">

    {{-- Kepala --}}
    <div class="flex items-start justify-between gap-3 px-4 sm:px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/60">WS Inner Circle</p>
            <h3 id="dialogAturJudul" class="font-bold uppercase tracking-wide text-lg">Atur acara</h3>
            <p class="text-sm text-white/72 wrap-break-word">{{ $label }} ·
                {{ $event->tanggal->locale('id')->translatedFormat('D, j M Y') }}</p>
        </div>
        <button type="button" data-atur-tutup aria-label="Tutup"
            class="w-11 h-11 shrink-0 inline-flex items-center justify-center rounded-full text-white/60 hover:text-white hover:bg-white/10 transition">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Tab bagian --}}
    <div class="px-4 sm:px-6 py-3 border-b border-white/10 shrink-0">
        <div role="tablist" aria-label="Bagian pengaturan acara"
            class="flex gap-2 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible sm:pb-0">
            @foreach ($tab as $kunci => [$nama, $ikon])
                <button type="button" role="tab" id="atur-tab-{{ $kunci }}"
                    data-atur-tab="{{ $kunci }}" aria-controls="atur-panel-{{ $kunci }}"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}"
                    class="cms-chip shrink-0 whitespace-nowrap rounded-full border border-white/20 text-sm font-semibold text-white/70 hover:bg-white/10 transition-colors aria-selected:bg-white aria-selected:text-black aria-selected:border-white">
                    <i class="bi {{ $ikon }}" aria-hidden="true"></i> {{ $nama }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Isi (menggulir sendiri; kepala, tab, dan tombol simpan tetap terlihat, terutama di HP) --}}
    <div class="flex-1 min-h-0 overflow-y-auto overscroll-none p-4 sm:p-6 flex flex-col gap-5">
        @if ($errors->any())
            <div role="alert" data-atur-galat
                class="rounded-2xl border border-red-400/50 bg-red-400/10 px-4 py-3 text-sm text-red-200">
                <p class="font-semibold mb-1">Belum bisa disimpan:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $galat)
                        <li>{{ $galat }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($event->bisa_checkin && !$event->lokasi_aktif)
            <div role="note"
                class="flex items-start gap-3 rounded-2xl border border-amber-300/40 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">
                <i class="bi bi-geo-alt mt-0.5 shrink-0" aria-hidden="true"></i>
                <p>Acara ini <b>belum punya titik lokasi</b>. Pada aturan check-in yang baru, acara tanpa titik lokasi
                    tidak membuka check-in. Tentukan titik dan radiusnya di tab Lokasi check-in.</p>
            </div>
        @endif

        {{-- ============ FORM PENGATURAN: Data acara, GL gratis, Check-in dan poin ============ --}}
        <form id="form-atur" method="POST" action="{{ route('ic.admin.acara.atur.simpan', $event->kode) }}"
            enctype="multipart/form-data" class="flex flex-col gap-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="_atur" value="{{ $event->kode }}">

            <section id="atur-panel-data" data-atur-panel="data" role="tabpanel" aria-labelledby="atur-tab-data"
                class="flex flex-col gap-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-tanggal" class="{{ $kelasLabel }}">Tanggal show <span
                                class="text-red-400">*</span></label>
                        <input type="date" id="at-tanggal" name="tanggal" required
                            value="{{ old('tanggal', $event->tanggal->format('Y-m-d')) }}"
                            @error('tanggal') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-tipe" class="{{ $kelasLabel }}">Tipe acara <span
                                class="text-red-400">*</span></label>
                        <select id="at-tipe" name="tipe" required @error('tipe') aria-invalid="true" @enderror
                            class="{{ $kelasInput }}">
                            @foreach ($tipeEvent as $kunci => $t)
                                <option value="{{ $kunci }}" @selected(old('tipe', $event->tipe) === $kunci)>{{ $t['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-venue" class="{{ $kelasLabel }}">Venue</label>
                        <input type="text" id="at-venue" name="venue" maxlength="80"
                            value="{{ old('venue', $event->venue) }}" @error('venue') aria-invalid="true" @enderror
                            class="{{ $kelasInput }}">
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-kota" class="{{ $kelasLabel }}">Kota</label>
                        <input type="text" id="at-kota" name="kota" maxlength="60"
                            value="{{ old('kota', $event->kota) }}" @error('kota') aria-invalid="true" @enderror
                            class="{{ $kelasInput }}">
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="at-deskripsi" class="{{ $kelasLabel }}">Deskripsi singkat</label>
                    <textarea id="at-deskripsi" name="deskripsi" rows="3" maxlength="500"
                        @error('deskripsi') aria-invalid="true" @enderror class="{{ $kelasInput }}"
                        placeholder="Tampil di halaman detail acara anggota (maksimal 500 karakter).">{{ old('deskripsi', $event->deskripsi) }}</textarea>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="at-poster" class="{{ $kelasLabel }}">Poster</label>
                    @if ($posterUrl)
                        <div class="flex items-center gap-4">
                            <img src="{{ $posterUrl }}" alt="Poster {{ $label }}" width="96"
                                height="171" loading="lazy"
                                class="w-24 aspect-9/16 object-cover rounded-lg border border-white/10">
                            <label class="flex items-center gap-2 text-sm text-white/72 min-h-11">
                                <input type="checkbox" name="hapus_poster" value="1" class="w-5 h-5"> Hapus
                                poster
                                ini
                            </label>
                        </div>
                    @endif
                    <input type="file" id="at-poster" name="poster" accept="image/jpeg,image/png,image/webp"
                        @error('poster') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                    <p class="{{ $kelasBantu }}">JPG, PNG, atau WebP, maksimal 1 MB. Otomatis diubah ke WebP dan
                        dipotong ke rasio 9:16 (tegak, seperti story); pakai gambar 9:16 supaya tidak terpotong. Bila
                        kosong, halaman detail memakai ikon dan warna tipe acara.</p>
                </div>
            </section>

            <section id="atur-panel-gl" data-atur-panel="gl" role="tabpanel" aria-labelledby="atur-tab-gl" hidden
                class="flex flex-col gap-5">
                <p class="text-sm text-white/72">Status undian: <b class="text-white">{{ $statusUndian }}</b>.
                    Undian dijalankan admin dari halaman Hari H. Kuota dan waktu undian
                    @if ($kunciUndian)
                        <b>terkunci</b> karena undian sudah selesai.
                    @else
                        bisa diubah sampai undian dijalankan.
                    @endif
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-kuota" class="{{ $kelasLabel }}">Kuota GL</label>
                        <input type="number" inputmode="numeric" min="0"
                            max="{{ config('inner_circle.gl.kuota_maks') }}" id="at-kuota" name="kuota_gl"
                            value="{{ old('kuota_gl', $event->kuota_gl) }}" @disabled($kunciUndian)
                            @error('kuota_gl') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                        <p class="{{ $kelasBantu }}">Kosong atau 0 = acara ini tidak punya antrean GL.</p>
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-undian" class="{{ $kelasLabel }}">Waktu undian</label>
                        <input type="datetime-local" id="at-undian" name="undian_pada"
                            value="{{ old('undian_pada', $dt($event->undian_pada)) }}" @disabled($kunciUndian)
                            @error('undian_pada') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                        <p class="{{ $kelasBantu }}">Kosong = bawaan:
                            {{ $event->undian_efektif->locale('id')->translatedFormat('D, j M Y \p\u\k\u\l H.i') }} WIB
                            (hari H pukul 18.00).</p>
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-username" class="{{ $kelasLabel }}">Username acara</label>
                        <input type="text" id="at-username" name="username_acara" maxlength="60"
                            autocomplete="off" value="{{ old('username_acara', $event->username_acara) }}"
                            @error('username_acara') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                        <p class="{{ $kelasBantu }}">Boleh memakai spasi, mis. “Whisnu Santika VIP”.</p>
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-sandi" class="{{ $kelasLabel }}">Sandi acara (kode)</label>
                        {{-- Kode terlihat langsung (bukan titik-titik) supaya tim bisa membacanya di sini dan di halaman Undian GL. --}}
                        <div class="flex gap-2">
                            <input type="text" id="at-sandi" name="sandi_acara" maxlength="60"
                                autocomplete="off" spellcheck="false" autocapitalize="off"
                                value="{{ old('sandi_acara', $event->sandi_acara) }}"
                                placeholder="{{ $event->sandiRusak() ? 'Tidak terbaca, isi ulang' : 'Belum diisi' }}"
                                @error('sandi_acara') aria-invalid="true" @enderror
                                class="{{ $kelasInput }} font-mono tracking-wider">
                            <button type="button" data-buat-kode="#at-sandi"
                                class="shrink-0 {{ $kelasTombol }}">Buat
                                kode</button>
                        </div>
                        <p class="{{ $kelasBantu }}">Huruf, angka, dan simbol tanpa spasi (minimal 4 karakter).
                            "Buat
                            kode" mengisi kode acak 8 karakter.</p>
                        @if ($event->sandi_acara !== null)
                            <label class="flex items-center gap-2 text-sm text-white/72 min-h-11"><input
                                    type="checkbox" name="hapus_sandi" value="1" class="w-5 h-5"> Hapus
                                sandi</label>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="at-petunjuk" class="{{ $kelasLabel }}">Petunjuk masuk untuk pemenang</label>
                    <textarea id="at-petunjuk" name="petunjuk_masuk" rows="2" maxlength="300"
                        @error('petunjuk_masuk') aria-invalid="true" @enderror class="{{ $kelasInput }}"
                        placeholder="Mis. tunjukkan kartu GL di pintu VIP sebelum pukul 22.00.">{{ old('petunjuk_masuk', $event->petunjuk_masuk) }}</textarea>
                </div>
                @unless ($event->glLengkap())
                    @if ($event->punya_gl)
                        <p class="text-sm text-amber-100 rounded-xl border border-amber-300/40 bg-amber-300/10 px-3 py-2">
                            Data GL belum lengkap: isi username dan sandi acara agar undian bisa jalan.</p>
                    @endif
                @endunless
            </section>

            <section id="atur-panel-checkin" data-atur-panel="checkin" role="tabpanel"
                aria-labelledby="atur-tab-checkin" hidden class="flex flex-col gap-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-mulai" class="{{ $kelasLabel }}">Check-in dibuka</label>
                        <input type="datetime-local" id="at-mulai" name="jendela_mulai"
                            value="{{ old('jendela_mulai', $dt($event->jendela_mulai)) }}"
                            @error('jendela_mulai') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-selesai" class="{{ $kelasLabel }}">Check-in ditutup</label>
                        <input type="datetime-local" id="at-selesai" name="jendela_selesai"
                            value="{{ old('jendela_selesai', $dt($event->jendela_selesai)) }}"
                            @error('jendela_selesai') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-poin-gl" class="{{ $kelasLabel }}">Poin GL (scan QR)</label>
                        <input type="number" inputmode="numeric" min="1" max="1000000" id="at-poin-gl"
                            name="poin_gl" value="{{ old('poin_gl', $event->poin_gl) }}"
                            @error('poin_gl') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0">
                        <label for="at-poin-mandiri" class="{{ $kelasLabel }}">Poin check-in mandiri</label>
                        <input type="number" inputmode="numeric" min="1" max="1000000"
                            id="at-poin-mandiri" name="poin_mandiri"
                            value="{{ old('poin_mandiri', $event->poin_mandiri) }}"
                            @error('poin_mandiri') aria-invalid="true" @enderror class="{{ $kelasInput }}">
                    </div>
                </div>
                <p class="{{ $kelasBantu }}">Kosongkan semuanya untuk memakai bawaan
                    ({{ app(\App\Services\InnerCircle\IcAturan::class)->ringkasan() }}).</p>
            </section>
        </form>

        {{-- ============ LOKASI CHECK-IN (form sendiri) ============ --}}
        <section id="atur-panel-lokasi" data-atur-panel="lokasi" role="tabpanel" aria-labelledby="atur-tab-lokasi"
            hidden class="flex flex-col gap-5">
            @if ($event->bisa_checkin)
                <div data-atur-peta class="flex flex-col gap-4">
                    <div data-atur-kotor-lokasi hidden role="note"
                        class="rounded-xl border border-amber-300/40 bg-amber-300/10 px-3 py-2 text-sm text-amber-100">
                        Ada perubahan di tab lain yang belum disimpan. Menyimpan lokasi akan membuangnya, jadi simpan
                        pengaturannya dulu bila masih diperlukan.</div>

                    {{-- Cari dari alamat (saran muncul saat mengetik, seperti di peta online) --}}
                    <div class="flex flex-col gap-2">
                        <label for="lokasi-alamat" class="{{ $kelasLabel }}">Cari dari alamat</label>
                        <div class="relative">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <input id="lokasi-alamat" maxlength="150" autocomplete="off" role="combobox"
                                    aria-autocomplete="list" aria-expanded="false" aria-controls="lokasi-saran"
                                    placeholder="{{ trim(($event->venue ?? '') . ', ' . ($event->kota ?? ''), ', ') ?: 'Ketik nama venue atau alamat lengkap' }}"
                                    class="{{ $kelasInput }}">
                                <button type="button" data-lokasi-cari class="{{ $kelasTombol }} shrink-0"><i
                                        class="bi bi-search" aria-hidden="true"></i> Cari</button>
                            </div>
                            <ul id="lokasi-saran" role="listbox" aria-label="Saran alamat" hidden
                                class="absolute z-20 left-0 right-0 mt-1 max-h-72 overflow-y-auto rounded-xl border border-white/20 bg-black shadow-xl p-1">
                            </ul>
                        </div>
                        <div id="lokasi-hasil" role="status" aria-live="polite" class="text-sm text-white/72">
                        </div>
                        <p class="{{ $kelasBantu }}">Ketik nama venue atau alamat; pilih saran yang benar, lalu
                            periksa
                            posisinya di peta. Bisa juga menempel koordinat atau tautan Google Maps panjang. Data
                            peta dari OpenStreetMap, jadi venue yang belum terdaftar di sana tidak akan muncul:
                            klik langsung di peta.</p>
                    </div>

                    <div id="peta-acara"
                        class="isolate w-full h-72 sm:h-80 rounded-2xl overflow-hidden border border-white/10 bg-white/5"
                        role="application" aria-label="Peta titik check-in"></div>
                    <p class="{{ $kelasBantu }}">Klik peta untuk menaruh titik. Radius menentukan seberapa jauh dari
                        titik anggota masih boleh check-in ({{ config('inner_circle.lokasi.radius_min_m') }} sampai
                        {{ number_format(config('inner_circle.lokasi.radius_maks_m'), 0, ',', '.') }} m).</p>

                    <form id="form-lokasi" method="POST"
                        action="{{ route('ic.admin.acara.lokasi', $event->kode) }}" class="flex flex-col gap-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="kembali" value="atur">
                        <input type="hidden" name="_atur" value="{{ $event->kode }}">
                        <input type="hidden" name="sumber" id="lokasi-sumber"
                            value="{{ old('sumber', $event->lokasi_sumber ?: 'manual') }}">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="flex flex-col gap-1.5 min-w-0">
                                <label for="lokasi-lat" class="{{ $kelasLabel }}">Latitude</label>
                                <input id="lokasi-lat" name="lat" inputmode="decimal" autocomplete="off"
                                    value="{{ $latIsi }}" placeholder="-6.2088"
                                    @error('lat') aria-invalid="true" @enderror class="{{ $kelasInput }}" required>
                            </div>
                            <div class="flex flex-col gap-1.5 min-w-0">
                                <label for="lokasi-lng" class="{{ $kelasLabel }}">Longitude</label>
                                <input id="lokasi-lng" name="lng" inputmode="decimal" autocomplete="off"
                                    value="{{ $lngIsi }}" placeholder="106.8456"
                                    @error('lng') aria-invalid="true" @enderror class="{{ $kelasInput }}" required>
                            </div>
                            <div class="flex flex-col gap-1.5 min-w-0">
                                <label for="lokasi-radius" class="{{ $kelasLabel }}">Radius (m)</label>
                                <input id="lokasi-radius" name="radius" type="number" inputmode="numeric"
                                    min="{{ config('inner_circle.lokasi.radius_min_m') }}"
                                    max="{{ config('inner_circle.lokasi.radius_maks_m') }}"
                                    value="{{ old('radius', $event->lokasi_radius ?: app(\App\Services\InnerCircle\IcAturan::class)->radiusBawaan()) }}"
                                    @error('radius') aria-invalid="true" @enderror class="{{ $kelasInput }}"
                                    required>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1.5 min-w-0">
                            <label for="lokasi-petugas" class="{{ $kelasLabel }}">Nama petugas (opsional)</label>
                            <input id="lokasi-petugas" name="petugas" maxlength="40" autocomplete="off"
                                value="{{ old('petugas', $event->lokasi_diatur_oleh) }}"
                                class="{{ $kelasInput }}">
                        </div>
                        @if ($punyaLokasi)
                            <label class="flex items-start gap-2 text-sm text-white/72 min-h-11">
                                <input type="checkbox" name="konfirmasi_geser" value="1"
                                    class="w-5 h-5 mt-0.5">
                                <span>Saya yakin memindahkan titik yang sudah tersimpan bila jaraknya lebih dari
                                    {{ number_format(config('inner_circle.lokasi.geser_konfirmasi_m'), 0, ',', '.') }}
                                    m.</span>
                            </label>
                        @endif
                        <div class="flex flex-wrap gap-3">
                            <button type="button" data-lokasi-hp class="{{ $kelasTombol }}"><i
                                    class="bi bi-phone" aria-hidden="true"></i> Pakai lokasi HP</button>
                            <a href="#" data-lokasi-gmaps target="_blank" rel="noopener noreferrer"
                                class="{{ $kelasTombol }}"><i class="bi bi-box-arrow-up-right"
                                    aria-hidden="true"></i> Periksa di Google Maps</a>
                        </div>
                    </form>

                    @if ($punyaLokasi)
                        <form method="POST" action="{{ route('ic.admin.acara.lokasi.hapus', $event->kode) }}"
                            data-swal-confirm data-confirm-tone="bahaya" data-confirm-loading="Menghapus titik..."
                            data-confirm-title="Hapus titik lokasi?"
                            data-confirm-message="Check-in acara ini tidak lagi memeriksa lokasi sampai titik baru disimpan."
                            data-confirm-button="Ya, hapus titik">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="kembali" value="atur">
                            <button type="submit"
                                class="text-sm text-red-300 underline underline-offset-4 hover:text-red-200 min-h-11">Hapus
                                titik lokasi</button>
                        </form>
                    @endif
                </div>
            @else
                <p class="text-sm text-white/72">Acara privat atau tanpa venue tidak memakai check-in, jadi tidak
                    punya titik lokasi. Isi venue di tab Data acara bila acara ini perlu check-in.</p>
            @endif
        </section>
    </div>

    {{-- Tombol tetap terlihat di bawah (tombol simpan menunjuk ke form lewat atribut form=) --}}
    <div
        class="flex flex-wrap items-center justify-between gap-2 px-4 sm:px-6 py-3 border-t border-white/10 bg-white/3 shrink-0">
        <p data-atur-kotor hidden class="text-xs font-semibold text-amber-200">Ada perubahan yang belum disimpan.</p>
        <div class="flex flex-wrap gap-2 ml-auto">
            <button type="button" data-atur-tutup
                class="min-h-11 px-5 rounded-lg border border-white/20 text-sm font-semibold text-white/72 hover:bg-white/10 hover:text-white transition-colors">Tutup</button>
            <button type="submit" form="form-atur" data-atur-simpan="pengaturan" class="{{ $kelasSimpan }}"><i
                    class="bi bi-check2" aria-hidden="true"></i> Simpan pengaturan</button>
            @if ($event->bisa_checkin)
                <button type="submit" form="form-lokasi" data-atur-simpan="lokasi" hidden
                    class="{{ $kelasSimpan }}"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> Simpan
                    lokasi</button>
            @endif
        </div>
    </div>
</div>
