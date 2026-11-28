{{-- Tab Game di Komunitas: landing penjelasan cara main, aturan, dan poin. Variabel: $member, $ringkasanGame, $teksGame. --}}
@php
    $bisaMain = $member->email_verified_at !== null && !$member->diblokir();
@endphp
<div class="ic-bagian-kepala">
    <div>
        <p class="ic-eyebrow">{{ ic_teks('komunitas.game.eyebrow') }}</p>
        <h2 class="ic-judul-bagian">{{ ic_teks('komunitas.game.judul') }}</h2>
    </div>
    <span class="ic-pil">{{ ic_teks('komunitas.game.pil') }}</span>
</div>

<div class="ic-panel ic-game-intro">
    <p class="ic-game-intro__teks">{{ ic_html('komunitas.game.intro', $teksGame) }}</p>
    <ul class="ic-game-status" aria-label="Jatah main hari ini">
        <li><b>{{ $ringkasanGame['ronde_sisa'] }}</b> ronde tersisa hari ini</li>
        <li><b>{{ ic_angka($ringkasanGame['poin_hari_ini']) }}</b> / {{ ic_angka($ringkasanGame['poin_batas_hari']) }}
            poin game hari ini</li>
        <li>Rekormu <b>{{ ic_angka($ringkasanGame['rekor']) }}</b></li>
    </ul>
    @if ($bisaMain && $ringkasanGame['ronde_sisa'] > 0)
        <a class="ic-tombol" href="{{ route('ic.game') }}">{{ ic_teks('komunitas.game.tombol') }}
            <i class="bi bi-controller" aria-hidden="true"></i></a>
    @else
        <button type="button" class="ic-tombol" disabled>{{ ic_teks('komunitas.game.tombol') }}</button>
        <p class="ic-bantuan">
            @if (!$bisaMain)
                {{ ic_teks('komunitas.game.verifikasi') }}
            @else
                {{ ic_teks('komunitas.game.habis') }}
            @endif
        </p>
    @endif
</div>

<div class="ic-game-kolom">
    <section class="ic-panel" aria-labelledby="game-cara">
        <h3 class="ic-judul-bagian" id="game-cara">{{ ic_teks('komunitas.game.cara.judul') }}</h3>
        <ol class="ic-game-langkah">
            <li>{{ ic_teks('komunitas.game.cara.satu') }}</li>
            <li>{{ ic_teks('komunitas.game.cara.dua') }}</li>
            <li>{{ ic_teks('komunitas.game.cara.tiga') }}</li>
            <li>{{ ic_teks('komunitas.game.cara.empat') }}</li>
        </ol>
    </section>

    <section class="ic-panel" aria-labelledby="game-aturan">
        <h3 class="ic-judul-bagian" id="game-aturan">{{ ic_teks('komunitas.game.aturan.judul') }}</h3>
        <ul class="ic-game-daftar">
            <li>{{ ic_teks('komunitas.game.aturan.satu', $teksGame) }}</li>
            <li>{{ ic_teks('komunitas.game.aturan.dua', $teksGame) }}</li>
            <li>{{ ic_teks('komunitas.game.aturan.tiga') }}</li>
            <li>{{ ic_teks('komunitas.game.aturan.empat') }}</li>
            <li>{{ ic_teks('komunitas.game.aturan.lima') }}</li>
        </ul>
    </section>

    <section class="ic-panel" aria-labelledby="game-poin">
        <h3 class="ic-judul-bagian" id="game-poin">{{ ic_teks('komunitas.game.poin.judul') }}</h3>
        <ul class="ic-game-daftar">
            <li>{{ ic_teks('komunitas.game.poin.satu', $teksGame) }}</li>
            <li>{{ ic_teks('komunitas.game.poin.dua', $teksGame) }}</li>
            <li>{{ ic_teks('komunitas.game.poin.tiga', $teksGame) }}</li>
            <li>{{ ic_teks('komunitas.game.poin.empat') }}</li>
        </ul>
    </section>
</div>
