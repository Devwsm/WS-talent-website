<?php

namespace Tests\Feature\InnerCircle\Komunitas;

use App\Models\ic_memory;
use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Penyuka kenangan ala IG: tumpukan foto profil + "Disukai oleh Nama dan N lainnya" di kartu, dan daftar penyuka (JSON).
 */
class PenyukaKenanganTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(string $nama, string $email): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m->fresh();
    }

    private function kenangan(member $pemilik, array $ubah = []): ic_memory
    {
        return ic_memory::create($ubah + ['member_id' => $pemilik->id_member, 'caption' => 'Seru', 'kategori' => 'festival', 'status' => 'tayang']);
    }

    private function suka(member $m, ic_memory $k, $waktu): void
    {
        DB::table('ic_memory_likes')->insert(['member_id' => $m->id_member, 'memory_id' => $k->id_memory, 'created_at' => $waktu]);
    }

    public function test_galeri_menampilkan_foto_profil_penyuka_terbaru_dan_sisanya(): void
    {
        $pemilik = $this->anggota('Pemilik Kenangan', 'p@contoh.test');
        $lama = $this->anggota('Rani Putri', 'r@contoh.test');
        $baru = $this->anggota('Sinta Dewi', 's@contoh.test');
        $baru->forceFill(['avatar' => 'inner-circle/avatar/sinta.webp'])->save();

        $k = $this->kenangan($pemilik);
        $this->suka($lama, $k, now()->subMinute());
        $this->suka($baru, $k, now());

        $html = $this->actingAs($pemilik, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertOk()
            ->assertSee('data-ic-penyuka="' . $k->id_memory . '"', false)
            ->assertSee('id="modal-penyuka"', false)
            ->assertSee(Storage::url('inner-circle/avatar/sinta.webp'), false)
            ->getContent();

        // Spasi dan baris baru di markup tidak penting (formatter Blade bisa memecah baris).
        $this->assertMatchesRegularExpression('/Disukai oleh\s*<b>Sinta Dewi<\/b>\s*dan\s*<b>1 lainnya<\/b>/', $html);
    }

    public function test_satu_penyuka_tanpa_kata_dan_lainnya(): void
    {
        $pemilik = $this->anggota('Pemilik Kenangan', 'p@contoh.test');
        $rani = $this->anggota('Rani Putri', 'r@contoh.test');
        $k = $this->kenangan($pemilik);
        $this->suka($rani, $k, now());

        $html = $this->actingAs($pemilik, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/Disukai oleh\s*<b>Rani Putri<\/b>\s*<\/span>/', $html);
    }

    public function test_ringkasan_tersembunyi_selama_belum_ada_yang_suka(): void
    {
        $pemilik = $this->anggota('Pemilik Kenangan', 'p@contoh.test');
        $k = $this->kenangan($pemilik);

        $html = $this->actingAs($pemilik, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<button[^>]*data-ic-penyuka="' . $k->id_memory . '"[^>]*\shidden\s*>/', $html);
    }

    public function test_halaman_detail_memuat_ringkasan_dan_popup_penyuka(): void
    {
        $pemilik = $this->anggota('Pemilik Kenangan', 'p@contoh.test');
        $rani = $this->anggota('Rani Putri', 'r@contoh.test');
        $k = $this->kenangan($pemilik);
        $this->suka($rani, $k, now());

        $this->actingAs($pemilik, 'member')->get(route('ic.kenangan.detail', $k->id_memory))->assertOk()
            ->assertSee('data-ic-penyuka="' . $k->id_memory . '"', false)
            ->assertSee('id="modal-penyuka"', false)
            ->assertSee('Rani Putri');
    }

    public function test_daftar_penyuka_json_terbaru_dulu_dengan_foto_atau_emoji(): void
    {
        $pemilik = $this->anggota('Pemilik Kenangan', 'p@contoh.test');
        $lama = $this->anggota('Rani Putri', 'r@contoh.test');
        $baru = $this->anggota('Sinta Dewi', 's@contoh.test');
        $baru->forceFill(['avatar' => 'inner-circle/avatar/sinta.webp'])->save();

        $k = $this->kenangan($pemilik);
        $this->suka($lama, $k, now()->subMinute());
        $this->suka($baru, $k, now());

        $this->actingAs($pemilik, 'member')->getJson(route('ic.kenangan.penyuka', $k->id_memory))
            ->assertOk()
            ->assertJsonPath('jumlah', 2)
            ->assertJsonPath('penyuka.0.nama', 'Sinta Dewi')
            ->assertJsonPath('penyuka.0.foto', Storage::url('inner-circle/avatar/sinta.webp'))
            ->assertJsonPath('penyuka.1.nama', 'Rani Putri')
            ->assertJsonPath('penyuka.1.foto', null)
            ->assertJsonPath('penyuka.1.emoji', '😎');
    }

    public function test_daftar_penyuka_kenangan_disembunyikan_404_dan_tamu_diarahkan_masuk(): void
    {
        $pemilik = $this->anggota('Pemilik Kenangan', 'p@contoh.test');
        $tersembunyi = $this->kenangan($pemilik, ['status' => 'disembunyikan']);

        $this->actingAs($pemilik, 'member')->getJson(route('ic.kenangan.penyuka', $tersembunyi->id_memory))->assertNotFound();

        auth('member')->logout();
        $this->get(route('ic.kenangan.penyuka', $tersembunyi->id_memory))->assertRedirect(route('ic.masuk'));
    }
}