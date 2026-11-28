{{-- Aturan mini game di tab Konten. Variabel: $gameAturan (efektif), $gameBawaan, $gameBatas. --}}
@php
    $galat = $errors->getBag('game');
    $pakaiOld = $galat->any();
    $v = fn(string $k) => $pakaiOld ? old($k, $gameAturan[$k]) : $gameAturan[$k];
    $aktifSekarang = (string) $v('aktif');
    $aktifSekarang = in_array($aktifSekarang, ['1', 'true'], true) ? '1' : '0';
    $kelasInput =
        'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition scheme-dark';
    $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
    $kelasBantu = 'text-xs text-white/40';
    $angka = [
        'ronde_harian' => [
            'f-game-ronde',
            'Jatah ronde per hari',
            'Ronde yang ditinggalkan atau ditolak tetap terhitung. Jatah kembali penuh tiap hari (WIB).',
        ],
        'skor_per_poin' => [
            'f-game-skor',
            'Skor per 1 poin',
            '1 mengikuti prototype: nanas +10 skor = +10 poin. Isi 10 bila ingin nanas = 1 poin.',
        ],
        'poin_maks_ronde' => [
            'f-game-poin-ronde',
            'Batas poin per ronde',
            'Skor di atas batas ini tidak menambah poin.',
        ],
        'poin_maks_harian' => ['f-game-poin-hari', 'Batas poin per hari', 'Total poin dari game per anggota per hari.'],
    ];
@endphp
<section id="aturan-game" aria-labelledby="judul-aturan-game" class="scroll-mt-8 flex flex-col gap-4">
    <div class="min-w-0">
        <h2 id="judul-aturan-game" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
            <i class="bi bi-controller text-white/50" aria-hidden="true"></i> Aturan game
        </h2>
        <p class="text-xs text-white/40 mt-1">Mini game Mambo Jambo Pineapple Catcher. Perubahan berlaku untuk ronde
            berikutnya, poin yang sudah diberikan tidak berubah. Teks halaman (cara main, peraturan, ajakan di Beranda)
            diubah di <b>Halaman anggota &rsaquo; Halaman Komunitas</b> dan <b>Beranda anggota</b>.</p>
    </div>

    <form method="POST" action="{{ route('ic.admin.konten.game.simpan') }}"
        class="rounded-3xl border border-white/10 bg-white/3 p-5 sm:p-6 md:p-8 flex flex-col gap-6">
        @csrf
        @if ($galat->any())
            <div role="alert"
                class="rounded-xl border border-red-300/40 bg-red-950/20 p-3 text-xs text-red-300 flex flex-col gap-1">
                @foreach ($galat->all() as $pesan)
                    <p>{{ $pesan }}</p>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col gap-1.5 min-w-0 sm:max-w-sm">
            <label for="f-game-aktif" class="{{ $kelasLabel }}">Status game</label>
            <select id="f-game-aktif" name="aktif" class="{{ $kelasInput }}">
                <option class="bg-neutral-900" value="1" @selected($aktifSekarang === '1')>Aktif (tab Game dan ajakan di
                    Beranda tampil)
                </option>
                <option class="bg-neutral-900" value="0" @selected($aktifSekarang === '0')>Nonaktif (disembunyikan,
                    tidak bisa dimainkan)
                </option>
            </select>
            <p class="{{ $kelasBantu }}">Ronde yang sedang berjalan tetap bisa diselesaikan.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($angka as $nama => [$id, $label, $bantu])
                <div class="flex flex-col gap-1.5 min-w-0">
                    <label for="{{ $id }}" class="{{ $kelasLabel }}">{{ $label }}</label>
                    <input type="number" inputmode="numeric" id="{{ $id }}" name="{{ $nama }}"
                        required min="{{ $gameBatas[$nama][0] }}" max="{{ $gameBatas[$nama][1] }}"
                        value="{{ $v($nama) }}" class="{{ $kelasInput }}">
                    <p class="{{ $kelasBantu }}">{{ $bantu }} {{ ic_angka($gameBatas[$nama][0]) }} sampai
                        {{ ic_angka($gameBatas[$nama][1]) }}, bawaan {{ ic_angka($gameBawaan[$nama]) }}.</p>
                </div>
            @endforeach
        </div>

        <p class="rounded-xl border border-white/10 bg-white/2 px-4 py-3 text-sm text-white/70">
            Mekanik game (nyawa, +10 nanas, −10 bir, kenaikan kecepatan) mengikuti prototype dan tidak bisa diubah dari
            sini. Poin game masuk ke saldo yang sama dengan poin kehadiran, jadi jaga batasnya kecil dibanding poin
            check-in.</p>

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit"
                class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                <i class="bi bi-check2-circle" aria-hidden="true"></i> Simpan aturan game</button>
            <button type="submit" formaction="{{ route('ic.admin.konten.game.bawaan') }}" formnovalidate
                class="min-h-11 text-xs text-white/50 underline underline-offset-4 hover:text-white">Kembalikan semua
                ke bawaan</button>
        </div>
    </form>
</section>
