<?php

namespace Tests\Feature;

use App\Mail\InnerCircle\StatusKlaimMail;
use App\Models\ic_broadcast;
use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_event;
use App\Models\ic_reward;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\StempelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Halaman publik penjelasan program, buka kembali klaim yang ditolak, dan tampil/sembunyikan hadiah + pengumuman. */
class InnerCircleFase10Test extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function anggota(int $stempel = 0): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->save();
        if ($stempel > 0) {
            app(StempelService::class)->tambahManual($m, $stempel, 'Uji', 'admin');
        }

        return $m->fresh();
    }

    private function acara(array $o = []): ic_event
    {
        return app(AcaraService::class)->buat($o + [
            'tanggal' => now()->addDays(3)->toDateString(),
            'tipe' => 'club',
            'venue' => 'Venue Uji Satu',
            'kota' => 'Bandung',
        ]);
    }

    private function klaim(member $m, string $status, ?string $catatan = null): ic_claim
    {
        $reward = ic_reward::where('target_stempel', 10)->firstOrFail();
        $k = ic_claim::create(['member_id' => $m->id_member, 'reward_id' => $reward->id_reward, 'status' => $status, 'catatan_admin' => $catatan]);
        ic_claim_log::create(['claim_id' => $k->id_claim, 'status' => 'diajukan']);

        return $k;
    }

    private function broadcast(array $o = []): ic_broadcast
    {
        return ic_broadcast::create($o + [
            'judul' => 'Pengumuman uji',
            'isi' => 'Isi pengumuman uji.',
            'ikon' => 'sparkles',
            'warna' => 'kuning',
            'cta_label' => 'Lihat',
            'cta_tujuan' => 'ic.checkin',
            'unggulan' => false,
            'terbit_pada' => now()->toDateString(),
            'aktif' => true,
        ]);
    }

    // ---------- halaman publik ----------

    public function test_tamu_membuka_halaman_penjelasan_dengan_hadiah_dan_acara_publik(): void
    {
        $this->acara(['venue' => 'Venue Publik Uji']);
        $this->acara(['tipe' => 'private', 'venue' => 'Acara Privat Rahasia']);
        $this->acara(['venue' => null, 'kota' => 'Kota Tanpa Venue']);
        $this->acara(['tanggal' => now()->subDays(5)->toDateString(), 'venue' => 'Acara Lewat Uji']);

        $this->get(route('ic.tentang'))
            ->assertOk()
            ->assertSee('Cara kerjanya')
            ->assertSee('Hadiah Pertama')
            ->assertSee('Venue Publik Uji')
            ->assertDontSee('Acara Privat Rahasia')
            ->assertDontSee('Kota Tanpa Venue')
            ->assertDontSee('Acara Lewat Uji')
            ->assertDontSee('TERKUNCI') // tanpa label status khas anggota
            ->assertSee(route('ic.daftar'), false)
            ->assertSee('<link rel="canonical"', false)
            ->assertDontSee('noindex', false);
    }

    public function test_halaman_penjelasan_tanpa_acara_dan_tanpa_hadiah_aktif_tetap_tampil(): void
    {
        ic_reward::query()->update(['aktif' => false]);

        $this->get(route('ic.tentang'))->assertOk()->assertSee('Jadwal acara berikutnya akan diumumkan')->assertDontSee('Hadiah Pertama');
    }

    public function test_tamu_di_beranda_dialihkan_ke_penjelasan_dan_anggota_tetap_ke_beranda(): void
    {
        $this->get('/innercircle')->assertRedirect(route('ic.tentang'));
        $this->get(route('ic.hadiah'))->assertRedirect(route('ic.masuk'));

        $m = $this->anggota();
        $this->actingAs($m, 'member')->get('/innercircle')->assertOk();
        $this->actingAs($m, 'member')->get(route('ic.tentang'))->assertOk()->assertSee('Buka Inner Circle');
    }

    public function test_halaman_penjelasan_mati_bersama_flag_dan_robots_membukanya(): void
    {
        $robots = $this->get('/robots.txt')->getContent();
        $this->assertStringContainsString('Allow: /innercircle/tentang', $robots);
        $this->assertLessThan(strpos($robots, 'Disallow: /innercircle'), strpos($robots, 'Allow: /innercircle/tentang'), 'Allow harus ditulis sebelum Disallow.');

        config(['inner_circle.aktif' => false]);
        $this->get(route('ic.tentang'))->assertNotFound();
        $this->assertStringNotContainsString('/innercircle/tentang', $this->get('/robots.txt')->getContent());
    }

    // ---------- buka kembali klaim ----------

    public function test_klaim_ditolak_bisa_dibuka_kembali_dan_anggota_diberi_kabar(): void
    {
        Mail::fake();
        $m = $this->anggota(12);
        $k = $this->klaim($m, 'ditolak', 'Stok habis');

        $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'buka_kembali', 'dari' => 'ditolak', 'catatan' => ''])
            ->assertSessionHas('success');

        $k->refresh();
        $this->assertSame('diajukan', $k->status);
        $this->assertNull($k->catatan_admin); // alasan penolakan lama tidak lagi tampil ke anggota
        $this->assertSame('admin', $k->diproses_oleh);
        $this->assertSame(['diajukan', 'diajukan'], $k->logs->pluck('status')->all());
        Mail::assertSent(StatusKlaimMail::class, fn($mail) => $mail->hasTo('a@contoh.test'));

        // Alur berjalan normal lagi, dan anggota tidak mengajukan ganda.
        $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertSessionHas('success');
        $this->assertSame('ditinjau', $k->fresh()->status);
        $this->assertSame(1, ic_claim::count());
    }

    public function test_buka_kembali_boleh_membawa_catatan_baru(): void
    {
        Mail::fake();
        $k = $this->klaim($this->anggota(12), 'ditolak', 'Alasan lama');

        $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'buka_kembali', 'dari' => 'ditolak', 'catatan' => 'Stok sudah ada lagi']);

        $this->assertSame('Stok sudah ada lagi', $k->fresh()->catatan_admin);
        $this->assertContains('Stok sudah ada lagi', $k->logs()->pluck('catatan')->all());
    }

    public function test_hanya_klaim_ditolak_yang_bisa_dibuka_kembali(): void
    {
        Mail::fake();
        $m = $this->anggota(12);

        foreach (['diajukan', 'ditinjau', 'disetujui', 'selesai'] as $status) {
            ic_claim::query()->delete();
            $k = $this->klaim($m, $status);

            $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'buka_kembali', 'dari' => $status])
                ->assertSessionHas('error');
            $this->assertSame($status, $k->fresh()->status);
        }

        Mail::assertNothingSent();
    }

    public function test_klik_ganda_buka_kembali_ditolak_karena_status_sudah_berubah(): void
    {
        Mail::fake();
        $k = $this->klaim($this->anggota(12), 'ditolak', 'x');
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $this->admin()->patch($url, ['aksi' => 'buka_kembali', 'dari' => 'ditolak'])->assertSessionHas('success');
        $this->admin()->patch($url, ['aksi' => 'buka_kembali', 'dari' => 'ditolak'])->assertSessionHas('error');

        $this->assertSame('diajukan', $k->fresh()->status);
        Mail::assertSent(StatusKlaimMail::class, 1);
    }

    public function test_lanjut_pada_klaim_ditolak_menunjuk_ke_buka_kembali(): void
    {
        $k = $this->klaim($this->anggota(12), 'ditolak', 'x');

        $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'lanjut', 'dari' => 'ditolak'])
            ->assertSessionHas('error', fn($pesan) => str_contains($pesan, 'Buka kembali'));
    }

    public function test_tombol_buka_kembali_hanya_muncul_di_baris_yang_ditolak(): void
    {
        $this->klaim($this->anggota(12), 'ditolak', 'x');

        $this->admin()->get(route('ic.admin.klaim', ['status' => 'ditolak']))->assertOk()->assertSee('data-aksi="buka_kembali"', false);
        $this->admin()->get(route('ic.admin.klaim', ['status' => 'menunggu']))->assertDontSee('data-aksi="buka_kembali"', false);
    }

    // ---------- tampil / sembunyikan hadiah ----------

    public function test_tahap_hadiah_disembunyikan_hilang_dari_anggota_dan_tidak_bisa_diklaim(): void
    {
        $m = $this->anggota(30);
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertSee('Hadiah Pertama');

        $this->admin()->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 0])->assertSessionHas('success');
        $this->assertFalse(ic_reward::where('target_stempel', 10)->first()->aktif);

        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertDontSee('Hadiah Pertama');
        $this->actingAs($m, 'member')->post(route('ic.hadiah.klaim', 10))->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());
        $this->get(route('ic.tentang'))->assertDontSee('Hadiah Pertama');

        // Admin tetap melihat tahap itu (dengan tanda) dan bisa mengaktifkannya lagi.
        $this->admin()->get(route('ic.admin.hadiah'))->assertOk()->assertSee('Disembunyikan');
        $this->admin()->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 1])->assertSessionHas('success');
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertSee('Hadiah Pertama');
    }

    public function test_klaim_yang_sudah_masuk_tetap_diproses_walau_tahapnya_disembunyikan(): void
    {
        Mail::fake();
        $k = $this->klaim($this->anggota(12), 'diajukan');
        $this->admin()->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 0]);

        $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertSessionHas('success');
        $this->assertSame('ditinjau', $k->fresh()->status);
    }

    public function test_minimal_satu_tahap_hadiah_harus_aktif(): void
    {
        ic_reward::query()->where('target_stempel', '!=', 250)->update(['aktif' => false]);

        $this->admin()->patch(route('ic.admin.hadiah.aktif', 250), ['aktif' => 0])->assertSessionHas('error');
        $this->assertTrue(ic_reward::where('target_stempel', 250)->first()->aktif);
    }

    public function test_aksi_tampil_sembunyikan_butuh_admin_dan_nilai_valid(): void
    {
        $this->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 0])->assertRedirect(route('login'));
        $this->patch(route('ic.admin.pengumuman.aktif', 1), ['aktif' => 0])->assertRedirect(route('login'));

        $this->admin()->patch(route('ic.admin.hadiah.aktif', 10), ['aktif' => 'mungkin'])->assertSessionHasErrors('aktif');
        $this->admin()->patch(route('ic.admin.hadiah.aktif', 999), ['aktif' => 0])->assertNotFound();
        $this->admin()->patch(route('ic.admin.pengumuman.aktif', 999), ['aktif' => 0])->assertNotFound();
    }

    // ---------- tampil / sembunyikan pengumuman ----------

    public function test_pengumuman_disembunyikan_tidak_tampil_ke_anggota_tapi_tidak_dihapus(): void
    {
        $p = $this->broadcast(['judul' => 'Kabar Uji Sembunyi']);
        $m = $this->anggota();
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertSee('Kabar Uji Sembunyi');

        $this->admin()->patch(route('ic.admin.pengumuman.aktif', $p->id_broadcast), ['aktif' => 0])->assertSessionHas('success');

        $this->assertNotNull(ic_broadcast::find($p->id_broadcast)); // masih ada
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertDontSee('Kabar Uji Sembunyi');
        $this->admin()->get(route('ic.admin.pengumuman'))->assertSee('Kabar Uji Sembunyi')->assertSee('Disembunyikan');

        $this->admin()->patch(route('ic.admin.pengumuman.aktif', $p->id_broadcast), ['aktif' => 1]);
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertSee('Kabar Uji Sembunyi');
    }
}