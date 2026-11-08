<?php

namespace Tests\Feature\Admin;

use App\Models\account;
use App\Models\ic_reward;
use App\Models\log_aktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Halaman Log > Aktivitas: aksi tulis admin tercatat tanpa isi form, serta jejak login/logout.
 */
class LogAktivitasTest extends TestCase
{
    use RefreshDatabase;
    // ---------- log error ----------
    // ---------- email peringatan error ----------
    // ---------- halaman Log ----------
    // ---------- log aktivitas ----------
    // ---------- 2FA: kode OTP lewat email ----------

    private const SANDI = 'SandiPanjangUji123';

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function akun(?string $email = 'admin@contoh.test'): account
    {
        return account::create(['username' => 'admin', 'email' => $email, 'password' => Hash::make(self::SANDI)]);
    }

    private function login(array $o = [])
    {
        return $this->post(route('login.proses'), $o + ['username' => 'admin', 'password' => self::SANDI]);
    }

    public function test_aksi_tulis_admin_tercatat_dan_isi_form_tidak_disimpan(): void
    {
        $this->admin()->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 0, 'rahasia' => 'JANGAN-DISIMPAN'])
            ->assertRedirect();

        $baris = log_aktivitas::first();
        $this->assertSame('Aktif/nonaktifkan tahap hadiah', $baris->aksi);
        $this->assertSame('admin', $baris->pelaku);
        $this->assertSame('inner-circle', $baris->kategori);
        $this->assertSame('berhasil', $baris->status);
        $this->assertStringContainsString('target=10', $baris->keterangan);
        $this->assertStringContainsString('aktif=0', $baris->keterangan);
        $this->assertStringNotContainsString('JANGAN-DISIMPAN', (string) $baris->keterangan);
    }

    public function test_gagal_validasi_tidak_dicatat_tapi_penolakan_aturan_dicatat_sebagai_gagal(): void
    {
        $this->admin()->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 'sembarang'])->assertSessionHasErrors('aktif');
        $this->assertSame(0, log_aktivitas::count());

        // Menyembunyikan tahap terakhir yang aktif ditolak aturan bisnis -> dicatat sebagai gagal.
        ic_reward::query()->where('target_stempel', '!=', 10)->update(['aktif' => false]);
        $this->admin()->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 0]);
        $this->assertSame('gagal', log_aktivitas::first()->status);
    }

    public function test_membuka_halaman_tidak_dicatat_dan_halaman_aktivitas_bisa_disaring(): void
    {
        $this->admin()->get(route('ic.admin.hadiah'))->assertOk();
        $this->assertSame(0, log_aktivitas::count());

        $this->admin()->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 0]);
        log_aktivitas::create(['pelaku' => 'admin', 'kategori' => 'akses', 'aksi' => 'Login gagal', 'status' => 'gagal', 'created_at' => now()]);

        $this->admin()->get(route('log.index', ['tab' => 'aktivitas']))
            ->assertOk()->assertSee('Aktif/nonaktifkan tahap hadiah')->assertSee('Login gagal');
        $this->admin()->get(route('log.index', ['tab' => 'aktivitas', 'kategori' => 'akses']))
            ->assertSee('Login gagal')->assertDontSee('Aktif/nonaktifkan tahap hadiah');
        $this->admin()->get(route('log.index', ['tab' => 'aktivitas', 'status' => 'gagal']))
            ->assertSee('Login gagal')->assertDontSee('Aktif/nonaktifkan tahap hadiah');
    }

    public function test_login_gagal_berhasil_dan_logout_tercatat_tanpa_username_yang_diketik(): void
    {
        $this->akun();

        $this->login(['username' => 'SalahKetikSandiDiSini'])->assertSessionHasErrors('login');
        $gagal = log_aktivitas::first();
        $this->assertSame('Login gagal', $gagal->aksi);
        $this->assertSame('gagal', $gagal->status);
        $this->assertStringNotContainsString('SalahKetikSandiDiSini', json_encode($gagal->toArray()));

        $this->login()->assertRedirect(route('dashboard'));
        $this->assertTrue(log_aktivitas::where('aksi', 'Login')->where('pelaku', 'admin')->exists());

        $this->post(route('logout'));
        $this->assertTrue(log_aktivitas::where('aksi', 'Logout')->exists());
    }
}
