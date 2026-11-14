<?php

namespace App\View\Composers;

use App\Models\news;
use App\Support\CachePublik;
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
        $this->adaBerita ??= (bool) CachePublik::ingat('nav', fn() => ['berita' => news::query()->exists()])['berita'];

        $view->with('adaBerita', $this->adaBerita);
    }
}