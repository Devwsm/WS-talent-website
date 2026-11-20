<?php

namespace Tests\Feature\InnerCircle\Gl;

use App\Mail\InnerCircle\KartuGlMail;
use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\log_aktivitas;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\GlService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Fase 6B, panel undian GL di CMS: halaman, undi sekarang (wajib alasan), tunda/lanjutkan, tanda diterima tim Show,
 * ekspor CSV dan daftar WhatsApp tanpa username/sandi, serta pencatatan Log tanpa ganda.
 */
class PanelUndianCmsTest extends TestCase
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

    private function anggota(?string $nama = null): member
    {
        $this->nomor++;
        $m = member::create(['nama' => $nama ?? 'Anggota ' . $this->nomor, 'email' => "g{$this->nomor}@contoh.test", 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function acara(array $o = []): ic_event
    {
        $e = app(AcaraService::class)->buat(['tanggal' => now()->toDateString(), 'tipe' => 'club', 'venue' => 'Superhouse Satrio', 'kota' => 'Jakarta']);
        $e->forceFill($o + ['kuota_gl' => 2, 'username_acara' => 'wsgl-rahasia', 'sandi_acara' => 'sandi-rahasia-9'])->save();

        return $e->fresh();
    }

    private function antri(ic_event $e, int $jumlah): array
    {
        $daftar = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $daftar[] = $m = $this->anggota();
            app(GlService::class)->antri($m, $e);
        }

        return $daftar;
    }

    // ---------- akses dan tampilan ----------

    public function test_panel_butuh_login_admin(): void
    {
        $e = $this->acara();

        $this->get(route('ic.admin.acara.gl', $e->kode))->assertRedirect();
        $this->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'tanpa login'])->assertRedirect();
        $this->assertSame('belum', $e->fresh()->status_undian);
    }

    public function test_panel_menampilkan_antrean_dan_tombol_undian(): void
    {
        $e = $this->acara();
        $this->antri($e, 3);

        $this->admin()->get(route('ic.admin.acara.gl', $e->kode))
            ->assertOk()
            ->assertSee('Undi sekarang')
            ->assertSee('Tunda undian')
            ->assertSee('Belum diundi')
            ->assertSee('Anggota 1')
            ->assertDontSee('wsgl-rahasia')
            ->assertDontSee('sandi-rahasia-9');
    }

    public function test_panel_acara_tanpa_gl_tidak_menawarkan_undi(): void
    {
        $e = $this->acara(['kuota_gl' => null]);

        $this->admin()->get(route('ic.admin.acara.gl', $e->kode))
            ->assertOk()
            ->assertDontSee('Undi sekarang')
            ->assertSee('kuota GL');
    }

    public function test_detail_hari_h_menaut_ke_panel(): void
    {
        $e = $this->acara();

        $this->admin()->get(route('ic.admin.acara.harih.detail', $e->kode))
            ->assertOk()
            ->assertSee(route('ic.admin.acara.gl', $e->kode), false);
    }

    // ---------- undi sekarang ----------

    public function test_undi_sekarang_wajib_alasan(): void
    {
        $e = $this->acara();
        $this->antri($e, 2);

        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => ''])->assertSessionHasErrors('alasan');
        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'abc'])->assertSessionHasErrors('alasan');

        $this->assertSame('belum', $e->fresh()->status_undian);
    }

    public function test_undi_sekarang_mengundi_mengirim_email_dan_mencatat_log_sekali(): void
    {
        $e = $this->acara(['kuota_gl' => 2]);
        $this->antri($e, 4);

        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'acara dimajukan'])
            ->assertRedirect(route('ic.admin.acara.gl', $e->kode) . '#undian')
            ->assertSessionHas('success');

        $this->assertSame('selesai', $e->fresh()->status_undian);
        $this->assertSame(2, ic_gl_antrean::query()->where('status', 'menang')->count());
        Mail::assertSent(KartuGlMail::class, 2);

        $log = log_aktivitas::query()->where('aksi', 'Undi GL sekarang')->get();
        $this->assertCount(1, $log);
        $this->assertSame('admin-uji', $log->first()->pelaku);
        $this->assertStringContainsString('acara dimajukan', $log->first()->keterangan);
        $this->assertSame(0, log_aktivitas::query()->where('aksi', 'Perubahan di CMS')->count(), 'tidak dicatat ganda oleh pencatat otomatis');
    }

    public function test_undi_ulang_setelah_selesai_ditolak(): void
    {
        $e = $this->acara();
        $this->antri($e, 2);
        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'undian pertama']);
        $menang = ic_gl_antrean::query()->where('status', 'menang')->pluck('member_id')->all();

        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'undian kedua'])->assertSessionHasErrors('gl');

        $this->assertSame($menang, ic_gl_antrean::query()->where('status', 'menang')->pluck('member_id')->all());
    }

    public function test_undi_dengan_data_belum_lengkap_ditolak(): void
    {
        $e = $this->acara(['sandi_acara' => null]);
        $this->antri($e, 2);

        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'coba undi'])->assertSessionHasErrors('gl');

        $this->assertSame('belum', $e->fresh()->status_undian);
    }

    // ---------- tunda dan lanjutkan ----------

    public function test_tunda_lalu_lanjutkan(): void
    {
        $e = $this->acara();

        $this->admin()->post(route('ic.admin.acara.gl.tunda', $e->kode))->assertSessionHas('success');
        $this->assertSame('ditunda', $e->fresh()->status_undian);
        $this->admin()->get(route('ic.admin.acara.gl', $e->kode))->assertSee('Lanjutkan sesuai jadwal')->assertSee('Ditunda admin');

        $this->admin()->post(route('ic.admin.acara.gl.lanjutkan', $e->kode))->assertSessionHas('success');
        $this->assertSame('belum', $e->fresh()->status_undian);

        $this->assertSame(1, log_aktivitas::query()->where('aksi', 'Tunda undian GL')->count());
        $this->assertSame(1, log_aktivitas::query()->where('aksi', 'Lanjutkan undian GL')->count());
        $this->assertSame(0, log_aktivitas::query()->where('aksi', 'Perubahan di CMS')->count());
    }

    public function test_tunda_setelah_selesai_ditolak(): void
    {
        $e = $this->acara();
        $this->antri($e, 1);
        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'undi dulu']);

        $this->admin()->post(route('ic.admin.acara.gl.tunda', $e->kode))->assertSessionHasErrors('gl');
        $this->assertSame('selesai', $e->fresh()->status_undian);
    }

    // ---------- hasil: pemenang, WhatsApp, diterima, CSV ----------

    public function test_setelah_selesai_tampil_pemenang_dan_daftar_whatsapp_tanpa_sandi(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $this->antri($e, 3);
        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'undian uji']);

        $pemenang = ic_gl_antrean::query()->with('anggota')->where('status', 'menang')->first()->anggota;

        $res = $this->admin()->get(route('ic.admin.acara.gl', $e->kode))->assertOk();
        $res->assertSee('Salin untuk WhatsApp')
            ->assertSee('GL gratis: Superhouse Satrio, Jakarta')
            ->assertSee($pemenang->kode_member)
            ->assertDontSee('wsgl-rahasia')
            ->assertDontSee('sandi-rahasia-9')
            ->assertDontSee('Undi sekarang');
    }

    public function test_tandai_diterima_tim_show(): void
    {
        $e = $this->acara(['kuota_gl' => 2]);
        $this->antri($e, 3);

        $this->admin()->post(route('ic.admin.acara.gl.diterima', $e->kode))->assertSessionHasErrors('gl'); // belum selesai

        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'undian uji']);
        $this->admin()->post(route('ic.admin.acara.gl.diterima', $e->kode))->assertSessionHas('success');

        $this->assertSame(2, ic_gl_antrean::query()->where('status', 'menang')->whereNotNull('diterima_show_pada')->count());
        $this->assertSame(0, ic_gl_antrean::query()->where('status', '!=', 'menang')->whereNotNull('diterima_show_pada')->count());
        $this->assertSame(1, log_aktivitas::query()->where('aksi', 'Daftar GL diterima tim Show')->count());

        // Mengulang tidak mencatat lagi.
        $this->admin()->post(route('ic.admin.acara.gl.diterima', $e->kode))->assertSessionHas('success');
        $this->assertSame(1, log_aktivitas::query()->where('aksi', 'Daftar GL diterima tim Show')->count());
    }

    public function test_ekspor_csv_pemenang_dan_antrean_tanpa_sandi(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $this->antri($e, 3);
        $this->admin()->post(route('ic.admin.acara.gl.undi', $e->kode), ['alasan' => 'undian uji']);

        $pemenang = $this->admin()->get(route('ic.admin.acara.gl.ekspor', ['kode' => $e->kode, 'jenis' => 'pemenang']))->assertOk();
        $isi = $pemenang->streamedContent();
        $this->assertSame(2, substr_count(trim($isi), "\n") + 1, 'judul + 1 pemenang');
        $this->assertStringContainsString('Menang', $isi);
        $this->assertStringNotContainsString('wsgl-rahasia', $isi);
        $this->assertStringNotContainsString('sandi-rahasia-9', $isi);

        $semua = $this->admin()->get(route('ic.admin.acara.gl.ekspor', ['kode' => $e->kode, 'jenis' => 'antrean']))->streamedContent();
        $this->assertSame(4, substr_count(trim($semua), "\n") + 1, 'judul + 3 pendaftar');
        $this->assertStringContainsString('Tidak terpilih', $semua);

        $this->assertSame(2, log_aktivitas::query()->where('kategori', 'inner-circle')->where('aksi', 'Daftar GL diunduh')->count());
    }

    public function test_acara_privat_tidak_punya_panel(): void
    {
        $e = $this->acara(['tipe' => 'private']);

        $this->admin()->get(route('ic.admin.acara.gl', $e->kode))->assertNotFound();
    }
}