<?php

namespace Tests\Feature\InnerCircle\KontenCms;

use App\Mail\InnerCircle\ResetSandiMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tab Email di CMS: pratinjau semua jenis email dan email tes.
 */
class TabEmailTest extends TestCase
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

    public function test_tab_email_butuh_login_admin(): void
    {
        $this->get(route('ic.admin.email'))->assertRedirect(route('login'));
    }

    public function test_tab_email_dan_pratinjau_tampil_untuk_semua_jenis(): void
    {
        foreach (['reset', 'google', 'selamat-datang', 'klaim'] as $jenis) {
            $this->admin()->get(route('ic.admin.email', ['jenis' => $jenis]))->assertOk();
            $this->admin()->get(route('ic.admin.email.pratinjau', $jenis))->assertOk()->assertSee('WS Inner Circle');
        }
        $this->admin()->get(route('ic.admin.email.pratinjau', 'ngawur'))->assertNotFound();
    }

    public function test_email_tes_terkirim_dengan_tanda_tes(): void
    {
        Mail::fake();

        $this->admin()->post(route('ic.admin.email.tes'), ['jenis' => 'reset', 'tujuan' => 'saya@contoh.com'])
            ->assertSessionHas('success');

        Mail::assertSent(ResetSandiMail::class, fn($m) => $m->hasTo('saya@contoh.com') && $m->tes === true && str_starts_with($m->envelope()->subject, '[TES]'));
    }

    public function test_email_tes_validasi(): void
    {
        $this->admin()->post(route('ic.admin.email.tes'), ['jenis' => 'ngawur', 'tujuan' => 'bukan-email'])
            ->assertSessionHasErrors(['jenis', 'tujuan']);
    }
}
