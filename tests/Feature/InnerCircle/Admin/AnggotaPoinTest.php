<?php

namespace Tests\Feature\InnerCircle\Admin;

use App\Models\ic_event;
use App\Models\ic_poin;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tab Anggota di CMS: daftar anggota dan poin manual.
 */
class AnggotaPoinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bekukan jam di malam hari WIB supaya jendela check-in "hari ini" (22.00 s.d. H+1 04.00) selalu terbuka,
        // berapa pun jam tes dijalankan.
        Carbon::setTestNow(now()->setTime(23, 0));
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

    /** Acara uji selalu punya titik lokasi: pada aturan baru check-in tanpa titik tidak dibuka. */
    private function acara(array $o = []): ic_event
    {
        $e = app(AcaraService::class)->buat($o + [
            'tanggal' => now()->toDateString(),
            'tipe' => 'club',
            'venue' => 'Superhouse Satrio',
            'kota' => 'Jakarta',
        ]);
        $e->forceFill(['lokasi_lat' => -6.2088, 'lokasi_lng' => 106.8456, 'lokasi_radius' => 150])->save();

        return $e;
    }

    private function checkin(member $m, string $kode, ?string $acara = null)
    {
        // Pemindaian QR khusus pemenang GL: anggota uji dijadikan pemenang di semua acara yang ada.
        foreach (\App\Models\ic_event::all() as $acaraUji) {
            \App\Models\ic_gl_antrean::query()->firstOrCreate(['event_id' => $acaraUji->id_event, 'member_id' => $m->id_member], ['status' => \App\Models\ic_gl_antrean::MENANG]);
        }
        // HP uji berada tepat di titik acara.
        return $this->actingAs($m, 'member')->post(route('ic.checkin.proses'), array_filter(['kode' => $kode, 'acara' => $acara]) + ['lat' => -6.2088, 'lng' => 106.8456, 'akurasi' => 20]);
    }

    public function test_admin_tab_tampil_dan_poin_manual_menambah_total(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        $admin = $this->withSession(['login' => true, 'user' => 'admin']);

        $this->checkin($m, $e->token, $e->kode);
        $this->app['auth']->guard('member')->logout();

        foreach (['ic.admin.ringkasan', 'ic.admin.anggota', 'ic.admin.acara', 'ic.admin.acara.harih', 'ic.admin.klaim'] as $r) {
            $admin->get(route($r))->assertOk();
        }
        $admin->get(route('ic.admin.acara.harih', ['acara' => $e->kode]))->assertOk()->assertSee('<svg', false)->assertSee($e->token);
        $admin->get(route('ic.admin.anggota', ['q' => 'IC-0000']))->assertOk()->assertSee('Anggota Uji');

        $admin->post(route('ic.admin.anggota.poin', $m->id_member), ['jumlah' => 3000, 'catatan' => 'Acara privat'])->assertRedirect();
        $this->assertSame(4000, $m->fresh()->poin_total); // 1.000 poin GL (QR) + 3.000 manual
        $this->assertSame('admin', ic_poin::where('sumber', 'manual')->first()->dicatat_oleh);

        $admin->post(route('ic.admin.anggota.poin', $m->id_member), ['jumlah' => 10001, 'catatan' => 'x'])->assertSessionHasErrors('jumlah');
        $admin->post(route('ic.admin.anggota.poin', $m->id_member), ['jumlah' => 1, 'catatan' => ''])->assertSessionHasErrors('catatan');
    }

    public function test_admin_tab_butuh_login_admin(): void
    {
        $this->get(route('ic.admin.acara'))->assertRedirect(route('login'));
        $this->post(route('ic.admin.acara.simpan'), [])->assertRedirect(route('login'));
    }
}