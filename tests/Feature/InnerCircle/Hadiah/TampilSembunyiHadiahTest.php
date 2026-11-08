<?php

namespace Tests\Feature\InnerCircle\Hadiah;

use App\Models\ic_broadcast;
use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_reward;
use App\Models\member;
use App\Services\InnerCircle\StempelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tampil/sembunyikan tahap hadiah dan pengumuman dari CMS.
 */
class TampilSembunyiHadiahTest extends TestCase
{
    use RefreshDatabase;
    // ---------- halaman publik ----------
    // ---------- buka kembali klaim ----------
    // ---------- tampil / sembunyikan hadiah ----------
    // ---------- tampil / sembunyikan pengumuman ----------

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
