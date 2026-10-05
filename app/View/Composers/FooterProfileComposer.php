<?php

namespace App\View\Composers;

use App\Models\booking;
use App\Models\genre;
use App\Models\media_coverage;
use App\Models\media_sosial;
use Illuminate\View\View;

/**
 * Menyediakan data profil yang ditampilkan di footer (booking, genre, media coverage,
 * media sosial) ke view components.footer — jadi footer otomatis lengkap di halaman mana
 * pun ia di-include tanpa perlu menambah variabel di tiap controller.
 *
 * Data diambil dari tabel yang sama dengan halaman /profile, jadi kalau staf mengubahnya
 * lewat dashboard Profile, footer ikut berubah.
 */
class FooterProfileComposer
{
    protected ?array $data = null;

    public function compose(View $view): void
    {
        if ($this->data === null) {
            $this->data = [
                'footerBooking' => booking::all(),
                'footerGenre' => genre::all(),
                'footerMedia' => media_coverage::all(),
                'footerSosial' => media_sosial::all(),
            ];
        }

        $view->with($this->data);
    }
}