<?php

namespace Tests\Feature\InnerCircle\Admin;

use App\Models\member;
use App\Services\InnerCircle\GoogleAkun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tab Anggota di CMS Inner Circle: status verifikasi email dan cara anggota pertama kali mendaftar (manual atau Google).
 */
class AnggotaStatusTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function klaim(string $email = 'g@contoh.test', string $sub = '1000000001'): array
    {
        return ['sub' => $sub, 'email' => $email, 'name' => 'Pengguna Google', 'email_verified' => true];
    }

    public function test_daftar_manual_tercatat_sebagai_manual_dan_belum_terverifikasi(): void
    {
        $this->post(route('ic.daftar.proses'), [
            'nama' => 'Rani',
            'email' => 'rani@contoh.test',
            'sandi' => 'Rahasia123',
            'sandi_confirmation' => 'Rahasia123',
        ])->assertRedirect(route('ic.beranda'));

        $m = member::firstOrFail()->fresh();
        $this->assertSame(member::VIA_MANUAL, $m->daftar_via);
        $this->assertNull($m->email_verified_at);
    }

    public function test_akun_baru_dari_google_tercatat_sebagai_google_dan_langsung_terverifikasi(): void
    {
        [$m] = app(GoogleAkun::class)->masukkan($this->klaim());

        $m = $m->fresh();
        $this->assertSame(member::VIA_GOOGLE, $m->daftar_via);
        $this->assertNotNull($m->email_verified_at);
    }

    public function test_akun_manual_yang_dikaitkan_ke_google_tetap_tercatat_manual(): void
    {
        $manual = member::create(['nama' => 'Dewi', 'email' => 'g@contoh.test', 'password' => 'Rahasia123']);
        $manual->daftar_via = member::VIA_MANUAL;
        $manual->save();

        [$m] = app(GoogleAkun::class)->masukkan($this->klaim('g@contoh.test'));

        $m = $m->fresh();
        $this->assertSame(member::VIA_MANUAL, $m->daftar_via, 'Cara daftar pertama tidak boleh berubah saat Google dikaitkan.');
        $this->assertNotNull($m->google_id);
        $this->assertNotNull($m->email_verified_at);
    }

    public function test_tab_anggota_menampilkan_status_verifikasi_dan_cara_daftar(): void
    {
        $belum = member::create(['nama' => 'Belum Verif', 'email' => 'belum@contoh.test', 'password' => 'Rahasia123']);

        $sudah = member::create(['nama' => 'Sudah Verif', 'email' => 'sudah@contoh.test', 'password' => 'Rahasia123']);
        $sudah->email_verified_at = now();
        $sudah->save();

        app(GoogleAkun::class)->masukkan($this->klaim('google@contoh.test', 'sub-2'));

        $kait = member::create(['nama' => 'Manual Lalu Google', 'email' => 'kait@contoh.test', 'password' => 'Rahasia123']);
        app(GoogleAkun::class)->masukkan($this->klaim('kait@contoh.test', 'sub-3'));

        // Spasi/baris baru di HTML diratakan dulu supaya teks yang terpecah di beberapa baris tetap terhitung.
        // Form cari/filter dibuang: pilihan filternya memakai kata yang sama dengan lencana ("Belum verifikasi", dst).
        $html = preg_replace('/\s+/', ' ', $this->admin()->get(route('ic.admin.anggota'))->assertOk()->getContent());
        $html = preg_replace('#<form[^>]*role="search".*?</form>#s', '', $html);

        $this->assertSame(1, substr_count($html, 'Belum verifikasi'), 'Hanya satu anggota yang belum terverifikasi.');
        $this->assertSame(3, substr_count($html, 'Terverifikasi'), 'Anggota Google, manual terverifikasi, dan manual yang dikaitkan ke Google.');
        $this->assertSame(1, substr_count($html, 'Daftar via Google'));
        $this->assertSame(3, substr_count($html, 'Daftar manual'));
        $this->assertSame(1, substr_count($html, 'Google terhubung'), 'Hanya akun manual yang punya Google terhubung.');
    }

    public function test_migrasi_menebak_cara_daftar_data_lama(): void
    {
        $buat = function (string $email, ?string $googleId, ?string $dibuat, ?string $verifikasi): int {
            $m = member::create(['nama' => $email, 'email' => $email, 'password' => 'Rahasia123']);
            DB::table('members')->where('id_member', $m->id_member)->update([
                'google_id' => $googleId,
                'created_at' => $dibuat,
                'email_verified_at' => $verifikasi,
            ]);

            return $m->id_member;
        };

        $tanpaGoogle = $buat('a@contoh.test', null, '2026-10-01 10:00:00', null);
        $googleBaru = $buat('b@contoh.test', 'g1', '2026-10-01 10:00:00', '2026-10-01 10:00:01');
        $manualLaluGoogle = $buat('c@contoh.test', 'g2', '2026-10-01 10:00:00', '2026-10-05 08:30:00');
        $googleTanpaTanggal = $buat('d@contoh.test', 'g3', null, null);

        // Ulang migrasi dari kolom yang belum ada, seperti saat pertama kali dijalankan di server.
        Schema::table('members', fn($t) => $t->dropColumn('daftar_via'));
        // Dicari lewat nama belakangnya supaya tes tidak bergantung pada awalan tanggal pada nama file migrasi.
        $berkas = glob(database_path('migrations/*_members_daftar_via.php'));
        $this->assertCount(1, $berkas, 'File migrasi members_daftar_via harus ada tepat satu di database/migrations.');
        (require $berkas[0])->up();

        $via = fn(int $id) => DB::table('members')->where('id_member', $id)->value('daftar_via');
        $this->assertSame('manual', $via($tanpaGoogle));
        $this->assertSame('google', $via($googleBaru));
        $this->assertSame('manual', $via($manualLaluGoogle));
        $this->assertSame('manual', $via($googleTanpaTanggal));
    }
}