<?php

namespace Tests\Feature\InnerCircle\Gl;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\log_aktivitas;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\GlService;
use App\Services\InnerCircle\QrService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Kartu GL untuk vendor: pratinjau + PDF (dibuat di browser) berisi kartu acara dan daftar pemenang.
 * Berbeda dari CSV/WhatsApp, halaman ini memuat username dan sandi acara, jadi hanya terbuka setelah undian
 * selesai, hanya untuk admin, dan setiap pembukaan dicatat di Log.
 */
class KartuGlVendorTest extends TestCase
{
    use RefreshDatabase;

    private int $nomor = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now()->setTime(12, 0));
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    private function anggota(string $nama): member
    {
        $this->nomor++;
        $m = member::create(['nama' => $nama, 'email' => "v{$this->nomor}@contoh.test", 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m->fresh();
    }

    private function acara(array $o = []): ic_event
    {
        $e = app(AcaraService::class)->buat(['tanggal' => now()->toDateString(), 'tipe' => 'club', 'venue' => 'Superhouse Satrio', 'kota' => 'Jakarta']);
        $e->forceFill($o + ['kuota_gl' => 2, 'username_acara' => 'wsgl rahasia', 'sandi_acara' => 'sandi-rahasia-9'])->save();

        return $e->fresh();
    }

    /** Antri 3 anggota lalu undi (kuota 2): hasilnya 2 pemenang dan 1 tidak terpilih. */
    private function undi(ic_event $e): void
    {
        foreach (['Ancha Mahendra Kusuma', 'Rani Putri', 'Dio Santoso'] as $nama) {
            app(GlService::class)->antri($this->anggota($nama), $e);
        }
        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'acara dimajukan'])->assertSessionHas('success');
    }

    public function test_sebelum_undian_selesai_dialihkan_ke_panel_dan_sandi_tidak_tampil(): void
    {
        $e = $this->acara();

        $this->admin()->get(route('ic.admin.acara.gl.vendor', $e->kode))
            ->assertRedirect(route('ic.admin.acara.gl', $e->kode) . '#undian')
            ->assertSessionHasErrors('gl');

        $this->assertSame(0, log_aktivitas::query()->where('aksi', 'Kartu GL vendor dibuka')->count());
    }

    public function test_tamu_tanpa_login_admin_dialihkan(): void
    {
        $e = $this->acara();

        $this->get(route('ic.admin.acara.gl.vendor', $e->kode))->assertRedirect();
    }

    public function test_pratinjau_memuat_kartu_acara_dan_daftar_pemenang_tanpa_email(): void
    {
        $e = $this->acara();
        $this->undi($e);
        $pemenang = ic_gl_antrean::query()->where('event_id', $e->id_event)->where('status', ic_gl_antrean::MENANG)->with('anggota')->get();
        $kalah = ic_gl_antrean::query()->where('event_id', $e->id_event)->where('status', ic_gl_antrean::TIDAK_TERPILIH)->with('anggota')->first();
        $this->assertCount(2, $pemenang);

        $html = $this->admin()->get(route('ic.admin.acara.gl.vendor', $e->kode))->assertOk()
            ->assertSee('Superhouse Satrio, Jakarta')
            ->assertSee('wsgl rahasia')
            ->assertSee('sandi-rahasia-9')
            ->assertSee('id="bagikan-pdf"', false)
            ->assertSee('id="unduh-pdf"', false)
            ->assertSee('id="data-kartu-gl"', false)
            ->getContent();

        foreach ($pemenang as $b) {
            $this->assertStringContainsString($b->anggota->nama, $html);
            $this->assertStringContainsString($b->anggota->kode_member, $html);
            $this->assertStringNotContainsString($b->anggota->email, $html, 'email tidak boleh ikut');
        }
        $this->assertStringNotContainsString($kalah->anggota->nama, $html, 'yang tidak terpilih tidak masuk daftar');
        $this->assertStringNotContainsString($kalah->anggota->email, $html);
    }

    public function test_data_pdf_berisi_matriks_qr_dan_pemenang_berurutan(): void
    {
        $e = $this->acara();
        $this->undi($e);

        $html = $this->admin()->get(route('ic.admin.acara.gl.vendor', $e->kode))->assertOk()->getContent();
        preg_match('/<script type="application\/json" id="data-kartu-gl">(.*?)<\/script>/s', $html, $cocok);
        $data = json_decode($cocok[1], true);

        $this->assertSame('sandi-rahasia-9', $data['sandi']);
        $this->assertSame('wsgl rahasia', $data['username']);
        $this->assertSame(2, $data['kuota']);
        $this->assertCount(2, $data['pemenang']);
        $this->assertSame([1, 2], array_column($data['pemenang'], 'no'));
        $this->assertStringEndsWith('.pdf', $data['berkas']);
        $this->assertGreaterThanOrEqual(21, count($data['qr']));
        $this->assertSame(count($data['qr']), strlen($data['qr'][0]), 'matriks QR persegi');
    }

    public function test_pembukaan_tercatat_di_log_tanpa_memuat_sandi(): void
    {
        $e = $this->acara();
        $this->undi($e);

        $this->admin()->get(route('ic.admin.acara.gl.vendor', $e->kode))->assertOk();
        $this->admin()->get(route('ic.admin.acara.gl.vendor', $e->kode))->assertOk();

        // Pembukaan kembar dalam waktu berdekatan tidak dicatat ulang oleh pencatat Log (anti-spam): satu baris.
        $log = log_aktivitas::query()->where('aksi', 'Kartu GL vendor dibuka')->get();
        $this->assertCount(1, $log);
        $this->assertStringNotContainsString('sandi-rahasia-9', (string) $log->first()->keterangan);
        $this->assertSame(0, log_aktivitas::query()->where('aksi', 'Perubahan di CMS')->count(), 'tidak dicatat ganda');
    }

    public function test_panel_gl_punya_tombol_ke_kartu_vendor_dan_tetap_tanpa_sandi(): void
    {
        $e = $this->acara();
        $this->undi($e);

        $this->admin()->get(route('ic.admin.acara.gl', $e->kode))->assertOk()
            ->assertSee(route('ic.admin.acara.gl.vendor', $e->kode), false)
            ->assertDontSee('sandi-rahasia-9')
            ->assertDontSee('wsgl rahasia');
    }

    public function test_matriks_qr_service_hanya_berisi_nol_dan_satu(): void
    {
        $e = $this->acara();
        $matriks = app(QrService::class)->matriks($e);

        $this->assertGreaterThanOrEqual(21, count($matriks));
        foreach ($matriks as $baris) {
            $this->assertMatchesRegularExpression('/^[01]+$/', $baris);
            $this->assertSame(count($matriks), strlen($baris));
        }
    }
}