<?php

namespace Tests\Feature\Admin;

use App\Mail\Admin\PeringatanErrorMail;
use App\Models\log_error;
use App\Services\LogErrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Halaman Log > Error: pengelompokan, penyamaran rahasia, email peringatan, dan pencatat yang tidak pernah melempar galat.
 */
class LogErrorTest extends TestCase
{
    use RefreshDatabase;
    // ---------- log error ----------
    // ---------- email peringatan error ----------
    // ---------- halaman Log ----------
    // ---------- log aktivitas ----------
    // ---------- 2FA: kode OTP lewat email ----------

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_error_yang_sama_digabung_dan_penghitung_naik(): void
    {
        $buat = fn() => new RuntimeException('boom');

        app(LogErrorService::class)->catat($buat());
        app(LogErrorService::class)->catat($buat());

        $this->assertSame(1, log_error::count());
        $baris = log_error::first();
        $this->assertSame(2, $baris->jumlah);
        $this->assertSame(RuntimeException::class, $baris->kelas);
        $this->assertStringContainsString('LogErrorTest.php', $baris->lokasi);
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
        Schema::drop('log_errors');

        app(LogErrorService::class)->catat(new RuntimeException('x'));

        $this->assertTrue(true); // sampai di sini berarti tidak melempar
    }

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
}
