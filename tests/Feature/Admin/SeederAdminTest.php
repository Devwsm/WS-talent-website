<?php

namespace Tests\Feature\Admin;

use App\Models\account;
use Database\Seeders\accountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Seeder akun admin: kredensial hanya dari env, berhenti di produksi tanpa kredensial, dan tidak ada sandi di kode.
 */
class SeederAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seeder dijalankan langsung, bukan lewat `$this->seed()` (perintah `db:seed`): di env production perintah itu
     * meminta konfirmasi interaktif, sehingga hasil tes bergantung pada terminal yang dipakai (lolos di Linux,
     * gagal di PowerShell). Yang diuji di sini adalah logika seedernya, bukan perintah artisan-nya.
     */
    private function jalankanSeeder(): void
    {
        $this->app->make(accountSeeder::class)->run();
    }

    public function test_seeder_admin_berhenti_di_produksi_tanpa_kredensial_env(): void
    {
        $this->app['env'] = 'production';
        config(['site.admin_username' => null, 'site.admin_password' => null]);

        $this->jalankanSeeder();

        $this->assertSame(0, account::count());
    }

    public function test_seeder_admin_memakai_kredensial_dari_env_dan_tidak_menimpa(): void
    {
        config(['site.admin_username' => 'admin-env', 'site.admin_password' => 'sandi-panjang-yang-kuat-1']);

        $this->jalankanSeeder();

        $akun = account::where('username', 'admin-env')->firstOrFail();
        $this->assertTrue(Hash::check('sandi-panjang-yang-kuat-1', $akun->password));

        config(['site.admin_password' => 'sandi-baru-yang-lain-123']);
        $this->jalankanSeeder();

        $this->assertSame(1, account::count());
        $this->assertTrue(Hash::check('sandi-panjang-yang-kuat-1', $akun->fresh()->password));
    }

    public function test_seeder_admin_menolak_sandi_pendek(): void
    {
        config(['site.admin_username' => 'admin-env', 'site.admin_password' => 'pendek']);

        $this->jalankanSeeder();

        $this->assertSame(0, account::count());
    }

    public function test_kredensial_lama_tidak_ada_lagi_di_kode_seeder(): void
    {
        $isi = file_get_contents(database_path('seeders/accountSeeder.php'));

        $this->assertStringNotContainsString('manage@ws2026', $isi);
        $this->assertStringNotContainsString('staff@ws', $isi);
    }
}
