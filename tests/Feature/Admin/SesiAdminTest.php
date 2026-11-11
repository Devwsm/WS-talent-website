<?php

namespace Tests\Feature\Admin;

use App\Models\account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Sesi admin dicabut otomatis bila sandi akun diganti atau akunnya dihapus.
 */
class SesiAdminTest extends TestCase
{
    use RefreshDatabase;

    private function masuk(): account
    {
        $akun = account::create(['username' => 'admin-sesi', 'password' => Hash::make('SandiPanjangUji123')]);

        $this->post(route('login.proses'), ['username' => 'admin-sesi', 'password' => 'SandiPanjangUji123'])
            ->assertRedirect(route('dashboard'));

        return $akun;
    }

    public function test_login_baru_membawa_id_dan_sidik_dan_dashboard_terbuka(): void
    {
        $akun = $this->masuk();

        $this->assertSame($akun->getKey(), session('admin_id'));
        $this->assertNotEmpty(session('admin_sidik'));
        $this->assertStringNotContainsString('SandiPanjangUji123', (string) session('admin_sidik'));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_sandi_diganti_membuat_sesi_lama_keluar(): void
    {
        $akun = $this->masuk();
        $this->get(route('dashboard'))->assertOk();

        $akun->update(['password' => Hash::make('SandiBaruLebihPanjang456')]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertNull(session('login'));

        // Sesi sudah dicabut sungguhan: permintaan berikutnya juga tidak lolos.
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_akun_dihapus_membuat_sesi_lama_keluar(): void
    {
        $akun = $this->masuk();
        $akun->delete();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_ganti_email_atau_username_tidak_mengeluarkan_admin(): void
    {
        $akun = $this->masuk();
        $akun->update(['email' => 'baru@contoh.test']);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_pencabutan_tercatat_di_log_aktivitas(): void
    {
        $akun = $this->masuk();
        $akun->update(['password' => Hash::make('SandiBaruLebihPanjang456')]);
        $this->get(route('dashboard'));

        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'Sesi admin dicabut', 'status' => 'gagal']);
    }

    public function test_sesi_lama_tanpa_sidik_tetap_diterima_sampai_habis_masa_berlaku(): void
    {
        $this->withSession(['login' => true, 'user' => 'admin'])->get(route('dashboard'))->assertOk();
    }
}