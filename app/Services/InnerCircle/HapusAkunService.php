<?php

namespace App\Services\InnerCircle;

use App\Models\ic_claim;
use App\Models\ic_memory;
use App\Models\member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Penghapusan akun anggota oleh anggota sendiri (hak hapus data, lihat halaman /privasi).
 *
 * Semua kunci asing ke `members` bersifat cascade, jadi menghapus barisnya otomatis ikut menghapus stempel,
 * pengajuan hadiah beserta lognya, kenangan, dan tanda suka. Berkas fotonya (avatar dan foto kenangan) berada di
 * disk, bukan di database, jadi dikumpulkan dulu lalu dihapus setelah transaksi berhasil.
 *
 * Syarat: tidak ada pengajuan hadiah yang masih menunggu tindakan admin. Kalau ada, akun tidak dihapus supaya
 * hadiah yang sedang diproses tidak hilang begitu saja.
 */
class HapusAkunService
{
    public function adaKlaimMenunggu(member $anggota): bool
    {
        return ic_claim::where('member_id', $anggota->getKey())
            ->whereIn('status', ic_claim::statusMenunggu())
            ->exists();
    }

    /** @throws AkunGagal */
    public function hapus(member $anggota): void
    {
        $berkas = [];
        $kode = $anggota->kode_member;

        DB::transaction(function () use ($anggota, &$berkas) {
            // Baris dikunci: pengajuan hadiah baru (yang juga mengunci baris ini) tidak bisa menyelip di tengah.
            $terkunci = member::whereKey($anggota->getKey())->lockForUpdate()->first();

            if (! $terkunci) {
                return; // sudah terhapus (klik ganda)
            }

            if ($this->adaKlaimMenunggu($terkunci)) {
                throw new AkunGagal('Kamu masih punya pengajuan hadiah yang belum selesai. Akun bisa dihapus setelah pengajuannya selesai atau ditolak.');
            }

            $berkas = ic_memory::where('member_id', $terkunci->getKey())->whereNotNull('foto')->pluck('foto')->all();
            if ($terkunci->avatar) {
                $berkas[] = $terkunci->avatar;
            }

            $terkunci->delete();
        });

        foreach ($berkas as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                // Akun sudah terhapus; berkas yatim cukup dicatat supaya bisa dibersihkan manual.
                Log::warning('Hapus akun: berkas tidak terhapus', ['path' => $path, 'pesan' => $e->getMessage()]);
            }
        }

        Log::info('Akun anggota dihapus oleh pemiliknya', ['kode_member' => $kode]);
    }
}