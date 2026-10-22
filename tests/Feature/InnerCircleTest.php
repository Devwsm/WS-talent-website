<?php

namespace Tests\Feature;

use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InnerCircleTest extends TestCase
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
        $this->get('/inner-circle/masuk')->assertNotFound();
    }

    public function test_tamu_diarahkan_ke_masuk_inner_circle(): void
    {
        $this->get('/inner-circle')->assertRedirect(route('ic.masuk'));
        $this->get('/inner-circle/check-in?kode=WS-ABCDEFGHJKLM')->assertRedirect(route('ic.masuk'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_daftar_memberi_kode_member_dan_stempel_nol(): void
    {
        $this->post(route('ic.daftar.proses'), [
            'nama' => 'Rani',
            'email' => 'Rani@Contoh.Test',
            'sandi' => 'rahasia123',
            'sandi_confirmation' => 'rahasia123',
        ])->assertRedirect(route('ic.beranda'));

        $m = member::first();
        $this->assertSame('IC-00001', $m->kode_member);
        $this->assertSame('rani@contoh.test', $m->email);
        $this->assertSame(0, $m->stempel_total);
        $this->assertNotSame('rahasia123', $m->password);
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

    public function test_layar_anggota_lain_tampil_dengan_anggota_sungguhan(): void
    {
        $m = $this->anggota();
        $this->acara();
        foreach (['ic.beranda', 'ic.hadiah', 'ic.checkin', 'ic.komunitas', 'ic.profil'] as $r) {
            $this->actingAs($m, 'member')->get(route($r))->assertOk();
        }
        $this->actingAs($m, 'member')->get(route('ic.checkin', ['bulan' => 10, 'tipe' => 'club', 'q' => 'super']))->assertOk();
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

    public function test_admin_crud_acara_unduh_dan_cetak_qr(): void
    {
        $admin = $this->withSession(['login' => true, 'user' => 'admin']);

        $admin->post(route('ic.admin.acara.simpan'), ['tanggal' => '2026-11-14', 'tipe' => 'festival', 'venue' => 'Lapangan A', 'kota' => 'Bandung'])->assertSessionHasNoErrors();
        $e = ic_event::first();
        $this->assertSame('WS26-1114', $e->kode);
        $this->assertMatchesRegularExpression('/^WS-[A-Z0-9]{12}$/', $e->token);

        $admin->post(route('ic.admin.acara.simpan'), ['tanggal' => '2026-11-14', 'tipe' => 'club'])->assertSessionHasNoErrors();
        $this->assertSame('WS26-1114-B', ic_event::orderBy('id_event', 'desc')->first()->kode);

        $admin->post(route('ic.admin.acara.simpan'), ['tanggal' => 'bukan', 'tipe' => 'x'])->assertSessionHasErrors(['tanggal', 'tipe']);
        $admin->post(route('ic.admin.acara.simpan'), ['tanggal' => '2026-11-15', 'tipe' => 'club', 'jendela_mulai' => '2026-11-15 10:00'])->assertSessionHasErrors('jendela_selesai');

        $token = $e->token;
        $admin->put(route('ic.admin.acara.ubah', $e->kode), ['tanggal' => '2026-11-15', 'tipe' => 'club', 'venue' => 'Lapangan B', 'kota' => 'Bandung'])->assertSessionHasNoErrors();
        $e->refresh();
        $this->assertSame('Lapangan B', $e->venue);
        $this->assertSame($token, $e->token);

        $unduh = $admin->get(route('ic.admin.acara.qr', $e->kode));
        $unduh->assertOk();
        $this->assertStringContainsString('image/svg+xml', $unduh->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $unduh->getContent());
        $admin->get(route('ic.admin.acara.cetak', $e->kode))->assertOk()->assertSee($token);

        $tanpaVenue = ic_event::where('venue', null)->first();
        $admin->get(route('ic.admin.acara.qr', $tanpaVenue->kode))->assertNotFound();

        $admin->delete(route('ic.admin.acara.hapus', $e->kode))->assertSessionHas('success');
        $this->assertNull(ic_event::find($e->id_event));
    }

    public function test_acara_yang_sudah_punya_check_in_tidak_bisa_dihapus(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        $this->checkin($m, $e->token, $e->kode);

        $this->withSession(['login' => true, 'user' => 'admin'])
            ->delete(route('ic.admin.acara.hapus', $e->kode))->assertSessionHas('error');
        $this->assertNotNull(ic_event::find($e->id_event));
    }

    public function test_impor_csv_semua_atau_tidak_sama_sekali(): void
    {
        $admin = $this->withSession(['login' => true, 'user' => 'admin']);

        $bagus = \Illuminate\Http\UploadedFile::fake()->createWithContent('j.csv', "kode,tanggal,kota,venue,tipe\nWS26-1001,2026-10-01,Lombok,Depo,festival\n,2026-10-02,Medan,Amavi,club\nWS26-1023,2026-10-23,,,private\n");
        $admin->post(route('ic.admin.acara.impor'), ['csv' => $bagus])->assertSessionHas('success');
        $this->assertSame(3, ic_event::count());
        $this->assertNull(ic_event::where('kode', 'WS26-1023')->first()->venue);

        $tokenAwal = ic_event::where('kode', 'WS26-1001')->first()->token;
        $ulang = \Illuminate\Http\UploadedFile::fake()->createWithContent('j.csv', "kode,tanggal,kota,venue,tipe\nWS26-1001,2026-10-01,Lombok,Depo Baru,festival\n");
        $admin->post(route('ic.admin.acara.impor'), ['csv' => $ulang]);
        $this->assertSame('Depo Baru', ic_event::where('kode', 'WS26-1001')->first()->venue);
        $this->assertSame($tokenAwal, ic_event::where('kode', 'WS26-1001')->first()->token);

        $rusak = \Illuminate\Http\UploadedFile::fake()->createWithContent('j.csv', "kode,tanggal,kota,venue,tipe\nA1,2026-12-01,X,Y,club\nA2,31-12-2026,X,Y,pesta\n");
        $admin->post(route('ic.admin.acara.impor'), ['csv' => $rusak])->assertSessionHasErrors('csv');
        $this->assertSame(3, ic_event::count());
        $this->assertNull(ic_event::where('kode', 'A1')->first());

        $tanpaKolom = \Illuminate\Http\UploadedFile::fake()->createWithContent('j.csv', "nama,hari\nx,y\n");
        $admin->post(route('ic.admin.acara.impor'), ['csv' => $tanpaKolom])->assertSessionHasErrors('csv');
    }

    public function test_seeder_contoh_hanya_jalan_bila_flag_menyala(): void
    {
        config(['inner_circle.seed_contoh' => false]);
        $this->seed(\Database\Seeders\icEventContohSeeder::class);
        $this->assertSame(0, ic_event::count());

        config(['inner_circle.seed_contoh' => true]);
        $this->seed(\Database\Seeders\icEventContohSeeder::class);
        $this->assertSame(50, ic_event::count());
        $this->assertSame(0, ic_event::where('tipe', 'private')->whereNotNull('venue')->count() - ic_event::where('tipe', 'private')->whereNotNull('venue')->count());
    }
}
