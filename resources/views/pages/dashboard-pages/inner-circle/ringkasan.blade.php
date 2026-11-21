@extends('template/icDashboardLayout')
@section('content')
    @php
        $sapaan = match (true) {
            now()->hour < 11 => 'Selamat pagi',
            now()->hour < 15 => 'Selamat siang',
            now()->hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
        $statusKlaim = config('inner_circle.status_klaim');
        $lencana = [
            'hijau' => 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200',
            'merah' => 'border-red-400/30 bg-red-400/10 text-red-300',
            'kuning' => 'border-amber-300/30 bg-amber-300/10 text-amber-200',
            'netral' => 'border-white/15 text-white/60',
        ];
        $kartu = [
            [
                'ikon' => 'bi-people-fill',
                'angka' => $ringkasan->anggota_total,
                'label' => 'Anggota terdaftar',
                'href' => route('ic.admin.anggota'),
            ],
            [
                'ikon' => 'bi-patch-check-fill',
                'angka' => $ringkasan->kehadiran_terverifikasi,
                'label' => 'Kehadiran terverifikasi',
                'href' => route('ic.admin.anggota'),
            ],
            [
                'ikon' => 'bi-inbox-fill',
                'angka' => $ringkasan->klaim_menunggu,
                'label' => 'Penukaran menunggu',
                'href' => route('ic.admin.klaim'),
            ],
            [
                'ikon' => 'bi-chat-left-text-fill',
                'angka' => $ringkasan->pengaduan_terbuka,
                'label' => 'Pengaduan terbuka',
                'href' => route('ic.admin.anggota', ['pengaduan' => 'terbuka']),
            ],
            [
                'ikon' => 'bi-broadcast',
                'angka' => $ringkasan->acara_aktif,
                'label' => 'Acara aktif hari ini',
                'href' => route('ic.admin.acara'),
            ],
            [
                'ikon' => 'bi-megaphone-fill',
                'angka' => $angka->pengumuman_tayang,
                'label' => 'Pengumuman tayang',
                'href' => route('ic.admin.pengumuman'),
            ],
            [
                'ikon' => 'bi-images',
                'angka' => $angka->kenangan_tayang,
                'label' => 'Kenangan tayang',
                'href' => route('ic.admin.kenangan'),
            ],
        ];
    @endphp

    @push('head')
        {{-- CSS area anggota, dipakai kartu acara di bawah (semua class-nya di bawah .ic-app). --}}
        @vite('resources/css/inner-circle.css')
    @endpush

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak: titik-titik halus + dua blob hangat, senada dengan Dashboard CMS Artis --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 z-0 opacity-5"
            style="background-image: radial-gradient(circle, var(--color-white) 1px, transparent 1px); background-size: 26px 26px;">
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-40 -right-32 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.45;"></div>
        <div aria-hidden="true" class="pointer-events-none absolute top-1/2 -left-40 w-80 h-80 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, var(--cms-glow, #5e0006) 0%, transparent 70%); opacity: 0.3;"></div>

        <div
            class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-4 sm:px-5 md:px-10 pt-6 md:pt-8 pb-28 text-white">

            {{-- Sapaan + pintasan ke Inner Circle --}}
            <div
                class="relative rounded-3xl border border-white/10 bg-white/3 overflow-hidden px-5 py-5 md:px-8 md:py-6
                flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/40 mb-1">{{ ic_program() }}</p>
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold uppercase wrap-anywhere">
                        {{ $sapaan }}, <span class="normal-case text-white/80">{{ session('user', 'Admin') }}</span>
                        👋
                    </h1>
                    <p class="text-white/50 mt-1 text-sm md:text-base">
                        Gambaran komunitas Inner Circle hari ini: anggota, kehadiran, hadiah, dan kabar terbaru.
                    </p>
                </div>
                <a href="{{ route('ic.beranda') }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center justify-center gap-2 shrink-0 px-4 py-2 rounded-full border border-white/15
                    text-sm font-semibold text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka Inner Circle
                </a>
            </div>

            @include('components/errors')
            @include('components/success')

            {{-- Enam angka utama, masing-masing menuju tab-nya --}}
            <ul class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($kartu as $k)
                    <li class="min-w-0">
                        <a href="{{ $k['href'] }}"
                            class="block h-full rounded-3xl border border-white/10 bg-white/3 p-5 md:p-6 transition-colors hover:border-white/20 hover:bg-white/5">
                            <i class="bi {{ $k['ikon'] }} text-white/40 text-xl" aria-hidden="true"></i>
                            <p class="text-3xl md:text-4xl font-bold mt-3">{{ number_format($k['angka'], 0, ',', '.') }}</p>
                            <p class="text-xs text-white/50 mt-1">{{ $k['label'] }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Tren: pendaftaran per minggu dan check-in per acara, supaya Ringkasan bukan hanya angka total --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
                @component('components.dashboard.card.card', [
                    'title' => 'Pendaftaran per minggu',
                    'icon' => 'bi-person-plus-fill',
                    'href' => route('ic.admin.anggota'),
                ])
                    <x-dashboard.inner-circle.grafik-batang :data="$tren->pendaftaran" satuan="pendaftar"
                        kosong="Belum ada pendaftaran." />
                    <p class="text-xs text-white/30 mt-3">12 minggu terakhir, Senin sampai Minggu.</p>
                @endcomponent

                @component('components.dashboard.card.card', [
                    'title' => 'Check-in per acara',
                    'icon' => 'bi-geo-alt-fill',
                    'href' => route('ic.admin.acara'),
                ])
                    <x-dashboard.inner-circle.grafik-batang :data="$tren->checkin" satuan="check-in"
                        kosong="Belum ada acara yang sudah berlangsung." />
                    <p class="text-xs text-white/30 mt-3">8 acara terakhir yang tanggalnya sudah tiba, lama ke baru.</p>
                @endcomponent
            </div>

            {{-- Baris 1: Klaim menunggu + Acara berikutnya --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                @component('components.dashboard.card.card', [
                    'title' => 'Penukaran menunggu (' . $ringkasan->klaim_menunggu . ')',
                    'href' => route('ic.admin.klaim'),
                    'icon' => 'bi-inbox-fill',
                ])
                    @if (empty($klaimMenunggu))
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Tidak ada penukaran yang menunggu.',
                        ])
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($klaimMenunggu as $k)
                                <li class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/2 p-3 min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-sm truncate">{{ $k->nama_member }}
                                            <span class="text-white/40 font-normal">· {{ $k->kode_member }}</span>
                                        </p>
                                        <p class="text-xs text-white/50 truncate">{{ ic_angka($k->harga_poin) }} poin ·
                                            {{ $k->judul }} ·
                                            {{ $k->diajukan_pada->locale('id')->translatedFormat('j M Y') }}</p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $lencana['kuning'] }}">{{ $statusKlaim[$k->status]['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endcomponent

                {{-- Acara berikutnya: kartu yang sama dengan yang dilihat anggota di halaman Check-in --}}
                @component('components.dashboard.card.card', [
                    'title' => 'Acara berikutnya',
                    'href' => route('ic.admin.acara'),
                    'icon' => 'bi-calendar-event-fill',
                ])
                    @if ($ringkasan->acara_berikutnya)
                        @php $e = $ringkasan->acara_berikutnya; @endphp
                        <div class="ic-app rounded-3xl p-1 sm:p-4" inert>
                            <x-inner-circle.kartu-event :event="$e" href="#" />
                        </div>
                        <p class="text-xs text-white/40 mt-3">
                            Jendela check-in:
                            {{ $e->jendela_buka->locale('id')->translatedFormat('D, j M') }} pukul
                            {{ $e->jendela_buka->format('H.i') }}
                            sampai
                            {{ $e->jendela_tutup->locale('id')->translatedFormat('D, j M') }} pukul
                            {{ $e->jendela_tutup->format('H.i') }} WIB.
                        </p>
                    @else
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada acara yang akan datang.',
                            'cta_href' => route('ic.admin.acara'),
                            'cta_label' => 'Kelola acara',
                        ])
                    @endif
                @endcomponent
            </div>

            {{-- Baris 2: Anggota terbaru + Tahap hadiah --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                @component('components.dashboard.card.card', [
                    'title' => 'Anggota terbaru',
                    'href' => route('ic.admin.anggota'),
                    'icon' => 'bi-people-fill',
                ])
                    @if ($anggotaBaru->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada anggota yang mendaftar.',
                        ])
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($anggotaBaru as $m)
                                <li class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/2 p-3 min-w-0">
                                    <span aria-hidden="true"
                                        class="shrink-0 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center font-bold text-sm">{{ mb_strtoupper(mb_substr($m->nama, 0, 1)) }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-sm truncate">{{ $m->nama }}
                                            <span class="text-white/40 font-normal">· {{ $m->kode_member }}</span>
                                        </p>
                                        <p class="text-xs text-white/50 truncate">
                                            Daftar
                                            {{ $m->daftar_via === \App\Models\member::VIA_GOOGLE ? 'lewat Google' : 'dengan email' }}
                                            · {{ $m->created_at->locale('id')->translatedFormat('j M Y') }}</p>
                                    </div>
                                    <span
                                        class="shrink-0 text-xs font-semibold text-white/70">{{ number_format($m->poin_total, 0, ',', '.') }}
                                        poin</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endcomponent

                @component('components.dashboard.card.card', [
                    'title' => 'Tahap hadiah (' . $hadiahTahap->count() . ')',
                    'href' => route('ic.admin.hadiah'),
                    'icon' => 'bi-gift-fill',
                ])
                    @if ($hadiahTahap->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada tahap hadiah.',
                        ])
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($hadiahTahap as $h)
                                <li
                                    class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/2 p-3 min-w-0 {{ $h->aktif ? '' : 'opacity-60' }}">
                                    <span
                                        class="shrink-0 min-w-12 text-center rounded-xl bg-white/10 px-2 py-1.5 text-sm font-bold">{{ ic_angka($h->harga_poin) }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-sm truncate">{{ $h->judul }}</p>
                                        <p class="text-xs text-white/50 truncate">{{ $h->deskripsi }}</p>
                                    </div>
                                    @if ($h->klaim_menunggu > 0)
                                        <span
                                            class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $lencana['kuning'] }}">{{ $h->klaim_menunggu }}
                                            menunggu</span>
                                    @elseif (!$h->aktif)
                                        <span
                                            class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $lencana['netral'] }}">Disembunyikan</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endcomponent
            </div>

            {{-- Baris 3: Pengumuman terbaru + Kenangan terbaru --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                @component('components.dashboard.card.card', [
                    'title' => 'Pengumuman terbaru',
                    'href' => route('ic.admin.pengumuman'),
                    'icon' => 'bi-megaphone-fill',
                ])
                    @if ($pengumumanTerbaru->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada pengumuman.',
                            'cta_href' => route('ic.admin.pengumuman'),
                            'cta_label' => 'Tambah pengumuman',
                        ])
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($pengumumanTerbaru as $p)
                                <li class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/2 p-3 min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-sm truncate">{{ $p->judul }}</p>
                                        <p class="text-xs text-white/50 truncate">
                                            {{ $p->terbit_pada->locale('id')->translatedFormat('j M Y') }}@if ($p->tag)
                                                · {{ $p->tag }}
                                            @endif
                                        </p>
                                    </div>
                                    @if (!$p->aktif)
                                        <span
                                            class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $lencana['netral'] }}">Nonaktif</span>
                                    @elseif ($p->terjadwal)
                                        <span
                                            class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $lencana['kuning'] }}">Terjadwal</span>
                                    @else
                                        <span
                                            class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $lencana['hijau'] }}">Tayang</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endcomponent

                @component('components.dashboard.card.card', [
                    'title' => 'Kenangan terbaru',
                    'href' => route('ic.admin.kenangan'),
                    'icon' => 'bi-images',
                ])
                    @if ($kenanganTerbaru->isEmpty())
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Belum ada kenangan yang diunggah anggota.',
                        ])
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($kenanganTerbaru as $k)
                                <li class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/2 p-3 min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-sm truncate">{{ $k->caption }}</p>
                                        <p class="text-xs text-white/50 truncate">{{ $k->member->nama ?? 'Anggota' }} ·
                                            {{ $k->created_at->locale('id')->translatedFormat('j M Y') }}</p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $k->disembunyikan ? $lencana['merah'] : $lencana['hijau'] }}">{{ $k->disembunyikan ? 'Disembunyikan' : 'Tayang' }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="text-xs text-white/40 mt-4">{{ number_format($angka->kenangan_tayang, 0, ',', '.') }} tayang
                            ·
                            {{ number_format($angka->kenangan_disembunyikan, 0, ',', '.') }} disembunyikan</p>
                    @endif
                @endcomponent
            </div>

            {{-- Baris 4: Log + pintasan Konten dan Email --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                @component('components.dashboard.card.card', [
                    'title' => 'Log Inner Circle',
                    'href' => route('ic.admin.log.index'),
                    'icon' => 'bi-journal-text',
                ])
                    <a href="{{ route('ic.admin.log.index') }}"
                        class="flex items-center gap-3 rounded-2xl border p-3 mb-4 {{ $logRingkas->error_terbuka > 0 ? $lencana['merah'] : $lencana['hijau'] }}">
                        <i class="bi {{ $logRingkas->error_terbuka > 0 ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"
                            aria-hidden="true"></i>
                        <span class="text-sm font-semibold">
                            {{ $logRingkas->error_terbuka > 0 ? $logRingkas->error_terbuka . ' masalah masih terbuka' : 'Tidak ada masalah yang terbuka' }}
                        </span>
                    </a>
                    @if ($logRingkas->aktivitas->isEmpty())
                        <p class="text-sm text-white/50">Belum ada perubahan yang tercatat.</p>
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($logRingkas->aktivitas as $a)
                                <li class="min-w-0">
                                    <p class="text-sm font-semibold truncate">{{ $a->aksi }}</p>
                                    <p class="text-xs text-white/50 truncate">
                                        {{ $a->created_at->locale('id')->translatedFormat('j M Y, H.i') }} ·
                                        {{ $a->pelaku ?? 'tidak dikenal' }}@if ($a->perangkat)
                                            · {{ $a->perangkat }}
                                        @endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endcomponent

                @component('components.dashboard.card.card', [
                    'title' => 'Pengaturan cepat',
                    'icon' => 'bi-sliders',
                ])
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ([['route' => 'ic.admin.konten', 'ikon' => 'bi-pencil-square', 'judul' => 'Konten', 'ket' => 'Ubah teks halaman, tipe acara, aturan check-in, dan tampilan.'], ['route' => 'ic.admin.email', 'ikon' => 'bi-envelope-fill', 'judul' => 'Email', 'ket' => 'Atur isi email ke anggota dan kirim email tes.']] as $t)
                            <li>
                                <a href="{{ route($t['route']) }}"
                                    class="flex h-full flex-col gap-2 rounded-2xl border border-white/10 bg-white/4 p-4 text-white/70
                                    hover:border-white/20 hover:bg-white/10 hover:text-white transition-colors">
                                    <i class="bi {{ $t['ikon'] }} text-2xl" aria-hidden="true"></i>
                                    <span class="text-sm font-semibold">{{ $t['judul'] }}</span>
                                    <span class="text-xs text-white/50">{{ $t['ket'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endcomponent
            </div>
        </div>
    </div>
@endsection
