<?php

namespace App\Services\InnerCircle;

use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_reward;
use App\Models\ic_reward_tunggu;
use App\Models\member;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Hadiah dan penukaran poin WS Inner Circle (aturan baru, semua dicek di server):
 * - anggota menukar poin dengan hadiah di katalog (Meet & Greet, Merchandise, dst). Poin DITAHAN saat mengajukan (saldo
 *   berkurang, dicatat di buku besar `ic_poin` sebagai baris `tukar`), habis dipakai saat penukaran `selesai`, dan
 *   DIKEMBALIKAN (baris `kembali`) bila ditolak admin atau dibatalkan anggota;
 * - penukaran hanya bila saldo cukup, email terverifikasi, hadiah aktif, dan stok ada (stok berkurang saat mengajukan dan
 *   kembali bersama poinnya). Hadiah boleh ditukar berulang, tetapi hanya satu penukaran per hadiah yang boleh berjalan;
 * - hadiah yang punya pilihan (mis. A/B) wajib memilih salah satunya;
 * - status bergerak diajukan -> ditinjau -> disetujui -> selesai, atau ditolak (alasan wajib) / dibatalkan (oleh anggota,
 *   selama masih `diajukan`). `selesai`, `ditolak`, dan `dibatalkan` adalah akhir alur. Tiap perubahan dicatat di ic_claim_logs;
 * - klaim lama sebelum Fase 8 (`poin_ditahan` = 0) tidak pernah memotong saldo, jadi menolaknya tidak mengembalikan apa pun.
 *
 * Urutan kunci baris dijaga sama di semua jalur supaya tidak saling buntu: penukaran, lalu anggota, lalu hadiah.
 */
class ClaimService
{
    public function __construct(private readonly PoinService $poin) {}

    /**
     * Katalog hadiah aktif untuk satu anggota. Tiap hadiah membawa `status_member` (terbuka | terkunci | habis), `sisa_poin`,
     * `persen` (progres saldo menuju harga), `stok_habis`, dan `di_tunggu` (anggota ini sudah masuk daftar tunggu).
     *
     * @return Collection<int, ic_reward>
     */
    public function hadiahUntuk(member $anggota): Collection
    {
        $saldo = (int) $anggota->poin_total;
        $tunggu = ic_reward_tunggu::query()
            ->where('member_id', $anggota->getKey())
            ->pluck('reward_id')
            ->map(fn($id) => (int) $id)
            ->all();

        return $this->hadiahAktif()->each(function (ic_reward $reward) use ($saldo, $tunggu) {
            $habis = $reward->stokHabis();

            $reward->setAttribute('status_member', $habis ? 'habis' : ($saldo >= $reward->harga_poin ? 'terbuka' : 'terkunci'));
            $reward->setAttribute('sisa_poin', max(0, $reward->harga_poin - $saldo));
            $reward->setAttribute('persen', $reward->harga_poin > 0 ? min(100, (int) floor($saldo / $reward->harga_poin * 100)) : 100);
            $reward->setAttribute('stok_habis', $habis);
            $reward->setAttribute('di_tunggu', in_array((int) $reward->getKey(), $tunggu, true));
        });
    }

    /**
     * Dompet poin anggota: saldo yang bisa dipakai, poin yang sedang ditahan penukaran yang belum selesai, dan poin yang
     * sudah dipakai penukaran yang selesai.
     */
    public function dompet(member $anggota): object
    {
        $klaim = ic_claim::query()->where('member_id', $anggota->getKey());

        return (object) [
            'saldo' => (int) $anggota->poin_total,
            'tertahan' => (int) (clone $klaim)->whereIn('status', ic_claim::statusMenunggu())->sum('poin_ditahan'),
            'dipakai' => (int) (clone $klaim)->where('status', 'selesai')->sum('poin_ditahan'),
        ];
    }

    /** Harga hadiah berikutnya yang belum terjangkau (setelah yang termahal, tetap di yang termahal). */
    public function berikutnya(int $poin): object
    {
        $target = $this->hadiahAktif()->pluck('harga_poin')->sort()->values();
        $berikutnya = $target->first(fn($n) => $n > $poin) ?? $target->last() ?? 250000;

        return (object) [
            'harga_poin' => $berikutnya,
            'sisa' => max(0, $berikutnya - $poin),
            'persen' => $berikutnya > 0 ? min(100, (int) round($poin / $berikutnya * 100)) : 100,
        ];
    }

    /** Penukaran milik satu anggota, terbaru di atas. */
    public function klaimAnggota(member $anggota): Collection
    {
        return ic_claim::query()
            ->with('reward')
            ->where('member_id', $anggota->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id_claim')
            ->get();
    }

    /**
     * Tukar poin dengan hadiah $rewardId. Melempar KlaimGagal (pesan siap tampil) bila ada aturan yang tidak terpenuhi.
     * Baris anggota dan hadiah dikunci selama transaksi supaya klik ganda dan penukaran bersamaan diproses bergantian.
     */
    public function ajukan(member $anggota, int $rewardId, ?string $opsi = null): ic_claim
    {
        $reward = ic_reward::query()->where('aktif', true)->whereKey($rewardId)->first();
        if (! $reward) {
            throw new KlaimGagal('Hadiah ini tidak ditemukan atau sedang tidak tersedia.');
        }

        $opsi = $this->bersihkan($opsi);
        $pilihan = $reward->opsi ?? [];
        if ($pilihan === []) {
            $opsi = null;
        } elseif ($opsi === null) {
            throw new KlaimGagal('Pilih salah satu hadiah dulu sebelum mengajukan.');
        } elseif (! in_array($opsi, $pilihan, true)) {
            throw new KlaimGagal('Pilihan hadiah tidak valid. Muat ulang halaman lalu coba lagi.');
        }

        try {
            return DB::transaction(function () use ($anggota, $reward, $opsi) {
                $terkunci = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();

                // Pendaftaran dengan kata sandi tidak membuktikan pemilik email (bisa memakai email orang lain), jadi
                // hadiah baru bisa ditukar setelah email terverifikasi. Akun Google sudah terverifikasi sejak masuk.
                if ($terkunci->email_verified_at === null) {
                    throw new KlaimGagal('Verifikasi email dulu sebelum menukar hadiah. Buka email dari WS Inner Circle, atau kirim ulang dari halaman Hadiah.');
                }

                $hadiah = ic_reward::query()->whereKey($reward->getKey())->lockForUpdate()->firstOrFail();
                if (! $hadiah->aktif) {
                    throw new KlaimGagal('Hadiah ini sedang tidak tersedia.');
                }

                if ($hadiah->stokHabis()) {
                    throw new KlaimGagal('Stok hadiah ini sedang habis. Masuk daftar tunggu supaya kamu tahu saat stoknya tersedia lagi.');
                }

                if ($terkunci->poin_total < $hadiah->harga_poin) {
                    throw new KlaimGagal(
                        'Poinmu belum cukup. Hadiah ini butuh ' . ic_angka($hadiah->harga_poin)
                            . ' poin, saldomu ' . ic_angka($terkunci->poin_total) . '.'
                    );
                }

                $berjalan = ic_claim::query()
                    ->where('member_id', $terkunci->getKey())
                    ->where('reward_id', $hadiah->getKey())
                    ->whereIn('status', ic_claim::statusMenunggu())
                    ->exists();
                if ($berjalan) {
                    throw new KlaimGagal('Penukaran hadiah ini masih berjalan. Tunggu sampai selesai sebelum menukar lagi.');
                }

                $klaim = ic_claim::create([
                    'member_id' => $terkunci->getKey(),
                    'reward_id' => $hadiah->getKey(),
                    'opsi_dipilih' => $opsi,
                    'poin_ditahan' => $hadiah->harga_poin,
                    'status' => 'diajukan',
                ]);

                ic_claim_log::create([
                    'claim_id' => $klaim->getKey(),
                    'status' => 'diajukan',
                    'catatan' => 'Poin ditahan: ' . ic_angka($hadiah->harga_poin),
                ]);

                $this->poin->tahanUntukPenukaran($terkunci, $klaim, $hadiah->harga_poin, $hadiah->judul, $hadiah->ikon);

                if ($hadiah->stok !== null) {
                    $hadiah->decrement('stok');
                }

                // Sudah menukar: tidak perlu menunggu lagi.
                ic_reward_tunggu::query()->where('member_id', $terkunci->getKey())->where('reward_id', $hadiah->getKey())->delete();

                return $klaim;
            });
        } finally {
            // Instance anggota dari guard masih membawa angka lama.
            $anggota->refresh();
        }
    }

    /**
     * Anggota membatalkan penukarannya sendiri. Hanya selama statusnya masih `diajukan` (belum diperiksa admin). Poin dan
     * stok kembali.
     */
    public function batalkan(member $anggota, int $klaimId): ic_claim
    {
        try {
            return DB::transaction(function () use ($anggota, $klaimId) {
                $k = ic_claim::query()
                    ->whereKey($klaimId)
                    ->where('member_id', $anggota->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $k) {
                    throw new KlaimGagal('Penukaran ini tidak ditemukan.');
                }

                if ($k->status !== 'diajukan') {
                    throw new KlaimGagal(match ($k->status) {
                        'dibatalkan' => 'Penukaran ini sudah dibatalkan.',
                        'ditolak' => 'Penukaran ini sudah ditolak.',
                        'selesai' => 'Penukaran ini sudah selesai.',
                        default => 'Penukaran ini sudah diperiksa tim WSM, jadi tidak bisa dibatalkan lagi. Hubungi tim WSM bila perlu.',
                    });
                }

                $this->akhiriDanKembalikan($k, 'dibatalkan', null, null);

                return $k;
            });
        } finally {
            $anggota->refresh();
        }
    }

    /**
     * Proses penukaran dari dashboard.
     *
     * - `lanjut`  : status maju satu langkah (lihat config alur_klaim). Catatan opsional; bila kosong,
     *               catatan yang sudah ada dibiarkan. Poin tetap ditahan sampai `selesai`.
     * - `tolak`   : status jadi `ditolak`, alasan (catatan) wajib dan tampil di Profil anggota. Poin dan stok KEMBALI.
     * - `catatan` : hanya mengganti catatan (mis. kode voucher), status tetap. Boleh dikosongkan.
     * - `buka_kembali` : hanya dari `ditolak` dan hanya untuk klaim lama (tanpa poin ditahan). Status kembali ke `diajukan`.
     *               Penukaran yang sudah mengembalikan poin tidak bisa dibuka kembali: anggota mengajukan lagi.
     *
     * $dariStatus adalah status yang dilihat admin di layarnya. Bila sudah berubah (klik ganda, atau
     * admin lain sudah memprosesnya, atau anggota membatalkan), perubahan ditolak supaya status tidak melompat.
     */
    public function proses(ic_claim $klaim, string $aksi, string $dariStatus, ?string $catatan, ?string $admin): ic_claim
    {
        $catatan = $this->bersihkan($catatan);
        $admin = $admin !== null ? mb_substr($admin, 0, 60) : null;

        return DB::transaction(function () use ($klaim, $aksi, $dariStatus, $catatan, $admin) {
            $k = ic_claim::query()->whereKey($klaim->getKey())->lockForUpdate()->firstOrFail();

            if ($k->status !== $dariStatus) {
                throw new KlaimGagal('Status penukaran ini sudah berubah (sekarang: ' . config('inner_circle.status_klaim.' . $k->status . '.label') . '). Muat ulang halaman untuk melihat yang terbaru.');
            }

            $alur = config('inner_circle.alur_klaim');
            $statusBaru = $k->status;
            $catatanBaru = $k->catatan_admin;

            switch ($aksi) {
                case 'lanjut':
                    if (! isset($alur[$k->status])) {
                        throw new KlaimGagal($this->pesanAkhirAlur($k->status));
                    }
                    $statusBaru = $alur[$k->status]['berikutnya'];
                    $catatanBaru = $catatan ?? $k->catatan_admin;
                    break;

                case 'tolak':
                    if (! isset($alur[$k->status])) {
                        throw new KlaimGagal($this->pesanAkhirAlur($k->status));
                    }
                    if ($catatan === null) {
                        throw new KlaimGagal('Alasan penolakan wajib diisi.');
                    }
                    $statusBaru = 'ditolak';
                    $catatanBaru = $catatan;
                    break;

                case 'catatan':
                    $catatanBaru = $catatan;
                    break;

                case 'buka_kembali':
                    if ($k->status !== 'ditolak') {
                        throw new KlaimGagal('Hanya penukaran yang ditolak yang bisa dibuka kembali.');
                    }
                    if ($k->memakaiPoin()) {
                        throw new KlaimGagal('Poin penukaran ini sudah dikembalikan ke anggota, jadi tidak bisa dibuka kembali. Minta anggota mengajukan lagi dari halaman Hadiah.');
                    }
                    $statusBaru = 'diajukan';
                    $catatanBaru = $catatan;
                    break;

                default:
                    throw new KlaimGagal('Aksi tidak dikenal.');
            }

            if ($statusBaru === $k->status && $catatanBaru === $k->catatan_admin) {
                return $k; // tidak ada yang berubah
            }

            if ($statusBaru === 'ditolak') {
                $this->akhiriDanKembalikan($k, 'ditolak', $admin, $catatanBaru);

                return $k;
            }

            $k->update([
                'status' => $statusBaru,
                'catatan_admin' => $catatanBaru,
                'diproses_oleh' => $admin,
            ]);

            ic_claim_log::create([
                'claim_id' => $k->getKey(),
                'status' => $statusBaru,
                'oleh' => $admin,
                'catatan' => $catatanBaru,
            ]);

            return $k;
        });
    }

    /** Masuk daftar tunggu hadiah yang stoknya habis. */
    public function ikutTunggu(member $anggota, int $rewardId): void
    {
        $reward = ic_reward::query()->where('aktif', true)->whereKey($rewardId)->first();
        if (! $reward) {
            throw new KlaimGagal('Hadiah ini tidak ditemukan atau sedang tidak tersedia.');
        }

        if (! $reward->stokHabis()) {
            throw new KlaimGagal('Hadiah ini masih tersedia, kamu bisa langsung menukarnya.');
        }

        try {
            ic_reward_tunggu::query()->firstOrCreate([
                'member_id' => $anggota->getKey(),
                'reward_id' => $reward->getKey(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Klik ganda: sudah terdaftar, hasilnya sama.
        }
    }

    public function keluarTunggu(member $anggota, int $rewardId): void
    {
        ic_reward_tunggu::query()
            ->where('member_id', $anggota->getKey())
            ->where('reward_id', $rewardId)
            ->delete();
    }

    /**
     * Akhiri penukaran sebagai `ditolak` atau `dibatalkan`: status dan catatan disimpan, poin yang ditahan dikembalikan
     * (sekali saja, dijaga kunci unik dan pemeriksaan sudahKembali), dan stok hadiah ditambah lagi. Dipanggil di dalam
     * transaksi yang sudah mengunci baris penukaran.
     */
    private function akhiriDanKembalikan(ic_claim $k, string $statusBaru, ?string $oleh, ?string $catatan): void
    {
        $k->update([
            'status' => $statusBaru,
            'catatan_admin' => $catatan,
            'diproses_oleh' => $oleh,
        ]);

        ic_claim_log::create([
            'claim_id' => $k->getKey(),
            'status' => $statusBaru,
            'oleh' => $oleh,
            'catatan' => $catatan,
        ]);

        if (! $k->memakaiPoin() || $this->poin->sudahKembali($k)) {
            return;
        }

        $anggota = member::query()->whereKey($k->member_id)->lockForUpdate()->first();
        $hadiah = ic_reward::query()->whereKey($k->reward_id)->lockForUpdate()->first();

        if ($anggota) {
            $this->poin->kembalikanPenukaran(
                $anggota,
                $k,
                $k->poin_ditahan,
                $hadiah?->judul ?? 'hadiah',
                $statusBaru === 'ditolak' ? 'Penukaran ditolak.' : 'Penukaran dibatalkan.',
                $hadiah?->ikon,
            );
        }

        if ($hadiah && $hadiah->stok !== null) {
            $hadiah->increment('stok');
        }
    }

    private function pesanAkhirAlur(string $status): string
    {
        return match ($status) {
            'ditolak' => 'Penukaran ini ditolak. Gunakan "Buka kembali" hanya untuk klaim lama; selebihnya anggota mengajukan lagi.',
            'dibatalkan' => 'Penukaran ini dibatalkan oleh anggota, jadi statusnya tidak bisa diubah lagi.',
            default => 'Penukaran ini sudah selesai, jadi statusnya tidak bisa diubah lagi.',
        };
    }

    /** @return Collection<int, ic_reward> */
    private function hadiahAktif(): Collection
    {
        return ic_reward::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('harga_poin')
            ->get();
    }

    private function bersihkan(?string $teks): ?string
    {
        $teks = $teks === null ? '' : trim($teks);

        return $teks === '' ? null : $teks;
    }
}