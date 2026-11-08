<?php

namespace Tests\Feature\InnerCircle\Komunitas;

use App\Models\ic_broadcast;
use App\Models\ic_memory;
use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lencana "belum dibaca" di tab Komunitas.
 */
class LencanaBelumDibacaTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Pratinjau replika ----------
    // ---------- Lencana belum dibaca ----------
    // ---------- Logo, favicon, Open Graph ----------

    private function anggota(string $nama = 'Ancha Uji', string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m->fresh();
    }

    private function pengumuman(array $ubah = []): ic_broadcast
    {
        return ic_broadcast::create($ubah + [
            'judul' => 'Kabar uji',
            'isi' => 'Isi uji',
            'cta_label' => 'Lihat',
            'terbit_pada' => today()->toDateString(),
            'aktif' => true,
        ]);
    }

    private function kenangan(member $pemilik, array $ubah = []): ic_memory
    {
        return ic_memory::create($ubah + ['member_id' => $pemilik->id_member, 'caption' => 'Seru', 'kategori' => 'festival', 'status' => 'tayang']);
    }

    public function test_lencana_tersembunyi_bila_tidak_ada_yang_baru(): void
    {
        $m = $this->anggota();

        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertOk()->assertDontSee('ic-tab__lencana');
    }

    public function test_isi_lama_sebelum_bergabung_tidak_jadi_lencana(): void
    {
        $penulis = $this->anggota('Penulis', 'p@contoh.test');
        $lama = $this->kenangan($penulis);
        $lama->forceFill(['created_at' => now()->subDay()])->save();
        $this->pengumuman()->forceFill(['created_at' => now()->subDay(), 'terbit_pada' => today()->subDay()->toDateString()])->save();

        $baru = $this->anggota('Anggota Baru', 'b@contoh.test');

        $this->actingAs($baru, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertDontSee('ic-tab__lencana');
    }

    public function test_lencana_muncul_untuk_isi_baru_dan_hilang_setelah_tabnya_dibuka(): void
    {
        $m = $this->anggota('Pembaca', 'r@contoh.test');
        $m->forceFill(['created_at' => now()->subWeek(), 'pengumuman_dibaca_pada' => now()->subDay(), 'galeri_dibaca_pada' => now()->subDay()])->save();

        $penulis = $this->anggota('Penulis', 'p@contoh.test');
        $this->kenangan($penulis);
        $this->kenangan($penulis);
        $this->kenangan($m); // milik sendiri tidak dihitung
        $this->kenangan($penulis, ['status' => 'disembunyikan']); // tidak tayang tidak dihitung
        $this->pengumuman();

        // Tab Pengumuman terbuka: lencana pengumuman hilang, galeri masih menunjuk 2 kenangan baru.
        $html = $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'ic-tab__lencana'));
        $this->assertStringContainsString('>2<span class="ic-sr-saja"> belum dibaca', $html);
        $this->assertNotNull($m->fresh()->pengumuman_dibaca_pada);
        $this->assertTrue($m->fresh()->pengumuman_dibaca_pada->isToday());

        // Sudah dibaca: membuka lagi tidak memunculkan lencana pengumuman.
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertSee('>2<span class="ic-sr-saja">', false);

        // Membuka galeri menghabiskan lencana galeri.
        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertOk();
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertDontSee('ic-tab__lencana');
    }

    public function test_pengumuman_terjadwal_baru_dihitung_setelah_tanggalnya_tiba(): void
    {
        $m = $this->anggota();
        $m->forceFill(['pengumuman_dibaca_pada' => now()->subDay()])->save();
        $this->pengumuman(['terbit_pada' => today()->addDays(3)->toDateString()])->forceFill(['created_at' => now()->subDays(5)])->save();

        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertDontSee('ic-tab__lencana');

        $this->travel(4)->days();
        $this->actingAs($m->fresh(), 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertSee('>1<span class="ic-sr-saja">', false);
    }
}
