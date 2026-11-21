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
                <img class="ic-foto-poster" src="{{ $event->poster_url }}" alt="Poster {{ $event->venue }}" width="450"
                    height="800" decoding="async" fetchpriority="high">
            @else
                <x-inner-circle.poster-kosong :tipe="$tipe" besar />
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

            {{-- Pemenang membuka Kartu GL lewat tombol "Cek pemenang GL" (popup); panel ini untuk yang lain. --}}
            @if ($event->punya_gl && $statusGl !== 'menang')
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
                                Kali ini kamu belum terpilih GL. Kamu tetap bisa check-in saat check-in dibuka
                                dan mendapat {{ ic_angka($poinMandiri) }} poin. Cukup tekan Check-in, tanpa QR.
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

            @php $pengajuan = $ajukan['pengajuan'] ?? null; @endphp
            @if ($pengajuan)
                <section class="ic-panel ic-pemeriksaan ic-pemeriksaan--{{ $pengajuan->status }}"
                    aria-labelledby="judul-pemeriksaan">
                    <h2 class="ic-panel__judul" id="judul-pemeriksaan">Pemeriksaan kehadiran</h2>
                    <p class="ic-bantuan">
                        @if ($pengajuan->status === 'menunggu')
                            <b>Menunggu tim WSM.</b> Pengajuanmu ({{ $pengajuan->labelAlasan() }}) sedang diperiksa.
                            Bila disetujui, poin check-in masuk otomatis.
                        @elseif ($pengajuan->status === 'disetujui')
                            <b>Disetujui.</b> Poin check-in sudah masuk ke akunmu.
                        @else
                            <b>Belum disetujui.</b>
                            {{ filled($pengajuan->catatan_admin) ? 'Alasan tim WSM: ' . $pengajuan->catatan_admin : '' }}
                        @endif
                    </p>
                </section>
            @endif

            <section class="ic-panel" aria-labelledby="judul-info-checkin">
                <h2 class="ic-panel__judul" id="judul-info-checkin">Check-in</h2>
                <p class="ic-bantuan">{{ $event->pesanCheckin($statusCheckin) }}
                    @if ($statusCheckin !== 'sudah')
                        @if ($pemenangGl)
                            Sebagai pemenang GL, tunjukkan Kartu GL ke staf untuk masuk. Mau dapat
                            +{{ ic_angka($poinGl) }} poin? Minta staf menunjukkan QR acara, lalu pindai.
                        @else
                            Check-in memberi +{{ ic_angka($poinMandiri) }} poin, tanpa QR: kehadiranmu dinilai dari
                            lokasi HP-mu. Check-in yang mencurigakan bisa ditarik poinnya oleh tim WSM.
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
            @elseif (in_array($statusGl, ['menang', 'tidak_terpilih'], true))
                {{-- Sesudah diundi, tombol Antri GL berganti jadi Cek pemenang GL: popup Kartu GL untuk ditunjukkan ke staf. --}}
                <button type="button" class="ic-acara__tombol ic-acara__tombol--garis" data-ic-modal-buka="modal-gl">Cek
                    pemenang GL</button>
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
    @if (in_array($statusGl, ['menang', 'tidak_terpilih'], true))
        <x-inner-circle.modal id="modal-gl" :judul="$statusGl === 'menang' ? 'Kamu menang GL gratis 🎉' : 'Hasil undian GL'" :eyebrow="$statusGl === 'menang' ? 'Kartu GL' : 'Hasil undian'" lembar>
            @if ($kartu)
                {{-- Username dan sandi acara hanya dikirim ke pemenang (GlService::kartu). --}}
                <section class="ic-kartu-gl" aria-label="Kartu GL">
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
                    <p class="ic-bantuan"><b>Tunjukkan kartu ini ke staf</b> di pintu masuk. Jangan bagikan username dan
                        sandi ke orang lain.</p>
                </section>

                <section class="ic-kartu-gl__poin" aria-labelledby="judul-gl-poin">
                    <p class="ic-panel__judul" id="judul-gl-poin">Mau dapat +{{ ic_angka($poinGl) }} poin?</p>
                    <p class="ic-bantuan">Setelah masuk, minta staf menunjukkan QR acara, lalu pindai QR itu untuk
                        mencatat kehadiranmu.</p>
                    @if ($statusCheckin === 'sudah')
                        <button type="button" class="ic-tombol ic-tombol--penuh" disabled>Sudah check-in</button>
                    @elseif ($statusCheckin === 'siap' && $terverifikasi)
                        <button type="button" class="ic-tombol ic-tombol--penuh" data-ic-modal-buka="modal-checkin">
                            <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Pindai QR acara</button>
                    @else
                        <button type="button" class="ic-tombol ic-tombol--penuh" disabled>{{ $labelCheckin }}</button>
                    @endif
                </section>
            @elseif ($statusGl === 'menang')
                <p class="ic-bantuan">Kamu terpilih GL gratis. Kartu GL-mu belum bisa ditampilkan karena data akses acara
                    belum lengkap. Tim WSM akan mengabari lewat email dan halaman ini.</p>
            @else
                <p class="ic-bantuan">Kali ini kamu belum terpilih GL gratis. Kamu tetap bisa check-in saat check-in dibuka
                    dan mendapat {{ ic_angka($poinMandiri) }} poin, cukup tekan Check-in tanpa QR.</p>
                <button type="button" class="ic-tombol ic-tombol--terang ic-tombol--penuh"
                    data-ic-modal-tutup>Mengerti</button>
            @endif
        </x-inner-circle.modal>
    @endif

    <x-inner-circle.lembar-checkin :event-dipilih="$event" :galat-halaman="$galatCheckin" :boleh-mandiri="$bolehMandiri" :buka-awal="(bool) $galatCheckin" :ajukan="$ajukan"
        kembali-ke-detail />
@endpush
