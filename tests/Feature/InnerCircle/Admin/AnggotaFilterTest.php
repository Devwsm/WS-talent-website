<?php

namespace Tests\Feature\InnerCircle\Admin;

use App\Models\member;
use App\Services\InnerCircle\GoogleAkun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tab Anggota: filter status email (terverifikasi / belum) dan cara daftar (google / manual), bisa digabung dengan
 * pencarian dan tetap terbawa saat pindah halaman.
 */
class AnggotaFilterTest extends TestCase
{
    use RefreshDatabase;

    private function halaman(string $query = ''): string
    {
        return $this->withSession(['login' => true, 'user' => 'admin'])
            ->get(route('ic.admin.anggota') . $query)
            ->assertOk()
            ->getContent();
    }

    private function manual(string $nama, bool $verifikasi): member
    {
        $m = new member(['nama' => $nama, 'email' => strtolower(str_replace(' ', '', $nama)) . '@contoh.test', 'password' => 'Rahasia123']);
        $m->daftar_via = member::VIA_MANUAL;
        $m->email_verified_at = $verifikasi ? now() : null;
        $m->save();

        return $m;
    }

    private function siapkan(): void
    {
        $this->manual('Anggota Belum', false);
        $this->manual('Anggota Manual Verif', true);
        app(GoogleAkun::class)->masukkan(['sub' => 'g-1', 'email' => 'lewatgoogle@contoh.test', 'name' => 'Anggota Google', 'email_verified' => true]);

        // Daftar manual dulu, Google dikaitkan belakangan: cara daftar pertamanya tetap manual.
        $this->manual('Anggota Kaitan', true);
        app(GoogleAkun::class)->masukkan(['sub' => 'g-2', 'email' => 'anggotakaitan@contoh.test', 'name' => 'Anggota Kaitan', 'email_verified' => true]);
    }

    public function test_tanpa_filter_semua_anggota_tampil(): void
    {
        $this->siapkan();
        $html = $this->halaman();

        foreach (['Anggota Belum', 'Anggota Manual Verif', 'Anggota Google', 'Anggota Kaitan'] as $nama) {
            $this->assertStringContainsString($nama, $html);
        }
    }

    public function test_filter_status_email(): void
    {
        $this->siapkan();

        $belum = $this->halaman('?status=belum');
        $this->assertStringContainsString('Anggota Belum', $belum);
        foreach (['Anggota Manual Verif', 'Anggota Google', 'Anggota Kaitan'] as $nama) {
            $this->assertStringNotContainsString($nama, $belum);
        }

        $sudah = $this->halaman('?status=terverifikasi');
        $this->assertStringNotContainsString('Anggota Belum', $sudah);
        foreach (['Anggota Manual Verif', 'Anggota Google', 'Anggota Kaitan'] as $nama) {
            $this->assertStringContainsString($nama, $sudah);
        }
    }

    public function test_filter_cara_daftar_memakai_cara_pertama_bukan_penautan_google(): void
    {
        $this->siapkan();

        $google = $this->halaman('?via=google');
        $this->assertStringContainsString('Anggota Google', $google);
        foreach (['Anggota Belum', 'Anggota Manual Verif', 'Anggota Kaitan'] as $nama) {
            $this->assertStringNotContainsString($nama, $google);
        }

        $manual = $this->halaman('?via=manual');
        $this->assertStringNotContainsString('Anggota Google', $manual);
        foreach (['Anggota Belum', 'Anggota Manual Verif', 'Anggota Kaitan'] as $nama) {
            $this->assertStringContainsString($nama, $manual);
        }
    }

    public function test_filter_digabung_dengan_pencarian(): void
    {
        $this->siapkan();

        $html = $this->halaman('?status=terverifikasi&via=manual&q=Kaitan');
        $this->assertStringContainsString('Anggota Kaitan', $html);
        $this->assertStringNotContainsString('Anggota Manual Verif', $html);
        $this->assertStringNotContainsString('Anggota Google', $html);
        $this->assertStringContainsString('Hasil pencarian (1 dari 4)', preg_replace('/\s+/', ' ', $html));
    }

    public function test_nilai_filter_asing_dianggap_semua(): void
    {
        $this->siapkan();
        $html = $this->halaman('?status=ngawur&via=' . urlencode('<b>qqq</b>'));

        foreach (['Anggota Belum', 'Anggota Manual Verif', 'Anggota Google', 'Anggota Kaitan'] as $nama) {
            $this->assertStringContainsString($nama, $html);
        }
        $this->assertStringNotContainsString('<b>qqq', $html, 'Nilai filter asing tidak boleh ikut tampil.');
    }

    public function test_pilihan_filter_tampil_terpilih_dan_tombol_reset_muncul(): void
    {
        $this->siapkan();
        $html = preg_replace('/\s+/', ' ', $this->halaman('?status=belum&via=manual'));

        $this->assertMatchesRegularExpression('/<option[^>]*value="belum"[^>]*selected/', $html);
        $this->assertMatchesRegularExpression('/<option[^>]*value="manual"[^>]*selected/', $html);
        $this->assertStringContainsString('Reset', $html);

        $this->assertStringNotContainsString('Reset', preg_replace('/\s+/', ' ', $this->halaman()));
    }

    public function test_filter_terbawa_ke_halaman_berikutnya(): void
    {
        foreach (range(1, 20) as $i) {
            $this->manual('Banyak ' . $i, false);
        }

        $html = $this->halaman('?status=belum');
        $this->assertMatchesRegularExpression('/page=2[^"]*status=belum|status=belum[^"]*page=2/', html_entity_decode($html));
    }
}