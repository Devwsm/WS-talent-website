<?php

namespace Tests\Feature;

use App\Mail\Admin\KodeLoginAdminMail;
use App\Mail\Admin\PeringatanErrorMail;
use App\Models\account;
use App\Models\ic_reward;
use App\Models\log_aktivitas;
use App\Models\log_error;
use App\Services\LogErrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

/** Halaman Log (error + aktivitas admin), email peringatan error, dan login admin dengan kode OTP email. */
class AdminLogDuaFaktorTest extends TestCase
{
    use RefreshDatabase;

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

    /** Kode OTP dari email terakhir yang terkirim (properti privat dibaca lewat refleksi). */
    private function kodeTerkirim(): string
    {
        $surat = Mail::sent(KodeLoginAdminMail::class)->last();
        $p = new ReflectionProperty(KodeLoginAdminMail::class, 'kode');

        return $p->getValue($surat);
    }

    // ---------- log error ----------

    public function test_error_yang_sama_digabung_dan_penghitung_naik(): void
    {
        $buat = fn() => new RuntimeException('boom');

        app(LogErrorService::class)->catat($buat());
        app(LogErrorService::class)->catat($buat());

        $this->assertSame(1, log_error::count());
        $baris = log_error::first();
        $this->assertSame(2, $baris->jumlah);
        $this->assertSame(RuntimeException::class, $baris->kelas);
        $this->assertStringContainsString('AdminLogDuaFaktorTest.php', $baris->lokasi);
        $this->assertStringNotContainsString(base_path(), $baris->lokasi);
    }

    public function test_error_selesai_dibuka_lagi_bila_terjadi_lagi(): void
    {
        $buat = fn() => new RuntimeException('boom');
        app(LogErrorService::class)->catat($buat());
        log_error::query()->update(['selesai' => true]);

        app(LogErrorService::class)->catat($buat());

        $this->assertFalse(log_error::first()->selesai);
    }

    public function test_rahasia_di_pesan_error_disamarkan(): void
    {
        app(LogErrorService::class)->catat(new RuntimeException('gagal password=Rahasia123 dan token: abcDEF999 serta Bearer xyz.123'));

        $pesan = log_error::first()->pesan;
        $this->assertStringNotContainsString('Rahasia123', $pesan);
        $this->assertStringNotContainsString('abcDEF999', $pesan);
        $this->assertStringNotContainsString('xyz.123', $pesan);
        $this->assertStringContainsString('***', $pesan);
    }

    public function test_error_di_route_tercatat_tanpa_query_string(): void
    {
        Route::get('/_uji-error', fn() => throw new RuntimeException('meledak'));

        $this->get('/_uji-error?token=RAHASIA')->assertStatus(500);

        $baris = log_error::first();
        $this->assertNotNull($baris);
        $this->assertSame('meledak', $baris->pesan);
        $this->assertSame('GET', $baris->metode);
        $this->assertStringEndsWith('/_uji-error', $baris->url);
        $this->assertStringNotContainsString('RAHASIA', $baris->url);
    }

    public function test_kesalahan_biasa_tidak_masuk_log_error(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound();
        $this->post(route('login.proses'), [])->assertSessionHasErrors();

        $this->assertSame(0, log_error::count());
    }

    // ---------- email peringatan error ----------

    public function test_email_peringatan_dikirim_sekali_per_jenis_error_dan_dibatasi_per_jam(): void
    {
        Mail::fake();
        config(['site.peringatan_email' => 'owner@contoh.test', 'log_admin.email_maks_per_jam' => 2]);

        $a = fn() => new RuntimeException('a');
        $b = fn() => new \LogicException('b');
        $c = fn() => new \DomainException('c');

        app(LogErrorService::class)->catat($a());
        app(LogErrorService::class)->catat($a()); // sama: tidak dikirim lagi
        Mail::assertSent(PeringatanErrorMail::class, 1);

        app(LogErrorService::class)->catat($b());
        Mail::assertSent(PeringatanErrorMail::class, 2);

        app(LogErrorService::class)->catat($c()); // melewati batas 2 per jam
        Mail::assertSent(PeringatanErrorMail::class, 2);
        $this->assertSame(3, log_error::count()); // tetap tercatat di halaman Log
        Mail::assertSent(PeringatanErrorMail::class, fn($m) => $m->hasTo('owner@contoh.test'));
    }

    public function test_tanpa_alamat_peringatan_tidak_ada_email(): void
    {
        Mail::fake();
        config(['site.peringatan_email' => null]);

        app(LogErrorService::class)->catat(new RuntimeException('x'));

        Mail::assertNothingSent();
        $this->assertSame(1, log_error::count());
    }

    public function test_pencatat_tidak_pernah_melempar_galat_walau_database_rusak(): void
    {
        \Schema::drop('log_errors');

        app(LogErrorService::class)->catat(new RuntimeException('x'));

        $this->assertTrue(true); // sampai di sini berarti tidak melempar
    }

    // ---------- halaman Log ----------

    public function test_halaman_log_butuh_login_admin(): void
    {
        $this->get(route('log.index'))->assertRedirect(route('login'));
        $this->patch(route('log.error.ubah', 1), ['aksi' => 'selesai'])->assertRedirect(route('login'));
        $this->delete(route('log.error.bersihkan'))->assertRedirect(route('login'));
    }

    public function test_halaman_log_menampilkan_error_dan_bisa_diselesaikan_lalu_dibersihkan(): void
    {
        app(LogErrorService::class)->catat(new RuntimeException('Pesan unik uji'));
        $id = log_error::first()->id_log_error;

        $this->admin()->get(route('log.index'))->assertOk()->assertSee('Pesan unik uji')->assertSee('RuntimeException');

        $this->admin()->patch(route('log.error.ubah', $id), ['aksi' => 'selesai'])->assertSessionHas('success');
        $this->assertTrue(log_error::first()->selesai);

        // Bawaan hanya yang terbuka; yang selesai ada di filter Selesai.
        $this->admin()->get(route('log.index'))->assertDontSee('Pesan unik uji');
        $this->admin()->get(route('log.index', ['status' => 'selesai']))->assertSee('Pesan unik uji');

        $this->admin()->patch(route('log.error.ubah', $id), ['aksi' => 'buka']);
        $this->assertFalse(log_error::first()->selesai);

        $this->admin()->patch(route('log.error.ubah', $id), ['aksi' => 'selesai']);
        $this->admin()->delete(route('log.error.bersihkan'))->assertRedirect(route('log.index'));
        $this->assertSame(0, log_error::count());

        $this->admin()->patch(route('log.error.ubah', $id), ['aksi' => 'sembarang'])->assertSessionHasErrors('aksi');
    }

    // ---------- log aktivitas ----------

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

    // ---------- 2FA: kode OTP lewat email ----------

    public function test_tanpa_2fa_login_langsung_masuk_seperti_biasa(): void
    {
        Mail::fake();
        $this->akun();

        $this->login()->assertRedirect(route('dashboard'))->assertSessionHas('login', true);
        Mail::assertNothingSent();
    }

    public function test_dengan_2fa_sandi_benar_belum_cukup_dan_kode_dikirim(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();

        $this->login()->assertRedirect(route('login.otp'))->assertSessionMissing('login')->assertSessionHas('otp_akun');
        Mail::assertSent(KodeLoginAdminMail::class, fn($m) => $m->hasTo('admin@contoh.test'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->kodeTerkirim());

        // Dashboard tetap tertutup selama kode belum benar.
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('login.otp'))->assertOk()->assertSee('ad***@contoh.test');
    }

    public function test_sandi_salah_dengan_2fa_tidak_mengirim_kode(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();

        $this->login(['password' => 'sandi-salah-sekali'])->assertSessionHasErrors('login');
        Mail::assertNothingSent();
    }

    public function test_kode_benar_masuk_dan_hanya_berlaku_sekali(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login();
        $kode = $this->kodeTerkirim();

        $this->post(route('login.otp.proses'), ['kode' => substr($kode, 0, 3) . ' ' . substr($kode, 3)])
            ->assertRedirect(route('dashboard'))->assertSessionHas('login', true)->assertSessionHas('user', 'admin');
        $this->assertTrue(log_aktivitas::where('aksi', 'Login')->where('keterangan', 'kode OTP email')->exists());

        // Kode yang sama tidak bisa dipakai di sesi lain.
        $this->flushSession();
        $this->withSession(['otp_akun' => account::first()->getKey()])
            ->post(route('login.otp.proses'), ['kode' => $kode])->assertSessionHasErrors('kode')->assertSessionMissing('login');
    }

    public function test_kode_salah_ditolak_dan_lima_kali_salah_menghanguskan_kode(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login();
        $benar = $this->kodeTerkirim();
        $salah = $benar === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('login.otp.proses'), ['kode' => $salah])->assertSessionHasErrors('kode')->assertSessionMissing('login');
        }

        // Kali kelima: kode hangus dan proses OTP berakhir, harus masuk ulang dengan sandi.
        $this->post(route('login.otp.proses'), ['kode' => $salah])->assertRedirect(route('login'))->assertSessionHasErrors('login');
        $this->assertNull(session('otp_akun'));

        // Kode yang benar pun tidak lagi berlaku.
        $this->withSession(['otp_akun' => account::first()->getKey()])
            ->post(route('login.otp.proses'), ['kode' => $benar])->assertSessionMissing('login');
    }

    public function test_kode_kedaluwarsa_setelah_sepuluh_menit(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login();
        $kode = $this->kodeTerkirim();

        $this->travel(11)->minutes();

        $this->post(route('login.otp.proses'), ['kode' => $kode])->assertSessionHasErrors('kode')->assertSessionMissing('login');
    }

    public function test_format_kode_dicek_dan_halaman_otp_butuh_sandi_benar_dulu(): void
    {
        $this->get(route('login.otp'))->assertRedirect(route('login'));
        $this->post(route('login.otp.proses'), ['kode' => '123456'])->assertRedirect(route('login'));

        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login();

        $this->post(route('login.otp.proses'), ['kode' => '12ab'])->assertSessionHasErrors('kode');
        $this->post(route('login.otp.proses'), ['kode' => ''])->assertSessionHasErrors('kode');
    }

    public function test_kirim_ulang_kode_dibatasi_tiga_kali_per_sepuluh_menit(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login(); // pengiriman ke-1

        $this->post(route('login.otp.ulang'))->assertRedirect(route('login.otp'))->assertSessionHas('success'); // ke-2
        $this->post(route('login.otp.ulang'))->assertRedirect(route('login.otp'))->assertSessionHas('success'); // ke-3
        Mail::assertSent(KodeLoginAdminMail::class, 3);

        $this->post(route('login.otp.ulang'))->assertSessionHasErrors('kode'); // ke-4 ditolak
        Mail::assertSent(KodeLoginAdminMail::class, 3);
    }

    public function test_email_admin_kosong_tidak_mengunci_pemilik(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun(null);

        $this->login()->assertRedirect(route('dashboard'))->assertSessionHas('login', true);
        Mail::assertNothingSent();
        $this->assertTrue(log_aktivitas::where('aksi', 'Login tanpa 2FA')->exists());
    }

    public function test_email_gagal_terkirim_memberi_pesan_jelas_dan_tidak_membuka_dashboard(): void
    {
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp mati'));

        $this->login()->assertRedirect(route('login'))->assertSessionHasErrors('login')
            ->assertSessionMissing('login')->assertSessionMissing('otp_akun');

        $this->assertTrue(log_aktivitas::where('aksi', 'Kode OTP gagal dikirim')->exists());
        $this->assertTrue(log_error::where('pesan', 'smtp mati')->exists()); // tampil di halaman Log
    }
}