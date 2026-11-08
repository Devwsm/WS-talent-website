<?php

namespace App\Http\Controllers\Concerns;

use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Validation\ValidationException;

/**
 * Konversi unggahan gambar CMS (banner, header, album, merch, news, profil) ke WebP.
 *
 * Dulu dua controller (dashboardController dan profileController) masing-masing membawa salinan persis fungsi
 * ini. Sekarang satu tempat, dan pengolahannya diserahkan ke ImageProcessor: format dibaca dari ISI berkas,
 * orientasi EXIF foto HP diluruskan (metadata, termasuk lokasi GPS, ikut terbuang), dan memori dijaga.
 *
 * Yang sengaja dipertahankan dari perilaku lama CMS: batas 16 megapiksel, kunci galat `gambar`, dan pesan
 * validasi berbahasa Indonesia (pelanggaran jadi pesan di form, bukan halaman error 500).
 */
trait MengonversiGambarWebp
{
    /**
     * Batas jumlah piksel (lebar x tinggi) gambar yang mau didecode. Berkas kecil di disk bisa mengembang jadi
     * ratusan MB di memori (bom dekompresi) dan menjatuhkan request dengan error 500 / kehabisan memori.
     * 16 MP ~ 4000x4000, decode-nya sekitar 64 MB sehingga masih aman di hosting dengan memory_limit 128 MB.
     */
    private const MAKS_PIKSEL_CMS = 16_000_000;

    /**
     * @return string isi WebP (belum disimpan ke mana pun)
     *
     * @throws ValidationException bila berkas bukan gambar valid, terlalu besar, atau gagal diolah
     */
    private function convertToWebP(string $sourcePath, int $quality = 82): string
    {
        // Periksa ukuran SEBELUM didecode. Pelanggaran jadi pesan validasi (bukan error 500).
        $info = @getimagesize($sourcePath);
        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw ValidationException::withMessages(['gambar' => 'Berkas ini bukan gambar yang valid. Pilih gambar JPG atau PNG.']);
        }
        if ($info[0] * $info[1] > self::MAKS_PIKSEL_CMS) {
            throw ValidationException::withMessages(['gambar' => 'Resolusi gambar terlalu besar (maksimal 16 megapiksel, misalnya 4000x4000 piksel). Kecilkan gambar lalu unggah ulang.']);
        }

        try {
            // Sisi maksimal 0 = ukuran asli dipertahankan (CMS tidak memperkecil banner/album).
            return app(ImageProcessor::class)->keWebp($sourcePath, 0, $quality);
        } catch (GambarGagal $e) {
            throw ValidationException::withMessages(['gambar' => $e->getMessage()]);
        }
    }
}