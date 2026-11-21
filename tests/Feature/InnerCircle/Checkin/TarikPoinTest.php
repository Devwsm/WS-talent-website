<?php

namespace Tests\Feature\InnerCircle\Checkin;

use App\Models\ic_event;
use App\Models\ic_penanda;
use App\Models\ic_poin;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Check-in mencurigakan: admin menarik poinnya lewat halaman pemeriksaan (koreksi negatif, jejak tetap ada).
 */
class TarikPoinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now()->setTime(23, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function checkinMencurigakan(): array
    {
        $m = member::create(['nama' => 'Curang Uji', 'email' => 'c@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        $e = app(AcaraService::class)->buat(['tanggal' => now()->toDateString(), 'tipe' => 'club', 'venue' => 'Superhouse', 'kota' => 'Jakarta']);
        $e->forceFill(['lokasi_lat' => -6.2, 'lokasi_lng' => 106.8, 'lokasi_radius' => 150])->save();

        $this->actingAs($m, 'member')->post(route('ic.checkin.mandiri'), ['acara' => $e->kode, 'lat' => -6.2, 'lng' => 106.8, 'akurasi' => 10]);
        $poin = ic_poin::where('sumber', 'checkin')->firstOrFail();
        $p = ic_penanda::create(['member_id' => $m->id_member, 'event_id' => $e->id_event, 'poin_id' => $poin->getKey(), 'jenis' => ic_penanda::PERANGKAT_SAMA, 'detail' => 'uji', 'status' => ic_penanda::BARU]);
        $this->app['auth']->guard('member')->logout();

        return [$m, $e, $p];
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    public function test_tarik_poin_mengurangi_saldo_menandai_ditindak_dan_meninggalkan_jejak(): void
    {
        [$m, , $p] = $this->checkinMencurigakan();
        $this->assertSame(2000, $m->fresh()->poin_total);

        $this->admin()->patch(route('ic.admin.acara.penanda.tarik', $p->id_penanda), ['catatan' => 'Satu HP banyak akun'])
            ->assertSessionHas('success');

        $this->assertSame(0, $m->fresh()->poin_total);
        $this->assertSame('ditindak', $p->fresh()->status);
        $this->assertStringContainsString('Satu HP banyak akun', $p->fresh()->catatan_admin);
        $koreksi = ic_poin::where('sumber', 'koreksi')->firstOrFail();
        $this->assertSame(-2000, $koreksi->jumlah);
        $this->assertNull($koreksi->event_id);
        $this->assertSame(2, ic_poin::count()); // check-in asli tidak dihapus
    }

    public function test_tarik_poin_tidak_bisa_dua_kali_dan_tidak_membuat_saldo_negatif(): void
    {
        [$m, , $p] = $this->checkinMencurigakan();
        // Sebagian poin sudah dipakai: yang ditarik hanya sisa saldo.
        $m->forceFill(['poin_total' => 500])->save();

        $this->admin()->patch(route('ic.admin.acara.penanda.tarik', $p->id_penanda))->assertSessionHas('success');
        $this->assertSame(0, $m->fresh()->poin_total);

        $this->admin()->patch(route('ic.admin.acara.penanda.tarik', $p->id_penanda))->assertSessionHasErrors('pemeriksaan');
        $this->assertSame(1, ic_poin::where('sumber', 'koreksi')->count());
    }

    public function test_tarik_poin_ditolak_bila_saldo_sudah_nol_dan_hanya_untuk_admin(): void
    {
        [$m, , $p] = $this->checkinMencurigakan();

        $this->patch(route('ic.admin.acara.penanda.tarik', $p->id_penanda))->assertRedirect();
        $this->assertSame(2000, $m->fresh()->poin_total);

        $m->forceFill(['poin_total' => 0])->save();
        $this->admin()->patch(route('ic.admin.acara.penanda.tarik', $p->id_penanda))->assertSessionHasErrors('pemeriksaan');
        $this->assertSame('baru', $p->fresh()->status);
    }

    public function test_halaman_pemeriksaan_menampilkan_tombol_tarik_poin(): void
    {
        $this->checkinMencurigakan();

        $this->admin()->get(route('ic.admin.acara.pemeriksaan'))->assertOk()->assertSee('Tarik poin');
    }
}
