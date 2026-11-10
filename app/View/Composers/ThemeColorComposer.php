<?php

namespace App\View\Composers;

use App\Models\color_pages;
use App\Support\CachePublik;
use Illuminate\View\View;

/**
 * Menyediakan variabel $color ke semua view yang butuh (navbar, footer, dll)
 * tanpa perlu di-pass manual satu-satu dari tiap controller.
 *
 * Sebelumnya $color cuma didefinisikan di home.blade.php lewat @php block,
 * jadi begitu navbar/footer di-include dari view lain (mis. /profile),
 * $color jadi undefined. Dengan composer ini, satu sumber kebenaran untuk
 * semua halaman.
 */
class ThemeColorComposer
{
    protected ?string $color = null;

    public function compose(View $view): void
    {
        if ($this->color === null) {
            // Disimpan sementara (CachePublik): dipakai di hampir semua halaman publik, dan dulu bisa memicu
            // query yang sama beberapa kali dalam satu halaman (layout, navbar, footer).
            $this->color = CachePublik::ingat('tema', function () {
                $color = color_pages::first()->color ?? null;

                // Fallback hitam (warna universal default) kalau kosong / format hex tidak valid.
                return ($color && preg_match('/^#([0-9a-f]{3}){1,2}$/i', $color))
                    ? $color
                    : '#000000';
            });
        }

        $view->with('color', $this->color);
    }
}