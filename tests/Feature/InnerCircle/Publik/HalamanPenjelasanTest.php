<?php

namespace Tests\Feature\InnerCircle\Publik;

use App\Models\ic_event;
use App\Models\ic_reward;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\StempelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman publik penjelasan program untuk calon anggota.
 */
class HalamanPenjelasanTest extends TestCase
{
    use RefreshDatabase;
    // ---------- halaman publik ----------
    // ---------- buka kembali klaim ----------
    // ---------- tampil / sembunyikan hadiah ----------
    // ---------- tampil / sembunyikan pengumuman ----------

    private function anggota(int $stempel = 0): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->save();
        if ($stempel > 0) {
            app(StempelService::class)->tambahManual($m, $stempel, 'Uji', 'admin');
        }

        return $m->fresh();
    }

    private function acara(array $o = []): ic_event
    {
        return app(AcaraService::class)->buat($o + [
            'tanggal' => now()->addDays(3)->toDateString(),
            'tipe' => 'club',
            'venue' => 'Venue Uji Satu',
            'kota' => 'Bandung',
        ]);
    }

    public function test_tamu_membuka_halaman_penjelasan_dengan_hadiah_dan_acara_publik(): void
    {
        $this->acara(['venue' => 'Venue Publik Uji']);
        $this->acara(['tipe' => 'private', 'venue' => 'Acara Privat Rahasia']);
        $this->acara(['venue' => null, 'kota' => 'Kota Tanpa Venue']);
        $this->acara(['tanggal' => now()->subDays(5)->toDateString(), 'venue' => 'Acara Lewat Uji']);

        $this->get(route('ic.tentang'))
            ->assertOk()
            ->assertSee('Cara kerjanya')
            ->assertSee('Hadiah Pertama')
            ->assertSee('Venue Publik Uji')
            ->assertDontSee('Acara Privat Rahasia')
            ->assertDontSee('Kota Tanpa Venue')
            ->assertDontSee('Acara Lewat Uji')
            ->assertDontSee('TERKUNCI') // tanpa label status khas anggota
            ->assertSee(route('ic.daftar'), false)
            ->assertSee('<link rel="canonical"', false)
            ->assertDontSee('noindex', false);
    }

    public function test_halaman_penjelasan_tanpa_acara_dan_tanpa_hadiah_aktif_tetap_tampil(): void
    {
        ic_reward::query()->update(['aktif' => false]);

        $this->get(route('ic.tentang'))->assertOk()->assertSee('Jadwal acara berikutnya akan diumumkan')->assertDontSee('Hadiah Pertama');
    }

    public function test_tamu_di_beranda_dialihkan_ke_penjelasan_dan_anggota_tetap_ke_beranda(): void
    {
        $this->get('/innercircle')->assertRedirect(route('ic.tentang'));
        $this->get(route('ic.hadiah'))->assertRedirect(route('ic.masuk'));

        $m = $this->anggota();
        $this->actingAs($m, 'member')->get('/innercircle')->assertOk();
        $this->actingAs($m, 'member')->get(route('ic.tentang'))->assertOk()->assertSee('Buka Inner Circle');
    }

    public function test_halaman_penjelasan_mati_bersama_flag_dan_robots_membukanya(): void
    {
        $robots = $this->get('/robots.txt')->getContent();
        $this->assertStringContainsString('Allow: /innercircle/tentang', $robots);
        $this->assertLessThan(strpos($robots, 'Disallow: /innercircle'), strpos($robots, 'Allow: /innercircle/tentang'), 'Allow harus ditulis sebelum Disallow.');

        config(['inner_circle.aktif' => false]);
        $this->get(route('ic.tentang'))->assertNotFound();
        $this->assertStringNotContainsString('/innercircle/tentang', $this->get('/robots.txt')->getContent());
    }
}
