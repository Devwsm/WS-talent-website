@extends('template/dashboardLayout')
@section('content')
    @php
        // Isi awal form dan preview: kosong, dengan teks pengganti di preview (mirip form News).
        $kosong = (object) [
            'tag' => 'KATEGORI • PENGUMUMAN',
            'judul' => 'Judul pengumuman',
            'isi' => 'Isi pengumuman tampil di sini.',
            'cta_label' => 'Lihat detail',
            'cta_tujuan' => 'ic.checkin',
            'ikon' => 'sparkles',
            'warna' => 'kuning',
            'terbit_pada' => \Carbon\Carbon::today(),
            'unggulan' => false,
        ];

        $kelasInput =
            'bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
        $labelKelas = 'text-sm font-semibold uppercase tracking-widest text-white/60';
        $ctaPengumuman = config('inner_circle.cta_pengumuman');
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.tabs', [
                'aktif' => 'pengumuman',
                'judul' => 'Pengumuman',
                'deskripsi' => 'Tulis kabar resmi untuk anggota. Satu pengumuman bisa dijadikan Kabar Pilihan.',
            ])

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start" id="form-pengumuman-bagian">

                {{-- FORM --}}
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 scroll-mt-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi bi-plus-circle text-white/50" id="form-ikon" aria-hidden="true"></i>
                        <span id="form-judul">Tambah pengumuman</span>
                    </h2>

                    <form id="form-pengumuman" data-ic-segera="Simpan pengumuman aktif di Fase 6."
                        class="flex flex-col gap-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-tag" class="{{ $labelKelas }}">Kategori</label>
                                <input type="text" id="p-tag" name="tag" maxlength="40"
                                    placeholder="Contoh: MUSIK • PENGUMUMAN" class="{{ $kelasInput }}" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-terbit" class="{{ $labelKelas }}">Tanggal terbit <span
                                        class="text-red-400">*</span></label>
                                <input type="date" id="p-terbit" name="terbit_pada" required
                                    value="{{ $kosong->terbit_pada->format('Y-m-d') }}"
                                    class="{{ $kelasInput }} scheme-dark" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="p-judul" class="{{ $labelKelas }}">Judul <span
                                    class="text-red-400">*</span></label>
                            <input type="text" id="p-judul" name="judul" maxlength="80" required
                                placeholder="Contoh: Sampai jumpa di pertunjukan berikutnya" class="{{ $kelasInput }}" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="p-isi" class="{{ $labelKelas }}">Isi <span
                                    class="text-red-400">*</span></label>
                            <textarea id="p-isi" name="isi" rows="4" maxlength="220" required
                                placeholder="Tulis kabar singkat untuk anggota..." class="{{ $kelasInput }}"></textarea>
                            <p class="text-xs text-white/30"><span id="p-isi-hitung">0</span> / 220 karakter</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-ikon" class="{{ $labelKelas }}">Ikon</label>
                                <select id="p-ikon" name="ikon" class="{{ $kelasInput }} scheme-dark">
                                    @foreach (config('inner_circle.ikon_pilihan') as $kunci => $label)
                                        <option class="bg-neutral-900" value="{{ $kunci }}"
                                            @selected($kunci === $kosong->ikon)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-warna" class="{{ $labelKelas }}">Warna</label>
                                <select id="p-warna" name="warna" class="{{ $kelasInput }} scheme-dark">
                                    @foreach (config('inner_circle.warna_kartu') as $kunci => $label)
                                        <option class="bg-neutral-900" value="{{ $kunci }}"
                                            @selected($kunci === $kosong->warna)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-cta-label" class="{{ $labelKelas }}">Teks tombol <span
                                        class="text-red-400">*</span></label>
                                <input type="text" id="p-cta-label" name="cta_label" maxlength="30" required
                                    placeholder="Contoh: Lihat jadwal acara" class="{{ $kelasInput }}" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-cta-tujuan" class="{{ $labelKelas }}">Tombol menuju</label>
                                <select id="p-cta-tujuan" name="cta_tujuan" class="{{ $kelasInput }} scheme-dark">
                                    @foreach ($ctaPengumuman as $route => $label)
                                        <option class="bg-neutral-900" value="{{ $route }}">{{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label
                            class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/2 p-4 cursor-pointer">
                            <input type="checkbox" id="p-unggulan" name="unggulan" value="1"
                                class="mt-1 w-5 h-5 shrink-0 accent-red-900" />
                            <span>
                                <span class="block text-sm font-semibold">Jadikan Kabar Pilihan</span>
                                <span class="block text-xs text-white/40 mt-0.5">Tampil sebagai kartu besar di atas daftar.
                                    Hanya satu yang bisa jadi Kabar Pilihan; yang lama otomatis menjadi pengumuman
                                    biasa.</span>
                            </span>
                        </label>

                        <div class="flex flex-col sm:flex-row gap-2 mt-2">
                            <button type="submit" id="p-kirim"
                                class="flex-1 text-white font-bold uppercase tracking-widest p-3 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                Terbitkan
                            </button>
                            <button type="button" id="p-batal"
                                class="hidden h-12 px-5 rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                Batal ubah
                            </button>
                        </div>
                    </form>
                </div>

                {{-- PREVIEW: tampil sebagai Kabar Pilihan atau item biasa, sesuai kotak centang --}}
                <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                        <i class="bi bi-eye text-white/40" aria-hidden="true"></i>
                        <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di Komunitas › Pengumuman
                            WS</span>
                    </div>
                    <div class="ic-app p-5" inert>
                        <div id="preview-unggulan" hidden>
                            <x-inner-circle.pengumuman-unggulan :pengumuman="$kosong" />
                        </div>
                        <div id="preview-item">
                            <x-inner-circle.item-pengumuman :pengumuman="$kosong" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ DAFTAR TERSIMPAN ============ --}}
            <section aria-labelledby="judul-daftar-pengumuman" class="flex flex-col gap-4">
                <h2 id="judul-daftar-pengumuman"
                    class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-megaphone-fill text-white/50" aria-hidden="true"></i>
                    Pengumuman tersimpan ({{ count($daftar) }})
                </h2>

                @if (empty($daftar))
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada pengumuman yang ditambahkan.',
                    ])
                @else
                    <ul class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                        @foreach ($daftar as $p)
                            @php
                                $ubah = [
                                    'tag' => $p->tag,
                                    'terbit_pada' => $p->terbit_pada->format('Y-m-d'),
                                    'judul' => $p->judul,
                                    'isi' => $p->isi,
                                    'ikon' => $p->ikon,
                                    'warna' => $p->warna,
                                    'cta_label' => $p->cta_label,
                                    'cta_tujuan' => $p->cta_tujuan,
                                    'unggulan' => $p->unggulan,
                                ];
                            @endphp
                            <li
                                class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/3 p-3 transition-colors hover:border-white/20 min-w-0">
                                <span class="ic-app ic-klaim__bulat ic-warna-{{ $p->warna }} shrink-0">
                                    <x-inner-circle.ikon :nama="$p->ikon" kelas="ic-ikon--kecil" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] mb-0.5">
                                        <span class="text-white/50 truncate">{{ $p->tag }}</span>
                                        <span
                                            class="text-red-400 shrink-0">{{ $p->terbit_pada->locale('id')->translatedFormat('j M Y') }}</span>
                                        @if ($p->unggulan)
                                            <span
                                                class="shrink-0 rounded-full border border-amber-300/30 bg-amber-300/10 px-2 py-0.5 font-semibold text-amber-200">Kabar
                                                Pilihan</span>
                                        @endif
                                    </div>
                                    <p class="font-semibold text-sm line-clamp-1">{{ $p->judul }}</p>
                                    <p class="text-xs text-white/40 line-clamp-1 mt-0.5">{{ $p->isi }}</p>
                                </div>
                                <div class="flex items-center shrink-0">
                                    <button type="button" data-ubah="{{ json_encode($ubah) }}"
                                        aria-label="Ubah pengumuman {{ $p->judul }}"
                                        class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-white transition">
                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" data-ic-segera="Hapus pengumuman aktif di Fase 6."
                                        aria-label="Hapus pengumuman {{ $p->judul }}"
                                        class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-red-400 transition">
                                        <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            window.icIkonPeta = @json($ikonPeta);

            const KOSONG = @json($kosong);
            const $ = (id) => document.getElementById(id);
            const form = $('form-pengumuman');

            const unggulan = $('preview-unggulan');
            const item = $('preview-item');

            // Ganti teks tombol (CTA) tanpa menghapus ikon panahnya.
            function setTombol(a, teks) {
                a.textContent = teks + ' ';
                a.insertAdjacentHTML('beforeend', '<i class="bi bi-arrow-up-right" aria-hidden="true"></i>');
            }

            function perbaruiPreview() {
                const isi = (id, pengganti) => $(id).value.trim() || pengganti;
                const tag = isi('p-tag', KOSONG.tag);
                const judul = isi('p-judul', KOSONG.judul);
                const teks = isi('p-isi', KOSONG.isi);
                const label = isi('p-cta-label', KOSONG.cta_label);
                const ikon = $('p-ikon').value;
                const warna = $('p-warna').value;
                const besar = $('p-unggulan').checked;

                unggulan.hidden = !besar;
                item.hidden = besar;

                unggulan.querySelector('.ic-unggulan__judul').textContent = judul;
                unggulan.querySelector('.ic-unggulan__isi').textContent = teks;
                setTombol(unggulan.querySelector('.ic-tombol'), label);

                item.querySelector('.ic-pengumuman__meta').textContent = `${tag} · ${window.icTanggal($('p-terbit').value)}`;
                item.querySelector('.ic-pengumuman__judul').textContent = judul;
                item.querySelector('.ic-pengumuman__isi').textContent = teks;
                setTombol(item.querySelector('.ic-tombol'), label);

                const bulat = item.querySelector('.ic-pengumuman__ikon');
                bulat.className = bulat.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${warna}`;
                bulat.replaceChildren(window.icBuatIkon(ikon, 'ic-ikon--kecil'));

                $('p-isi-hitung').textContent = $('p-isi').value.length;
            }

            form.addEventListener('input', perbaruiPreview);

            // ---------- Mode ubah: isi form dari pengumuman yang dipilih ----------
            const peta = {
                'p-tag': 'tag',
                'p-terbit': 'terbit_pada',
                'p-judul': 'judul',
                'p-isi': 'isi',
                'p-ikon': 'ikon',
                'p-warna': 'warna',
                'p-cta-label': 'cta_label',
                'p-cta-tujuan': 'cta_tujuan',
            };

            function aturMode(ubah) {
                $('form-judul').textContent = ubah ? 'Ubah pengumuman' : 'Tambah pengumuman';
                $('form-ikon').className = 'bi text-white/50 ' + (ubah ? 'bi-pencil-square' : 'bi-plus-circle');
                $('p-kirim').textContent = ubah ? 'Simpan perubahan' : 'Terbitkan';
                $('p-batal').classList.toggle('hidden', !ubah);
            }

            document.addEventListener('click', (e) => {
                const tombol = e.target.closest('[data-ubah]');
                if (!tombol) return;
                const d = JSON.parse(tombol.dataset.ubah);
                Object.entries(peta).forEach(([id, kunci]) => ($(id).value = d[kunci]));
                $('p-unggulan').checked = d.unggulan;
                aturMode(true);
                perbaruiPreview();
                $('form-pengumuman-bagian').scrollIntoView({
                    block: 'start'
                });
                $('p-judul').focus({
                    preventScroll: true
                });
            });

            $('p-batal').addEventListener('click', () => {
                form.reset();
                aturMode(false);
                perbaruiPreview();
            });

            // form.reset() mengembalikan isian ke nilai awal HTML; preview ikut diperbarui lewat event ini.
            form.addEventListener('reset', () => setTimeout(perbaruiPreview));

            perbaruiPreview();
        </script>
    @endpush
@endsection
