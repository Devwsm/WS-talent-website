@extends('template.innerCircleLayout')

@section('title', 'Game')

{{-- CSS dan JS game hanya dimuat di halaman ini (entri Vite terpisah), jadi halaman Inner Circle lain tidak bertambah berat. --}}
@push('head')
    @vite(['resources/css/game-mambo.css', 'resources/js/game-mambo.js'])
@endpush

@section('content')
    <x-inner-circle.panel-verifikasi />

    <div class="gm" data-gm data-url-mulai="{{ route('ic.game.mulai') }}" data-url-selesai="{{ route('ic.game.selesai') }}"
        data-aset="{{ asset('aset/game-mambo') }}"
        data-url-lagu="https://open.spotify.com/embed/track/1CaXLlWZ5IM3WQtYiOlnT8?utm_source=generator&amp;theme=0"
        data-bisa-main="{{ $bisaMain ? 1 : 0 }}" data-ronde-sisa="{{ $ringkasan['ronde_sisa'] }}"
        data-poin-hari-ini="{{ $ringkasan['poin_hari_ini'] }}" data-poin-batas-hari="{{ $ringkasan['poin_batas_hari'] }}"
        data-poin-maks-ronde="{{ $ringkasan['poin_maks_ronde'] }}" data-skor-per-poin="{{ $ringkasan['skor_per_poin'] }}"
        data-rekor="{{ $ringkasan['rekor'] }}" data-langkah-maks="{{ config('inner_circle.game.langkah_maks') }}">

        <header class="gm-kepala">
            <a class="ic-tautan" href="{{ route('ic.komunitas', ['tab' => 'game']) }}">← Cara main</a>
            <p class="ic-eyebrow">Game Inner Circle</p>
            <h1 class="ic-judul">Pineapple Catcher<span>.</span></h1>
            <p class="gm-kepala__teks">Geser Whisnu, tangkap nanas, hindari botol bir. Skormu jadi poin Inner Circle.</p>
            <ul class="gm-chip" aria-label="Jatah main hari ini">
                <li><i class="bi bi-controller" aria-hidden="true"></i> <span
                        data-gm-chip-ronde>{{ $ringkasan['ronde_sisa'] }}
                        ronde tersisa</span></li>
                <li><i class="bi bi-stars" aria-hidden="true"></i> <span
                        data-gm-chip-poin>{{ $ringkasan['poin_hari_ini'] }}/{{ $ringkasan['poin_batas_hari'] }} poin hari
                        ini</span></li>
            </ul>
        </header>

        <div class="gm-tata">
            <section class="gm-mesin" aria-label="Arena Pineapple Catcher">
                <div class="gm-hud">
                    <div class="gm-metrik"><span>SKOR</span><strong data-gm-skor>000</strong></div>
                    <div class="gm-metrik gm-metrik--nyawa">
                        <span>NYAWA</span>
                        <div class="gm-nyawa" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
                        <strong class="gm-sr" data-gm-nyawa>5 / 5</strong>
                    </div>
                    <div class="gm-metrik gm-metrik--rekor"><span>REKOR</span><strong data-gm-rekor>000</strong></div>
                </div>
                <div class="gm-arena" data-gm-arena data-state="ready">
                    <canvas width="320" height="280" data-gm-kanvas
                        aria-label="Arena game. Geser mouse, sentuh layar, atau gunakan tombol panah kiri dan kanan.">Game
                        ini
                        memerlukan dukungan canvas.</canvas>
                    <div class="gm-overlay" data-gm-overlay>
                        <div class="gm-overlay__isi">
                            <p class="gm-judul-hasil" data-gm-judul hidden></p>
                            <p class="gm-hasil" data-gm-hasil hidden></p>
                            <p class="gm-catatan" data-gm-catatan hidden></p>
                            <button class="gm-main" type="button" data-gm-main><span class="gm-main__ikon"
                                    aria-hidden="true"></span><span data-gm-main-label>MULAI MAIN</span></button>
                        </div>
                    </div>
                </div>
                <div class="gm-kaki">
                    <span><i></i><span data-gm-status role="status">SIAP MAIN</span></span>
                    <span data-gm-poin-ronde>POIN RONDE 0/{{ $ringkasan['poin_maks_ronde'] }}</span>
                    <span data-gm-level>LEVEL 01</span>
                </div>
                <button class="gm-efek" type="button" data-gm-efek aria-pressed="false">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                        stroke-width="1.8" aria-hidden="true">
                        <path d="M3 9h4l5-4v14l-5-4H3z" />
                        <path d="m16 9 5 6m0-6-5 6" />
                    </svg>
                    <span data-gm-efek-label>EFEK OFF</span>
                </button>
            </section>

            <aside class="gm-info">
                <img class="gm-logo" src="{{ asset('aset/game-mambo/mambo-jambo-logo.webp') }}" width="480"
                    height="302" alt="Mambo Jambo" loading="lazy" decoding="async">

                <div class="gm-aturan">
                    <div><img src="{{ asset('aset/game-mambo/pineapple.webp') }}" width="21" height="26"
                            alt="" loading="lazy">
                        <p><b>+10 skor per nanas</b><span>Tangkap sebanyak mungkin.</span></p>
                    </div>
                    <div><img src="{{ asset('aset/game-mambo/beer.webp') }}" width="19" height="29" alt=""
                            loading="lazy">
                        <p><b>−10 skor per botol bir</b><span>Biarkan jatuh lewat.</span></p>
                    </div>
                    <div><span class="gm-hati" aria-hidden="true"></span>
                        <p><b>5 nyawa per ronde</b><span>Nanas lolos = −1 nyawa. Makin tinggi skor, makin cepat.</span></p>
                    </div>
                </div>

                <div class="gm-poin-info">
                    <h2 class="ic-judul-bagian">Soal poin</h2>
                    <ul>
                        <li>{{ $ringkasan['skor_per_poin'] }} skor = 1 poin. Maksimal {{ $ringkasan['poin_maks_ronde'] }}
                            poin per
                            ronde.</li>
                        <li>{{ $ringkasan['ronde_batas'] }} ronde per hari (maksimal
                            {{ $ringkasan['poin_batas_hari'] }} poin), satu ronde paling lama {{ $menitRonde }} menit.
                        </li>
                        <li>Jangan tutup atau muat ulang halaman saat main.</li>
                        <li>Skor dicek ulang server supaya adil.</li>
                    </ul>
                </div>

                <p class="gm-kontrol"><kbd>←</kbd><kbd>→</kbd> Geser mouse, sentuh layar, atau tombol panah.</p>

                <button class="gm-lagu" type="button" data-gm-lagu><i class="bi bi-spotify" aria-hidden="true"></i> Putar
                    lagu Mambo Jambo</button>
            </aside>
        </div>
    </div>
@endsection
