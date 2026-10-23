@extends('template/dashboardLayout')
@section('content')
    @php
        // Placeholder preview saat isian masih kosong (mirip form News). `$ubah` = pengumuman yang sedang diubah, atau null.
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

        // Isi awal form: dari pengumuman yang diubah, kalau tidak nilai bawaan. old() menang setelah gagal validasi.
        $awal = [
            'tag' => $ubah?->tag ?? '',
            'terbit_pada' => ($ubah?->terbit_pada ?? $kosong->terbit_pada)->format('Y-m-d'),
            'judul' => $ubah?->judul ?? '',
            'isi' => $ubah?->isi ?? '',
            'ikon' => $ubah?->ikon ?? $kosong->ikon,
            'warna' => $ubah?->warna ?? $kosong->warna,
            'cta_label' => $ubah?->cta_label ?? '',
            'cta_tujuan' => $ubah?->cta_tujuan ?? $kosong->cta_tujuan,
        ];
        $nilai = fn(string $kunci) => old($kunci, $awal[$kunci]);
        // Kotak centang tidak terkirim bila tidak dicentang, jadi old() kosong tidak boleh jatuh ke nilai awal.
        $unggulanDicentang = session()->hasOldInput() ? (bool) old('unggulan') : (bool) $ubah?->unggulan;

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

            @include('components/errors')
            @include('components/success')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start" id="form-pengumuman-bagian">

                {{-- FORM --}}
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 scroll-mt-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi {{ $ubah ? 'bi-pencil-square' : 'bi-plus-circle' }} text-white/50"
                            aria-hidden="true"></i>
                        <span>{{ $ubah ? 'Ubah pengumuman' : 'Tambah pengumuman' }}</span>
                    </h2>

                    <form id="form-pengumuman" method="POST"
                        action="{{ $ubah ? route('ic.admin.pengumuman.ubah', $ubah->id_broadcast) : route('ic.admin.pengumuman.simpan') }}"
                        class="flex flex-col gap-4">
                        @csrf
                        @if ($ubah)
                            @method('PUT')
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-tag" class="{{ $labelKelas }}">Kategori</label>
                                <input type="text" id="p-tag" name="tag" maxlength="40"
                                    value="{{ $nilai('tag') }}" placeholder="Contoh: MUSIK • PENGUMUMAN"
                                    class="{{ $kelasInput }}" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-terbit" class="{{ $labelKelas }}">Tanggal terbit <span
                                        class="text-red-400">*</span></label>
                                <input type="date" id="p-terbit" name="terbit_pada" required
                                    value="{{ $nilai('terbit_pada') }}" class="{{ $kelasInput }} scheme-dark" />
                                <p class="text-xs text-white/30">Tanggal di masa depan = terjadwal, belum tampil ke
                                    anggota.</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="p-judul" class="{{ $labelKelas }}">Judul <span
                                    class="text-red-400">*</span></label>
                            <input type="text" id="p-judul" name="judul" maxlength="80" required
                                value="{{ $nilai('judul') }}" placeholder="Contoh: Sampai jumpa di pertunjukan berikutnya"
                                class="{{ $kelasInput }}" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="p-isi" class="{{ $labelKelas }}">Isi <span
                                    class="text-red-400">*</span></label>
                            <textarea id="p-isi" name="isi" rows="4" maxlength="220" required
                                placeholder="Tulis kabar singkat untuk anggota..." class="{{ $kelasInput }}">{{ $nilai('isi') }}</textarea>
                            <p class="text-xs text-white/30"><span id="p-isi-hitung">0</span> / 220 karakter</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-ikon" class="{{ $labelKelas }}">Ikon</label>
                                <select id="p-ikon" name="ikon" class="{{ $kelasInput }} scheme-dark">
                                    @foreach (config('inner_circle.ikon_pilihan') as $kunci => $label)
                                        <option class="bg-neutral-900" value="{{ $kunci }}"
                                            @selected($kunci === $nilai('ikon'))>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-warna" class="{{ $labelKelas }}">Warna</label>
                                <select id="p-warna" name="warna" class="{{ $kelasInput }} scheme-dark">
                                    @foreach (config('inner_circle.warna_kartu') as $kunci => $label)
                                        <option class="bg-neutral-900" value="{{ $kunci }}"
                                            @selected($kunci === $nilai('warna'))>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="p-cta-label" class="{{ $labelKelas }}">Teks tombol <span
                                        class="text-red-400">*</span></label>
                                <input type="text" id="p-cta-label" name="cta_label" maxlength="30" required
                                    value="{{ $nilai('cta_label') }}" placeholder="Contoh: Lihat jadwal acara"
                                    class="{{ $kelasInput }}" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="p-cta-tujuan" class="{{ $labelKelas }}">Tombol menuju</label>
                                <select id="p-cta-tujuan" name="cta_tujuan" class="{{ $kelasInput }} scheme-dark">
                                    @foreach ($ctaPengumuman as $route => $label)
                                        <option class="bg-neutral-900" value="{{ $route }}"
                                            @selected($route === $nilai('cta_tujuan'))>{{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label
                            class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/2 p-4 cursor-pointer">
                            <input type="checkbox" id="p-unggulan" name="unggulan" value="1"
                                @checked($unggulanDicentang) class="mt-1 w-5 h-5 shrink-0 accent-red-900" />
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
                                {{ $ubah ? 'Simpan perubahan' : 'Terbitkan' }}
                            </button>
                            @if ($ubah)
                                <a href="{{ route('ic.admin.pengumuman') }}"
                                    class="h-12 px-5 inline-flex items-center justify-center rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                    Batal ubah
                                </a>
                            @endif
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
                    Pengumuman tersimpan ({{ $daftar->total() }})
                </h2>

                @if ($daftar->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada pengumuman yang ditambahkan.',
                    ])
                @else
                    <ul class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                        @foreach ($daftar as $p)
                            <li
                                class="flex items-center gap-3 rounded-2xl border p-3 transition-colors hover:border-white/20 min-w-0 {{ $ubah && $ubah->id_broadcast === $p->id_broadcast ? 'border-white/40 bg-white/6' : 'border-white/10 bg-white/3' }}">
                                <span class="ic-app ic-klaim__bulat ic-warna-{{ $p->warna }} shrink-0">
                                    <x-inner-circle.ikon :nama="$p->ikon" kelas="ic-ikon--kecil" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] mb-0.5">
                                        @if ($p->tag)
                                            <span class="text-white/50 truncate">{{ $p->tag }}</span>
                                        @endif
                                        <span
                                            class="text-red-400 shrink-0">{{ $p->terbit_pada->locale('id')->translatedFormat('j M Y') }}</span>
                                        @if ($p->terjadwal)
                                            <span
                                                class="shrink-0 rounded-full border border-sky-300/30 bg-sky-300/10 px-2 py-0.5 font-semibold text-sky-200">Terjadwal</span>
                                        @endif
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
                                    <a href="{{ route('ic.admin.pengumuman', ['ubah' => $p->id_broadcast, 'page' => $daftar->currentPage() > 1 ? $daftar->currentPage() : null]) }}#form-pengumuman-bagian"
                                        aria-label="Ubah pengumuman {{ $p->judul }}"
                                        class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-white transition">
                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                    </a>
                                    <form method="POST"
                                        action="{{ route('ic.admin.pengumuman.hapus', $p->id_broadcast) }}"
                                        class="inline-flex" data-swal-confirm
                                        data-confirm-message="Pengumuman “{{ $p->judul }}” akan dihapus dan hilang dari Komunitas anggota.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus pengumuman {{ $p->judul }}"
                                            class="w-11 h-11 inline-flex items-center justify-center text-white/40 hover:text-red-400 transition">
                                            <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @include('components.dashboard.pagination', ['paginator' => $daftar])
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
                const tag = $('p-tag').value.trim();
                const judul = isi('p-judul', KOSONG.judul);
                const teks = isi('p-isi', KOSONG.isi);
                const label = isi('p-cta-label', KOSONG.cta_label);
                const ikon = $('p-ikon').value;
                const warna = $('p-warna').value;
                const besar = $('p-unggulan').checked;
                // Kategori boleh kosong: preview mengikuti tampilan anggota (tanpa tag), placeholder hanya bila form masih benar-benar baru.
                const awalan = tag ? `${tag} · ` : '';

                unggulan.hidden = !besar;
                item.hidden = besar;

                unggulan.querySelector('.ic-unggulan__judul').textContent = judul;
                unggulan.querySelector('.ic-unggulan__isi').textContent = teks;
                setTombol(unggulan.querySelector('.ic-tombol'), label);

                item.querySelector('.ic-pengumuman__meta').textContent = `${awalan}${window.icTanggal($('p-terbit').value)}`;
                item.querySelector('.ic-pengumuman__judul').textContent = judul;
                item.querySelector('.ic-pengumuman__isi').textContent = teks;
                setTombol(item.querySelector('.ic-tombol'), label);

                const bulat = item.querySelector('.ic-pengumuman__ikon');
                bulat.className = bulat.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${warna}`;
                bulat.replaceChildren(window.icBuatIkon(ikon, 'ic-ikon--kecil'));

                $('p-isi-hitung').textContent = $('p-isi').value.length;
            }

            form.addEventListener('input', perbaruiPreview);
            form.addEventListener('change', perbaruiPreview);

            perbaruiPreview();
        </script>
    @endpush
@endsection
