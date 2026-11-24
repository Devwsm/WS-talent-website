<?php

namespace Tests\Feature\InnerCircle\Acara;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\UndianService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Sembunyikan acara dari daftar anggota (otomatis menurut bulan, atau manual dari CMS), tanda info
 * "Check-in dibuka" dan "Undian GL gratis", serta pintasan Kartu GL dari Riwayat aktivitas.
 */
class TampilAnggotaTest extends TestCase
{
    use RefreshDatabase;

    private int $nomor = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // Pertengahan November, malam hari: jendela check-in bawaan acara hari ini (22.00 s.d. 04.00) sedang terbuka.
        Carbon::setTestNow('2026-11-24 23:00:00');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function anggota(): member
    {
        $this->nomor++;
        $m = member::create(['nama' => 'Anggota ' . $this->nomor, 'email' => "t{$this->nomor}@contoh.test", 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function acara(string $tanggal, string $venue, array $o = []): ic_event
    {
        $e = app(AcaraService::class)->buat(['tanggal' => $tanggal, 'tipe' => 'club', 'venue' => $venue, 'kota' => 'Jakarta']);
        $e->forceFill($o)->save();

        return $e->fresh();
    }

    /** Spasi dan baris baru dirapatkan supaya tes tidak bergantung pada cara editor membungkus baris Blade. */
    private function rapat(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', $html));
    }

    private function daftarAnggota(): string
    {
        return $this->actingAs($this->anggota(), 'member')->get(route('ic.checkin'))->assertOk()->getContent();
    }

    public function test_acara_bulan_lalu_tersembunyi_otomatis_dan_bulan_ini_tetap_tampil(): void
    {
        $this->acara('2026-10-31', 'Klub Oktober');
        $this->acara('2026-11-02', 'Klub Awal November'); // sudah lewat tapi masih bulan ini
        $this->acara('2026-12-20', 'Klub Desember');

        $html = $this->daftarAnggota();

        $this->assertStringNotContainsString('Klub Oktober', $html);
        $this->assertStringNotContainsString('Oktober 2026', $html, 'bulan yang seluruhnya tersembunyi tidak jadi pilihan filter');
        $this->assertStringContainsString('Klub Awal November', $html);
        $this->assertStringContainsString('Klub Desember', $html);
    }

    public function test_admin_menyembunyikan_lalu_menampilkan_lagi_acara_mendatang(): void
    {
        $e = $this->acara('2026-12-20', 'Klub Desember');

        $this->admin()->patch(route('ic.admin.acara.tampil', $e->kode))->assertSessionHas('success');
        $this->assertFalse($e->fresh()->tampil_anggota);
        $this->assertSame('sembunyi_manual', $e->fresh()->status_tampil);
        $this->assertStringNotContainsString('Klub Desember', $this->daftarAnggota());

        // Kembali ke aturan otomatis (acara mendatang tampil), bukan dipaksa tampil.
        $this->admin()->patch(route('ic.admin.acara.tampil', $e->kode))->assertSessionHas('success');
        $this->assertNull($e->fresh()->tampil_anggota);
        $this->assertStringContainsString('Klub Desember', $this->daftarAnggota());
    }

    public function test_acara_lama_bisa_dipaksa_tampil_dan_dikembalikan_ke_otomatis(): void
    {
        $e = $this->acara('2026-10-31', 'Klub Oktober');

        $this->admin()->patch(route('ic.admin.acara.tampil', $e->kode))->assertSessionHas('success');
        $this->assertTrue($e->fresh()->tampil_anggota);
        $this->assertStringContainsString('Klub Oktober', $this->daftarAnggota());

        $this->admin()->patch(route('ic.admin.acara.tampil', $e->kode));
        $this->assertNull($e->fresh()->tampil_anggota);
        $this->assertStringNotContainsString('Klub Oktober', $this->daftarAnggota());
    }

    public function test_sembunyikan_hanya_untuk_admin_dan_kode_asing_404(): void
    {
        $e = $this->acara('2026-12-20', 'Klub Desember');

        $this->patch(route('ic.admin.acara.tampil', $e->kode))->assertRedirect();
        $this->assertNull($e->fresh()->tampil_anggota);

        $this->admin()->patch(route('ic.admin.acara.tampil', 'TIDAK-ADA'))->assertNotFound();
    }

    public function test_acara_tersembunyi_tetap_bisa_dibuka_lewat_tautan_langsung(): void
    {
        $e = $this->acara('2026-10-31', 'Klub Oktober');

        $this->actingAs($this->anggota(), 'member')->get(route('ic.acara.detail', $e->kode))->assertOk();
    }

    public function test_tanda_check_in_dibuka_dan_undian_gl_tampil_di_daftar_anggota(): void
    {
        $this->acara('2026-11-24', 'Klub Malam Ini', ['kuota_gl' => 5]);
        $this->acara('2026-12-20', 'Klub Desember');

        $html = $this->rapat($this->daftarAnggota());

        $this->assertStringContainsString('Check-in dibuka', $html);
        $this->assertStringContainsString('GL gratis', $html);
        // Hanya satu acara yang jendelanya terbuka dan hanya satu yang punya GL.
        $this->assertSame(1, substr_count($html, 'ic-tanda--buka'));
        $this->assertSame(1, substr_count($html, 'ic-tanda--gl'));
    }

    public function test_tanda_dan_status_tampil_muncul_di_cms_pengaturan_dan_hari_h(): void
    {
        $this->acara('2026-11-24', 'Klub Malam Ini', ['kuota_gl' => 5]);
        $this->acara('2026-10-31', 'Klub Oktober');

        $pengaturan = $this->rapat($this->admin()->get(route('ic.admin.acara'))->assertOk()->getContent());
        $this->assertStringContainsString('Check-in dibuka', $pengaturan);
        $this->assertStringContainsString('Undian GL gratis', $pengaturan);
        $this->assertStringContainsString('Tersembunyi otomatis', $pengaturan);

        $harih = $this->rapat($this->admin()->get(route('ic.admin.acara.harih'))->assertOk()->getContent());
        $this->assertStringContainsString('Check-in dibuka', $harih);
        $this->assertStringContainsString('Undian GL gratis', $harih);
    }

    public function test_tanpa_gl_dan_check_in_belum_dibuka_tidak_ada_tanda(): void
    {
        Carbon::setTestNow('2026-11-24 12:00:00'); // siang: jendela check-in belum dibuka
        $this->acara('2026-11-24', 'Klub Biasa');

        $html = $this->daftarAnggota();

        $this->assertStringNotContainsString('ic-tanda--buka', $html);
        $this->assertStringNotContainsString('ic-tanda--gl', $html);
    }

    public function test_riwayat_gl_pemenang_membuka_kartu_gl_langsung(): void
    {
        $e = $this->acara('2026-11-24', 'Klub Malam Ini', [
            'kuota_gl' => 1,
            'username_acara' => 'wsgl-rahasia',
            'sandi_acara' => 'sandi-rahasia-9',
            'lokasi_lat' => -6.2088,
            'lokasi_lng' => 106.8456,
            'lokasi_radius' => 150,
        ]);
        $a = $this->anggota();

        Carbon::setTestNow('2026-11-24 12:00:00');
        $this->actingAs($a, 'member')->post(route('ic.acara.gl.antri', $e->kode));
        Carbon::setTestNow('2026-11-24 18:05:00');
        app(UndianService::class)->jalankanJatuhTempo();
        $this->assertSame(ic_gl_antrean::MENANG, ic_gl_antrean::query()->first()->status);

        $this->actingAs($a, 'member')->get(route('ic.profil'))->assertOk()
            ->assertSee(route('ic.acara.detail', ['kode' => $e->kode, 'kartu' => 1]), false);

        // Dengan ?kartu=1 popup Kartu GL terbuka otomatis; tanpa itu tertutup seperti biasa.
        $this->actingAs($a, 'member')->get(route('ic.acara.detail', ['kode' => $e->kode, 'kartu' => 1]))->assertOk()
            ->assertSee('data-ic-buka-awal', false)->assertSee('sandi-rahasia-9');
        $this->actingAs($a, 'member')->get(route('ic.acara.detail', $e->kode))->assertOk()
            ->assertDontSee('data-ic-buka-awal', false);
    }
}