<?php

namespace App\Services\InnerCircle;

use App\Models\member;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mencocokkan identitas Google dengan anggota Inner Circle: masuk, buat baru, atau kaitkan ke akun yang ada.
 */
class GoogleAkun
{
    public const BARU = 'baru';
    public const KEMBALI = 'kembali';
    public const TERKAIT = 'terkait';

    /**
     * @param  array{sub:string,email:string,name:string,email_verified:bool}  $klaim  hasil GoogleOAuth::selesai()
     * @return array{0: member, 1: string}  anggota dan salah satu konstanta status di atas
     *
     * @throws GoogleGagal
     */
    public function masukkan(array $klaim): array
    {
        try {
            return DB::transaction(function () use ($klaim) {
                // 1) Sudah pernah masuk lewat Google: pencocokan paling andal memakai `sub`, bukan email.
                $anggota = member::where('google_id', $klaim['sub'])->lockForUpdate()->first();
                if ($anggota) {
                    return [$anggota, self::KEMBALI];
                }

                // 2) Email sudah terdaftar (lewat sandi): kaitkan, dengan pengamanan pengambilalihan.
                $anggota = member::where('email', $klaim['email'])->lockForUpdate()->first();
                if ($anggota) {
                    return [$this->kaitkan($anggota, $klaim['sub']), $this->statusKait($anggota)];
                }

                // 3) Anggota baru. Nama diambil dari Google, diperpendek sesuai kolom.
                $nama = $klaim['name'] !== '' ? $klaim['name'] : Str::before($klaim['email'], '@');

                $baru = new member();
                $baru->nama = Str::limit($nama, 100, '');
                $baru->email = $klaim['email'];
                $baru->google_id = $klaim['sub'];
                $baru->email_verified_at = now();
                $baru->save();

                $baru->kode_member = sprintf('IC-%05d', $baru->id_member);
                $baru->save();

                return [$baru, self::BARU];
            });
        } catch (UniqueConstraintViolationException) {
            // Dua proses masuk bersamaan untuk orang yang sama: yang kalah diminta mengulang.
            throw new GoogleGagal('Terjadi benturan data saat membuat akun. Silakan coba lagi.');
        }
    }

    /**
     * Mengaitkan akun Google ke anggota yang sudah ada.
     *
     * Pendaftaran sandi TIDAK memverifikasi email, jadi akun itu bisa dibuat orang lain yang lebih dulu memakai email
     * ini. Karena Google kini membuktikan pemilik email sebenarnya, akun belum terverifikasi dikaitkan dengan:
     * sandi lama dihapus, token "ingat saya" diganti, dan sesi_versi dinaikkan (semua sesi lama otomatis keluar).
     */
    private function kaitkan(member $anggota, string $sub): member
    {
        if ($anggota->google_id !== null && $anggota->google_id !== $sub) {
            throw new GoogleGagal('Email ini sudah terhubung ke akun Google yang lain.');
        }

        if ($anggota->email_verified_at === null) {
            $anggota->password = null;
            $anggota->remember_token = Str::random(60);
            $anggota->sesi_versi = (int) $anggota->sesi_versi + 1;
        }

        $anggota->google_id = $sub;
        $anggota->email_verified_at ??= now();
        $anggota->save();

        return $anggota;
    }

    private function statusKait(member $anggota): string
    {
        // sesi_versi > 0 hanya bila baru saja dinaikkan oleh kaitkan() pada akun belum terverifikasi.
        return $anggota->wasChanged('sesi_versi') ? self::TERKAIT : self::KEMBALI;
    }
}