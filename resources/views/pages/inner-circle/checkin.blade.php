@extends('template.innerCircleLayout')

@section('title', 'Acara')

@php
    $adaFilter = count($parameterFilter) > 0;
    // Lembar check-in terbuka sendiri bila acara sudah terpilih (lewat QR atau tautan kartu), termasuk saat ada galat.
    $bukaLembar = (bool) $eventDipilih;
    $galatHalaman = $errors->first('kode') ?: $galatKode;
@endphp

@section('content')
    <p class="ic-eyebrow">{{ ic_teks('checkin.eyebrow') }}</p>
    <h1 class="ic-judul">Acara<span>.</span></h1>
    <p class="ic-deskripsi">{{ ic_teks('checkin.deskripsi') }}</p>

    <x-inner-circle.panel-verifikasi />

    @if ($galatHalaman && !$eventDipilih)
        <p class="ic-galat ic-galat--blok ic-galat--halaman" role="alert">{{ $galatHalaman }}</p>
    @endif

    <section class="ic-filter" aria-labelledby="judul-jadwal">
        <div>
            <h2 class="ic-filter__judul" id="judul-jadwal">{{ ic_teks('checkin.jadwal.judul') }}</h2>
            <p class="ic-bantuan">{{ ic_teks('checkin.jadwal.deskripsi') }}</p>
        </div>
        <form class="ic-filter__form" method="GET" action="{{ route('ic.checkin') }}" role="search">
            <label class="ic-sr-saja" for="filter-bulan">Bulan</label>
            <select class="ic-input" id="filter-bulan" name="bulan" data-ic-otomatis>
                <option value="all">Semua bulan</option>
                @foreach ($bulanPilihan as $ym => $nama)
                    <option value="{{ $ym }}" @selected($filter['bulan'] === $ym)>{{ $nama }}</option>
                @endforeach
            </select>

            <label class="ic-sr-saja" for="filter-tipe">Tipe acara</label>
            <select class="ic-input" id="filter-tipe" name="tipe" data-ic-otomatis>
                <option value="all">Semua tipe</option>
                @foreach (ic_tipe_event() as $kunci => $tipe)
                    <option value="{{ $kunci }}" @selected($filter['tipe'] === $kunci)>{{ $tipe['label'] }}</option>
                @endforeach
            </select>

            <label class="ic-sr-saja" for="filter-q">Cari kota atau venue</label>
            <input class="ic-input" type="search" id="filter-q" name="q" value="{{ $filter['q'] }}"
                placeholder="Cari kota / venue…" maxlength="60">

            <button type="submit" class="ic-tombol ic-tombol--kecil">Terapkan</button>
        </form>
    </section>

    <section class="ic-kotak" aria-labelledby="judul-kode">
        <h2 class="ic-kotak__judul" id="judul-kode">✦ {{ ic_teks('checkin.lokasi.judul') }}</h2>
        <p class="ic-bantuan">{{ ic_teks('checkin.lokasi.deskripsi') }}</p>
        <ol class="ic-langkah">
            <li>Pilih acara, lalu izinkan akses lokasi bila diminta (hanya untuk memastikan kamu ada di venue).</li>
            <li>Pindai QR panitia atau ketik kodenya, lalu tekan Check-in sekarang.</li>
            <li>Check-in hanya bisa dilakukan saat acara berlangsung. Satu acara memberi poin satu kali.</li>
        </ol>
        <p class="ic-bantuan">Acara privat tidak tersedia untuk check-in.</p>
        <div class="ic-aksi-baris ic-aksi-baris--rapat">
            <button type="button" class="ic-tombol ic-tombol--kecil" data-ic-pindai-buka><i class="bi bi-qr-code-scan"
                    aria-hidden="true"></i> Pindai QR dengan kamera</button>
            <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang" data-ic-pindai-berkas><i
                    class="bi bi-image" aria-hidden="true"></i> Unggah gambar QR</button>
            <input type="file" id="ic-qr-berkas" accept="image/*" hidden>
        </div>
        <noscript>
            <p class="ic-galat">Check-in membutuhkan JavaScript. Aktifkan JavaScript di browser lalu muat ulang halaman.</p>
        </noscript>
    </section>

    <p class="ic-jumlah" role="status">
        {{ count($events) }} dari {{ $totalEvents }} jadwal ditampilkan
        @if ($adaFilter)
            · <a class="ic-tautan" href="{{ route('ic.checkin') }}">Atur ulang filter</a>
        @endif
    </p>

    <div class="ic-poster-grid">
        @forelse ($events as $event)
            <x-inner-circle.kartu-poster :event="$event" :href="route('ic.acara.detail', $event->kode)" />
        @empty
            <div class="ic-panel ic-kosong ic-poster-grid__kosong">
                <b>Belum ada jadwal yang cocok.</b>
                <p class="ic-bantuan">Coba ganti bulan, tipe, atau kata pencarian.</p>
            </div>
        @endforelse
    </div>
@endsection

@push('modal')
    <x-inner-circle.lembar-checkin :event-dipilih="$eventDipilih" :kode-isian="$kodeIsian" :galat-halaman="$galatHalaman" :boleh-mandiri="$bolehMandiri" />
@endpush
