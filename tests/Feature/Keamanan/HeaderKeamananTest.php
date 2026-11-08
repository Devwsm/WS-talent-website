<?php

namespace Tests\Feature\Keamanan;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Header keamanan, HSTS hanya di produksi, dan robots.txt yang tidak tertimpa berkas statis.
 */
class HeaderKeamananTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_keamanan_terpasang_di_halaman_publik(): void
    {
        $respons = $this->get('/')->assertOk();

        $respons->assertHeader('X-Content-Type-Options', 'nosniff');
        $respons->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respons->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=(self)', $respons->headers->get('Permissions-Policy'));
        $this->assertStringContainsString('geolocation=(self)', $respons->headers->get('Permissions-Policy'));
    }

    public function test_hsts_hanya_di_produksi_lewat_https(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'production';
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
    }

    public function test_robots_tidak_tertimpa_berkas_statis(): void
    {
        // Apache melayani public/robots.txt lebih dulu daripada route Laravel, jadi berkas ini tidak boleh ada.
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }
}
