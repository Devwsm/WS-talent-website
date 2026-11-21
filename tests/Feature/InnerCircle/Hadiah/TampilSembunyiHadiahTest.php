<?php

namespace Tests\Feature\InnerCircle\Hadiah;

use App\Models\ic_broadcast;
use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_reward;
use App\Models\member;
use App\Services\InnerCircle\PoinService;
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

    private function anggota(int $poin = 0): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->save();
        if ($poin > 0) {
            app(PoinService::class)->tambahManual($m, $poin, 'Uji', 'admin');
        }

        return $m->fresh();
    }

    private function klaim(member $m, string $status, ?string $catatan = null): ic_claim
    {
        $reward = ic_reward::where('harga_poin', 10000)->firstOrFail();
        $k = ic_claim::create(['member_id' => $m->id_member, 'reward_id' => $reward->id_reward, 'status' => $status, 'catatan_admin' => $catatan]);
        ic_claim_log::create(['claim_id' => $k->id_claim, 'status' => 'diajukan']);

        return $k;
    }

    /** Id hadiah menurut judulnya (katalog baru: Meet & Greet 150.000, Merchandise 250.000). */
    private function idHadiah(string $judul): int
    {
        return (int) ic_reward::where('judul', $judul)->value('id_reward');
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

    public function test_hadiah_disembunyikan_hilang_dari_anggota_dan_tidak_bisa_ditukar(): void
    {
        $m = $this->anggota(200000);
        $id = $this->idHadiah('Meet & Greet');
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertSee('Meet & Greet');

        $this->admin()->patch(route('ic.admin.hadiah.aktif', $id), ['aktif' => 0])->assertSessionHas('success');
        $this->assertFalse(ic_reward::find($id)->aktif);

        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertDontSee('Meet & Greet');
        $this->actingAs($m, 'member')->post(route('ic.hadiah.tukar', $id))->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());
        $this->assertSame(200000, $m->fresh()->poin_total);
        $this->get(route('ic.tentang'))->assertDontSee('Meet & Greet');

        // Admin tetap melihat hadiah itu (dengan tanda) dan bisa mengaktifkannya lagi.
        $this->admin()->get(route('ic.admin.hadiah', ['hadiah' => $id]))->assertOk()->assertSee('Disembunyikan');
        $this->admin()->patch(route('ic.admin.hadiah.aktif', $id), ['aktif' => 1])->assertSessionHas('success');
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertSee('Meet & Greet');
    }

    public function test_klaim_yang_sudah_masuk_tetap_diproses_walau_hadiahnya_disembunyikan(): void
    {
        Mail::fake();
        $k = $this->klaim($this->anggota(12000), 'diajukan');
        $this->admin()->patch(route('ic.admin.hadiah.aktif', $k->reward_id), ['aktif' => 0]);

        $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertSessionHas('success');
        $this->assertSame('ditinjau', $k->fresh()->status);
    }

    public function test_minimal_satu_hadiah_harus_aktif(): void
    {
        $id = $this->idHadiah('Merchandise');
        ic_reward::query()->where('id_reward', '!=', $id)->update(['aktif' => false]);

        $this->admin()->patch(route('ic.admin.hadiah.aktif', $id), ['aktif' => 0])->assertSessionHas('error');
        $this->assertTrue(ic_reward::find($id)->aktif);
    }

    public function test_aksi_tampil_sembunyikan_butuh_admin_dan_nilai_valid(): void
    {
        $id = $this->idHadiah('Meet & Greet');
        $this->patch(route('ic.admin.hadiah.aktif', $id), ['aktif' => 0])->assertRedirect(route('login'));
        $this->patch(route('ic.admin.pengumuman.aktif', 1), ['aktif' => 0])->assertRedirect(route('login'));

        $this->admin()->patch(route('ic.admin.hadiah.aktif', $id), ['aktif' => 'mungkin'])->assertSessionHasErrors('aktif');
        $this->admin()->patch(route('ic.admin.hadiah.aktif', 9999), ['aktif' => 0])->assertNotFound();
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