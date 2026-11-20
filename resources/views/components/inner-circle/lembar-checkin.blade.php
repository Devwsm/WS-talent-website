@props([
    'eventDipilih' => null,
    'kodeIsian' => '',
    'galatHalaman' => null,
    'bolehMandiri' => false,
    'bukaAwal' => null,
    'kembaliKeDetail' => false,
])
@php
    // Terbuka sendiri bila acaranya sudah terpilih (QR, tautan kartu) atau bila pemanggil memintanya (mis. ada galat).
    $bukaLembar = $bukaAwal ?? (bool) $eventDipilih;
@endphp
{{--
    Lembar check-in dan lembar pemindai QR. Dipakai halaman Check-in (daftar acara) dan halaman detail acara.
    JavaScript (resources/js/inner-circle.js) membaca id dan atribut data-* di sini, jadi id jangan diganti.
    $bolehMandiri: anggota non-pemenang GL melihat tombol check-in mandiri (tanpa QR, memakai lokasi).
    $kembaliKeDetail: setelah check-in gagal, kembali ke halaman detail acara (bukan daftar acara).
--}}
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
        @if ($kembaliKeDetail)
            <input type="hidden" name="kembali" value="detail">
        @endif
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
                    data-ic-isi="tipe">{{ $eventDipilih ? ic_tipe_event()[$eventDipilih->tipe]['label'] ?? '' : '' }}</span>
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

            @if ($bolehMandiri)
                {{-- Tanpa QR: kode tidak wajib, lokasi HP yang dinilai server. Hanya untuk non-pemenang GL. --}}
                <button type="submit" class="ic-tombol ic-tombol--terang ic-tombol--penuh" id="ic-checkin-mandiri"
                    formaction="{{ route('ic.checkin.mandiri') }}" formnovalidate data-ic-mandiri>
                    <i class="bi bi-geo-alt" aria-hidden="true"></i> Check-in mandiri (tanpa QR)</button>
                <p class="ic-bantuan">Tanpa QR, check-in dinilai dari lokasi HP-mu dan memberi poin mandiri.
                    Sudah punya QR panitia? Pindai di atas.</p>
            @endif
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
