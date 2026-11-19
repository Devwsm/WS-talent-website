<?php

namespace Tests\Feature\InnerCircle\Acara;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Aturan baru Inner Circle, Fase 2: fondasi acara (kolom GL, tabel antrean) dan menu Acara yang dibagi dua
 * (Pengaturan acara dan Hari H).
 */
class FondasiAcaraTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function acara(string $tanggal = '2030-05-01', string $venue = 'Club Satu'): ic_event
    {
        return app(AcaraService::class)->buat(['tanggal' => $tanggal, 'tipe' => 'club', 'kota' => 'Jakarta', 'venue' => $venue]);
    }

    private function anggota(string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    // ---------- Menu Acara dibagi dua ----------

    public function test_pengaturan_dan_hari_h_terbuka_dengan_navbar_atas(): void
    {
        $e = $this->acara();

        foreach ([route('ic.admin.acara'), route('ic.admin.acara.harih')] as $url) {
            $this->admin()->get($url)->assertOk()
                ->assertSee('Bagian menu Acara', false)
                ->assertSee('Pengaturan acara')
                ->assertSee('Hari H');
        }

        // Pengaturan memuat form dan jadwal; QR ada di Hari H.
        $this->admin()->get(route('ic.admin.acara'))->assertSee('Tambah acara')->assertDontSee('Pembuat QR check-in');
        $this->admin()->get(route('ic.admin.acara.harih'))->assertSee('Pembuat QR check-in')->assertSee('<svg', false)
            ->assertSee($e->token);
    }

    public function test_alamat_lama_panel_qr_dialihkan_ke_hari_h(): void
    {
        $e = $this->acara();

        $this->admin()->get(route('ic.admin.acara', ['acara' => $e->kode]))
            ->assertRedirect(route('ic.admin.acara.harih', ['acara' => $e->kode]) . '#qr');
    }

    public function test_hari_h_butuh_login_admin(): void
    {
        $this->get(route('ic.admin.acara.harih'))->assertRedirect(route('login'));
    }

    public function test_tambah_acara_kembali_ke_pengaturan_bukan_hari_h(): void
    {
        $this->admin()->post(route('ic.admin.acara.simpan'), ['tanggal' => '2030-06-01', 'tipe' => 'club', 'venue' => 'Klub Baru', 'kota' => 'Bandung'])
            ->assertRedirect(route('ic.admin.acara') . '#jadwal');
    }

    public function test_token_baru_kembali_ke_hari_h(): void
    {
        $e = $this->acara();

        $this->admin()->post(route('ic.admin.acara.token', $e->kode))
            ->assertRedirect(route('ic.admin.acara.harih', ['acara' => $e->kode]) . '#qr');
    }

    // ---------- Kolom acara baru ----------

    public function test_acara_baru_punya_bawaan_aman_untuk_kolom_gl(): void
    {
        $e = $this->acara('2030-05-10')->fresh();

        $this->assertNull($e->kuota_gl);
        $this->assertFalse($e->punya_gl);
        $this->assertFalse($e->glLengkap());
        $this->assertSame(ic_event::UNDIAN_BELUM, $e->status_undian);
        $this->assertNull($e->poster);
    }

    public function test_waktu_undian_bawaan_hari_h_pukul_18_00_dan_bisa_diganti(): void
    {
        $e = $this->acara('2030-05-10')->fresh();

        $this->assertSame('2030-05-10 18:00:00', $e->undian_efektif->format('Y-m-d H:i:s'));

        $e->update(['undian_pada' => '2030-05-09 12:30:00']);
        $this->assertSame('2030-05-09 12:30:00', $e->fresh()->undian_efektif->format('Y-m-d H:i:s'));
    }

    public function test_gl_lengkap_butuh_kuota_username_dan_sandi(): void
    {
        $e = $this->acara();

        $e->update(['kuota_gl' => 10, 'username_acara' => 'tamu-ws']);
        $this->assertFalse($e->fresh()->glLengkap());

        $e->update(['sandi_acara' => 'rahasia-acara']);
        $this->assertTrue($e->fresh()->glLengkap());
    }

    public function test_sandi_acara_tersimpan_terenkripsi_dan_tidak_ikut_serialisasi(): void
    {
        $e = $this->acara();
        $e->update(['sandi_acara' => 'rahasia-acara']);

        $mentah = DB::table('ic_events')->where('id_event', $e->id_event)->value('sandi_acara');
        $this->assertNotSame('rahasia-acara', $mentah);
        $this->assertSame('rahasia-acara', $e->fresh()->sandi_acara);
        $this->assertArrayNotHasKey('sandi_acara', $e->fresh()->toArray());
        $this->assertArrayNotHasKey('token', $e->fresh()->toArray());
    }

    public function test_sandi_acara_yang_tidak_terbaca_jadi_null_bukan_galat(): void
    {
        $e = $this->acara();
        DB::table('ic_events')->where('id_event', $e->id_event)->update(['sandi_acara' => 'bukan-terenkripsi']);

        $baru = $e->fresh();
        $this->assertNull($baru->sandi_acara);
        $this->assertTrue($baru->sandiRusak());
        $this->admin()->get(route('ic.admin.acara'))->assertOk();
        $this->admin()->get(route('ic.admin.acara.harih'))->assertOk();
    }

    // ---------- Antrean GL ----------

    public function test_satu_anggota_hanya_sekali_antre_per_acara(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member]);

        $this->expectException(QueryException::class);
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member]);
    }

    public function test_antrean_baru_berstatus_antri_dan_bisa_dibaca_lewat_acara(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member]);

        $baris = $e->antreanGl()->first();
        $this->assertSame(ic_gl_antrean::ANTRI, $baris->status);
        $this->assertFalse($baris->menang());
        $this->assertSame($m->id_member, $baris->anggota->id_member);
    }

    public function test_menghapus_acara_ikut_menghapus_antreannya(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member]);

        $this->admin()->delete(route('ic.admin.acara.hapus', $e->kode))->assertSessionHas('success');
        $this->assertSame(0, ic_gl_antrean::count());
    }
}