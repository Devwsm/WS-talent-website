<?php

namespace Tests\Feature;

use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 7: integrasi WS Inner Circle ke situs publik (teaser di home, link navbar, robots.txt).
 * Semuanya mengikuti flag INNER_CIRCLE_AKTIF: mati = situs publik persis seperti sebelum Inner Circle ada.
 */
class InnerCircleSitusPublikTest extends TestCase
{
    use RefreshDatabase;

    private function nyala(bool $aktif): void
    {
        config(['inner_circle.aktif' => $aktif]);
    }

    public function test_flag_mati_home_dan_profile_tidak_menampilkan_inner_circle(): void
    {
        $this->nyala(false);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('WS Inner Circle')
            ->assertDontSee(route('ic.daftar'), false)
            ->assertDontSee(route('ic.beranda'), false);

        $this->get('/profile')
            ->assertOk()
            ->assertDontSee('Inner Circle');
    }

    public function test_flag_menyala_home_menampilkan_teaser_dengan_link_daftar_dan_masuk(): void
    {
        $this->nyala(true);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="inner-circle"', false)
            ->assertSee('WS Inner Circle')
            ->assertSee(route('ic.daftar'), false)
            ->assertSee(route('ic.masuk'), false);
    }

    public function test_teaser_untuk_anggota_yang_sudah_masuk_mengarah_ke_beranda(): void
    {
        $this->nyala(true);
        $anggota = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);

        $this->actingAs($anggota, 'member')
            ->get('/')
            ->assertOk()
            ->assertSee('Buka Inner Circle')
            ->assertDontSee('Gabung sekarang');
    }

    public function test_navbar_menampilkan_link_inner_circle_di_desktop_dan_menu_mobile(): void
    {
        $this->nyala(true);

        foreach (['/', '/profile'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // Satu di navigasi desktop, satu di drawer mobile.
            $this->assertSame(
                2,
                substr_count($html, 'href="' . route('ic.beranda') . '"'),
                "Link Inner Circle di navbar {$url} harus muncul dua kali (desktop + drawer mobile)."
            );
        }
    }

    public function test_robots_menutup_inner_circle_tapi_tidak_halaman_publik(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /innercircle', $robots);
        $this->assertStringContainsString('Disallow: /dashboard', $robots);
        $this->assertStringContainsString('Allow: /', $robots);
    }

    public function test_sitemap_hanya_memuat_halaman_penjelasan_inner_circle(): void
    {
        $this->nyala(true);

        // Halaman penjelasan program boleh diindeks; area anggota tidak boleh ada di sitemap.
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/innercircle/tentang')
            ->assertDontSee('/innercircle/masuk')
            ->assertDontSee('/innercircle/daftar')
            ->assertDontSee('/innercircle/hadiah');

        $this->nyala(false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('inner-circle');
    }

    public function test_halaman_inner_circle_tetap_noindex(): void
    {
        $this->nyala(true);

        $this->get(route('ic.masuk'))
            ->assertOk()
            ->assertSee('noindex', false);
    }
}