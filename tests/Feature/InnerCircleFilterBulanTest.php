<?php

namespace Tests\Feature;

use App\Models\ic_event;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Filter bulan jadwal: pilihannya dari data acara (bukan daftar tetap Oktober-Desember), di area anggota dan di dashboard. */
class InnerCircleFilterBulanTest extends TestCase
{
    use RefreshDatabase;

    private function acara(string $tanggal, string $venue): ic_event
    {
        return app(AcaraService::class)->buat([
            'tanggal' => $tanggal,
            'tipe' => 'club',
            'venue' => $venue,
            'kota' => 'Jakarta',
        ]);
    }

    private function anggota(): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    public function test_pilihan_bulan_mengikuti_acara_yang_ada_termasuk_tahun_berikutnya(): void
    {
        $this->acara('2026-12-20', 'Klub Desember');
        $this->acara('2027-01-15', 'Klub Januari');
        $this->acara('2027-01-30', 'Klub Januari Dua');

        $this->assertSame(
            ['2026-12' => 'Desember 2026', '2027-01' => 'Januari 2027'],
            ic_event::pilihanBulan()->all(),
        );
    }

    public function test_anggota_bisa_memfilter_acara_januari_2027(): void
    {
        $this->acara('2026-12-20', 'Klub Desember');
        $this->acara('2027-01-15', 'Klub Januari');

        $html = $this->actingAs($this->anggota(), 'member')
            ->get(route('ic.checkin', ['bulan' => '2027-01']))
            ->assertOk()
            ->assertSee('Januari 2027')
            ->getContent();

        $this->assertStringContainsString('Klub Januari', $html);
        $this->assertStringNotContainsString('Klub Desember', $html);
    }

    public function test_bulan_yang_tidak_dikenal_atau_tautan_lama_dianggap_semua(): void
    {
        $this->acara('2026-12-20', 'Klub Desember');

        $m = $this->anggota();
        foreach (['10', '2030-05', 'abc', '2026-13'] as $nilai) {
            $this->actingAs($m, 'member')
                ->get(route('ic.checkin', ['bulan' => $nilai]))
                ->assertOk()
                ->assertSee('Klub Desember');
        }
    }

    public function test_judul_jadwal_tidak_lagi_memuat_kuartal_tetap(): void
    {
        $this->acara('2027-02-10', 'Klub Februari');

        $this->actingAs($this->anggota(), 'member')
            ->get(route('ic.checkin'))
            ->assertOk()
            ->assertDontSee('Kuartal IV')
            ->assertDontSee('Okt–Des');
    }

    public function test_dashboard_acara_memfilter_per_bulan_dan_tahun(): void
    {
        $this->acara('2026-12-20', 'Klub Desember');
        $this->acara('2027-01-15', 'Klub Januari');

        // Panel QR di halaman yang sama memuat acara terdekat, jadi yang dipastikan hanya: pilihan bulan tampil
        // dan acara Januari ada di jadwal hasil filter.
        $this->withSession(['login' => true, 'user' => 'admin'])
            ->get(route('ic.admin.acara', ['bulan' => '2027-01']))
            ->assertOk()
            ->assertSee('Januari 2027')
            ->assertSee('Klub Januari');
    }
}