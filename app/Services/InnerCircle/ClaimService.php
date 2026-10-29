<?php

namespace App\Services\InnerCircle;

use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_reward;
use App\Models\member;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aturan hadiah dan klaim WS Inner Circle (semua dicek di server):
 * - klaim hanya bila stempel_total >= target tahap, sekali per anggota per tahap;
 * - tahap yang punya pilihan (mis. 50 stempel) wajib memilih salah satunya;
 * - status bergerak diajukan -> ditinjau -> disetujui -> selesai, atau ditolak (alasan wajib);
 *   selesai adalah akhir alur. Pengajuan yang ditolak bisa dibuka kembali oleh admin (kembali ke diajukan).
 *   Tiap perubahan dicatat di ic_claim_logs.
 *
 * Stempel tidak berkurang saat klaim: hadiah tidak "membeli" stempel.
 */
class ClaimService
{
    /**
     * Tahapan hadiah aktif untuk satu anggota. Tiap tahap membawa `status_member`
     * (terkunci | terbuka | status klaimnya) dan `catatan_admin` (kosong bila belum diklaim).
     *
     * @return Collection<int, ic_reward>
     */
    public function hadiahUntuk(member $anggota): Collection
    {
        $klaim = ic_claim::query()
            ->where('member_id', $anggota->getKey())
            ->get()
            ->keyBy('reward_id');

        return $this->tahapanAktif()->each(function (ic_reward $reward) use ($anggota, $klaim) {
            $k = $klaim->get($reward->getKey());

            $reward->setAttribute(
                'status_member',
                $k?->status ?? ($anggota->stempel_total >= $reward->target_stempel ? 'terbuka' : 'terkunci')
            );
            $reward->setAttribute('catatan_admin', $k?->catatan_admin);
        });
    }

    /** Tahapan berikutnya yang belum tercapai (setelah tahap terakhir, tetap di tahap terakhir). */
    public function berikutnya(int $stempel): object
    {
        $target = $this->tahapanAktif()->pluck('target_stempel');
        $berikutnya = $target->first(fn($n) => $n > $stempel) ?? $target->last() ?? 250;

        return (object) [
            'target_stempel' => $berikutnya,
            'sisa' => max(0, $berikutnya - $stempel),
            'persen' => min(100, (int) round($stempel / $berikutnya * 100)),
        ];
    }

    /** Pengajuan milik satu anggota, terbaru di atas. */
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
     * Ajukan hadiah tahap $targetStempel. Melempar KlaimGagal (pesan siap tampil) bila ada aturan
     * yang tidak terpenuhi. Baris anggota dikunci selama transaksi supaya klik ganda diproses
     * bergantian; kunci unik (member_id, reward_id) jadi pengaman terakhir.
     */
    public function ajukan(member $anggota, int $targetStempel, ?string $opsi = null): ic_claim
    {
        $reward = ic_reward::query()
            ->where('aktif', true)
            ->where('target_stempel', $targetStempel)
            ->first();
        if (! $reward) {
            throw new KlaimGagal('Tahapan hadiah ini tidak ditemukan.');
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

                if ($terkunci->stempel_total < $reward->target_stempel) {
                    throw new KlaimGagal(
                        'Stempelmu belum cukup. Tahap ini butuh ' . $reward->target_stempel
                            . ' stempel, kamu punya ' . $terkunci->stempel_total . '.'
                    );
                }

                $sudah = ic_claim::query()
                    ->where('member_id', $terkunci->getKey())
                    ->where('reward_id', $reward->getKey())
                    ->exists();
                if ($sudah) {
                    throw new KlaimGagal('Kamu sudah mengajukan hadiah tahap ini. Setiap tahap hanya bisa diklaim satu kali.');
                }

                $klaim = ic_claim::create([
                    'member_id' => $terkunci->getKey(),
                    'reward_id' => $reward->getKey(),
                    'opsi_dipilih' => $opsi,
                    'status' => 'diajukan',
                ]);

                ic_claim_log::create([
                    'claim_id' => $klaim->getKey(),
                    'status' => 'diajukan',
                ]);

                return $klaim;
            });
        } catch (UniqueConstraintViolationException) {
            throw new KlaimGagal('Kamu sudah mengajukan hadiah tahap ini. Setiap tahap hanya bisa diklaim satu kali.');
        } finally {
            // Instance anggota dari guard masih membawa angka lama.
            $anggota->refresh();
        }
    }

    /**
     * Proses pengajuan dari dashboard.
     *
     * - `lanjut`  : status maju satu langkah (lihat config alur_klaim). Catatan opsional; bila kosong,
     *               catatan yang sudah ada dibiarkan.
     * - `tolak`   : status jadi `ditolak`, alasan (catatan) wajib dan tampil di Profil anggota.
     * - `catatan` : hanya mengganti catatan (mis. kode voucher), status tetap. Boleh dikosongkan.
     * - `buka_kembali` : hanya dari `ditolak`. Status kembali ke `diajukan` dan alur dimulai lagi dari awal. Catatan lama
     *               (alasan penolakan) dihapus dari tampilan anggota, diganti catatan baru bila diisi; alasan lama tetap
     *               tersimpan di riwayat (ic_claim_logs).
     *
     * $dariStatus adalah status yang dilihat admin di layarnya. Bila sudah berubah (klik ganda, atau
     * admin lain sudah memprosesnya), perubahan ditolak supaya status tidak melompat dua langkah.
     */
    public function proses(ic_claim $klaim, string $aksi, string $dariStatus, ?string $catatan, ?string $admin): ic_claim
    {
        $catatan = $this->bersihkan($catatan);
        $admin = $admin !== null ? mb_substr($admin, 0, 60) : null;

        return DB::transaction(function () use ($klaim, $aksi, $dariStatus, $catatan, $admin) {
            $k = ic_claim::query()->whereKey($klaim->getKey())->lockForUpdate()->firstOrFail();

            if ($k->status !== $dariStatus) {
                throw new KlaimGagal('Status pengajuan ini sudah berubah (sekarang: ' . config('inner_circle.status_klaim.' . $k->status . '.label') . '). Muat ulang halaman untuk melihat yang terbaru.');
            }

            $alur = config('inner_circle.alur_klaim');
            $statusBaru = $k->status;
            $catatanBaru = $k->catatan_admin;

            switch ($aksi) {
                case 'lanjut':
                    if (! isset($alur[$k->status])) {
                        throw new KlaimGagal($k->status === 'ditolak'
                            ? 'Pengajuan ini ditolak. Gunakan "Buka kembali" bila ingin memprosesnya lagi.'
                            : 'Pengajuan ini sudah selesai, jadi statusnya tidak bisa diubah lagi.');
                    }
                    $statusBaru = $alur[$k->status]['berikutnya'];
                    $catatanBaru = $catatan ?? $k->catatan_admin;
                    break;

                case 'tolak':
                    if (! isset($alur[$k->status])) {
                        throw new KlaimGagal($k->status === 'ditolak'
                            ? 'Pengajuan ini sudah ditolak.'
                            : 'Pengajuan ini sudah selesai, jadi statusnya tidak bisa diubah lagi.');
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
                        throw new KlaimGagal('Hanya pengajuan yang ditolak yang bisa dibuka kembali.');
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

    /** @return Collection<int, ic_reward> */
    private function tahapanAktif(): Collection
    {
        return ic_reward::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('target_stempel')
            ->get();
    }

    private function bersihkan(?string $teks): ?string
    {
        $teks = $teks === null ? '' : trim($teks);

        return $teks === '' ? null : $teks;
    }
}