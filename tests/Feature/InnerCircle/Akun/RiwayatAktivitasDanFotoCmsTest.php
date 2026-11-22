<?php

namespace Tests\Feature\InnerCircle\Akun;

use App\Models\ic_gl_antrean;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\PoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Profil: Riwayat aktivitas (poin + GL dalam satu daftar). CMS: foto profil anggota ikut tampil. */
class RiwayatAktivitasDanFotoCmsTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(string $nama = 'Anggota Uji', string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m->fresh();
    }

    private function acara(string $venue = 'Superhouse Satrio')
    {
        return app(AcaraService::class)->buat(['tanggal' => now()->addDays(3)->toDateString(), 'tipe' => 'club', 'venue' => $venue, 'kota' => 'Jakarta']);
    }

    /** Teks halaman tanpa tag, spasi dan baris baru dirapatkan (aman dari formatter Blade). */
    private function teks($respons): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($respons->getContent()), ENT_QUOTES | ENT_HTML5)));
    }

    public function test_profil_menggabungkan_poin_dan_gl_dalam_riwayat_aktivitas(): void
    {
        $m = $this->anggota();
        app(PoinService::class)->tambahManual($m, 1000, 'Poin uji', 'admin-uji');
        $e = $this->acara();
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member, 'status' => ic_gl_antrean::MENANG]);

        $teks = $this->teks($this->actingAs($m, 'member')->get(route('ic.profil'))->assertOk());

        $this->assertStringContainsString('Riwayat aktivitas', $teks);
        $this->assertStringNotContainsString('Riwayat poin', $teks);
        $this->assertStringContainsString('+1.000', $teks);
        $this->assertStringContainsString('GL gratis · Superhouse Satrio', $teks);
        $this->assertStringContainsString('Menang GL', $teks);
    }

    public function test_riwayat_gl_menaut_ke_detail_acara_dan_menampilkan_status_antri(): void
    {
        $m = $this->anggota();
        $e = $this->acara('Black Owl');
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member, 'status' => ic_gl_antrean::ANTRI]);

        $this->actingAs($m, 'member')->get(route('ic.profil'))->assertOk()
            ->assertSee(route('ic.acara.detail', $e->kode), false)
            ->assertSee('Dalam antrean');
    }

    public function test_cms_menampilkan_foto_profil_anggota(): void
    {
        $m = $this->anggota('Sinta Dewi', 's@contoh.test');
        $m->forceFill(['avatar' => 'inner-circle/avatar/sinta.webp'])->save();
        $foto = Storage::url('inner-circle/avatar/sinta.webp');
        $admin = $this->withSession(['login' => true, 'user' => 'admin-uji']);

        $admin->get(route('ic.admin.anggota'))->assertOk()->assertSee($foto, false)->assertSee('Sinta Dewi');
        $admin->get(route('ic.admin.anggota.detail', $m->id_member))->assertOk()->assertSee($foto, false);
    }
}