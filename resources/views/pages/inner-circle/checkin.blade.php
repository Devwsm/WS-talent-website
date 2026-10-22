@extends('template.innerCircleLayout')

@section('title', 'Check-in')

@php
    $adaFilter = count($parameterFilter) > 0;
@endphp

@section('content')
    <p class="ic-eyebrow">Datang. Kumpulkan stempel.</p>
    <h1 class="ic-judul">Check-in<span>.</span></h1>
    <p class="ic-deskripsi">Jadilah bagian dari perjalanan ini. Temukan kegiatan Inner Circle dan kumpulkan stempel
        ketika hadir.</p>

    <section class="ic-filter" aria-labelledby="judul-jadwal">
        <div>
            <h2 class="ic-filter__judul" id="judul-jadwal">Jadwal Pertunjukan WS · Kuartal IV 2026</h2>
            <p class="ic-bantuan">Jadwal berdasarkan rencana WSM dan bersifat tentatif. Tidak semua acara otomatis
                memberi stempel.</p>
        </div>
        <form class="ic-filter__form" method="GET" action="{{ route('ic.checkin') }}" role="search">
            <label class="ic-sr-saja" for="filter-bulan">Bulan</label>
            <select class="ic-input" id="filter-bulan" name="bulan" data-ic-otomatis>
                <option value="all">Okt–Des 2026</option>
                @foreach (config('inner_circle.bulan_event') as $nomor => $nama)
                    <option value="{{ $nomor }}" @selected($filter['bulan'] === (string) $nomor)>{{ $nama }}</option>
                @endforeach
            </select>

            <label class="ic-sr-saja" for="filter-tipe">Tipe acara</label>
            <select class="ic-input" id="filter-tipe" name="tipe" data-ic-otomatis>
                <option value="all">Semua tipe</option>
                @foreach (config('inner_circle.tipe_event') as $kunci => $tipe)
                    <option value="{{ $kunci }}" @selected($filter['tipe'] === $kunci)>{{ $tipe['label'] }}</option>
                @endforeach
            </select>

            <label class="ic-sr-saja" for="filter-q">Cari kota atau venue</label>
            <input class="ic-input" type="search" id="filter-q" name="q" value="{{ $filter['q'] }}"
                placeholder="Cari kota / venue…" maxlength="60">

            <button type="submit" class="ic-tombol ic-tombol--kecil">Terapkan</button>
        </form>
    </section>

    <p class="ic-jumlah" role="status">
        {{ count($events) }} dari {{ $totalEvents }} jadwal ditampilkan
        @if ($adaFilter)
            · <a class="ic-tautan" href="{{ route('ic.checkin') }}">Atur ulang filter</a>
        @endif
    </p>

    <div class="ic-daftar">
        @forelse ($events as $event)
            <x-inner-circle.kartu-event :event="$event" :href="route('ic.checkin', $parameterFilter + ['acara' => $event->kode]) . '#kode-kehadiran'" :dipilih="$eventDipilih && $eventDipilih->kode === $event->kode" />
        @empty
            <div class="ic-panel ic-kosong">
                <b>Belum ada jadwal yang cocok.</b>
                <p class="ic-bantuan">Coba ganti bulan, tipe, atau kata pencarian.</p>
            </div>
        @endforelse
    </div>

    <section class="ic-kotak" id="kode-kehadiran" aria-labelledby="judul-kode">
        <h2 class="ic-kotak__judul" id="judul-kode">✦ Kode kehadiran acara</h2>

        @if ($eventDipilih)
            <p class="ic-bantuan"><b>{{ $eventDipilih->venue }}</b> · {{ $eventDipilih->kota }} ·
                {{ $eventDipilih->tanggal->locale('id')->translatedFormat('D, j M Y') }}</p>
            @if ($eventDipilih->sudah_checkin)
                <p class="ic-bantuan"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Kamu sudah check-in di
                    acara
                    ini.</p>
            @endif
        @else
            <p class="ic-bantuan"><b>Belum memilih acara.</b> Pilih dari daftar di atas, atau langsung pindai QR di lokasi.
            </p>
        @endif

        <ol class="ic-langkah">
            <li>Pindai QR yang disediakan panitia di lokasi, atau ketik kodenya.</li>
            <li>Tekan Verifikasi. Stempel bertambah satu untuk setiap acara.</li>
            <li>Check-in hanya bisa dilakukan saat acara berlangsung.</li>
        </ol>
        <p class="ic-bantuan">Acara privat tidak tersedia untuk check-in.</p>

        <div class="ic-aksi-baris">
            <button type="button" class="ic-tombol ic-tombol--kecil" data-ic-pindai-buka><i class="bi bi-qr-code-scan"
                    aria-hidden="true"></i> Pindai QR dengan kamera</button>
            <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang" data-ic-pindai-berkas><i
                    class="bi bi-image" aria-hidden="true"></i> Unggah gambar QR</button>
            <input type="file" id="ic-qr-berkas" accept="image/*" hidden>
        </div>

        <form method="POST" action="{{ route('ic.checkin.proses') }}" novalidate>
            @csrf
            @if ($eventDipilih)
                <input type="hidden" name="acara" value="{{ $eventDipilih->kode }}">
            @endif
            <label class="ic-label" for="kode">Kode check-in acara</label>
            <input class="ic-input" id="kode" name="kode" value="{{ old('kode', $kodeIsian) }}"
                placeholder="Masukkan kode dari panitia atau hasil pindai QR" autocomplete="off" autocapitalize="characters"
                maxlength="300" required
                @if ($errors->has('kode') || $galatKode) aria-invalid="true" aria-describedby="kode-galat" @endif>
            @if ($errors->has('kode'))
                <p class="ic-galat" id="kode-galat" role="alert">{{ $errors->first('kode') }}</p>
            @elseif ($galatKode)
                <p class="ic-galat" id="kode-galat" role="alert">{{ $galatKode }}</p>
            @endif
            <div class="ic-aksi-baris">
                <button type="submit" class="ic-tombol">Verifikasi &amp; dapatkan stempel <i class="bi bi-arrow-right"
                        aria-hidden="true"></i></button>
            </div>
        </form>
    </section>
@endsection

@push('modal')
    <x-inner-circle.modal id="modal-pindai" judul="Pindai QR check-in" eyebrow="Arahkan kamera">
        <div class="ic-pindai">
            <video id="ic-pindai-video" class="ic-pindai__video" playsinline muted></video>
            <p class="ic-bantuan ic-umpan" id="ic-pindai-umpan" role="status" aria-live="polite"></p>
        </div>
    </x-inner-circle.modal>
@endpush
