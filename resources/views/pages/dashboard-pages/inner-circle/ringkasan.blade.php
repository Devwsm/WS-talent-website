@extends('template/dashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.tabs', [
                'aktif' => 'ringkasan',
                'judul' => 'Ringkasan',
                'deskripsi' =>
                    'Gambaran singkat komunitas Inner Circle: anggota, kehadiran, klaim hadiah, dan acara.',
            ])

            @php
                $kartu = [
                    ['ikon' => 'bi-people-fill', 'angka' => $ringkasan->anggota_total, 'label' => 'Anggota terdaftar'],
                    [
                        'ikon' => 'bi-patch-check-fill',
                        'angka' => $ringkasan->kehadiran_terverifikasi,
                        'label' => 'Kehadiran terverifikasi',
                    ],
                    ['ikon' => 'bi-inbox-fill', 'angka' => $ringkasan->klaim_menunggu, 'label' => 'Klaim menunggu'],
                    ['ikon' => 'bi-broadcast', 'angka' => $ringkasan->acara_aktif, 'label' => 'Acara aktif hari ini'],
                ];
            @endphp

            {{-- Empat angka utama --}}
            <ul class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($kartu as $k)
                    <li class="rounded-3xl border border-white/10 bg-white/3 p-5 md:p-6 min-w-0">
                        <i class="bi {{ $k['ikon'] }} text-white/40 text-xl" aria-hidden="true"></i>
                        <p class="text-3xl md:text-4xl font-bold mt-3">{{ number_format($k['angka'], 0, ',', '.') }}</p>
                        <p class="text-xs text-white/50 mt-1">{{ $k['label'] }}</p>
                    </li>
                @endforeach
            </ul>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                {{-- Klaim yang menunggu tindakan --}}
                @component('components.dashboard.card.card', [
                    'title' => 'Klaim menunggu (' . $ringkasan->klaim_menunggu . ')',
                    'href' => route('ic.admin.klaim'),
                    'icon' => 'bi-inbox-fill',
                ])
                    @if (empty($klaimMenunggu))
                        @include('components/dashboard/card/empty-state', [
                            'message' => 'Tidak ada klaim yang menunggu.',
                        ])
                    @else
                        @php $statusKlaim = config('inner_circle.status_klaim'); @endphp
                        <ul class="flex flex-col gap-3">
                            @foreach ($klaimMenunggu as $k)
                                <li class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/2 p-3 min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-sm truncate">{{ $k->nama_member }}
                                            <span class="text-white/40 font-normal">· {{ $k->kode_member }}</span>
                                        </p>
                                        <p class="text-xs text-white/50 truncate">{{ $k->target_stempel }} stempel ·
                                            {{ $k->judul }} ·
                                            {{ $k->diajukan_pada->locale('id')->translatedFormat('j M Y') }}</p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full border border-amber-300/30 bg-amber-300/10 px-2.5 py-1 text-[11px] font-semibold text-amber-200">{{ $statusKlaim[$k->status]['label'] }}</span>
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
                        <div class="ic-app rounded-3xl p-4" inert>
                            <x-inner-circle.kartu-event :event="$e" href="#" />
                        </div>
                        <p class="text-xs text-white/40 mt-3">
                            Jendela check-in:
                            {{ $e->tanggal->copy()->setTime(12, 0)->locale('id')->translatedFormat('D, j M') }} pukul 12.00
                            sampai
                            {{ $e->tanggal->copy()->addDay()->setTime(6, 0)->locale('id')->translatedFormat('D, j M') }} pukul
                            06.00 WIB.
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
        </div>
    </div>
@endsection
