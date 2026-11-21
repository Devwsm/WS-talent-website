<?php

namespace Tests\Feature\InnerCircle\Acara;

use App\Models\ic_event;
use App\Services\InnerCircle\AcaraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 3: halaman Atur acara (pengaturan lengkap, GL, sandi, poster, lokasi dari peta).
 */
class AturAcaraTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function acara(string $tanggal = '2030-05-10'): ic_event
    {
        return app(AcaraService::class)->buat(['tanggal' => $tanggal, 'tipe' => 'club', 'kota' => 'Jakarta', 'venue' => 'Club Satu']);
    }

    private function data(array $tambah = []): array
    {
        return $tambah + ['tanggal' => '2030-05-10', 'tipe' => 'club', 'venue' => 'Club Satu', 'kota' => 'Jakarta'];
    }

    public function test_halaman_atur_terbuka_dan_butuh_login(): void
    {
        $e = $this->acara();

        $this->get(route('ic.admin.acara.atur', $e->kode))->assertRedirect(route('login'));
        $this->admin()->get(route('ic.admin.acara.atur', $e->kode))->assertOk()
            ->assertSee('Atur acara')->assertSee('GL gratis')->assertSee('Lokasi check-in')->assertSee('belum punya titik lokasi');
        $this->admin()->get(route('ic.admin.acara.atur', 'KODE-TIDAK-ADA'))->assertNotFound();
    }

    public function test_tambah_acara_lanjut_ke_halaman_atur(): void
    {
        $r = $this->admin()->post(route('ic.admin.acara.simpan'), $this->data(['venue' => 'Klub Baru']));

        $e = ic_event::query()->where('venue', 'Klub Baru')->firstOrFail();
        $r->assertRedirect(route('ic.admin.acara.atur', $e->kode));
    }

    public function test_simpan_pengaturan_gl_dan_check_in(): void
    {
        $e = $this->acara();

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data([
            'deskripsi' => 'Malam spesial',
            'petunjuk_masuk' => 'Tunjukkan kartu di pintu',
            'kuota_gl' => 25,
            'undian_pada' => '2030-05-10T17:00',
            'username_acara' => 'tamu-ws',
            'sandi_acara' => 'rahasia-acara',
            'poin_gl' => 1000,
            'poin_mandiri' => 2000,
            'jendela_mulai' => '2030-05-10T22:00',
            'jendela_selesai' => '2030-05-11T04:00',
        ]))->assertRedirect(route('ic.admin.acara.atur', $e->kode))->assertSessionHasNoErrors();

        $baru = $e->fresh();
        $this->assertSame(25, $baru->kuota_gl);
        $this->assertSame('2030-05-10 17:00:00', $baru->undian_pada->format('Y-m-d H:i:s'));
        $this->assertSame('tamu-ws', $baru->username_acara);
        $this->assertSame('rahasia-acara', $baru->sandi_acara);
        $this->assertSame(1000, $baru->poin_gl);
        $this->assertSame(2000, $baru->poin_mandiri);
        $this->assertSame('Malam spesial', $baru->deskripsi);
        $this->assertTrue($baru->glLengkap());
    }

    public function test_sandi_kosong_tidak_menimpa_dan_sandi_tampil_sebagai_kode_terbaca(): void
    {
        $e = $this->acara();
        $e->update(['sandi_acara' => 'rahasia-acara', 'username_acara' => 'tamu-ws', 'kuota_gl' => 5]);

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['kuota_gl' => 5, 'username_acara' => 'tamu-ws']))
            ->assertSessionHasNoErrors();
        $this->assertSame('rahasia-acara', $e->fresh()->sandi_acara);

        // Sandi tampil langsung sebagai kode di kolom teks (bukan kolom sandi bertitik-titik) dan tidak ada lagi tombol "Lihat sandi".
        $this->admin()->get(route('ic.admin.acara.atur', $e->kode))->assertOk()
            ->assertSee('value="rahasia-acara"', false)
            ->assertSee('data-buat-kode', false)
            ->assertDontSee('Lihat sandi');
    }

    public function test_username_acara_boleh_memakai_spasi_dan_spasi_ganda_dirapikan(): void
    {
        $e = $this->acara();

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['username_acara' => '  Whisnu   Santika VIP ']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Whisnu Santika VIP', $e->fresh()->username_acara);

        // Karakter di luar ASCII biasa tetap ditolak.
        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['username_acara' => "tab\tdi sini"]))
            ->assertSessionHasErrors('username_acara');
    }

    public function test_sandi_bisa_dihapus_lewat_centang(): void
    {
        $e = $this->acara();
        $e->update(['sandi_acara' => 'rahasia-acara']);

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['hapus_sandi' => 1]));

        $this->assertNull($e->fresh()->sandi_acara);
    }

    public function test_validasi_gl_dan_poin(): void
    {
        $e = $this->acara();
        $url = route('ic.admin.acara.atur.simpan', $e->kode);

        $this->admin()->put($url, $this->data(['kuota_gl' => 100000]))->assertSessionHasErrors('kuota_gl');
        $this->admin()->put($url, $this->data(['kuota_gl' => -1]))->assertSessionHasErrors('kuota_gl');
        $this->admin()->put($url, $this->data(['poin_gl' => 0]))->assertSessionHasErrors('poin_gl');
        $this->admin()->put($url, $this->data(['username_acara' => 'ada spasi']))->assertSessionHasNoErrors();
        $this->admin()->put($url, $this->data(['sandi_acara' => 'ada spasi']))->assertSessionHasErrors('sandi_acara');
        $this->admin()->put($url, $this->data(['sandi_acara' => 'abc']))->assertSessionHasErrors('sandi_acara');
        $this->admin()->put($url, $this->data(['undian_pada' => '2030-05-12T10:00']))->assertSessionHasErrors('undian_pada');
        $this->admin()->put($url, $this->data(['jendela_mulai' => '2030-05-10T22:00']))->assertSessionHasErrors('jendela_selesai');
    }

    public function test_kuota_nol_berarti_tanpa_gl(): void
    {
        $e = $this->acara();
        $e->update(['kuota_gl' => 10]);

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['kuota_gl' => 0]))->assertSessionHasNoErrors();

        $this->assertNull($e->fresh()->kuota_gl);
        $this->assertFalse($e->fresh()->punya_gl);
    }

    public function test_kuota_dan_waktu_undian_terkunci_setelah_undian_selesai(): void
    {
        $e = $this->acara();
        $e->update(['kuota_gl' => 10, 'status_undian' => ic_event::UNDIAN_SELESAI]);

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['kuota_gl' => 99, 'venue' => 'Venue Baru']))
            ->assertSessionHasNoErrors();

        $this->assertSame(10, $e->fresh()->kuota_gl);
        $this->assertSame('Venue Baru', $e->fresh()->venue);
    }

    public function test_status_tertunda_kembali_belum_saat_data_gl_lengkap(): void
    {
        $e = $this->acara();
        $e->update(['kuota_gl' => 5, 'username_acara' => 'tamu-ws', 'status_undian' => ic_event::UNDIAN_TERTUNDA]);

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['kuota_gl' => 5, 'username_acara' => 'tamu-ws', 'sandi_acara' => 'rahasia-acara']));

        $this->assertSame(ic_event::UNDIAN_BELUM, $e->fresh()->status_undian);
    }

    public function test_poster_ditolak_bila_bukan_gambar_atau_terlalu_besar(): void
    {
        $e = $this->acara();
        $url = route('ic.admin.acara.atur.simpan', $e->kode);

        $this->admin()->put($url, $this->data(['poster' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))->assertSessionHasErrors('poster');
        $this->admin()->put($url, $this->data(['poster' => UploadedFile::fake()->image('besar.jpg')->size(2048)]))->assertSessionHasErrors('poster');
        $this->assertNull($e->fresh()->poster);
    }

    public function test_hapus_poster_menghapus_berkas(): void
    {
        Storage::fake('public');
        $e = $this->acara();
        Storage::disk('public')->put('inner-circle/acara/lama.webp', 'x');
        $e->update(['poster' => 'inner-circle/acara/lama.webp']);

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['hapus_poster' => 1]))->assertSessionHasNoErrors();

        $this->assertNull($e->fresh()->poster);
        $this->assertFalse(Storage::disk('public')->exists('inner-circle/acara/lama.webp'));
    }

    public function test_acara_bandsintown_jadi_manual_bila_data_dasar_diubah(): void
    {
        $e = $this->acara();
        $e->update(['sumber' => 'bandsintown', 'bit_id' => '123']);

        $this->admin()->put(route('ic.admin.acara.atur.simpan', $e->kode), $this->data(['venue' => 'Venue Lain']));

        $this->assertSame('manual', $e->fresh()->sumber);
    }

    public function test_simpan_lokasi_dari_halaman_atur_kembali_ke_halaman_atur(): void
    {
        $e = $this->acara();

        $this->admin()->put(route('ic.admin.acara.lokasi', $e->kode), [
            'lat' => '-6.2088',
            'lng' => '106.8456',
            'radius' => 150,
            'sumber' => 'manual',
            'kembali' => 'atur',
        ])->assertRedirect(route('ic.admin.acara.atur', $e->kode) . '#lokasi');

        $this->assertTrue($e->fresh()->lokasi_aktif);

        $this->admin()->delete(route('ic.admin.acara.lokasi.hapus', $e->kode), ['kembali' => 'atur'])
            ->assertRedirect(route('ic.admin.acara.atur', $e->kode) . '#lokasi');
        $this->assertFalse($e->fresh()->lokasi_aktif);
    }

    public function test_jadwal_menampilkan_tautan_atur_dan_penanda_gl_dan_lokasi(): void
    {
        $e = $this->acara();
        $e->update(['kuota_gl' => 12]);

        $this->admin()->get(route('ic.admin.acara'))->assertOk()
            ->assertSee(route('ic.admin.acara.atur', $e->kode), false)
            ->assertSee('GL 12')
            ->assertSee('belum ada lokasi');
    }
}