<?php

namespace App\Services\InnerCircle;

use App\Models\ic_game;
use App\Models\member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mini game Mambo Jambo Pineapple Catcher untuk anggota Inner Circle.
 *
 * Alur dan alasan keamanannya:
 * 1. `mulai()`: server membuat ronde (kode acak + seed acak) dan mencatat jamnya. Jatah ronde harian dihitung dari
 *    database dengan baris anggota dikunci, jadi membuka banyak tab atau mengirim permintaan serempak tidak menambah jatah.
 *    Ronde lain yang masih berjalan otomatis jadi `ditinggalkan` (tetap terhitung di jatah).
 * 2. Browser memainkan ronde dengan mesin deterministik yang sama dengan GameSimulasi, lalu mengirim catatan input
 *    (bukan skor). `selesai()` menjalankan ulang ronde di server dan menghitung skor SENDIRI. Skor kiriman browser hanya
 *    dicatat sebagai jejak audit. Skor, nyawa, dan level tidak bisa dipalsukan tanpa memainkan ronde yang benar-benar sah.
 * 3. Catatan input harus pas dengan seed dari server, berakhir tepat saat nyawa habis atau batas waktu ronde, dan tidak
 *    boleh lebih cepat dari jam asli (60 langkah = 1 detik), jadi catatan tidak bisa disusun lebih cepat dari waktu main.
 * 4. Poin: skor / `skor_per_poin`, paling banyak `poin_maks_ronde` per ronde dan `poin_maks_harian` per hari, lewat
 *    PoinService (satu-satunya pintu poin, sumber `game`). Satu ronde hanya bisa dibayar sekali (baris dikunci, status berubah).
 *
 * Batas yang jujur: bot yang bermain sempurna (menghitung catatan input lebih dulu) tidak bisa dibedakan dari pemain
 * sangat mahir. Kerugiannya dibatasi jatah ronde dan batas poin harian; tim WSM bisa menarik poin lewat koreksi.
 */
class GameService
{
    public function __construct(private readonly PoinService $poin, private readonly IcGame $pengaturan) {}

    /**
     * Data untuk halaman Game anggota.
     *
     * @return array{ronde_batas:int,ronde_terpakai:int,ronde_sisa:int,poin_hari_ini:int,poin_batas_hari:int,poin_maks_ronde:int,skor_per_poin:int,rekor:int}
     */
    public function ringkasan(member $anggota): array
    {
        $batas = $this->batasRonde();
        $terpakai = ic_game::query()
            ->where('member_id', $anggota->getKey())
            ->where('tanggal', now()->toDateString())
            ->count();

        return [
            'ronde_batas' => $batas,
            'ronde_terpakai' => min($terpakai, $batas),
            'ronde_sisa' => max(0, $batas - $terpakai),
            'poin_hari_ini' => $this->poinHariIni($anggota->getKey(), now()->toDateString()),
            'poin_batas_hari' => $this->pengaturan->efektif()['poin_maks_harian'],
            'poin_maks_ronde' => $this->pengaturan->efektif()['poin_maks_ronde'],
            'skor_per_poin' => $this->skorPerPoin(),
            'rekor' => $this->rekor($anggota->getKey()),
        ];
    }

    /**
     * Mulai ronde baru. Melempar GameGagal bila jatah harian habis atau akun dinonaktifkan.
     */
    public function mulai(member $anggota): ic_game
    {
        return DB::transaction(function () use ($anggota) {
            // Kunci baris anggota: dua permintaan "mulai" serempak antre di sini, jadi jatah tidak bisa dilewati.
            $terkunci = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();

            if ($terkunci->diblokir()) {
                throw new GameGagal('Akun kamu sedang dinonaktifkan.', 403);
            }

            $hari = now()->toDateString();
            $terpakai = ic_game::query()->where('member_id', $terkunci->getKey())->where('tanggal', $hari)->count();

            if ($terpakai >= $this->batasRonde()) {
                throw new GameGagal('Jatah ronde hari ini sudah habis. Main lagi besok ya.', 429);
            }

            ic_game::query()
                ->where('member_id', $terkunci->getKey())
                ->where('status', ic_game::BERJALAN)
                ->update(['status' => ic_game::DITINGGALKAN, 'alasan' => 'ronde-baru', 'selesai_pada' => now()]);

            return ic_game::create([
                'member_id' => $terkunci->getKey(),
                'kode' => bin2hex(random_bytes(16)),
                'seed' => random_int(1, 4294967295),
                'tanggal' => $hari,
                'status' => ic_game::BERJALAN,
                'poin' => 0,
                'mulai_pada' => now(),
            ]);
        });
    }

    /**
     * Selesaikan ronde: jalankan ulang, hitung skor, beri poin. Hasilnya dipakai halaman untuk menampilkan ringkasan.
     *
     * @param  string  $log  catatan input "selisih,kode,selisih,kode,..." (bilangan bulat dipisah koma)
     * @return array{skor:int,poin:int,poin_mentah:int,level:int,rekor:int,rekor_baru:bool,ronde_sisa:int,poin_hari_ini:int,poin_batas_hari:int,poin_total:int,alasan_batas:?string}
     *
     * @throws GameGagal
     */
    public function selesai(member $anggota, string $kode, int $langkah, string $log, ?int $skorKlaim): array
    {
        $penolakan = null;

        $hasil = DB::transaction(function () use ($anggota, $kode, $langkah, $log, $skorKlaim, &$penolakan) {
            // Urutan kunci selalu anggota lalu ronde (sama seperti mulai()).
            $terkunci = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();
            $game = ic_game::query()->where('member_id', $terkunci->getKey())->where('kode', $kode)->lockForUpdate()->first();

            if (! $game) {
                throw new GameGagal('Ronde tidak ditemukan. Mulai ronde baru ya.', 404);
            }
            if ($game->status !== ic_game::BERJALAN) {
                throw new GameGagal('Ronde ini sudah selesai atau diganti ronde baru.', 409);
            }
            if ($terkunci->diblokir()) {
                throw new GameGagal('Akun kamu sedang dinonaktifkan.', 403);
            }

            $tolak = function (string $alasan) use ($game, $skorKlaim, &$penolakan) {
                $game->forceFill([
                    'status' => ic_game::DITOLAK,
                    'alasan' => $alasan,
                    'skor_klaim' => $skorKlaim,
                    'langkah' => null,
                    'selesai_pada' => now(),
                ])->save();
                $penolakan = $alasan;
                Log::notice('Ronde game ditolak', ['member' => $game->member_id, 'ronde' => $game->id_game, 'alasan' => $alasan]);

                return null;
            };

            $maks = (int) config('inner_circle.game.langkah_maks');
            $lama = now()->getTimestamp() - $game->mulai_pada->getTimestamp();

            if ($lama > (int) config('inner_circle.game.kedaluwarsa_menit') * 60) {
                return $tolak('kedaluwarsa');
            }
            // 60 langkah = 1 detik asli. Selisih 3 detik menampung jaringan lambat dan pembulatan jam.
            if ($langkah < 1 || $langkah > $maks || $langkah / GameSimulasi::LANGKAH_PER_DETIK > $lama + 3) {
                return $tolak('terlalu-cepat');
            }

            $peristiwa = $this->uraikanLog($log, $langkah);
            if ($peristiwa === null) {
                return $tolak('catatan-rusak');
            }

            $hasil = GameSimulasi::ulang((int) $game->seed, $peristiwa, $langkah);

            // Ronde sah berakhir persis saat nyawa habis atau tepat di batas waktu ronde; selain itu catatannya tidak utuh.
            if ($hasil['selesai'] ? $hasil['langkah'] !== $langkah : $langkah !== $maks) {
                return $tolak('catatan-tidak-sesuai');
            }

            $skor = $hasil['skor'];
            $poinMentah = intdiv(max(0, $skor), $this->skorPerPoin());
            $poin = min($poinMentah, $this->pengaturan->efektif()['poin_maks_ronde']);
            $alasanBatas = $poin < $poinMentah ? 'ronde' : null;

            $sudah = $this->poinHariIni($terkunci->getKey(), $game->tanggal->toDateString());
            $sisaHari = max(0, $this->pengaturan->efektif()['poin_maks_harian'] - $sudah);
            if ($poin > $sisaHari) {
                $poin = $sisaHari;
                $alasanBatas = 'harian';
            }

            $baris = $poin > 0
                ? $this->poin->tambah($terkunci, $poin, 'game', 'Game Mambo Jambo', 'mirror-ball', catatan: 'Skor ' . $skor)
                : null;

            $selisihSkor = $skorKlaim !== null && $skorKlaim !== $skor;
            if ($selisihSkor) {
                Log::warning('Skor game dari browser berbeda dengan hitungan server', ['member' => $game->member_id, 'ronde' => $game->id_game, 'klaim' => $skorKlaim, 'server' => $skor]);
            }

            $rekorLama = $this->rekor($terkunci->getKey());

            $game->forceFill([
                'status' => ic_game::SELESAI,
                'alasan' => $selisihSkor ? 'selisih-skor' : null,
                'skor' => $skor,
                'skor_klaim' => $skorKlaim,
                'level' => $hasil['level'],
                'langkah' => $hasil['langkah'],
                'nanas' => $hasil['nanas'],
                'bir' => $hasil['bir'],
                'lewat' => $hasil['lewat'],
                'poin' => $poin,
                'poin_id' => $baris?->getKey(),
                'selesai_pada' => now(),
            ])->save();

            $terpakai = ic_game::query()->where('member_id', $terkunci->getKey())->where('tanggal', now()->toDateString())->count();

            return [
                'skor' => $skor,
                'poin' => $poin,
                'level' => $hasil['level'],
                'rekor' => max($rekorLama, $skor),
                'rekor_baru' => $skor > $rekorLama && $skor > 0,
                'ronde_sisa' => max(0, $this->batasRonde() - $terpakai),
                'poin_mentah' => $poinMentah,
                'poin_hari_ini' => $sudah + $poin,
                'poin_batas_hari' => $this->pengaturan->efektif()['poin_maks_harian'],
                'poin_total' => (int) $terkunci->poin_total,
                'alasan_batas' => $alasanBatas,
            ];
        });

        if ($hasil === null) {
            throw new GameGagal('Ronde ini tidak bisa dihitung, jadi tidak ada poin yang diberikan. Coba main lagi.', 422);
        }

        return $hasil;
    }

    /** Skor tertinggi dari ronde yang sah (0 bila belum pernah main). */
    public function rekor(int $idAnggota): int
    {
        return max(0, (int) ic_game::query()
            ->where('member_id', $idAnggota)
            ->where('status', ic_game::SELESAI)
            ->max('skor'));
    }

    private function poinHariIni(int $idAnggota, string $tanggal): int
    {
        return (int) ic_game::query()
            ->where('member_id', $idAnggota)
            ->where('tanggal', $tanggal)
            ->where('status', ic_game::SELESAI)
            ->sum('poin');
    }

    private function batasRonde(): int
    {
        return $this->pengaturan->efektif()['ronde_harian'];
    }

    private function skorPerPoin(): int
    {
        return $this->pengaturan->efektif()['skor_per_poin'];
    }

    /**
     * Ubah catatan input dari browser jadi daftar bilangan, atau null bila bentuknya salah. Aturan: pasangan
     * (selisih langkah, kode); kode 0..320 atau 1000..1002; semua kejadian jatuh sebelum langkah terakhir.
     *
     * @return list<int>|null
     */
    private function uraikanLog(string $log, int $langkah): ?array
    {
        if ($log === '') {
            return [];
        }
        if (strlen($log) > (int) config('inner_circle.game.log_maks_karakter')) {
            return null;
        }

        $bagian = explode(',', $log);
        // Paling banyak satu kejadian per langkah per jenis (pointer dan panah): 2 bilangan x (langkah + 1) kejadian.
        if (count($bagian) % 2 !== 0 || count($bagian) > 2 * ($langkah + 1)) {
            return null;
        }

        $hasil = [];
        $waktu = 0;
        foreach ($bagian as $i => $teks) {
            if ($teks === '' || strlen($teks) > 5 || ! ctype_digit($teks)) {
                return null;
            }
            $n = (int) $teks;

            if ($i % 2 === 0) {
                $waktu += $n;
                if ($waktu >= $langkah) {
                    return null;
                }
            } elseif (! ($n <= GameSimulasi::LEBAR || ($n >= GameSimulasi::KODE_PANAH - 1 && $n <= GameSimulasi::KODE_PANAH + 1))) {
                return null;
            }

            $hasil[] = $n;
        }

        return $hasil;
    }
}