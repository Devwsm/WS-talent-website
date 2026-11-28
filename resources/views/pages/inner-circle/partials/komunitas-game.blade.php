{{-- Tab Game di Komunitas: hero bergambar (aset Mambo Jambo) + tiga kartu aturan singkat. Variabel: $member, $ringkasanGame, $teksGame.
     Semua teks tetap diambil dari ic_teks()/ic_html() supaya bisa diubah dari CMS; hanya aset gambarnya yang tetap. --}}
@php
    $bisaMain = $member->email_verified_at !== null && !$member->diblokir();
    $aset = asset('aset/game-mambo');
@endphp
<div class="ic-gk">
    <section class="ic-gk__hero" aria-labelledby="judul-game-hero">
        <div class="ic-gk__isi">
            <span class="ic-gk__stiker">{{ ic_teks('komunitas.game.pil') }}</span>
            <div>
                <p class="ic-gk__eyebrow">{{ ic_teks('komunitas.game.eyebrow') }}</p>
                <h2 class="ic-gk__judul" id="judul-game-hero">{{ ic_teks('komunitas.game.judul') }}</h2>
            </div>
            <p class="ic-gk__intro">{{ ic_html('komunitas.game.intro', $teksGame) }}</p>

            <ul class="ic-gk__status" aria-label="Jatah main hari ini">
                <li><b>{{ $ringkasanGame['ronde_sisa'] }}</b><span>ronde tersisa</span></li>
                <li><b>{{ ic_angka($ringkasanGame['poin_hari_ini']) }}<small>/{{ ic_angka($ringkasanGame['poin_batas_hari']) }}</small></b><span>poin
                        hari ini</span></li>
                <li><b>{{ ic_angka($ringkasanGame['rekor']) }}</b><span>rekormu</span></li>
            </ul>

            @if ($bisaMain && $ringkasanGame['ronde_sisa'] > 0)
                <a class="ic-tombol ic-gk__tombol" href="{{ route('ic.game') }}">{{ ic_teks('komunitas.game.tombol') }}
                    <i class="bi bi-controller" aria-hidden="true"></i></a>
            @else
                <button type="button" class="ic-tombol ic-gk__tombol"
                    disabled>{{ ic_teks('komunitas.game.tombol') }}</button>
                <p class="ic-gk__catatan">
                    @if (!$bisaMain)
                        {{ ic_teks('komunitas.game.verifikasi') }}
                    @else
                        {{ ic_teks('komunitas.game.habis') }}
                    @endif
                </p>
            @endif
        </div>

        {{-- Adegan pantai Mambo Jambo: hiasan saja (aria-hidden). Nanas dan botol jatuh pelan; mati otomatis bila pengguna
             memilih "kurangi gerakan" di perangkatnya. --}}
        <div class="ic-gk__adegan" aria-hidden="true">
            <img class="ic-gk__latar" src="{{ $aset }}/cafe-beach.webp" width="320" height="280"
                alt="" decoding="async">
            <img class="ic-gk__logo" src="{{ $aset }}/mambo-jambo-logo.webp" width="480" height="302"
                alt="" decoding="async">
            <img class="ic-gk__jatuh ic-gk__jatuh--1" src="{{ $aset }}/pineapple.webp" width="21"
                height="26" alt="">
            <img class="ic-gk__jatuh ic-gk__jatuh--2" src="{{ $aset }}/pineapple.webp" width="21"
                height="26" alt="">
            <img class="ic-gk__jatuh ic-gk__jatuh--3" src="{{ $aset }}/beer.webp" width="19" height="29"
                alt="">
            <img class="ic-gk__tokoh" src="{{ $aset }}/dj-left.webp" width="100" height="147"
                alt="">
        </div>
    </section>

    <div class="ic-gk__aturan">
        <section class="ic-gk__kartu ic-gk__kartu--cara" aria-labelledby="game-cara">
            <div class="ic-gk__kartu-kepala">
                <span class="ic-gk__kartu-ikon" aria-hidden="true"><img src="{{ $aset }}/pineapple.webp"
                        width="21" height="26" alt="" loading="lazy"></span>
                <h3 id="game-cara">{{ ic_teks('komunitas.game.cara.judul') }}</h3>
            </div>
            <ol class="ic-gk__daftar">
                <li>{{ ic_teks('komunitas.game.cara.satu') }}</li>
                <li>{{ ic_teks('komunitas.game.cara.dua') }}</li>
                <li>{{ ic_teks('komunitas.game.cara.tiga') }}</li>
                <li>{{ ic_teks('komunitas.game.cara.empat') }}</li>
            </ol>
        </section>

        <section class="ic-gk__kartu ic-gk__kartu--aturan" aria-labelledby="game-aturan">
            <div class="ic-gk__kartu-kepala">
                <span class="ic-gk__kartu-ikon" aria-hidden="true"><img src="{{ $aset }}/beer.webp"
                        width="19" height="29" alt="" loading="lazy"></span>
                <h3 id="game-aturan">{{ ic_teks('komunitas.game.aturan.judul') }}</h3>
            </div>
            <ul class="ic-gk__daftar ic-gk__daftar--centang">
                <li>{{ ic_teks('komunitas.game.aturan.satu', $teksGame) }}</li>
                <li>{{ ic_teks('komunitas.game.aturan.dua', $teksGame) }}</li>
                <li>{{ ic_teks('komunitas.game.aturan.tiga') }}</li>
                <li>{{ ic_teks('komunitas.game.aturan.empat') }}</li>
                <li>{{ ic_teks('komunitas.game.aturan.lima') }}</li>
            </ul>
        </section>

        <section class="ic-gk__kartu ic-gk__kartu--poin" aria-labelledby="game-poin">
            <div class="ic-gk__kartu-kepala">
                <span class="ic-gk__kartu-ikon" aria-hidden="true"><i class="bi bi-stars"></i></span>
                <h3 id="game-poin">{{ ic_teks('komunitas.game.poin.judul') }}</h3>
            </div>
            <ul class="ic-gk__daftar ic-gk__daftar--bintang">
                <li>{{ ic_teks('komunitas.game.poin.satu', $teksGame) }}</li>
                <li>{{ ic_teks('komunitas.game.poin.dua', $teksGame) }}</li>
                <li>{{ ic_teks('komunitas.game.poin.tiga', $teksGame) }}</li>
                <li>{{ ic_teks('komunitas.game.poin.empat') }}</li>
            </ul>
        </section>
    </div>
</div>
