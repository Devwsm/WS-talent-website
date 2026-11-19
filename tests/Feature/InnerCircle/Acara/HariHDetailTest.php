<?php

namespace Tests\Feature\InnerCircle\Acara;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\PoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 4: halaman Hari H (daftar acara, detail, yang hadir dipisah GL gratis dan beli tiket sendiri, ekspor CSV).
 */
class HariHDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    private function acara(string $venue = 'Club Satu', string $tanggal = '2030-05-10'): ic_event
    {
        return app(AcaraService::class)->buat(['tanggal' => $tanggal, 'tipe' => 'club', 'kota' => 'Jakarta', 'venue' => $venue]);
    }

    private function anggota(string $email, string $nama): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function hadir(member $m, ic_event $e, int $poin = 1000): void
    {
        app(PoinService::class)->tambah($m, $poin, 'checkin', 'Check-in', 'pin', $e, jarakM: 20, akurasiM: 10);
    }

    private function menang(member $m, ic_event $e): void
    {
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member, 'status' => ic_gl_antrean::MENANG]);
    }

    /** CSV ekspor memakai titik koma, BOM di awal, dan akhir baris CRLF (sama dengan EksporCsvTest). */
    private function baris(string $isi): array
    {
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);

        return array_map(
            fn($b) => str_getcsv($b, ';', '"', '\\'),
            array_values(array_filter(explode("\r\n", $isi), fn($b) => $b !== '')),
        );
    }

    public function test_detail_butuh_login_dan_kode_asing_atau_privat_404(): void
    {
        $e = $this->acara();
        $privat = app(AcaraService::class)->buat(['tanggal' => '2030-05-11', 'tipe' => 'private', 'kota' => 'Jakarta', 'venue' => 'Rumah']);

        $this->get(route('ic.admin.acara.harih.detail', $e->kode))->assertRedirect(route('login'));
        $this->admin()->get(route('ic.admin.acara.harih.detail', 'KODE-TIDAK-ADA'))->assertNotFound();
        $this->admin()->get(route('ic.admin.acara.harih.detail', $privat->kode))->assertNotFound();
        $this->admin()->get(route('ic.admin.acara.harih.detail', $e->kode))->assertOk()
            ->assertSee('Ringkasan kehadiran')->assertSee('Yang hadir')->assertSee('Beli tiket sendiri')->assertSee('GL gratis');
    }

    public function test_daftar_acara_di_hari_h_menautkan_ke_detail(): void
    {
        $e = $this->acara('Klub Mendatang', now()->addDays(3)->toDateString());

        $this->admin()->get(route('ic.admin.acara.harih'))->assertOk()
            ->assertSee(route('ic.admin.acara.harih.detail', $e->kode), false);
    }

    public function test_daftar_acara_menyembunyikan_acara_lama_kecuali_semua(): void
    {
        $lama = $this->acara('Klub Lawas', now()->subDays(60)->toDateString());

        $this->admin()->get(route('ic.admin.acara.harih'))->assertOk()
            ->assertDontSee(route('ic.admin.acara.harih.detail', $lama->kode), false);
        $this->admin()->get(route('ic.admin.acara.harih', ['semua' => 1]))->assertOk()
            ->assertSee(route('ic.admin.acara.harih.detail', $lama->kode), false);
    }

    public function test_yang_hadir_dipisah_pemenang_gl_dan_beli_tiket_sendiri(): void
    {
        $e = $this->acara();
        $gl = $this->anggota('gl@contoh.test', 'Gita GL');
        $tiket = $this->anggota('tiket@contoh.test', 'Toni Tiket');
        $antri = $this->anggota('antri@contoh.test', 'Andi Antri');

        $this->menang($gl, $e);
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $antri->id_member]);
        $this->hadir($gl, $e, 1000);
        $this->hadir($tiket, $e, 2000);

        $this->admin()->get(route('ic.admin.acara.harih.detail', ['kode' => $e->kode, 'tab' => 'tiket']))->assertOk()
            ->assertSee('Toni Tiket')->assertDontSee('Gita GL');
        $this->admin()->get(route('ic.admin.acara.harih.detail', ['kode' => $e->kode, 'tab' => 'gl']))->assertOk()
            ->assertSee('Gita GL')->assertDontSee('Toni Tiket');
        // Tab tak dikenal jatuh ke tiket sendiri.
        $this->admin()->get(route('ic.admin.acara.harih.detail', ['kode' => $e->kode, 'tab' => 'ngawur']))->assertOk()
            ->assertSee('Toni Tiket');
    }

    public function test_hadir_di_acara_lain_tidak_ikut_dan_pemenang_acara_lain_bukan_gl_di_sini(): void
    {
        $e = $this->acara();
        $lain = $this->acara('Hall Lain', '2030-05-11');
        $m = $this->anggota('a@contoh.test', 'Ani Wijaya');

        $this->menang($m, $lain);
        $this->hadir($m, $e, 2000);

        $this->admin()->get(route('ic.admin.acara.harih.detail', ['kode' => $e->kode, 'tab' => 'tiket']))->assertSee('Ani Wijaya');
        $this->admin()->get(route('ic.admin.acara.harih.detail', ['kode' => $e->kode, 'tab' => 'gl']))->assertDontSee('Ani Wijaya');
    }

    public function test_ekspor_csv_per_jenis_dan_kolom_sumber(): void
    {
        $e = $this->acara();
        $gl = $this->anggota('gl@contoh.test', 'Gita GL');
        $tiket = $this->anggota('tiket@contoh.test', 'Toni Tiket');
        $this->menang($gl, $e);
        $this->hadir($gl, $e);
        $this->hadir($tiket, $e, 2000);

        $semua = $this->baris($this->admin()->get(route('ic.admin.acara.kehadiran', $e->kode))->streamedContent());
        $this->assertCount(3, $semua);
        $this->assertSame('Sumber kehadiran', end($semua[0]));

        $hanyaGl = $this->admin()->get(route('ic.admin.acara.kehadiran', ['kode' => $e->kode, 'jenis' => 'gl']));
        $this->assertStringContainsString('-gl-', $hanyaGl->headers->get('Content-Disposition'));
        $baris = $this->baris($hanyaGl->streamedContent());
        $this->assertCount(2, $baris);
        $this->assertSame('Gita GL', $baris[1][2]);
        $this->assertSame('GL gratis', end($baris[1]));

        $hanyaTiket = $this->baris($this->admin()->get(route('ic.admin.acara.kehadiran', ['kode' => $e->kode, 'jenis' => 'tiket']))->streamedContent());
        $this->assertCount(2, $hanyaTiket);
        $this->assertSame('Toni Tiket', $hanyaTiket[1][2]);
        $this->assertSame('Beli tiket sendiri', end($hanyaTiket[1]));
    }

    public function test_ringkasan_menghitung_hadir_dan_poin(): void
    {
        $e = $this->acara();
        $gl = $this->anggota('gl@contoh.test', 'Gita GL');
        $tiket = $this->anggota('tiket@contoh.test', 'Toni Tiket');
        $this->menang($gl, $e);
        $this->hadir($gl, $e, 1000);
        $this->hadir($tiket, $e, 2000);

        $this->admin()->get(route('ic.admin.acara.harih.detail', $e->kode))->assertOk()->assertSee('3.000');
    }
}