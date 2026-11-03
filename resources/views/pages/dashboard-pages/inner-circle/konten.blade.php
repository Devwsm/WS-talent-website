@extends('template/icDashboardLayout')
@section('content')
    @php
        $kelasInput =
            'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
        $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
        $kelasTombol =
            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'konten',
                'judul' => 'Konten',
                'deskripsi' =>
                    'Ubah teks halaman anggota, halaman Tentang, dan teaser beranda tanpa menyentuh kode. Kosongkan isian untuk memakai teks bawaan.',
                'pakaiCss' => false,
            ])

            @include('components/errors')
            @include('components/success')

            {{-- Lompat ke bagian: berderet dan turun baris di layar sempit, tidak pernah melebar ke samping. --}}
            <nav aria-label="Bagian konten" class="flex flex-wrap gap-2">
                @foreach ($grup as $kodeGrup => $info)
                    <a href="#grup-{{ $kodeGrup }}"
                        class="min-h-11 px-3.5 inline-flex items-center gap-2 rounded-full border border-white/15 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                        <i class="bi {{ $info['ikon'] }} text-white/50" aria-hidden="true"></i>
                        <span class="wrap-break-word">{{ $info['label'] }}</span>
                    </a>
                @endforeach
                @foreach (['pilihan-tipe' => ['bi-calendar-event', 'Tipe acara'], 'pilihan-kategori' => ['bi-images', 'Kategori kenangan'], 'aturan-checkin' => ['bi-sliders', 'Aturan check-in']] as $jangkar => [$ikonNav, $labelNav])
                    <a href="#{{ $jangkar }}"
                        class="min-h-11 px-3.5 inline-flex items-center gap-2 rounded-full border border-white/15 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                        <i class="bi {{ $ikonNav }} text-white/50" aria-hidden="true"></i>
                        <span class="wrap-break-word">{{ $labelNav }}</span>
                    </a>
                @endforeach
            </nav>

            @foreach ($grup as $kodeGrup => $info)
                @php
                    $isianGrup = $kunci->where('grup', $kodeGrup);
                    $jumlahDiubah = $isianGrup->keys()->filter(fn($k) => isset($tersimpan[$k]))->count();
                @endphp

                <section id="grup-{{ $kodeGrup }}" aria-labelledby="judul-{{ $kodeGrup }}"
                    class="scroll-mt-8 flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                        <div class="min-w-0">
                            <h2 id="judul-{{ $kodeGrup }}"
                                class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                                <i class="bi {{ $info['ikon'] }} text-white/50" aria-hidden="true"></i>
                                {{ $info['label'] }}
                            </h2>
                            <p class="text-xs text-white/40 mt-1">{{ $info['petunjuk'] }}
                                @if ($jumlahDiubah > 0)
                                    <span class="text-white/60">{{ $jumlahDiubah }} dari {{ $isianGrup->count() }} isian
                                        sudah diubah.</span>
                                @endif
                            </p>
                        </div>
                        @if (!empty($info['halaman']))
                            <a href="{{ route($info['halaman']) }}{{ !empty($info['jangkar']) ? '#' . $info['jangkar'] : '' }}"
                                target="_blank" rel="noopener"
                                class="{{ $kelasTombol }} self-start sm:self-auto shrink-0">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Lihat halaman</a>
                        @endif
                    </div>

                    {{-- Form di kiri, pratinjau halaman asli di kanan (di bawah form pada layar sempit). --}}
                    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_minmax(0,26rem)] gap-6 items-start">
                        <div class="flex flex-col gap-4 min-w-0">
                            <form method="POST" id="form-{{ $kodeGrup }}"
                                action="{{ route('ic.admin.konten.simpan', $kodeGrup) }}"
                                class="rounded-3xl border border-white/10 bg-white/3 p-5 sm:p-6 md:p-8 flex flex-col gap-6">
                                @csrf

                                @foreach ($isianGrup as $namaKunci => $def)
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
                                                value="{{ $nilai }}" placeholder="{{ $def['bawaan'] }}" data-hitung
                                                class="{{ $kelasInput }}">
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
                                                    Kembalikan bawaan (isian lain di bagian ini yang belum disimpan tidak
                                                    ikut)</button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                <div class="flex flex-wrap gap-2">
                                    <button type="submit"
                                        class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                        <i class="bi bi-check2-circle" aria-hidden="true"></i> Simpan
                                        {{ $info['label'] }}</button>
                                </div>
                            </form>

                            @foreach ($info['daftar'] ?? [] as $kodeDaftar)
                                @include('components.dashboard.inner-circle.daftar-konten')
                            @endforeach
                        </div>

                        @include('components.dashboard.inner-circle.pratinjau', [
                            'kodeGrup' => $kodeGrup,
                            'info' => $info,
                        ])
                    </div>
                </section>
            @endforeach

            @include('components.dashboard.inner-circle.pilihan-tipe')
            @include('components.dashboard.inner-circle.pilihan-kategori')
            @include('components.dashboard.inner-circle.aturan-checkin')
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

            // Pratinjau langsung: halaman asli dirender server dengan isian form yang belum disimpan (iframe di kanan form).
            // Dimuat saat bagiannya terlihat, lalu diperbarui sebentar setelah berhenti mengetik. Posisi gulir dipertahankan.
            document.querySelectorAll('[data-pratinjau]').forEach((panel) => {
                const form = document.getElementById(panel.dataset.form);
                const frame = panel.querySelector('[data-pratinjau-frame]');
                const status = panel.querySelector('[data-pratinjau-status]');
                const pilih = panel.querySelector('[data-pratinjau-halaman]');
                if (!form || !frame) return;

                let tunda = null;
                let batal = null;
                let sudahTampil = false;

                const muat = async () => {
                    if (batal) batal.abort();
                    batal = new AbortController();
                    status.textContent = 'Memuat…';

                    const data = new FormData(form);
                    data.set('grup', panel.dataset.grup);
                    if (pilih) data.set('halaman', pilih.value);

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
                        const y = sudahTampil && frame.contentWindow ? frame.contentWindow.scrollY : 0;
                        frame.addEventListener('load', () => {
                            if (y > 0 && frame.contentWindow) frame.contentWindow.scrollTo(0, y);
                        }, {
                            once: true
                        });
                        frame.srcdoc = html;
                        sudahTampil = true;
                        status.textContent = 'Pratinjau diperbarui';
                    } catch (galat) {
                        if (galat.name !== 'AbortError') status.textContent =
                            'Pratinjau gagal dimuat. Coba muat ulang.';
                    }
                };

                const jadwalkan = () => {
                    status.textContent = 'Memperbarui…';
                    clearTimeout(tunda);
                    tunda = setTimeout(muat, 600);
                };

                form.addEventListener('input', jadwalkan);
                if (pilih) pilih.addEventListener('change', muat);
                panel.querySelector('[data-pratinjau-muat]')?.addEventListener('click', muat);

                // Muat pertama kali hanya saat panelnya mendekati layar, supaya delapan halaman tidak dirender sekaligus.
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
