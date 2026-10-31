@extends('template.innerCircleLayout')

@section('title', 'Check-in')

@php
    $adaFilter = count($parameterFilter) > 0;
    // Lembar check-in terbuka sendiri bila acara sudah terpilih (lewat QR atau tautan kartu), termasuk saat ada galat.
    $bukaLembar = (bool) $eventDipilih;
    $galatHalaman = $errors->first('kode') ?: $galatKode;
@endphp

@section('content')
    <p class="ic-eyebrow">Datang. Kumpulkan stempel.</p>
    <h1 class="ic-judul">Check-in<span>.</span></h1>
    <p class="ic-deskripsi">Jadilah bagian dari perjalanan ini. Temukan kegiatan Inner Circle dan kumpulkan stempel
        ketika hadir.</p>

    <x-inner-circle.panel-verifikasi />

    @if ($galatHalaman && !$eventDipilih)
        <p class="ic-galat ic-galat--blok ic-galat--halaman" role="alert">{{ $galatHalaman }}</p>
    @endif

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
            <x-inner-circle.kartu-event :event="$event" :href="route('ic.checkin', $parameterFilter + ['acara' => $event->kode])" :dipilih="$eventDipilih && $eventDipilih->kode === $event->kode" />
        @empty
            <div class="ic-panel ic-kosong">
                <b>Belum ada jadwal yang cocok.</b>
                <p class="ic-bantuan">Coba ganti bulan, tipe, atau kata pencarian.</p>
            </div>
        @endforelse
    </div>

    <section class="ic-kotak" aria-labelledby="judul-kode">
        <h2 class="ic-kotak__judul" id="judul-kode">✦ Sudah di lokasi?</h2>
        <p class="ic-bantuan">Pilih acara di atas, atau langsung pindai QR dari panitia. Acaranya terpilih otomatis.</p>
        <ol class="ic-langkah">
            <li>Pilih acara, lalu izinkan akses lokasi bila diminta (hanya untuk memastikan kamu ada di venue).</li>
            <li>Pindai QR panitia atau ketik kodenya, lalu tekan Check-in sekarang.</li>
            <li>Check-in hanya bisa dilakukan saat acara berlangsung. Satu acara memberi satu stempel.</li>
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
@endsection

@push('modal')
    {{-- Lembar check-in: naik dari bawah di HP. Isinya diisi JavaScript dari kartu acara (atau dari #ic-checkin-awal). --}}
    <x-inner-circle.modal id="modal-checkin" :judul="$eventDipilih->venue ?? 'Check-in'" eyebrow="Kode kehadiran" lembar :terbuka="$bukaLembar"
        :data-ic-url-checkin="route('ic.checkin')">
        @if ($eventDipilih)
            <div hidden id="ic-checkin-awal"
                {{ new \Illuminate\View\ComponentAttributeBag($eventDipilih->atributCheckin()) }}></div>
        @endif

        <form id="ic-checkin-form" method="POST" action="{{ route('ic.checkin.proses') }}" novalidate>
            @csrf
            <input type="hidden" name="acara" id="ic-checkin-acara" value="{{ $eventDipilih->kode ?? '' }}">
            <input type="hidden" name="lat" id="ic-checkin-lat">
            <input type="hidden" name="lng" id="ic-checkin-lng">
            <input type="hidden" name="akurasi" id="ic-checkin-akurasi">

            <ul class="ic-ringkas">
                <li><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> <span
                        data-ic-isi="kota">{{ $eventDipilih->kota ?? '' }}</span></li>
                <li><i class="bi bi-calendar-event" aria-hidden="true"></i> <span
                        data-ic-isi="tanggal">{{ $eventDipilih?->tanggal->locale('id')->translatedFormat('D, j M Y') }}</span>
                </li>
                <li><span class="ic-pil"
                        data-ic-isi="tipe">{{ $eventDipilih ? config('inner_circle.tipe_event.' . $eventDipilih->tipe . '.label') : '' }}</span>
                </li>
            </ul>

            <p class="ic-checkin__pesan" id="ic-checkin-pesan" data-status="{{ $eventDipilih?->statusCheckin() }}"
                role="status" data-ic-isi="pesan">
                {{ $eventDipilih ? $eventDipilih->pesanCheckin($eventDipilih->statusCheckin()) : '' }}</p>

            @if ($galatHalaman && $eventDipilih)
                <p class="ic-galat ic-galat--blok" id="ic-checkin-galat" role="alert">{{ $galatHalaman }}</p>
            @endif

            <div id="ic-checkin-isi" @if ($eventDipilih && $eventDipilih->statusCheckin() !== 'siap') hidden @endif>
                <label class="ic-label" for="kode">Kode check-in acara</label>
                <input class="ic-input" id="kode" name="kode" value="{{ old('kode', $kodeIsian) }}"
                    placeholder="Pindai QR atau ketik kode dari panitia" autocomplete="off" autocapitalize="characters"
                    maxlength="300" required>
                <p class="ic-galat" id="kode-galat-js" role="alert" hidden></p>

                <div class="ic-aksi-baris ic-aksi-baris--rapat">
                    <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang" data-ic-pindai-buka><i
                            class="bi bi-qr-code-scan" aria-hidden="true"></i> Pindai QR</button>
                    <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang" data-ic-pindai-berkas><i
                            class="bi bi-image" aria-hidden="true"></i> Unggah gambar</button>
                </div>

                <div class="ic-lokasi" id="ic-lokasi" data-state="diam" @if (!$eventDipilih || !$eventDipilih->lokasi_aktif) hidden @endif>
                    <div class="ic-lokasi__status" role="status" aria-live="polite">
                        <span class="ic-lokasi__ikon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                        <span class="ic-lokasi__teks">
                            <b id="ic-lokasi-judul">Lokasi belum dicek</b>
                            <span class="ic-lokasi__detail" id="ic-lokasi-detail">Lokasi dicek saat kamu menekan Check-in
                                sekarang.</span>
                        </span>
                    </div>
                    <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang" data-ic-lokasi-ulang>
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Cek lokasi lagi</button>
                </div>

                <button type="submit" class="ic-tombol ic-tombol--penuh" id="ic-checkin-kirim">Check-in sekarang <i
                        class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </div>

            <button type="button" class="ic-tombol ic-tombol--terang ic-tombol--penuh" id="ic-checkin-tutup"
                data-ic-modal-tutup @if (!$eventDipilih || $eventDipilih->statusCheckin() === 'siap') hidden @endif>Tutup</button>
        </form>
    </x-inner-circle.modal>

    <x-inner-circle.modal id="modal-pindai" judul="Pindai QR check-in" eyebrow="Arahkan kamera" lembar>
        <div class="ic-pindai">
            <video id="ic-pindai-video" class="ic-pindai__video" playsinline muted></video>
            <p class="ic-bantuan ic-umpan" id="ic-pindai-umpan" role="status" aria-live="polite"></p>
        </div>
    </x-inner-circle.modal>
@endpush
