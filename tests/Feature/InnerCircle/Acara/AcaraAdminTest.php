<?php

namespace Tests\Feature\InnerCircle\Acara;

use App\Models\ic_event;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CMS Acara: CRUD, unduh/cetak QR, impor CSV, dan penghapusan acara yang sudah punya check-in.
 */
class AcaraAdminTest extends TestCase
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
