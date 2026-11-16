<?php

namespace App\View\Composers;

use App\Models\news;
use App\Support\CachePublik;
use Carbon\Carbon;
use Illuminate\View\View;

/**
 * Menyediakan $adaBerita ke navbar publik: link "News" hanya muncul bila ada berita, sama seperti bagian News di
 * beranda yang juga hilang saat kosong. Disimpan sementara (CachePublik) dan dibuang otomatis saat berita berubah.
 */
class NavbarPublikComposer
{
    protected ?bool $adaBerita = null;

    public function compose(View $view): void
    {
        $this->adaBerita ??= $this->hitung();

        $view->with('adaBerita', $this->adaBerita);
    }

    /**
     * Ada berita yang sudah tayang? Yang masih terjadwal tidak dihitung sampai jadwalnya tiba. Cache hanya mencatat
     * kapan berita terjadwal paling awal akan tayang, dan perbandingan dengan waktu sekarang dilakukan tiap kunjungan.
     */
    private function hitung(): bool
    {
        $data = CachePublik::ingat('nav', fn() => [
            'berita' => news::tayang()->exists(),
            'terjadwal_awal' => news::belumTayang()->min('terbit_pada'),
        ]);

        $awal = $data['terjadwal_awal'] ?? null; // `?? null`: cache lama belum punya kunci ini

        return (bool) $data['berita'] || ($awal !== null && Carbon::parse($awal)->lte(now()));
    }
}