@extends('template/icDashboardLayout')
@section('content')
    @php
        $ubah = $mode === 'ubah' && $dipilih;

        // Nilai awal form dan preview: hadiah yang dipilih, atau isian kosong untuk hadiah baru. Isian yang gagal
        // divalidasi (old) tampil lagi.
        $opsiAwal = $ubah ? $dipilih->opsi ?? [] : [];
        $opsiLama = old('opsi');
        $awal = (object) [
            'judul' => (string) old('judul', $ubah ? $dipilih->judul : ''),
            'deskripsi' => (string) old('deskripsi', $ubah ? $dipilih->deskripsi : ''),
            'ikon' => (string) old('ikon', $ubah ? $dipilih->ikon : 'gift'),
            'warna' => (string) old('warna', $ubah ? $dipilih->warna : 'koral'),
            'harga_poin' => (int) old('harga_poin', $ubah ? $dipilih->harga_poin : 100000),
            'stok' => old('stok', $ubah ? $dipilih->stok : null),
            'opsi' => is_array($opsiLama) ? array_values(array_map('strval', $opsiLama)) : $opsiAwal,
            'status_member' => 'terbuka',
        ];

        $kelasInput =
            'bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
        $labelKelas = 'text-sm font-semibold uppercase tracking-widest text-white/60';
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'hadiah',
                'judul' => 'Hadiah',
                'deskripsi' =>
                    'Katalog hadiah yang ditukar dengan poin: harga, stok, tampilan, dan tampil/sembunyikan.',
            ])

            @include('components/errors')
            @include('components/success')

            {{-- Pilih hadiah lewat ?hadiah=ID, atau tambah baru lewat ?baru=1 (tanpa JavaScript). --}}
            <nav aria-label="Pilih hadiah">
                <ul class="cms-chips">
                    @foreach ($rewards as $r)
                        @php $terpilih = $ubah && $r->id_reward === $dipilih->id_reward; @endphp
                        <li>
                            <a href="{{ route('ic.admin.hadiah', ['hadiah' => $r->id_reward]) }}"
                                @if ($terpilih) aria-current="page" @endif
                                class="cms-chip rounded-full border text-sm font-semibold transition-colors
                                {{ $terpilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white' }} {{ $r->aktif ? '' : 'opacity-60 border-dashed' }}"
                                @unless ($r->aktif) title="Disembunyikan dari anggota" @endunless>
                                {{ $r->judul }}
                                <span
                                    class="text-xs {{ $terpilih ? 'text-black/60' : 'text-white/40' }}">{{ ic_angka($r->harga_poin) }}</span>
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('ic.admin.hadiah', ['baru' => 1]) }}"
                            @if (!$ubah) aria-current="page" @endif
                            class="cms-chip rounded-full border text-sm font-semibold transition-colors
                            {{ !$ubah ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white' }}">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah hadiah
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                {{-- FORM --}}
                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8">
                    <h2 class="font-bold uppercase tracking-wide text-sm mb-5 flex items-center gap-2">
                        <i class="bi bi-pencil-square text-white/50" aria-hidden="true"></i>
                        {{ $ubah ? 'Ubah hadiah' : 'Hadiah baru' }}
                    </h2>

                    <form id="form-hadiah" method="POST"
                        action="{{ $ubah ? route('ic.admin.hadiah.ubah', $dipilih->id_reward) : route('ic.admin.hadiah.simpan') }}"
                        class="flex flex-col gap-4">
                        @csrf
                        @if ($ubah)
                            @method('PUT')
                        @endif

                        <div class="flex flex-col gap-1.5">
                            <label for="h-judul" class="{{ $labelKelas }}">Judul <span
                                    class="text-red-400">*</span></label>
                            <input type="text" id="h-judul" name="judul" maxlength="40" required
                                value="{{ $awal->judul }}" class="{{ $kelasInput }}" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="h-deskripsi" class="{{ $labelKelas }}">Deskripsi <span
                                    class="text-red-400">*</span></label>
                            <textarea id="h-deskripsi" name="deskripsi" rows="3" maxlength="140" required class="{{ $kelasInput }}">{{ $awal->deskripsi }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="h-harga" class="{{ $labelKelas }}">Harga poin <span
                                        class="text-red-400">*</span></label>
                                <input type="number" id="h-harga" name="harga_poin" min="1000" max="10000000"
                                    step="1" required value="{{ $awal->harga_poin }}" class="{{ $kelasInput }}" />
                                <p class="text-xs text-white/30">Hanya berlaku untuk penukaran baru; yang sudah masuk
                                    memakai harga saat diajukan.</p>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="h-stok" class="{{ $labelKelas }}">Stok</label>
                                <input type="number" id="h-stok" name="stok" min="0" max="100000"
                                    step="1" value="{{ $awal->stok }}" placeholder="Kosong = tak terbatas"
                                    class="{{ $kelasInput }}" />
                                <p class="text-xs text-white/30">Sisa yang masih bisa ditukar. Berkurang otomatis saat
                                    anggota mengajukan, dan kembali bila ditolak atau dibatalkan. 0 = habis (anggota bisa
                                    masuk daftar tunggu).</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="h-ikon" class="{{ $labelKelas }}">Ikon</label>
                                <select id="h-ikon" name="ikon" class="{{ $kelasInput }} scheme-dark">
                                    @foreach (config('inner_circle.ikon_pilihan') as $kunci => $label)
                                        <option class="bg-neutral-900" value="{{ $kunci }}"
                                            @selected($kunci === $awal->ikon)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="h-warna" class="{{ $labelKelas }}">Warna</label>
                                <select id="h-warna" name="warna" class="{{ $kelasInput }} scheme-dark">
                                    @foreach (config('inner_circle.warna_kartu') as $kunci => $label)
                                        <option class="bg-neutral-900" value="{{ $kunci }}"
                                            @selected($kunci === $awal->warna)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="h-opsi" class="{{ $labelKelas }}">Pilihan hadiah</label>
                            <textarea id="h-opsi" name="opsi" rows="2" placeholder="Kosongkan bila hadiah ini tidak punya pilihan"
                                class="{{ $kelasInput }}">{{ implode("\n", $awal->opsi) }}</textarea>
                            <p class="text-xs text-white/30">Satu pilihan per baris: isi tepat 2, atau kosongkan. Bila
                                terisi, anggota memilih salah satunya saat menukar. Penukaran yang sudah masuk tidak
                                berubah.</p>
                        </div>

                        <button type="submit"
                            class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                            {{ $ubah ? 'Simpan hadiah' : 'Tambah ke katalog' }}
                        </button>
                    </form>

                    @if ($ubah)
                        {{-- Tampil/sembunyikan hadiah ini (tanpa JavaScript). --}}
                        <div class="mt-8 pt-6 border-t border-white/10">
                            <h3 class="font-bold uppercase tracking-wide text-sm mb-1 flex items-center gap-2">
                                <i class="bi bi-eye text-white/50" aria-hidden="true"></i> Tampil ke anggota
                            </h3>
                            <p class="text-xs text-white/40 mb-4">Hadiah yang disembunyikan tidak tampil di halaman Hadiah
                                dan tidak bisa ditukar baru. Penukaran yang sudah masuk tetap diproses. Minimal satu hadiah
                                harus aktif.</p>
                            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/3 px-4 py-2.5">
                                <p class="min-w-0 flex-1 text-sm">
                                    <b>{{ ic_angka($dipilih->harga_poin) }}</b> poin
                                    <span class="text-white/50">· {{ $dipilih->judul }}</span>
                                </p>
                                <span
                                    class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $dipilih->aktif ? 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200' : 'border-white/15 text-white/50' }}">{{ $dipilih->aktif ? 'Aktif' : 'Disembunyikan' }}</span>
                                <form method="POST" action="{{ route('ic.admin.hadiah.aktif', $dipilih->id_reward) }}"
                                    class="shrink-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="aktif" value="{{ $dipilih->aktif ? 0 : 1 }}">
                                    <button type="submit"
                                        class="h-11 px-4 rounded-full border border-white/15 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                                        {{ $dipilih->aktif ? 'Sembunyikan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Angka penukaran dan daftar tunggu hadiah ini. --}}
                        <div class="mt-6 pt-6 border-t border-white/10 text-sm text-white/70 flex flex-col gap-2">
                            <p><b>{{ $dipilih->klaim_menunggu }}</b> penukaran menunggu tindakan
                                · <a class="underline" href="{{ route('ic.admin.klaim') }}">buka tab Penukaran</a></p>
                            <p>Stok: <b>{{ $dipilih->stok === null ? 'tak terbatas' : ic_angka($dipilih->stok) }}</b>
                                · <b>{{ $dipilih->jumlah_tunggu }}</b> anggota di daftar tunggu</p>
                            @if ($penunggu->isNotEmpty())
                                <details class="text-xs text-white/50">
                                    <summary class="cursor-pointer select-none py-1 hover:text-white/80">Lihat daftar
                                        tunggu
                                    </summary>
                                    <ul class="flex flex-col gap-1 pt-1 pl-1">
                                        @foreach ($penunggu as $t)
                                            <li>{{ $t->member?->nama ?? 'Anggota' }}
                                                <span class="text-white/40">· {{ $t->member?->kode_member }}
                                                    · {{ $t->created_at->locale('id')->translatedFormat('j M Y') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- PREVIEW: ubin dan baris hadiah yang sama dengan halaman Hadiah anggota --}}
                <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/3">
                        <i class="bi bi-eye text-white/40" aria-hidden="true"></i>
                        <span class="text-xs uppercase tracking-widest text-white/40">Tampilan di halaman Hadiah</span>
                    </div>

                    <div class="ic-app ic-ponsel p-5 flex flex-col gap-5" inert>
                        <div class="max-w-xs w-full mx-auto" id="preview-ubin">
                            <x-inner-circle.ubin-hadiah :reward="$awal" href="#" />
                        </div>

                        <div class="ic-panel ic-klaim-daftar">
                            <div class="ic-klaim" id="preview-klaim">
                                <span class="ic-klaim__bulat ic-warna-{{ $awal->warna }}">
                                    <x-inner-circle.ikon :nama="$awal->ikon" kelas="ic-ikon--kecil" />
                                </span>
                                <div>
                                    <h3>{{ ic_angka($awal->harga_poin) }} poin · {{ $awal->judul }}</h3>
                                    <p class="ic-bantuan">{{ $awal->deskripsi }}</p>
                                </div>
                                <button type="button" class="ic-tombol ic-tombol--kecil">Tukar <i
                                        class="bi bi-arrow-right" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-4 border-t border-white/10 bg-white/3">
                        <p class="text-xs uppercase text-white/40">Pilihan saat menukar</p>
                        <p id="preview-opsi" class="text-sm text-white/70 mt-1">Tanpa pilihan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.icIkonPeta = @json($ikonPeta);

            const $ = (id) => document.getElementById(id);
            const ubin = document.querySelector('#preview-ubin .ic-ubin');
            const baris = $('preview-klaim');
            const angka = (n) => (Number(n) || 0).toLocaleString('id-ID');

            // Gambar preview dari isi form saat ini.
            function perbaruiPreview() {
                const warna = $('h-warna').value;
                const ikon = $('h-ikon').value;
                const judul = $('h-judul').value;
                const deskripsi = $('h-deskripsi').value;
                const harga = $('h-harga').value;
                const opsi = $('h-opsi').value.split('\n').map((x) => x.trim()).filter(Boolean).slice(0, 2);

                ubin.className = ubin.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${warna}`;
                ubin.querySelector('.ic-ubin__angka').firstChild.nodeValue = angka(harga);
                ubin.querySelector('.ic-ubin__judul').textContent = judul;
                ubin.querySelector('.ic-ubin__deskripsi').textContent = deskripsi;
                ubin.querySelector('.ic-ubin__ikon').replaceWith(window.icBuatIkon(ikon, 'ic-ubin__ikon'));

                const bulat = baris.querySelector('.ic-klaim__bulat');
                bulat.className = bulat.className.replace(/ic-warna-\S+/g, '').trim() + ` ic-warna-${warna}`;
                bulat.replaceChildren(window.icBuatIkon(ikon, 'ic-ikon--kecil'));
                baris.querySelector('h3').textContent = `${angka(harga)} poin · ${judul}`;
                baris.querySelector('p').textContent = deskripsi;

                $('preview-opsi').textContent = opsi.length ? opsi.join(' · ') : 'Tanpa pilihan';
            }

            ['h-judul', 'h-deskripsi', 'h-harga', 'h-ikon', 'h-warna', 'h-opsi'].forEach((id) => {
                $(id).addEventListener('input', perbaruiPreview);
            });

            perbaruiPreview();
        </script>
    @endpush
@endsection
