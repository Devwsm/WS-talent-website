<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_pengaduan;
use App\Models\member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pengaduan anggota (Fase 9): kirim, daftar milik anggota, dan tindak lanjut admin.
 *
 * Aturan: kategori harus dari daftar baku; isi 10 sampai 1.000 karakter; tanpa lampiran; acara terkait boleh kosong;
 * paling banyak 3 pengaduan per anggota per hari (dihitung dari database, 24 jam terakhir). Status bergerak
 * baru -> diproses -> selesai; admin boleh mengubah catatan dan membuka kembali yang selesai.
 */
class PengaduanService
{
    /** @return Collection<int, ic_pengaduan> pengaduan milik satu anggota, terbaru di atas */
    public function milik(member $anggota): Collection
    {
        return ic_pengaduan::query()
            ->with('event')
            ->where('member_id', $anggota->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id_pengaduan')
            ->get();
    }

    public function sisaHariIni(member $anggota): int
    {
        $terpakai = ic_pengaduan::query()
            ->where('member_id', $anggota->getKey())
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return max(0, (int) config('inner_circle.pengaduan.batas_per_hari', 3) - $terpakai);
    }

    /** @throws PengaduanGagal */
    public function kirim(member $anggota, string $kategori, ?int $eventId, string $isi): ic_pengaduan
    {
        $isi = trim($isi);

        if (! array_key_exists($kategori, config('inner_circle.pengaduan.kategori'))) {
            throw new PengaduanGagal('Kategori pengaduan tidak dikenal.');
        }

        $min = (int) config('inner_circle.pengaduan.isi_min', 10);
        $maks = (int) config('inner_circle.pengaduan.isi_maks', 1000);
        if (mb_strlen($isi) < $min) {
            throw new PengaduanGagal("Ceritakan sedikit lebih lengkap (minimal $min karakter).");
        }
        if (mb_strlen($isi) > $maks) {
            throw new PengaduanGagal('Pengaduan terlalu panjang (maksimal ' . ic_angka($maks) . ' karakter).');
        }

        if ($eventId !== null && ! ic_event::query()->whereKey($eventId)->exists()) {
            throw new PengaduanGagal('Acara yang dipilih tidak ditemukan.');
        }

        return DB::transaction(function () use ($anggota, $kategori, $eventId, $isi) {
            // Baris anggota dikunci supaya dua kiriman bersamaan tidak sama-sama lolos batas harian.
            $terkunci = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();

            if ($this->sisaHariIni($terkunci) < 1) {
                throw new PengaduanGagal('Batas pengaduan hari ini sudah tercapai (' . (int) config('inner_circle.pengaduan.batas_per_hari', 3) . ' per hari). Coba lagi besok.');
            }

            return ic_pengaduan::create([
                'member_id' => $terkunci->getKey(),
                'kategori' => $kategori,
                'event_id' => $eventId,
                'isi' => $isi,
                'status' => ic_pengaduan::BARU,
            ]);
        });
    }

    /**
     * Tindak lanjut admin. $status = status tujuan (baru, diproses, selesai); $dariStatus = status yang dilihat admin
     * (ditolak bila sudah berubah, mis. klik ganda atau admin lain). Catatan kosong menghapus catatan.
     *
     * @return array{0: ic_pengaduan, 1: bool} pengaduan dan apakah STATUSNYA berubah (hanya itu yang dikabarkan lewat email)
     * @throws PengaduanGagal
     */
    public function proses(ic_pengaduan $pengaduan, string $status, string $dariStatus, ?string $catatan, ?string $admin): array
    {
        if (! array_key_exists($status, config('inner_circle.pengaduan.status'))) {
            throw new PengaduanGagal('Status tidak dikenal.');
        }

        $catatan = $catatan === null ? null : trim($catatan);
        $catatan = $catatan === '' ? null : $catatan;
        $admin = $admin !== null ? mb_substr($admin, 0, 60) : null;

        return DB::transaction(function () use ($pengaduan, $status, $dariStatus, $catatan, $admin) {
            $p = ic_pengaduan::query()->whereKey($pengaduan->getKey())->lockForUpdate()->firstOrFail();

            if ($p->status !== $dariStatus) {
                throw new PengaduanGagal('Status pengaduan ini sudah berubah (sekarang: ' . $p->labelStatus() . '). Muat ulang halaman untuk melihat yang terbaru.');
            }

            $berubah = $p->status !== $status;
            if (! $berubah && $p->catatan_admin === $catatan) {
                return [$p, false];
            }

            $p->update([
                'status' => $status,
                'catatan_admin' => $catatan,
                'diproses_oleh' => $admin,
            ]);

            return [$p, $berubah];
        });
    }
}