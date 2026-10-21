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
        @else
            <p class="ic-bantuan"><b>Belum memilih acara.</b> Pilih acara dari daftar di atas.</p>
        @endif

        <ol class="ic-langkah">
            <li>Pilih pertunjukan dari daftar.</li>
            <li>Pindai QR yang disediakan panitia di lokasi, atau ketik kodenya.</li>
            <li>Stempel bertambah satu untuk setiap acara.</li>
        </ol>
        <p class="ic-bantuan">Acara privat tidak tersedia untuk check-in.</p>

        <div class="ic-aksi-baris">
            <button type="button" class="ic-tombol ic-tombol--kecil" data-ic-segera="Pemindai kamera segera hadir."
                @disabled(!$eventDipilih)><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Pindai QR dengan
                kamera</button>
            <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang"
                data-ic-segera="Unggah gambar QR segera hadir." @disabled(!$eventDipilih)><i class="bi bi-image"
                    aria-hidden="true"></i> Unggah gambar QR</button>
        </div>

        <form data-ic-segera="Check-in segera aktif.">
            <label class="ic-label" for="kode">Kode check-in acara</label>
            <input class="ic-input" id="kode" name="kode"
                placeholder="Masukkan kode dari panitia atau hasil pindai QR" autocomplete="off" required
                @disabled(!$eventDipilih)>
            <div class="ic-aksi-baris">
                <button type="submit" class="ic-tombol" @disabled(!$eventDipilih)>Verifikasi &amp; dapatkan
                    stempel <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </div>
        </form>
    </section>
@endsection
