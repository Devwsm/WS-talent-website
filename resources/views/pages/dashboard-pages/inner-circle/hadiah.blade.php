@extends('template/dashboardLayout')
@section('content')
    @php
        // Data awal untuk form dan preview JavaScript: satu entri per tahapan, kunci = target stempel.
        $dataHadiah = collect($rewards)
            ->mapWithKeys(
                fn($r) => [
                    $r->target_stempel => [
                        'judul' => $r->judul,
                        'deskripsi' => $r->deskripsi,
                        'ikon' => $r->ikon,
                        'warna' => $r->warna,
                        'opsi' => $r->opsi ?? [],
                    ],
                ],
            )
            ->all();

        // Alamat simpan per tahapan (form dipakai bergantian untuk kelima tahap).
        $urlUbah = collect($rewards)
            ->mapWithKeys(fn($r) => [$r->target_stempel => route('ic.admin.hadiah.ubah', $r->target_stempel)])
            ->all();

        // Isian yang gagal divalidasi tampil lagi di tahap yang sedang dibuka.
        if (!empty($rewards) && (old('judul') !== null || old('deskripsi') !== null)) {
            $opsiLama = old('opsi', []);
            $dataHadiah[$tahapAwal] = array_merge($dataHadiah[$tahapAwal], [
                'judul' => (string) old('judul', $dataHadiah[$tahapAwal]['judul']),
                'deskripsi' => (string) old('deskripsi', $dataHadiah[$tahapAwal]['deskripsi']),
                'ikon' => (string) old('ikon', $dataHadiah[$tahapAwal]['ikon']),
                'warna' => (string) old('warna', $dataHadiah[$tahapAwal]['warna']),
                'opsi' => is_array($opsiLama) ? array_values(array_map('strval', $opsiLama)) : [],
            ]);
        }

        // Preview selalu tampil sebagai "terbuka" supaya label dan tombolnya terlihat penuh.
        $awal = empty($rewards)
            ? null
            : (object) array_merge($dataHadiah[$tahapAwal], [
                'target_stempel' => $tahapAwal,
                'status_member' => 'terbuka',
            ]);
        $kelasInput =
            'bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.tabs', [
                'aktif' => 'hadiah',
                'judul' => 'Hadiah',
                'deskripsi' => 'Atur lima tahapan hadiah: judul, deskripsi, ikon, warna, dan pilihan hadiah.',
            ])

            @include('components/errors')
            @include('components/success')

            @if (empty($rewards))
                @include('components/dashboard/card/empty-state', [
                    'message' => 'Belum ada tahapan hadiah. Jalankan php artisan db:seed --class=icRewardSeeder.',
                ])
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                    {{-- FORM --}}
                    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                        <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                            <i class="bi bi-pencil-square text-white/50" aria-hidden="true"></i> Ubah tahapan
                        </h2>

                        {{-- Pilih tahapan. Isian yang belum disimpan tetap ada saat berpindah tahapan, tapi hilang bila halaman dimuat ulang. --}}
                        <div role="group" aria-label="Pilih tahapan hadiah" class="flex flex-wrap gap-2 mb-6">
                            @foreach ($rewards as $r)
                                @php $dipilih = $r->target_stempel === $tahapAwal; @endphp
                                <button type="button" data-tahap="{{ $r->target_stempel }}"
                                    aria-pressed="{{ $dipilih ? 'true' : 'false' }}"
                                    class="h-11 min-w-16 px-4 rounded-full border text-sm font-semibold transition-colors
                                {{ $dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white' }}">
                                    {{ $r->target_stempel }}
                                </button>
                            @endforeach
                        </div>

                        <form id="form-hadiah" method="POST" action="{{ $urlUbah[$tahapAwal] }}"
                            class="flex flex-col gap-4">
                            @csrf
                            @method('PUT')
                            <div class="flex flex-col gap-1.5">
                                <span class="text-sm font-semibold uppercase tracking-widest text-white/60">Target
                                    stempel</span>
                                <p class="text-sm text-white/70"><b id="h-target">{{ $awal->target_stempel }}</b> stempel
                                    <span class="text-white/30">· tetap, tidak bisa diubah</span>
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="h-judul"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Judul <span
                                        class="text-red-400">*</span></label>
                                <input type="text" id="h-judul" name="judul" maxlength="40" required
                                    value="{{ $awal->judul }}" class="{{ $kelasInput }}" />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="h-deskripsi"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Deskripsi <span
                                        class="text-red-400">*</span></label>
                                <textarea id="h-deskripsi" name="deskripsi" rows="3" maxlength="140" required class="{{ $kelasInput }}">{{ $awal->deskripsi }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="h-ikon"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Ikon</label>
                                    <select id="h-ikon" name="ikon" class="{{ $kelasInput }} scheme-dark">
                                        @foreach (config('inner_circle.ikon_pilihan') as $kunci => $label)
                                            <option class="bg-neutral-900" value="{{ $kunci }}"
                                                @selected($kunci === $awal->ikon)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="h-warna"
                                        class="text-sm font-semibold uppercase tracking-widest text-white/60">Warna</label>
                                    <select id="h-warna" name="warna" class="{{ $kelasInput }} scheme-dark">
                                        @foreach (config('inner_circle.warna_kartu') as $kunci => $label)
                                            <option class="bg-neutral-900" value="{{ $kunci }}"
                                                @selected($kunci === $awal->warna)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="h-opsi"
                                    class="text-sm font-semibold uppercase tracking-widest text-white/60">Pilihan
                                    hadiah</label>
                                <textarea id="h-opsi" name="opsi" rows="2" placeholder="Kosongkan bila hadiah ini tidak punya pilihan"
                                    class="{{ $kelasInput }}">{{ implode("\n", $awal->opsi ?? []) }}</textarea>
                                <p class="text-xs text-white/30">Satu pilihan per baris: isi tepat 2, atau kosongkan. Bila
                                    terisi, anggota memilih salah satunya saat mengajukan klaim. Pengajuan yang sudah masuk
                                    tidak berubah.</p>
                            </div>

                            <button type="submit"
                                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                Simpan tahapan
                            </button>
                            <p class="text-xs text-white/30">Simpan dilakukan per tahapan: hanya tahapan yang sedang dibuka
                                yang tersimpan.</p>
                        </form>
                    </div>

                    {{-- PREVIEW: ubin dan baris klaim yang sama dengan halaman Hadiah anggota --}}
                    <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                            <i class="bi bi-eye text-white/40" aria-hidden="true"></i>
                            <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di halaman Hadiah</span>
                        </div>

                        <div class="ic-app p-5 flex flex-col gap-5" inert>
                            <div class="max-w-xs w-full mx-auto" id="preview-ubin">
                                <x-inner-circle.ubin-hadiah :reward="$awal" href="#" />
                            </div>

                            <div class="ic-panel ic-klaim-daftar">
                                <div class="ic-klaim" id="preview-klaim">
                                    <span class="ic-klaim__bulat ic-warna-{{ $awal->warna }}">
                                        <x-inner-circle.ikon :nama="$awal->ikon" kelas="ic-ikon--kecil" />
                                    </span>
                                    <div>
                                        <h3>{{ $awal->target_stempel }} stempel · {{ $awal->judul }}</h3>
                                        <p class="ic-bantuan">{{ $awal->deskripsi }}</p>
                                    </div>
                                    <button type="button" class="ic-tombol ic-tombol--kecil">Klaim <i
                                            class="bi bi-arrow-right" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-4 border-t border-white/10 bg-white/3">
                            <p class="text-xs uppercase text-white/40">Pilihan saat klaim</p>
                            <p id="preview-opsi" class="text-sm text-white/70 mt-1">Tanpa pilihan</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if (!empty($rewards))
        @push('scripts')
            <script>
                window.icIkonPeta = @json($ikonPeta);

                const DATA = @json($dataHadiah);
                const URL_UBAH = @json($urlUbah);
                const $ = (id) => document.getElementById(id);
                let aktif = String(@json($awal->target_stempel));

                const ubin = document.querySelector('#preview-ubin .ic-ubin');
                const klaim = $('preview-klaim');

                // Gambar preview dari data tahapan yang sedang dipilih.
                function perbaruiPreview() {
                    const d = DATA[aktif];

                    ubin.className = ubin.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${d.warna}`;
                    ubin.classList.toggle('ic-ubin--lebar', aktif === '250');
                    ubin.querySelector('.ic-ubin__angka').firstChild.nodeValue = aktif;
                    ubin.querySelector('.ic-ubin__judul').textContent = d.judul;
                    ubin.querySelector('.ic-ubin__deskripsi').textContent = d.deskripsi;
                    ubin.querySelector('.ic-ubin__ikon').replaceWith(window.icBuatIkon(d.ikon, 'ic-ubin__ikon'));

                    const bulat = klaim.querySelector('.ic-klaim__bulat');
                    bulat.className = bulat.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${d.warna}`;
                    bulat.replaceChildren(window.icBuatIkon(d.ikon, 'ic-ikon--kecil'));
                    klaim.querySelector('h3').textContent = `${aktif} stempel · ${d.judul}`;
                    klaim.querySelector('p').textContent = d.deskripsi;

                    $('preview-opsi').textContent = d.opsi.length ? d.opsi.join(' · ') : 'Tanpa pilihan';
                }

                // Isi form dari data tahapan yang dipilih.
                function muatForm() {
                    const d = DATA[aktif];
                    $('h-target').textContent = aktif;
                    $('h-judul').value = d.judul;
                    $('h-deskripsi').value = d.deskripsi;
                    $('h-ikon').value = d.ikon;
                    $('h-warna').value = d.warna;
                    $('h-opsi').value = d.opsi.join('\n');
                }

                document.querySelectorAll('[data-tahap]').forEach((tombol) => {
                    tombol.addEventListener('click', () => {
                        aktif = tombol.dataset.tahap;
                        document.querySelectorAll('[data-tahap]').forEach((t) => {
                            const pilih = t === tombol;
                            t.setAttribute('aria-pressed', String(pilih));
                            t.classList.toggle('bg-white', pilih);
                            t.classList.toggle('text-black', pilih);
                            t.classList.toggle('border-white', pilih);
                            t.classList.toggle('border-white/10', !pilih);
                            t.classList.toggle('text-white/60', !pilih);
                        });
                        muatForm();
                        perbaruiPreview();
                        $('form-hadiah').action = URL_UBAH[aktif];
                    });
                });

                const sinkron = {
                    'h-judul': (v, d) => (d.judul = v),
                    'h-deskripsi': (v, d) => (d.deskripsi = v),
                    'h-ikon': (v, d) => (d.ikon = v),
                    'h-warna': (v, d) => (d.warna = v),
                    'h-opsi': (v, d) => (d.opsi = v.split('\n').map((x) => x.trim()).filter(Boolean).slice(0, 2)),
                };
                Object.entries(sinkron).forEach(([id, simpan]) => {
                    $(id).addEventListener('input', (e) => {
                        simpan(e.target.value, DATA[aktif]);
                        perbaruiPreview();
                    });
                });

                perbaruiPreview();
            </script>
        @endpush
    @endif
@endsection
