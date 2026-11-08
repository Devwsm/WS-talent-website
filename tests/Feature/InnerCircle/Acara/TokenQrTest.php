<?php

namespace Tests\Feature\InnerCircle\Acara;

use App\Models\ic_event;
use App\Services\InnerCircle\AcaraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Token acara yang tidak terbaca (APP_KEY berbeda) tidak menjatuhkan halaman dan bisa diperbaiki.
 */
class TokenQrTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Token rusak ("The MAC is invalid") ----------
    // ---------- Bandsintown ----------
    // ---------- Lupa kata sandi ----------
    // ---------- Tab Email ----------
    // ---------- Validasi link CMS ----------

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function acara(string $tanggal = '2030-05-01', string $venue = 'Club Satu'): ic_event
    {
        return app(AcaraService::class)->buat(['tanggal' => $tanggal, 'tipe' => 'club', 'kota' => 'Jakarta', 'venue' => $venue]);
    }

    private function rusakkanToken(ic_event $e): void
    {
        DB::table('ic_events')->where('id_event', $e->id_event)->update(['token' => 'bukan-token-terenkripsi']);
    }

    public function test_token_rusak_tidak_menjatuhkan_halaman_acara_dan_bisa_diperbaiki(): void
    {
        $e = $this->acara(now()->addDay()->toDateString());
        $this->rusakkanToken($e);

        $this->assertTrue($e->fresh()->tokenRusak());

        $this->admin()->get(route('ic.admin.acara'))
            ->assertOk()
            ->assertSee('Perbaiki token');

        $this->admin()->post(route('ic.admin.acara.perbaiki'))->assertRedirect();

        $baru = $e->fresh();
        $this->assertFalse($baru->tokenRusak());
        $this->assertMatchesRegularExpression('/^WS-[A-Z0-9]{12}$/', $baru->token);
        $this->admin()->get(route('ic.admin.acara'))->assertOk()->assertDontSee('Perbaiki token');
    }

    public function test_unduh_qr_token_rusak_ditolak_dengan_jelas(): void
    {
        $e = $this->acara();
        $this->rusakkanToken($e);

        $this->admin()->get(route('ic.admin.acara.qr', $e->kode))->assertStatus(409);
    }
}
