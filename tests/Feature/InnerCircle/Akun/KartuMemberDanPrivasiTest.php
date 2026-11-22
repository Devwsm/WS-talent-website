<?php

namespace Tests\Feature\InnerCircle\Akun;

use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profil: kartu member, ganti nama (boleh berspasi), pengaduan di Akun dan privasi; daftar wajib centang privasi.
 */
class KartuMemberDanPrivasiTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(string $nama = 'Budi Santoso'): member
    {
        $m = member::create(['nama' => $nama, 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    public function test_profil_menampilkan_kartu_member_dan_pengaduan_ada_di_akun_dan_privasi(): void
    {
        $m = $this->anggota();
        $html = $this->actingAs($m, 'member')->get(route('ic.profil'))->assertOk()
            ->assertSee('ic-kartu-anggota--detail', false)
            ->assertSee('Budi Santoso')
            ->assertSee($m->kode_member)
            ->assertSee('Ganti nama')
            // Satu kartu: pensil membuka satu popup untuk foto dan nama; tidak ada lagi panel foto terpisah.
            ->assertSee('data-ic-modal-buka="modal-profil"', false)
            ->assertSee('id="modal-profil"', false)
            ->assertSee('id="ic-foto-input"', false)
            ->assertDontSee('ic-profil-editor', false)
            ->assertSee(route('ic.pengaduan'), false)
            ->getContent();

        // Tombol pengaduan berada sesudah judul "Akun dan privasi", bukan kartu terpisah di atas.
        $this->assertGreaterThan(strpos($html, 'Akun dan privasi'), strpos($html, route('ic.pengaduan')));
    }

    public function test_ganti_nama_boleh_berspasi_dan_dirapikan(): void
    {
        $m = $this->anggota();

        $this->actingAs($m, 'member')->patch(route('ic.profil.nama'), ['nama' => '  Ancha   Maulana Putri '])
            ->assertRedirect(route('ic.profil'));

        $this->assertSame('Ancha Maulana Putri', $m->fresh()->nama);
    }

    public function test_ganti_nama_ditolak_bila_kosong_terlalu_pendek_atau_berisi_simbol_aneh(): void
    {
        $m = $this->anggota();

        foreach (['', 'A', '<script>x</script>', str_repeat('a', 101)] as $nama) {
            $this->actingAs($m, 'member')->patch(route('ic.profil.nama'), ['nama' => $nama])->assertSessionHasErrors('nama');
        }
        $this->assertSame('Budi Santoso', $m->fresh()->nama);
    }

    public function test_ganti_nama_butuh_login(): void
    {
        $this->patch(route('ic.profil.nama'), ['nama' => 'Siapa Saja'])->assertRedirect(route('ic.masuk'));
    }

    // ---------- persetujuan privasi saat daftar ----------

    private function data(array $ubah = []): array
    {
        return $ubah + ['nama' => 'Anggota Baru', 'email' => 'baru@contoh.test', 'sandi' => 'Sandi-12345678', 'sandi_confirmation' => 'Sandi-12345678'];
    }

    public function test_daftar_tanpa_centang_privasi_ditolak(): void
    {
        $this->from(route('ic.daftar'))->post(route('ic.daftar.proses'), $this->data())
            ->assertSessionHasErrors(['setuju_privasi' => 'Centang persetujuan Kebijakan Privasi untuk membuat akun.']);
        $this->assertSame(0, member::count());
    }

    public function test_daftar_dengan_centang_menyimpan_bukti_persetujuan(): void
    {
        $this->post(route('ic.daftar.proses'), $this->data(['setuju_privasi' => 1]))->assertRedirect();

        $m = member::where('email', 'baru@contoh.test')->firstOrFail();
        $this->assertNotNull($m->privasi_disetujui_pada);
        $this->assertSame('2026-11-22', $m->privasi_versi);
    }

    public function test_halaman_daftar_punya_kotak_centang_privasi_yang_menjaga_tombol_google(): void
    {
        config(['inner_circle.google.aktif' => true, 'services.google.client_id' => 'x', 'services.google.client_secret' => 'y']);

        $this->get(route('ic.daftar'))->assertOk()
            ->assertSee('name="setuju_privasi"', false)
            ->assertSee('form="form-daftar"', false)
            ->assertSee('data-ic-butuh-setuju="#setuju-privasi"', false)
            ->assertSee(route('privasi'), false);
    }
}