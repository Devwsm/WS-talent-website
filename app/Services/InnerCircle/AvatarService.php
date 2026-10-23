<?php

namespace App\Services\InnerCircle;

use App\Models\member;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Foto profil anggota Inner Circle (kolom `members.avatar`, disk `public`). Tanpa foto, tampil emoji bawaan. */
class AvatarService
{
    public function __construct(private readonly ImageProcessor $gambar) {}

    /**
     * Simpan foto baru dan hapus yang lama. Foto lama baru dihapus setelah yang baru tersimpan,
     * jadi unggahan yang gagal tidak membuat anggota kehilangan foto lamanya.
     *
     * @throws GambarGagal
     */
    public function simpan(member $anggota, UploadedFile $berkas): string
    {
        $baru = $this->gambar->simpanWebp($berkas, 'inner-circle/avatar', (int) config('inner_circle.foto_profil_sisi_maks'), 82);
        $lama = $anggota->avatar;

        try {
            $anggota->forceFill(['avatar' => $baru])->save();
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($baru);
            throw $e;
        }

        if ($lama && $lama !== $baru) {
            Storage::disk('public')->delete($lama);
        }

        return $baru;
    }

    /** Kembali ke avatar bawaan. false bila memang belum punya foto. */
    public function hapus(member $anggota): bool
    {
        $lama = $anggota->avatar;
        if (! $lama) {
            return false;
        }

        $anggota->forceFill(['avatar' => null])->save();
        Storage::disk('public')->delete($lama);

        return true;
    }
}