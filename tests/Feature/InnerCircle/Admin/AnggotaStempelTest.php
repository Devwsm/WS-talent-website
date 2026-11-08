<?php

namespace Tests\Feature\InnerCircle\Admin;

use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tab Anggota di CMS: daftar anggota dan stempel manual.
 */
class AnggotaStempelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bekukan jam di malam hari WIB supaya jendela check-in "hari ini" (12.00 s.d. H+1 06.00) selalu terbuka,
        // berapa pun jam tes dijalankan.
        Carbon::setTestNow(now()->setTime(20, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function anggota(string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now(); // check-in, kenangan, dan suka butuh email terverifikasi
        $m->save();

        return $m;
    }

    private function acara(array $o = []): ic_event
    {
        return app(AcaraService::class)->buat($o + [
            'tanggal' => now()->toDateString(),
            'tipe' => 'club',
            'venue' => 'Superhouse Satrio',
            'kota' => 'Jakarta',
        ]);
    }

    private function checkin(member $m, string $kode, ?string $acara = null)
    {
        return $this->actingAs($m, 'member')->post(route('ic.checkin.proses'), array_filter(['kode' => $kode, 'acara' => $acara]));
    }

    public function test_admin_tab_tampil_dan_stempel_manual_menambah_total(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        $admin = $this->withSession(['login' => true, 'user' => 'admin']);

        $this->checkin($m, $e->token, $e->kode);
        $this->app['auth']->guard('member')->logout();

        foreach (['ic.admin.ringkasan', 'ic.admin.anggota', 'ic.admin.acara', 'ic.admin.klaim'] as $r) {
            $admin->get(route($r))->assertOk();
        }
        $admin->get(route('ic.admin.acara', ['acara' => $e->kode]))->assertOk()->assertSee('<svg', false)->assertSee($e->token);
        $admin->get(route('ic.admin.anggota', ['q' => 'IC-0000']))->assertOk()->assertSee('Anggota Uji');

        $admin->post(route('ic.admin.anggota.stempel', $m->id_member), ['jumlah' => 3, 'catatan' => 'Acara privat'])->assertRedirect();
        $this->assertSame(4, $m->fresh()->stempel_total);
        $this->assertSame('admin', ic_stempel::where('sumber', 'manual')->first()->dicatat_oleh);

        $admin->post(route('ic.admin.anggota.stempel', $m->id_member), ['jumlah' => 11, 'catatan' => 'x'])->assertSessionHasErrors('jumlah');
        $admin->post(route('ic.admin.anggota.stempel', $m->id_member), ['jumlah' => 1, 'catatan' => ''])->assertSessionHasErrors('catatan');
    }

    public function test_admin_tab_butuh_login_admin(): void
    {
        $this->get(route('ic.admin.acara'))->assertRedirect(route('login'));
        $this->post(route('ic.admin.acara.simpan'), [])->assertRedirect(route('login'));
    }
}
