<?php

namespace App\View\Composers;

use App\Models\booking;
use App\Models\genre;
use App\Models\media_coverage;
use App\Models\media_sosial;
use App\Support\CachePublik;
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
            // Disimpan sementara (CachePublik); dibuang otomatis saat data Profile berubah di CMS.
            $polos = CachePublik::ingat('footer', fn() => [
                'footerBooking' => CachePublik::polos(booking::all()),
                'footerGenre' => CachePublik::polos(genre::all()),
                'footerMedia' => CachePublik::polos(media_coverage::all()),
                'footerSosial' => CachePublik::polos(media_sosial::all()),
            ]);

            $this->data = [
                'footerBooking' => CachePublik::daftar(booking::class, $polos['footerBooking']),
                'footerGenre' => CachePublik::daftar(genre::class, $polos['footerGenre']),
                'footerMedia' => CachePublik::daftar(media_coverage::class, $polos['footerMedia']),
                'footerSosial' => CachePublik::daftar(media_sosial::class, $polos['footerSosial']),
            ];
        }

        $view->with($this->data);
    }
}