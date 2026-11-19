<?php

namespace Tests\Feature\InnerCircle\Poin;

use App\Models\member;
use App\Services\InnerCircle\PoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Tampilan poin: pemisah ribuan, satuan "poin", dan batas penambahan/pengurangan manual. */
class TampilanPoinTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(int $poin): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();
        app(PoinService::class)->tambahManual($m, $poin, 'Uji', 'admin');

        return $m->fresh();
    }

    public function test_ic_angka_memakai_titik_sebagai_pemisah_ribuan(): void
    {
        $this->assertSame('0', ic_angka(0));
        $this->assertSame('1.000', ic_angka(1000));
        $this->assertSame('250.000', ic_angka(250000));
        $this->assertSame('0', ic_angka(null));
    }

    public function test_beranda_hadiah_dan_profil_menampilkan_poin_dengan_pemisah_ribuan(): void
    {
        $m = $this->anggota(12000);

        $this->actingAs($m, 'member')->get(route('ic.beranda'))->assertOk()
            ->assertSee('12.000')->assertSee('25.000')->assertSee('poin lagi untuk membuka hadiah berikutnya.')
            ->assertDontSee('stempel');
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertOk()
            ->assertSee('12.000 poin terkumpul')->assertSee('10.000 poin · Hadiah Pertama', false)->assertDontSee('stempel');
        $this->actingAs($m, 'member')->get(route('ic.profil'))->assertOk()->assertSee('Total poin');
    }

    public function test_admin_melihat_poin_di_daftar_dan_detail_anggota(): void
    {
        $m = $this->anggota(12000);
        $admin = $this->withSession(['login' => true, 'user' => 'admin']);

        $admin->get(route('ic.admin.anggota'))->assertOk()->assertSee('12.000')->assertSee('Tambah poin');
        $detail = $admin->get(route('ic.admin.anggota.detail', $m->id_member))->assertOk()->assertSee('Riwayat poin');
        // Spasi dan baris baru di HTML dirapatkan dulu: formatter Blade di editor bisa memecah satu kalimat jadi beberapa baris.
        $teks = preg_replace('/\s+/', ' ', $detail->getContent());
        $this->assertStringContainsString('Maksimal 10.000 poin per penambahan.', $teks);
        $this->assertMatchesRegularExpression('/\+\s*12\.000/', $detail->getContent());
    }

    public function test_kurangi_poin_menolak_bila_melebihi_saldo_dengan_pesan_berformat(): void
    {
        $m = $this->anggota(1500);

        try {
            app(PoinService::class)->kurangi($m, 2000, 'Uji', 'admin');
            $this->fail('Seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('1.500 poin', $e->errors()['jumlah'][0]);
        }
        $this->assertSame(1500, $m->fresh()->poin_total);
    }

    public function test_batas_kurangi_oleh_admin_mengikuti_pengaturan(): void
    {
        $m = $this->anggota(5000);
        config(['inner_circle.poin.koreksi_maks' => 3000]);
        $admin = $this->withSession(['login' => true, 'user' => 'admin']);

        $admin->post(route('ic.admin.anggota.kurangi', $m->id_member), ['jumlah' => 3001, 'catatan' => 'x'])->assertSessionHasErrors('jumlah');
        $admin->post(route('ic.admin.anggota.kurangi', $m->id_member), ['jumlah' => 3000, 'catatan' => 'Salah input'])->assertSessionHas('success');
        $this->assertSame(2000, $m->fresh()->poin_total);
    }
}