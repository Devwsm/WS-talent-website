<?php

namespace App\Services\InnerCircle;

use App\Models\member;

/**
 * Menonaktifkan dan mengaktifkan kembali akun anggota oleh admin.
 *
 * Menonaktifkan = tidak bisa masuk (kata sandi maupun Google) dan sesi yang sedang berjalan berakhir pada permintaan
 * berikutnya (middleware IcSesiVersi). Data anggota (stempel, kenangan, klaim) tidak diubah atau dihapus, jadi akun bisa
 * diaktifkan kembali kapan saja tanpa kehilangan apa pun.
 */
class AkunAnggotaService
{
    public function nonaktifkan(member $anggota, string $alasan, ?string $admin): void
    {
        $anggota->forceFill([
            'diblokir_pada' => now(),
            'alasan_blokir' => $alasan,
            'diblokir_oleh' => $admin,
            // Naikkan versi sesi dan buang token "ingat saya": sesi lama tidak berlaku lagi bahkan sesudah akun dibuka.
            'sesi_versi' => (int) $anggota->sesi_versi + 1,
            'remember_token' => null,
        ])->save();
    }

    public function aktifkan(member $anggota): void
    {
        $anggota->forceFill([
            'diblokir_pada' => null,
            'alasan_blokir' => null,
            'diblokir_oleh' => null,
        ])->save();
    }
}