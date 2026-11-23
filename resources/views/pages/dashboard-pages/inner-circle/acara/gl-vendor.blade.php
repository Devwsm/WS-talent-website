@extends('template/icDashboardLayout')
@section('content')
    @php
        $kelasTombol =
            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 text-xs font-semibold uppercase tracking-widest text-white/70 hover:bg-white/10 hover:text-white transition-colors';
        $kelasTombolUtama =
            'h-11 px-5 inline-flex items-center justify-center gap-2 rounded-lg bg-white text-black text-xs font-semibold uppercase tracking-widest hover:bg-white/80 transition-colors disabled:opacity-50 disabled:pointer-events-none';
    @endphp

    @push('head')
        @vite('resources/js/kartu-gl-pdf.js')
    @endpush

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.kepala', [
                'aktif' => 'acara',
                'judul' => 'Kartu GL untuk vendor',
                'deskripsi' => $data['acara'] . ' · ' . $data['tanggal'],
            ])

            @include('components.dashboard.inner-circle.tab-acara', ['aktifTab' => 'harih'])

            @include('components/errors')
            @include('components/success')

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('ic.admin.acara.gl', $event->kode) }}#vendor" class="{{ $kelasTombol }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Panel undian GL</a>
            </div>

            <div class="rounded-2xl border border-amber-300/30 bg-amber-300/10 px-5 py-4 text-sm text-amber-100">
                <p class="font-semibold"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i> PDF ini memuat username
                    dan sandi acara.</p>
                <p class="text-amber-100/80 mt-1">Bagikan hanya ke vendor club atau festival acara ini. Daftar pemenang
                    hanya
                    berisi nama dan Member ID (tanpa email). Pembukaan halaman ini tercatat di Log.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="bagikan-pdf" class="{{ $kelasTombolUtama }}" disabled><i class="bi bi-share-fill"
                        aria-hidden="true"></i> <span>Bagikan PDF</span></button>
                <button type="button" id="unduh-pdf" class="{{ $kelasTombol }}" disabled><i class="bi bi-download"
                        aria-hidden="true"></i> <span>Unduh PDF</span></button>
                <span id="status-pdf" role="status" aria-live="polite" class="text-xs text-white/60"></span>
            </div>

            {{-- Pratinjau: tampilannya mencerminkan isi PDF. PDF-nya sendiri digambar dari data JSON di bawah. --}}
            <article aria-label="Pratinjau kartu GL"
                class="w-full max-w-3xl mx-auto overflow-hidden rounded-3xl bg-white text-slate-900 shadow-2xl">
                <header class="px-6 py-5 text-white" style="background:#17629a">
                    <p class="text-xs font-bold tracking-[0.2em]">WS INNER CIRCLE</p>
                    <h2 class="text-2xl font-extrabold mt-1">Daftar GL Gratis</h2>
                </header>

                <div class="p-6 flex flex-col gap-5">
                    <div>
                        <p class="text-xl font-extrabold wrap-anywhere">{{ $data['acara'] }}</p>
                        <p class="text-sm text-slate-500">{{ $data['tanggal'] }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-5 sm:items-center">
                        <dl class="flex-1 min-w-0 grid gap-4">
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Username acara
                                </dt>
                                <dd class="font-mono font-bold text-lg wrap-anywhere">{{ $data['username'] ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Sandi / kode
                                    acara</dt>
                                <dd class="font-mono font-bold text-lg wrap-anywhere">{{ $data['sandi'] ?: '-' }}</dd>
                            </div>
                            <p class="text-xs text-slate-500">Pemenang GL menunjukkan Kartu GL di pintu. Scan QR acara
                                untuk mendapat +{{ ic_angka($data['poin']) }} poin.</p>
                        </dl>
                        <figure class="shrink-0 self-center text-center">
                            <div class="w-40 h-40 [&>svg]:w-full [&>svg]:h-full">{!! $qrSvg !!}</div>
                            <figcaption class="text-[11px] text-slate-500 mt-1">Scan: +{{ ic_angka($data['poin']) }} poin
                            </figcaption>
                        </figure>
                    </div>

                    @if ($data['petunjuk'] !== '')
                        <p class="text-sm"><b>Cara masuk:</b> {{ $data['petunjuk'] }}</p>
                    @endif

                    <div>
                        <h3 class="font-bold">Daftar GL gratis ({{ count($data['pemenang']) }} pemenang dari kuota
                            {{ $data['kuota'] }})</h3>
                        @if (count($data['pemenang']) === 0)
                            <p class="text-sm text-slate-500 mt-2">Belum ada pemenang.</p>
                        @else
                            <div class="overflow-x-auto mt-2">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-sky-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                            <th class="px-3 py-2 w-12">No</th>
                                            <th class="px-3 py-2">Nama</th>
                                            <th class="px-3 py-2 whitespace-nowrap">Member ID</th>
                                            <th class="px-3 py-2 w-16 text-center">Hadir</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach ($data['pemenang'] as $p)
                                            <tr>
                                                <td class="px-3 py-2 text-slate-500">{{ $p['no'] }}</td>
                                                <td class="px-3 py-2 font-semibold wrap-anywhere">{{ $p['nama'] }}</td>
                                                <td class="px-3 py-2 font-mono text-xs whitespace-nowrap">
                                                    {{ $p['kode'] }}</td>
                                                <td class="px-3 py-2 text-center"><span aria-hidden="true"
                                                        class="inline-block w-4 h-4 rounded border border-slate-400"></span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <p class="text-[11px] text-slate-400">Dibuat {{ $data['dibuat'] }}. Memuat sandi acara: bagikan hanya
                        ke vendor.</p>
                </div>
            </article>
        </div>
    </div>

    <script type="application/json" id="data-kartu-gl">@json($data)</script>
@endsection
