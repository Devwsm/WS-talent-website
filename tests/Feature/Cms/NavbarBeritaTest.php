<?php

namespace Tests\Feature\Cms;

use App\Models\news;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Link News di navbar publik hanya ada bila ada berita (sama seperti bagian News di beranda). */
class NavbarBeritaTest extends TestCase
{
    use RefreshDatabase;

    private function berita(): news
    {
        return news::create([
            'news_title' => 'Rilisan Baru',
            'news_description' => '<p>Isi</p>',
            'news_source' => 'Media',
            'news_date' => '1 Nov 2026',
            'news_cover' => 'x.webp',
            'news_link' => 'https://contoh.test/a',
        ]);
    }

    public function test_tanpa_berita_link_news_hilang_di_beranda_dan_halaman_lain(): void
    {
        foreach (['/', '/profile', '/privasi'] as $alamat) {
            $this->get($alamat)->assertOk()->assertDontSee('#news', false);
        }
    }

    public function test_ada_berita_link_news_tampil_dan_hilang_lagi_setelah_dihapus(): void
    {
        $item = $this->berita();
        $this->get('/profile')->assertOk()->assertSee('#news', false);
        $this->get('/')->assertOk()->assertSee('#news', false);

        // Cache navbar ikut dibuang saat berita dihapus lewat model.
        $item->delete();
        $this->get('/profile')->assertOk()->assertDontSee('#news', false);
    }
}