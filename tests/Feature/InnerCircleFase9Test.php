<?php

namespace Tests\Feature;

use App\Mail\InnerCircle\AkunGoogleMail;
use App\Mail\InnerCircle\ResetSandiMail;
use App\Models\ic_event;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** Fase 9: token acara yang rusak, sinkron Bandsintown, lupa kata sandi, dan tab Email di dashboard. */
class InnerCircleFase9Test extends TestCase
{
    use RefreshDatabase;

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

    // ---------- Token rusak ("The MAC is invalid") ----------

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

    // ---------- Bandsintown ----------

    private function fakeBit(array $acara): void
    {
        config(['services.bandsintown.app_id' => 'abc', 'services.bandsintown.artist' => 'id_1']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['rest.bandsintown.com/*' => Http::response($acara)]);
    }

    private function itemBit(string $id, string $tgl, string $venue, string $kota = 'Bali', string $judul = ''): array
    {
        return [
            'id' => $id,
            'datetime' => $tgl . 'T22:00:00',
            'title' => $judul,
            'url' => "https://www.bandsintown.com/e/{$id}?app_id=rahasia&came_from=1",
            'venue' => ['name' => $venue, 'city' => $kota]
        ];
    }

    public function test_sinkron_membuat_acara_dan_tidak_dobel(): void
    {
        $this->fakeBit([
            $this->itemBit('11', '2030-06-01', 'Beach Club'),
            $this->itemBit('12', '2030-06-02', 'Rock Open Air', 'Bandung', 'Rock Fest'),
        ]);

        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertRedirect()->assertSessionHas('success');
        $this->assertSame(2, ic_event::count());
        $this->assertSame('festival', ic_event::where('bit_id', '12')->first()->tipe);
        $this->assertSame('club', ic_event::where('bit_id', '11')->first()->tipe);
        $this->assertSame('https://www.bandsintown.com/e/11', ic_event::where('bit_id', '11')->first()->bit_url);

        $token = ic_event::where('bit_id', '11')->first()->token;
        $this->admin()->post(route('ic.admin.acara.bandsintown'));
        $this->assertSame(2, ic_event::count());
        $this->assertSame($token, ic_event::where('bit_id', '11')->first()->token);
    }

    public function test_sinkron_menautkan_acara_manual_tanpa_menimpa(): void
    {
        $manual = $this->acara('2030-06-01', 'Beach Club');
        $this->fakeBit([$this->itemBit('11', '2030-06-01', 'BEACH CLUB!', 'Kota Lain')]);

        $this->admin()->post(route('ic.admin.acara.bandsintown'));

        $this->assertSame(1, ic_event::count());
        $manual = $manual->fresh();
        $this->assertSame('11', $manual->bit_id);
        $this->assertSame('Beach Club', $manual->venue);
        $this->assertSame('Jakarta', $manual->kota);
        $this->assertSame('manual', $manual->sumber);
    }

    public function test_sinkron_memperbarui_hanya_sumber_bandsintown_dan_tidak_menghapus(): void
    {
        $this->fakeBit([$this->itemBit('11', '2030-06-01', 'Beach Club'), $this->itemBit('12', '2030-07-01', 'Lama')]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'));

        $this->fakeBit([$this->itemBit('11', '2030-06-05', 'Beach Club Baru')]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertSessionHas('success');

        $e = ic_event::where('bit_id', '11')->first();
        $this->assertSame('2030-06-05', $e->tanggal->toDateString());
        $this->assertSame('Beach Club Baru', $e->venue);
        $this->assertNotNull(ic_event::where('bit_id', '12')->first(), 'acara yang hilang dari Bandsintown tidak dihapus');

        // Admin mengubah venue: sinkron berikutnya tidak menimpa lagi.
        $this->admin()->put(route('ic.admin.acara.ubah', $e->kode), ['tanggal' => '2030-06-05', 'tipe' => 'club', 'venue' => 'Venue Admin', 'kota' => 'Bali']);
        $this->assertSame('manual', $e->fresh()->sumber);
        $this->fakeBit([$this->itemBit('11', '2030-06-05', 'Beach Club Baru')]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'));
        $this->assertSame('Venue Admin', $e->fresh()->venue);
    }

    public function test_sinkron_gagal_menampilkan_pesan_bukan_galat(): void
    {
        config(['services.bandsintown.app_id' => 'abc', 'services.bandsintown.artist' => 'id_1']);
        Http::fake(['rest.bandsintown.com/*' => Http::response(['errors' => ['x']], 200)]);

        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertSessionHas('error');

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['rest.bandsintown.com/*' => Http::response('', 403)]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertSessionHas('error');
    }

    public function test_sinkron_tanpa_app_id_ditolak(): void
    {
        config(['services.bandsintown.app_id' => null]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertSessionHas('error');
    }

    // ---------- Lupa kata sandi ----------

    private function anggota(array $o = []): member
    {
        $m = new member();
        $m->nama = 'Budi';
        $m->email = $o['email'] ?? 'budi@contoh.com';
        $m->password = array_key_exists('password', $o) ? $o['password'] : 'sandi-lama-123';
        $m->kode_member = 'IC-00001';
        $m->save();
        if (isset($o['google'])) {
            $m->forceFill(['google_id' => 'g-1'])->save();
        }

        return $m;
    }

    public function test_lupa_sandi_mengirim_tautan_dan_jawabannya_sama_untuk_email_tak_dikenal(): void
    {
        Mail::fake();
        $this->anggota();

        $a = $this->post(route('ic.sandi.kirim'), ['email' => 'budi@contoh.com']);
        $b = $this->post(route('ic.sandi.kirim'), ['email' => 'tidak@ada.com']);

        $a->assertRedirect(route('ic.sandi.lupa'));
        $b->assertRedirect(route('ic.sandi.lupa'));
        Mail::assertSent(ResetSandiMail::class, 1);
        Mail::assertSent(ResetSandiMail::class, fn($m) => $m->hasTo('budi@contoh.com'));
    }

    public function test_akun_google_menerima_email_masuk_dengan_google_bukan_tautan(): void
    {
        Mail::fake();
        $this->anggota(['password' => null, 'google' => true]);

        $this->post(route('ic.sandi.kirim'), ['email' => 'budi@contoh.com'])->assertRedirect();

        Mail::assertSent(AkunGoogleMail::class, 1);
        Mail::assertNotSent(ResetSandiMail::class);
    }

    public function test_atur_ulang_mengganti_sandi_memverifikasi_email_dan_mengeluarkan_sesi_lama(): void
    {
        $m = $this->anggota();
        $this->assertNull($m->email_verified_at);
        $token = Password::broker('members')->createToken($m);

        $this->get(route('ic.sandi.atur', ['token' => $token, 'email' => $m->email]))->assertOk()->assertSee('Buat kata sandi baru');

        $this->post(route('ic.sandi.atur.proses'), [
            'token' => $token,
            'email' => $m->email,
            'sandi' => 'sandi-baru-456',
            'sandi_confirmation' => 'sandi-baru-456',
        ])->assertRedirect(route('ic.masuk'));

        $m = $m->fresh();
        $this->assertTrue(password_verify('sandi-baru-456', $m->password));
        $this->assertNotNull($m->email_verified_at);
        $this->assertSame(1, $m->sesi_versi);

        // Token sekali pakai.
        $this->post(route('ic.sandi.atur.proses'), [
            'token' => $token,
            'email' => $m->email,
            'sandi' => 'sandi-lain-789',
            'sandi_confirmation' => 'sandi-lain-789',
        ])->assertSessionHasErrors('email');
    }

    public function test_atur_ulang_token_salah_ditolak(): void
    {
        $m = $this->anggota();

        $this->post(route('ic.sandi.atur.proses'), [
            'token' => 'salah',
            'email' => $m->email,
            'sandi' => 'sandi-baru-456',
            'sandi_confirmation' => 'sandi-baru-456',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(password_verify('sandi-lama-123', $m->fresh()->password));
    }

    public function test_lupa_sandi_dibatasi_per_email(): void
    {
        Mail::fake();
        RateLimiter::clear('x');
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('ic.sandi.kirim'), ['email' => 'a@b.com'])->assertSessionDoesntHaveErrors();
        }
        $this->post(route('ic.sandi.kirim'), ['email' => 'a@b.com'])->assertSessionHasErrors('email');
    }

    // ---------- Tab Email ----------

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

    // ---------- Validasi link CMS ----------

    public function test_link_banner_javascript_ditolak(): void
    {
        $this->admin()->post(route('banner.tambah'), ['banner_name' => 'x', 'link_banner' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('link_banner');
    }
}