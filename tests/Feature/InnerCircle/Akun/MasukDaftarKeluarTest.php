<?php

namespace Tests\Feature\InnerCircle\Akun;

use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gerbang flag, masuk/daftar/keluar anggota, kembali ke QR setelah masuk, dan sesi admin yang tidak terganggu.
 */
class MasukDaftarKeluarTest extends TestCase
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

    public function test_flag_mati_membuat_route_404(): void
    {
        config(['inner_circle.aktif' => false]);
        $this->get('/innercircle/masuk')->assertNotFound();
    }

    public function test_tamu_diarahkan_ke_masuk_inner_circle(): void
    {
        // Beranda anggota: tamu dialihkan ke halaman penjelasan program; area anggota lain langsung ke Masuk.
        $this->get('/innercircle')->assertRedirect(route('ic.tentang'));
        $this->get('/innercircle/check-in?kode=WS-ABCDEFGHJKLM')->assertRedirect(route('ic.masuk'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_alamat_lama_inner_circle_dialihkan_permanen_ke_innercircle(): void
    {
        // QR check-in yang sudah dicetak dan tautan email lama memakai /inner-circle; query string ikut terbawa.
        $this->get('/inner-circle')->assertStatus(301)->assertRedirect('/innercircle');
        $this->get('/inner-circle/tentang')->assertStatus(301)->assertRedirect('/innercircle/tentang');
        $this->get('/inner-circle/check-in?kode=WS-ABCDEFGHJKLM')
            ->assertStatus(301)
            ->assertRedirect('/innercircle/check-in?kode=WS-ABCDEFGHJKLM');
    }

    public function test_email_belum_terverifikasi_bisa_masuk_tapi_tidak_bisa_beraktivitas(): void
    {
        $e = $this->acara();
        $m = member::create(['nama' => 'Belum Verifikasi', 'email' => 'belum@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->save();

        // Boleh membuka halaman dan melihat-lihat; panel verifikasi tampil.
        $this->actingAs($m, 'member')->get(route('ic.beranda'))->assertOk()->assertSee('Verifikasi emailmu dulu');
        $this->actingAs($m, 'member')->get(route('ic.checkin'))->assertOk()->assertSee('Verifikasi emailmu dulu');
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertOk()->assertSee('Verifikasi emailmu dulu');

        // Check-in, unggah kenangan, dan suka ditolak di server.
        $this->checkin($m, $e->token, $e->kode)
            ->assertRedirect(route('ic.beranda'))
            ->assertSessionHas('ic_toast_galat');
        $this->actingAs($m, 'member')->post(route('ic.kenangan.simpan'), ['caption' => 'Seru banget', 'kategori' => 'festival'])
            ->assertRedirect(route('ic.beranda'))
            ->assertSessionHas('ic_toast_galat');
        $this->actingAs($m, 'member')->postJson(route('ic.kenangan.suka', 1))->assertForbidden();

        $this->assertSame(0, ic_stempel::count());
        $this->assertSame(0, \App\Models\ic_memory::count());

        // Setelah terverifikasi, check-in yang sama berhasil.
        $m->forceFill(['email_verified_at' => now()])->save();
        $this->checkin($m->fresh(), $e->token, $e->kode)->assertRedirect(route('ic.beranda'))->assertSessionMissing('ic_toast_galat');
        $this->assertSame(1, ic_stempel::count());
    }

    public function test_daftar_memberi_kode_member_dan_stempel_nol(): void
    {
        $this->post(route('ic.daftar.proses'), [
            'nama' => 'Rani',
            'email' => 'Rani@Contoh.Test',
            'sandi' => 'Rahasia123',
            'sandi_confirmation' => 'Rahasia123',
        ])->assertRedirect(route('ic.beranda'));

        $m = member::first();
        $this->assertSame('IC-00001', $m->kode_member);
        $this->assertSame('rani@contoh.test', $m->email);
        $this->assertSame(0, $m->stempel_total);
        $this->assertNotSame('Rahasia123', $m->password);
        $this->assertAuthenticatedAs($m, 'member');
        $this->get(route('ic.beranda'))->assertOk()->assertSee('IC-00001');
    }

    public function test_daftar_menolak_email_kembar_dan_sandi_pendek(): void
    {
        $this->anggota('a@contoh.test');
        $this->from(route('ic.daftar'))->post(route('ic.daftar.proses'), [
            'nama' => 'X',
            'email' => 'A@contoh.test',
            'sandi' => 'rahasia123',
            'sandi_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');
        $this->post(route('ic.daftar.proses'), [
            'nama' => 'X',
            'email' => 'b@contoh.test',
            'sandi' => 'pendek',
            'sandi_confirmation' => 'pendek',
        ])->assertSessionHasErrors('sandi');
        $this->assertSame(1, member::count());
    }

    public function test_masuk_dan_keluar_tidak_menghapus_sesi_admin(): void
    {
        $this->anggota();
        $this->withSession(['login' => true, 'user' => 'admin']);

        $this->post(route('ic.masuk.proses'), ['email' => 'a@contoh.test', 'sandi' => 'salah'])->assertSessionHasErrors('email');
        $this->assertGuest('member');

        $this->post(route('ic.masuk.proses'), ['email' => 'a@contoh.test', 'sandi' => 'rahasia123'])->assertRedirect(route('ic.beranda'));
        $this->assertAuthenticated('member');

        $this->post(route('ic.keluar'))->assertRedirect(route('ic.masuk'));
        $this->assertGuest('member');
        $this->assertTrue(session('login'));
    }

    public function test_qr_membawa_anggota_kembali_ke_check_in_setelah_masuk(): void
    {
        $this->anggota();
        $e = $this->acara();
        $url = route('ic.checkin', ['kode' => $e->token]);

        $this->get($url)->assertRedirect(route('ic.masuk'));
        $this->post(route('ic.masuk.proses'), ['email' => 'a@contoh.test', 'sandi' => 'rahasia123'])->assertRedirect($url);
    }

    public function test_layar_anggota_lain_tampil_dengan_anggota_sungguhan(): void
    {
        $m = $this->anggota();
        $this->acara();
        foreach (['ic.beranda', 'ic.hadiah', 'ic.checkin', 'ic.komunitas', 'ic.profil'] as $r) {
            $this->actingAs($m, 'member')->get(route($r))->assertOk();
        }
        $this->actingAs($m, 'member')->get(route('ic.checkin', ['bulan' => 10, 'tipe' => 'club', 'q' => 'super']))->assertOk();
    }
}
