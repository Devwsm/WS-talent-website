<?php

namespace Tests\Feature\InnerCircle\Publik;

use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Berkas logo/favicon/Open Graph resmi untuk situs artis dan Inner Circle.
 */
class AsetMerekTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Pratinjau replika ----------
    // ---------- Lencana belum dibaca ----------
    // ---------- Logo, favicon, Open Graph ----------

    private function anggota(string $nama = 'Ancha Uji', string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m->fresh();
    }

    public function test_berkas_aset_resmi_ada(): void
    {
        foreach (
            [
                'aset/og/og-ws-artis.jpg',
                'aset/og/og-ws-inner-circle.jpg',
                'aset/inner-circle/logo/wsic-wordmark.webp',
                'aset/inner-circle/logo/wsic-wordmark-kompak.webp',
                'favicon.ico',
            ] as $berkas
        ) {
            $this->assertFileExists(public_path($berkas));
            $this->assertGreaterThan(0, filesize(public_path($berkas)), $berkas);
        }
        foreach (['ws-artis', 'ws-inner-circle'] as $merek) {
            foreach (['favicon.ico', 'favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png', 'android-chrome-192x192.png', 'android-chrome-512x512.png', 'site.webmanifest'] as $berkas) {
                $this->assertFileExists(public_path("aset/favicon/$merek/$berkas"));
            }
        }
        [$lebar, $tinggi] = getimagesize(public_path('aset/og/og-ws-inner-circle.jpg'));
        $this->assertSame([1200, 630], [$lebar, $tinggi]);
    }

    public function test_situs_artis_memakai_og_dan_favicon_artis(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('<meta property="og:image" content="' . asset('aset/og/og-ws-artis.jpg') . '">', false)
            ->assertSee('aset/favicon/ws-artis/favicon-32x32.png', false)
            ->assertSee('aset/favicon/ws-artis/apple-touch-icon.png', false)
            ->assertDontSee('aset/favicon/ws-inner-circle', false);
    }

    public function test_inner_circle_memakai_og_logo_dan_favicon_sendiri(): void
    {
        $this->get(route('ic.tentang'))->assertOk()
            ->assertSee('<meta property="og:image" content="' . asset('aset/og/og-ws-inner-circle.jpg') . '">', false)
            ->assertSee('twitter:image', false)
            ->assertSee('aset/favicon/ws-inner-circle/favicon.ico', false)
            ->assertSee('aset/inner-circle/logo/wsic-wordmark.webp', false);

        $this->get(route('ic.masuk'))->assertOk()
            ->assertSee('aset/favicon/ws-inner-circle/apple-touch-icon.png', false)
            ->assertSee('og-ws-inner-circle.jpg', false);

        $this->actingAs($this->anggota(), 'member')->get(route('ic.beranda'))->assertOk()
            ->assertSee('aset/favicon/ws-inner-circle/site.webmanifest', false);
    }
}
