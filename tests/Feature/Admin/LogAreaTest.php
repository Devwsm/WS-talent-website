<?php

namespace Tests\Feature\Admin;

use App\Models\log_error;
use App\Services\LogErrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Log dipisah per CMS: CMS Artis dan CMS Inner Circle masing-masing hanya menampilkan catatan areanya sendiri.
 */
class LogAreaTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function laporCsp(string $halaman, string $host = 'https://connect.facebook.net/en_US/fbevents.js')
    {
        return $this->call('POST', route('csp.laporan'), [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => [
                'document-uri' => 'https://whisnusantika.com' . $halaman . '?x=1',
                'effective-directive' => 'script-src-elem',
                'blocked-uri' => $host,
                'disposition' => 'report',
            ],
        ]));
    }

    public function test_laporan_csp_dari_halaman_inner_circle_masuk_log_inner_circle_saja(): void
    {
        config(['keamanan.csp.mode' => 'report']);

        $this->laporCsp('/innercircle/daftar')->assertNoContent();

        $this->assertSame('inner-circle', log_error::first()->area);
        $this->admin()->get(route('ic.admin.log.index'))->assertOk()->assertSee('Skrip dari connect.facebook.net terdeteksi')->assertSee('Daftar Inner Circle');
        $this->admin()->get(route('log.index'))->assertOk()->assertDontSee('connect.facebook.net')->assertSee('Tidak ada masalah yang terbuka');
    }

    public function test_masalah_yang_sama_di_dua_area_menjadi_dua_baris_terpisah(): void
    {
        config(['keamanan.csp.mode' => 'report']);

        $this->laporCsp('/innercircle/daftar');
        $this->laporCsp('/innercircle/masuk'); // area sama: digabung
        $this->laporCsp('/');                  // area lain: baris sendiri

        $this->assertSame(2, log_error::count());
        $this->assertSame(2, log_error::where('area', 'inner-circle')->value('jumlah'));
        $this->assertSame(1, log_error::where('area', 'artis')->value('jumlah'));
    }

    public function test_error_di_dashboard_inner_circle_masuk_area_inner_circle(): void
    {
        Route::get('/dashboard/inner-circle/_uji-error', fn() => throw new RuntimeException('meledak'));
        Route::get('/_uji-error-artis', fn() => throw new RuntimeException('meledak juga'));

        $this->get('/dashboard/inner-circle/_uji-error')->assertStatus(500);
        $this->get('/_uji-error-artis')->assertStatus(500);

        $this->assertSame('inner-circle', log_error::where('pesan', 'meledak')->value('area'));
        $this->assertSame('artis', log_error::where('pesan', 'meledak juga')->value('area'));
    }

    public function test_error_biasa_tampil_dengan_judul_awam_dan_detail_teknis_tetap_ada(): void
    {
        app(LogErrorService::class)->catat(new \Illuminate\Database\QueryException('mysql', 'select 1', [], new \Exception('SQLSTATE[HY000] gagal')));

        $this->admin()->get(route('log.index'))->assertOk()
            ->assertSee('Database bermasalah')
            ->assertSee('Detail teknis')
            ->assertSee('QueryException');
    }

    public function test_tandai_selesai_dan_bersihkan_hanya_untuk_area_masing_masing(): void
    {
        config(['keamanan.csp.mode' => 'report']);
        $this->laporCsp('/innercircle/daftar');
        $this->laporCsp('/');
        $ic = log_error::where('area', 'inner-circle')->first();
        $artis = log_error::where('area', 'artis')->first();

        // Error milik area lain tidak bisa diubah dari halaman ini.
        $this->admin()->patch(route('log.error.ubah', $ic->id_log_error), ['aksi' => 'selesai'])->assertNotFound();
        $this->admin()->patch(route('ic.admin.log.error.ubah', $artis->id_log_error), ['aksi' => 'selesai'])->assertNotFound();

        $this->admin()->patch(route('ic.admin.log.error.ubah', $ic->id_log_error), ['aksi' => 'selesai'])->assertRedirect();
        log_error::query()->update(['selesai' => true]);

        $this->admin()->delete(route('ic.admin.log.error.bersihkan'))->assertRedirect(route('ic.admin.log.index'));
        $this->assertSame(1, log_error::count());
        $this->assertSame('artis', log_error::first()->area);
    }

    public function test_halaman_log_inner_circle_butuh_login_dan_mengikuti_flag(): void
    {
        $this->get(route('ic.admin.log.index'))->assertRedirect(route('login'));

        config(['inner_circle.aktif' => false]);
        $this->admin()->get('/dashboard/inner-circle/log')->assertNotFound();
    }

    public function test_menu_log_ada_di_navbar_cms_inner_circle(): void
    {
        $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()->assertSee(route('ic.admin.log.index'), false);
    }

    public function test_bila_inner_circle_mati_log_artis_menampilkan_semua_error(): void
    {
        config(['keamanan.csp.mode' => 'report']);
        $this->laporCsp('/innercircle/daftar');

        config(['inner_circle.aktif' => false]);
        $this->admin()->get(route('log.index'))->assertOk()->assertSee('connect.facebook.net');
    }
}