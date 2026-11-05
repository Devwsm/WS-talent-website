@extends('template/icDashboardLayout')
@section('content')
    @php
        $kelasInput =
            'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
        $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
        $kelasTombol =
            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-x-clip">
        @include('components/dashboard/navbar')

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'konten',
                'judul' => $bagian['label'],
                'deskripsi' =>
                    $bagian['petunjuk'] ?:
                    'Ubah teks tanpa menyentuh kode. Kosongkan isian untuk memakai teks bawaan.',
                'pakaiCss' => false,
            ])

            @include('components/errors')
            @include('components/success')

            {{-- Pindah bagian lewat navbar ini saja. Bagian dikelompokkan (halaman publik, halaman anggota, ...) dan tiap
                 kelompok berisi chip mengalir yang turun baris (tidak digeser). Angka = isian yang sudah diubah. --}}
            <nav aria-label="Bagian konten"
                class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/3 p-4 sm:p-5">
                @foreach ($kelompokBagian as $kelompok)
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:gap-5">
                        <p
                            class="text-[11px] font-semibold uppercase tracking-widest text-white/50 lg:w-44 lg:shrink-0 lg:pt-3">
                            {{ $kelompok['label'] }}</p>
                        <div class="cms-chips">
                            @foreach ($kelompok['bagian'] as $slug => $b)
                                @php
                                    $aktifBagian = $slug === $bagian['slug'];
                                    $jumlahUbah = $diubahPerBagian[$slug] ?? 0;
                                @endphp
                                <a href="{{ \App\Services\InnerCircle\IcBagian::url($slug) }}"
                                    @if ($aktifBagian) aria-current="page" @endif
                                    class="cms-chip rounded-full border text-xs font-semibold transition-colors {{ $aktifBagian ? 'bg-white text-black border-white' : 'border-white/15 text-white/70 hover:bg-white/10 hover:text-white' }}">
                                    <i class="bi {{ $b['ikon'] }} {{ $aktifBagian ? 'text-black/60' : 'text-white/50' }}"
                                        aria-hidden="true"></i>
                                    <span>{{ $b['label'] }}</span>
                                    @if ($jumlahUbah > 0)
                                        <span
                                            class="rounded-full px-1.5 text-[11px] font-bold {{ $aktifBagian ? 'bg-black/10 text-black/70' : 'bg-white/10 text-white/60' }}"
                                            title="{{ $jumlahUbah }} isian sudah diubah">{{ $jumlahUbah }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            @if ($bagian['jenis'] === 'grup')
                @php
                    $kodeGrup = $bagian['kode'];
                    $info = $grup[$kodeGrup];
                @endphp

                <div id="grup-{{ $kodeGrup }}" class="flex flex-col gap-10">
                    @if (!empty($info['halaman']))
                        <div class="flex justify-end -mb-6">
                            <a href="{{ route($info['halaman']) }}{{ !empty($info['jangkar']) ? '#' . $info['jangkar'] : '' }}"
                                target="_blank" rel="noopener" class="{{ $kelasTombol }} shrink-0">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Lihat halaman</a>
                        </div>
                    @endif

                    {{-- Satu modul = satu topik kecil: formulir di kiri, pratinjau bagian itu saja di kanan (di bawah formulir pada layar sempit). --}}
                    @foreach ($modul as $slugModul => $m)
                        @php
                            $idForm = 'form-' . $kodeGrup . '-' . $slugModul;
                            $isianModul = collect($m['kunci'])->mapWithKeys(fn($k) => [$k => $kunci[$k]]);
                            $jumlahDiubah = $isianModul->keys()->filter(fn($k) => isset($tersimpan[$k]))->count();
                        @endphp

                        <section id="modul-{{ $kodeGrup }}-{{ $slugModul }}"
                            aria-labelledby="judul-{{ $kodeGrup }}-{{ $slugModul }}"
                            class="scroll-mt-8 flex flex-col gap-4">
                            <h2 id="judul-{{ $kodeGrup }}-{{ $slugModul }}"
                                class="font-bold uppercase tracking-wide text-sm flex flex-wrap items-center gap-x-2 gap-y-1">
                                <i class="bi {{ $m['ikon'] }} text-white/50" aria-hidden="true"></i>
                                {{ $m['label'] }}
                                @if ($jumlahDiubah > 0)
                                    <span
                                        class="rounded-full bg-red-950/40 px-2.5 py-0.5 text-[11px] font-bold normal-case text-white/70">{{ $jumlahDiubah }}
                                        isian diubah</span>
                                @endif
                            </h2>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                                <div class="flex flex-col gap-6 min-w-0">
                                    @if ($isianModul->isNotEmpty())
                                        <form method="POST" id="{{ $idForm }}"
                                            action="{{ route('ic.admin.konten.simpan', $kodeGrup) }}"
                                            class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-5">
                                            @csrf
                                            <input type="hidden" name="modul" value="{{ $slugModul }}">

                                            @foreach ($isianModul as $namaKunci => $def)
                                                @php
                                                    $kolom = str_replace('.', '__', $namaKunci);
                                                    $id = 'f-' . $kolom;
                                                    $nilai = old('k.' . $kolom, $tersimpan[$namaKunci] ?? '');
                                                    $diubah = isset($tersimpan[$namaKunci]);
                                                @endphp

                                                <div class="flex flex-col gap-1.5 min-w-0">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <label for="{{ $id }}"
                                                            class="{{ $kelasLabel }}">{{ $def['label'] }}</label>
                                                        @if ($diubah)
                                                            <span
                                                                class="rounded-full bg-red-950/40 text-white/70 px-2.5 py-0.5 text-[11px] font-bold">Diubah</span>
                                                        @endif
                                                    </div>

                                                    @if (!empty($def['baris']))
                                                        <textarea id="{{ $id }}" name="k[{{ $kolom }}]" rows="3" maxlength="{{ $def['maks'] }}"
                                                            placeholder="{{ $def['bawaan'] }}" data-hitung class="{{ $kelasInput }}">{{ $nilai }}</textarea>
                                                    @else
                                                        <input id="{{ $id }}" name="k[{{ $kolom }}]"
                                                            maxlength="{{ $def['maks'] }}" autocomplete="off"
                                                            value="{{ $nilai }}" placeholder="{{ $def['bawaan'] }}"
                                                            data-hitung class="{{ $kelasInput }}">
                                                    @endif

                                                    <div
                                                        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-white/40">
                                                        <p class="min-w-0 wrap-break-word">Bawaan: <span
                                                                class="text-white/60 whitespace-pre-line">{{ $def['bawaan'] }}</span>
                                                        </p>
                                                        <span class="shrink-0"><span
                                                                data-hitung-untuk="{{ $id }}">{{ mb_strlen($nilai) }}</span>/{{ $def['maks'] }}</span>
                                                    </div>

                                                    @php $bolehPakai = array_merge($def['placeholder'] ?? [], $def['tautan'] ?? []); @endphp
                                                    @if ($bolehPakai)
                                                        <p class="text-xs text-white/40">Bisa memakai:
                                                            @foreach ($bolehPakai as $nama)
                                                                <code
                                                                    class="rounded bg-white/10 px-1.5 py-0.5 text-white/70">&#123;{{ $nama }}&#125;</code>
                                                            @endforeach
                                                        </p>
                                                    @endif

                                                    @error('k.' . $kolom)
                                                        <p class="text-xs text-red-300" role="alert">{{ $message }}</p>
                                                    @enderror

                                                    @if ($diubah)
                                                        <div>
                                                            <button type="submit"
                                                                formaction="{{ route('ic.admin.konten.reset', [$kodeGrup, $namaKunci]) }}"
                                                                class="text-xs text-white/50 underline underline-offset-4 hover:text-white min-h-11">
                                                                Kembalikan bawaan</button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach

                                            <button type="submit"
                                                class="w-full text-white font-bold uppercase tracking-widest p-3 mt-1 bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                                Simpan {{ $m['label'] }}
                                            </button>
                                        </form>
                                    @endif

                                    @if (!empty($m['daftar']))
                                        @php $kodeDaftar = $m['daftar']; @endphp
                                        @include('components.dashboard.inner-circle.daftar-konten')
                                    @endif
                                </div>

                                @include('components.dashboard.inner-circle.pratinjau', [
                                    'kodeGrup' => $kodeGrup,
                                    'info' => $info,
                                    'idForm' => $isianModul->isNotEmpty() ? $idForm : null,
                                    'fokus' => $m['fokus'],
                                    'judulPanel' => $m['label'],
                                    'pilih' => false,
                                ])
                            </div>
                        </section>
                    @endforeach
                </div>
            @else
                @switch($bagian['slug'])
                    @case('tipe-acara')
                        @include('components.dashboard.inner-circle.pilihan-tipe')
                    @break

                    @case('kategori-kenangan')
                        @include('components.dashboard.inner-circle.pilihan-kategori')
                    @break

                    @case('aturan-checkin')
                        @include('components.dashboard.inner-circle.aturan-checkin')
                    @break

                    @case('tampilan')
                        @include('components.dashboard.inner-circle.tampilan')
                    @break

                    @case('privasi')
                        @include('components.dashboard.inner-circle.privasi-editor')
                    @break
                @endswitch
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            // Penghitung huruf per isian.
            document.querySelectorAll('[data-hitung]').forEach((el) => {
                const hitung = document.querySelector('[data-hitung-untuk="' + el.id + '"]');
                el.addEventListener('input', () => {
                    if (hitung) hitung.textContent = el.value.length;
                });
            });

            // Pratinjau potongan: hanya bagian halaman yang memuat teks yang sedang diubah (isian kosong = teks bawaan).
            // Diperbarui sebentar setelah berhenti mengetik. Teks yang difokus/diketik menentukan potongan yang tampil.
            document.querySelectorAll('[data-pratinjau]').forEach((panel) => {
                const form = panel.dataset.form ? document.getElementById(panel.dataset.form) : null;
                const frame = panel.querySelector('[data-pratinjau-frame]');
                const status = panel.querySelector('[data-pratinjau-status]');
                const catatan = panel.querySelector('[data-pratinjau-catatan]');
                const pilih = panel.querySelector('[data-pratinjau-halaman]');
                if (!frame) return;

                const kolomDari = (el) => {
                    const m = el && el.name ? el.name.match(/^k\[(.+)\]$/) : null;
                    return m ? m[1] : null;
                };
                // Potongan awal: kunci yang ditentukan modul (data-fokus); bila tidak ada, isian pertama di form.
                let fokus = panel.dataset.fokus || kolomDari(form ? form.querySelector('[name^="k["]') : null);
                let tunda = null;
                let batal = null;

                const muat = async () => {
                    if (batal) batal.abort();
                    batal = new AbortController();
                    status.textContent = 'Memuat…';

                    const data = form ? new FormData(form) : new FormData();
                    // Berkas unggahan (logo) tidak ikut dikirim ke pratinjau; logo baru tampil setelah disimpan.
                    data.delete('logo');
                    data.set('grup', panel.dataset.grup);
                    if (fokus) data.set('fokus', fokus);
                    if (pilih && pilih.value) data.set('halaman', pilih.value);

                    try {
                        const res = await fetch(panel.dataset.url, {
                            method: 'POST',
                            body: data,
                            signal: batal.signal,
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'text/html'
                            },
                        });
                        if (!res.ok) throw new Error(String(res.status));
                        const html = await res.text();
                        frame.srcdoc = html;
                        status.textContent = 'Pratinjau diperbarui';
                        if (catatan) {
                            const pesan = res.headers.get('X-Pratinjau-Catatan');
                            catatan.textContent = pesan ? decodeURIComponent(pesan) : '';
                            catatan.hidden = !pesan;
                        }
                    } catch (galat) {
                        if (galat.name !== 'AbortError') status.textContent =
                            'Pratinjau gagal dimuat. Coba muat ulang.';
                    }
                };

                const jadwalkan = () => {
                    status.textContent = 'Memperbarui…';
                    clearTimeout(tunda);
                    tunda = setTimeout(muat, 350);
                };

                // Pindah ke isian lain = pindah potongan, tanpa menunggu.
                if (form) {
                    form.addEventListener('focusin', (e) => {
                        const kolom = kolomDari(e.target);
                        if (kolom && kolom !== fokus) {
                            fokus = kolom;
                            clearTimeout(tunda);
                            muat();
                        }
                    });
                    form.addEventListener('input', (e) => {
                        const kolom = kolomDari(e.target);
                        if (kolom) fokus = kolom;
                        jadwalkan();
                    });
                }
                if (pilih) pilih.addEventListener('change', muat);
                panel.querySelector('[data-pratinjau-muat]')?.addEventListener('click', muat);

                // Tinggi iframe mengikuti isi potongan (dikirim oleh halaman di dalamnya).
                window.addEventListener('message', (e) => {
                    if (e.source !== frame.contentWindow || !e.data || !e.data.icPratinjauTinggi) return;
                    frame.style.height = Math.min(Math.max(e.data.icPratinjauTinggi + 4, 96), 640) + 'px';
                });

                // Muat pertama kali hanya saat modulnya mendekati layar, supaya banyak modul tidak merender halaman bersamaan.
                if ('IntersectionObserver' in window) {
                    const amati = new IntersectionObserver((baris) => {
                        if (baris.some((b) => b.isIntersecting)) {
                            amati.disconnect();
                            muat();
                        }
                    }, {
                        rootMargin: '300px'
                    });
                    amati.observe(panel);
                } else {
                    muat();
                }
            });

            // Tautan lama ke tab Konten (mis. /konten#grup-tentang, #privasi) dialihkan ke halaman bagiannya.
            (() => {
                const peta = @json($petaJangkarLama);
                const jangkar = decodeURIComponent(location.hash.replace(/^#/, ''));
                if (!jangkar || !Object.prototype.hasOwnProperty.call(peta, jangkar)) return;
                const tujuan = new URL(peta[jangkar], location.href);
                if (tujuan.pathname !== location.pathname) location.replace(tujuan.href);
            })();

            // Pemilih warna: pemilih bawaan browser dan isian #RRGGBB saling mengikuti; kosong = warna bawaan.
            document.querySelectorAll('[data-warna]').forEach((bungkus) => {
                const teks = bungkus.querySelector('[data-warna-teks]');
                const pilih = bungkus.querySelector('[data-warna-pilih]');
                const bawaan = bungkus.dataset.warnaBawaan;
                const valid = (v) => /^#[0-9a-f]{6}$/i.test(v);
                if (!teks || !pilih) return;

                pilih.addEventListener('input', () => {
                    teks.value = pilih.value;
                    teks.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                });
                teks.addEventListener('input', () => {
                    const nilai = teks.value.trim();
                    if (valid(nilai)) pilih.value = nilai.toLowerCase();
                });
                bungkus.querySelector('[data-warna-kosongkan]')?.addEventListener('click', () => {
                    teks.value = '';
                    pilih.value = bawaan;
                    teks.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                });
            });

            // Pratinjau ikon di samping pilihan ikon.
            document.querySelectorAll('[data-ikon-pilih]').forEach((pilih) => {
                const ikon = pilih.closest('div')?.querySelector('[data-ikon-pratinjau]');
                pilih.addEventListener('change', () => {
                    if (ikon) ikon.className = 'bi bi-' + pilih.value;
                });
            });
        </script>
    @endpush
@endsection
