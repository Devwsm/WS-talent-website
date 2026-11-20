@extends('template.innerCircleLayout')

@section('title', $event->venue ?? 'Acara')

@php
    $tipe = ic_tipe_event()[$event->tipe] ?? ['label' => 'Acara', 'ikon' => 'ticket', 'warna' => 'lilac'];
    $jamUndian = $waktuUndian->locale('id')->translatedFormat('D, j M \p\u\k\u\l H.i') . ' WIB';
    $jamBuka = $event->jendela_buka->locale('id')->translatedFormat('D, j M \p\u\k\u\l H.i') . ' WIB';

    // Lencana di atas poster: keadaan GL yang paling penting bagi anggota ini.
    $lencana = match ($statusGl) {
        'buka' => 'GL dibuka · diundi ' . $jamUndian,
        'antri' => 'Kamu dalam antrean · diundi ' . $jamUndian,
        'menang' => 'Kamu menang GL',
        'tidak_terpilih' => 'GL: tidak terpilih',
        'tertunda' => 'Undian GL tertunda',
        'ditutup' => 'Antrean GL ditutup',
        default => null,
    };

    $labelCheckin = [
        'sudah' => 'Sudah check-in',
        'belum' => 'Check-in dibuka ' . $event->jendela_buka->locale('id')->translatedFormat('j M H.i'),
        'tutup' => 'Check-in ditutup',
        'siap' => $pemenangGl ? 'Check-in (pindai QR)' : 'Check-in',
    ][$statusCheckin];

    $labelGlMati =
        [
            'tidak_ada' => 'Tidak ada GL',
            'ditutup' => 'Antrean GL ditutup',
            'tertunda' => 'Undian tertunda',
            'menang' => 'Kamu menang GL',
            'tidak_terpilih' => 'Tidak terpilih',
        ][$statusGl] ?? null;
    $galatCheckin = $errors->first('kode');
@endphp

@section('content')
    <article class="ic-acara">
        <div class="ic-acara__poster ic-warna-{{ $tipe['warna'] }}">
            @if ($event->poster_url)
                <img src="{{ $event->poster_url }}" alt="Poster {{ $event->venue }}" width="600" height="800"
                    decoding="async" fetchpriority="high">
            @else
                <span class="ic-acara__seni" aria-hidden="true"><x-inner-circle.ikon :nama="$tipe['ikon']"
                        kelas="ic-ikon--event" /></span>
            @endif
            <a class="ic-acara__kembali" href="{{ route('ic.checkin') }}" aria-label="Kembali ke daftar acara">
                <i class="bi bi-chevron-left" aria-hidden="true"></i></a>
            @if ($lencana)
                <span class="ic-acara__lencana" role="status">{{ $lencana }}</span>
            @endif
        </div>

        <div class="ic-acara__isi">
            <div class="ic-acara__judul-baris">
                <h1 class="ic-acara__judul">{{ $event->venue }}</h1>
                <button type="button" class="ic-acara__bagikan" data-ic-bagikan
                    data-judul="{{ $event->venue }}{{ $event->kota ? ', ' . $event->kota : '' }}"
                    data-url="{{ route('ic.acara.detail', $event->kode) }}" aria-label="Bagikan acara ini">
                    <i class="bi bi-box-arrow-up" aria-hidden="true"></i></button>
            </div>

            <ul class="ic-acara__meta">
                <li><i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <span>{{ $event->tanggal->locale('id')->translatedFormat('l, j F Y') }}</span>
                </li>
                <li><i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                    <span>{{ $event->venue }}{{ $event->kota ? ', ' . $event->kota : '' }}</span>
                </li>
                <li><i class="bi bi-tag-fill" aria-hidden="true"></i> <span>{{ $tipe['label'] }}</span></li>
            </ul>

            @if (filled($event->deskripsi))
                <p class="ic-acara__deskripsi">{{ $event->deskripsi }}</p>
            @endif

            <x-inner-circle.panel-verifikasi />

            @if ($galatCheckin)
                <p class="ic-galat ic-galat--blok" role="alert">{{ $galatCheckin }}</p>
            @endif

            {{-- Kartu GL: hanya untuk pemenang. Username dan sandi acara tidak pernah dikirim ke anggota lain. --}}
            @if ($kartu)
                <section class="ic-kartu-gl" aria-labelledby="judul-kartu-gl">
                    <p class="ic-eyebrow">Kartu GL</p>
                    <h2 class="ic-kartu-gl__judul" id="judul-kartu-gl">Kamu menang GL gratis 🎉</h2>
                    <dl class="ic-kartu-gl__data">
                        <div>
                            <dt>Nama</dt>
                            <dd>{{ $kartu['anggota'] }}</dd>
                        </div>
                        <div>
                            <dt>Member ID</dt>
                            <dd>{{ $kartu['kode_member'] }}</dd>
                        </div>
                        <div>
                            <dt>Acara</dt>
                            <dd>{{ $kartu['acara'] }}</dd>
                        </div>
                        <div>
                            <dt>Tanggal</dt>
                            <dd>{{ $kartu['tanggal'] }}</dd>
                        </div>
                        <div>
                            <dt>Username acara</dt>
                            <dd><code>{{ $kartu['username'] }}</code></dd>
                        </div>
                        <div>
                            <dt>Sandi acara</dt>
                            <dd>
                                <details class="ic-kartu-gl__sandi">
                                    <summary>Tampilkan sandi</summary>
                                    <code>{{ $kartu['sandi'] }}</code>
                                </details>
                            </dd>
                        </div>
                    </dl>
                    @if (filled($kartu['petunjuk']))
                        <p class="ic-bantuan"><b>Cara masuk:</b> {{ $kartu['petunjuk'] }}</p>
                    @endif
                    <p class="ic-bantuan">Tunjukkan kartu ini di pintu, lalu pindai QR acara untuk mencatat
                        kehadiranmu (+{{ ic_angka($poinGl) }} poin). Jangan bagikan username dan sandi ke orang lain.</p>
                </section>
            @endif

            @if ($event->punya_gl && !$kartu)
                <section class="ic-panel" aria-labelledby="judul-info-gl">
                    <h2 class="ic-panel__judul" id="judul-info-gl">GL gratis</h2>
                    <p class="ic-bantuan">
                        @switch($statusGl)
                            @case('buka')
                                Masuk antrean sekarang. Antrean ditutup dan pemenang diundi acak {{ $jamUndian }}.
                                Kamu boleh keluar dari antrean selama belum ditutup.
                            @break

                            @case('antri')
                                Kamu sudah di antrean. Hasil undian diumumkan {{ $jamUndian }}; pemenang mendapat kartu
                                GL di halaman ini dan lewat email.
                            @break

                            @case('tidak_terpilih')
                                Kali ini kamu belum terpilih GL. Kamu tetap bisa check-in mandiri saat check-in dibuka
                                dan mendapat {{ ic_angka($poinMandiri) }} poin.
                            @break

                            @case('tertunda')
                                Undian GL acara ini tertunda dan akan diumumkan tim WSM. Pantau halaman ini.
                            @break

                            @default
                                Antrean GL untuk acara ini sudah ditutup.
                        @endswitch
                    </p>
                </section>
            @endif

            <section class="ic-panel" aria-labelledby="judul-info-checkin">
                <h2 class="ic-panel__judul" id="judul-info-checkin">Check-in</h2>
                <p class="ic-bantuan">{{ $event->pesanCheckin($statusCheckin) }}
                    @if ($statusCheckin !== 'sudah')
                        @if ($pemenangGl)
                            Sebagai pemenang GL, pindai QR acara di pintu (+{{ ic_angka($poinGl) }} poin).
                        @else
                            Check-in mandiri memberi +{{ ic_angka($poinMandiri) }} poin dan dinilai dari lokasi
                            HP-mu.
                        @endif
                    @endif
                </p>
            </section>
        </div>

        {{-- Dua tombol di dasar layar (referensi 6.8): kiri Antri GL, kanan Check-in. Tombol yang belum bisa dipakai tampil abu-abu. --}}
        <div class="ic-acara__aksi">
            @if ($statusGl === 'buka')
                <form method="POST" action="{{ route('ic.acara.gl.antri', $event->kode) }}" data-ic-sekali>
                    @csrf
                    <button type="submit" class="ic-acara__tombol ic-acara__tombol--garis" @disabled(!$terverifikasi)
                        @if (!$terverifikasi) title="Verifikasi emailmu dulu" @endif>Antri GL</button>
                </form>
            @elseif ($statusGl === 'antri')
                <form method="POST" action="{{ route('ic.acara.gl.keluar', $event->kode) }}" data-ic-sekali>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ic-acara__tombol ic-acara__tombol--garis">Keluar antrean</button>
                </form>
            @else
                <button type="button" class="ic-acara__tombol" disabled>{{ $labelGlMati }}</button>
            @endif

            @if ($statusCheckin === 'siap' && $terverifikasi)
                <button type="button" class="ic-acara__tombol ic-acara__tombol--utama"
                    data-ic-modal-buka="modal-checkin">{{ $labelCheckin }}</button>
            @else
                <button type="button" class="ic-acara__tombol" disabled>{{ $labelCheckin }}</button>
            @endif
        </div>
    </article>
@endsection

@push('modal')
    <x-inner-circle.lembar-checkin :event-dipilih="$event" :galat-halaman="$galatCheckin" :boleh-mandiri="$bolehMandiri" :buka-awal="(bool) $galatCheckin"
        kembali-ke-detail />
@endpush
