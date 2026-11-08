<?php

namespace Tests\Feature\InnerCircle\Checkin;

use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan check-in: token QR, jendela waktu, satu stempel per acara, acara privat/tanpa venue, dan pembatas laju.
 */
class CheckinTest extends TestCase
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

    public function test_halaman_check_in_mengisi_kode_dan_acara_dari_token_qr(): void
    {
        $m = $this->anggota();
        $e = $this->acara();

        $this->actingAs($m, 'member')->get(route('ic.checkin', ['kode' => $e->token]))
            ->assertOk()
            ->assertSee('value="' . $e->token . '"', false)
            ->assertSee('Superhouse Satrio');

        $this->actingAs($m, 'member')->get(route('ic.checkin', ['kode' => 'WS-ZZZZZZZZZZZZ']))
            ->assertOk()->assertSee('tidak cocok dengan acara');
    }

    public function test_check_in_berhasil_menambah_satu_stempel_dan_masuk_riwayat(): void
    {
        $m = $this->anggota();
        $e = $this->acara();

        $this->checkin($m, $e->token, $e->kode)->assertRedirect(route('ic.beranda'));

        $this->assertSame(1, $m->fresh()->stempel_total);
        $baris = ic_stempel::first();
        $this->assertSame('checkin', $baris->sumber);
        $this->assertSame($e->id_event, $baris->event_id);
        $this->actingAs($m->fresh(), 'member')->get(route('ic.profil'))->assertOk()->assertSee('Check-in di Superhouse Satrio');
    }

    public function test_check_in_menerima_url_hasil_pindai_dan_acara_diambil_dari_token(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        $lain = $this->acara(['venue' => 'Blackowl']);

        $this->checkin($m, route('ic.checkin', ['kode' => $e->token]), $lain->kode)->assertRedirect(route('ic.beranda'));
        $this->assertSame($e->id_event, ic_stempel::first()->event_id);
    }

    public function test_check_in_kedua_di_acara_yang_sama_ditolak(): void
    {
        $m = $this->anggota();
        $e = $this->acara();

        $this->checkin($m, $e->token, $e->kode);
        $this->checkin($m, $e->token, $e->kode)->assertSessionHasErrors('kode');

        $this->assertSame(1, ic_stempel::count());
        $this->assertSame(1, $m->fresh()->stempel_total);
    }

    public function test_token_lama_setelah_dibuat_ulang_ditolak(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        $lama = $e->token;

        $this->withSession(['login' => true, 'user' => 'admin'])
            ->post(route('ic.admin.acara.token', $e->kode))->assertRedirect();
        $this->assertNotSame($lama, $e->fresh()->token);

        $this->checkin($m, $lama, $e->kode)->assertSessionHasErrors('kode');
        $this->assertSame(0, ic_stempel::count());
        $this->checkin($m, $e->fresh()->token, $e->kode)->assertRedirect(route('ic.beranda'));
    }

    public function test_acara_privat_dan_tanpa_venue_ditolak(): void
    {
        $m = $this->anggota();
        $privat = $this->acara(['tipe' => 'private']);
        $kosong = $this->acara(['venue' => null]);

        $this->checkin($m, $privat->token, $privat->kode)->assertSessionHasErrors(['kode' => 'Acara privat tidak bisa dipakai untuk check-in.']);
        $this->checkin($m, $kosong->token, $kosong->kode)->assertSessionHasErrors('kode');
        $this->assertSame(0, ic_stempel::count());
    }

    public function test_di_luar_jendela_waktu_ditolak_dan_jendela_malam_diterima(): void
    {
        $m = $this->anggota();
        $e = $this->acara(['tanggal' => '2026-10-10']);

        Carbon::setTestNow('2026-10-10 11:59:00');
        $this->checkin($m, $e->token, $e->kode)->assertSessionHasErrors('kode');

        Carbon::setTestNow('2026-10-11 06:01:00');
        $this->checkin($m, $e->token, $e->kode)->assertSessionHasErrors('kode');
        $this->assertSame(0, ic_stempel::count());

        // Show klub selesai lewat tengah malam: H+1 pukul 05.59 masih boleh.
        Carbon::setTestNow('2026-10-11 05:59:00');
        $this->checkin($m, $e->token, $e->kode)->assertRedirect(route('ic.beranda'));
        $this->assertSame(1, ic_stempel::count());
    }

    public function test_jendela_khusus_per_acara_dihormati(): void
    {
        $m = $this->anggota();
        $e = $this->acara(['tanggal' => '2026-10-10', 'jendela_mulai' => '2026-10-10 18:00:00', 'jendela_selesai' => '2026-10-10 23:00:00']);

        Carbon::setTestNow('2026-10-10 17:00:00');
        $this->checkin($m, $e->token)->assertSessionHasErrors('kode');
        Carbon::setTestNow('2026-10-10 19:00:00');
        $this->checkin($m, $e->token)->assertRedirect(route('ic.beranda'));
    }

    public function test_kode_ngawur_ditolak_dan_check_in_wajib_login(): void
    {
        $m = $this->anggota();
        $this->checkin($m, 'halo')->assertSessionHasErrors('kode');
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->post(route('ic.checkin.proses'), ['kode' => 'WS-ABCDEFGHJKLM'])->assertRedirect(route('ic.masuk'));
    }

    public function test_limiter_check_in_per_anggota(): void
    {
        $a = $this->anggota('a@contoh.test');
        $b = $this->anggota('b@contoh.test');
        for ($i = 0; $i < 8; $i++) {
            $this->checkin($a, 'salah');
        }
        $this->checkin($a, 'salah')->assertSessionHasErrors(['kode' => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.']);
        $this->app['auth']->forgetGuards();
        // Anggota lain dari IP yang sama tidak ikut terblokir.
        $e = $this->acara();
        $this->checkin($b, $e->token)->assertRedirect(route('ic.beranda'));
    }
}
