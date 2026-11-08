<?php

namespace Tests\Feature\InnerCircle\Hadiah;

use App\Mail\InnerCircle\StatusKlaimMail;
use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_reward;
use App\Models\member;
use App\Services\InnerCircle\StempelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Klaim yang ditolak bisa dibuka kembali oleh admin.
 */
class BukaKembaliKlaimTest extends TestCase
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
}
