<?php

namespace Tests\Feature\InnerCircle\Admin;

use App\Models\ic_broadcast;
use App\Models\ic_memory;
use App\Models\log_aktivitas;
use App\Models\log_error;
use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ringkasan CMS Inner Circle: sapaan, pintasan membuka Inner Circle, dan gambaran tiap bagian seperti Dashboard CMS Artis.
 */
class RingkasanLengkapTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'staff@ws']);
    }

    public function test_ada_sapaan_nama_admin_dan_tombol_buka_inner_circle(): void
    {
        $html = $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/Selamat (pagi|siang|sore|malam),/', $html);
        $this->assertStringContainsString('staff@ws', $html);
        $this->assertStringContainsString('Buka Inner Circle', $html);
        $this->assertStringContainsString('href="' . route('ic.beranda') . '"', $html);
        $this->assertStringContainsString('Gambaran komunitas Inner Circle hari ini', $html);
    }

    public function test_menampilkan_ringkasan_tiap_bagian(): void
    {
        $anggota = member::create(['nama' => 'Budi Santoso', 'email' => 'budi@contoh.test', 'password' => 'SandiPanjangUji123']);
        ic_broadcast::create(['judul' => 'Kabar Gembira', 'isi' => 'Isi', 'cta_label' => 'Lihat', 'terbit_pada' => today(), 'aktif' => true]);
        ic_memory::create(['member_id' => $anggota->id_member, 'caption' => 'Seru banget', 'kategori' => 'festival', 'status' => 'tayang']);

        $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()
            ->assertSee('Anggota terbaru')->assertSee('Budi Santoso')
            ->assertSee('Tahap hadiah')
            ->assertSee('Pengumuman terbaru')->assertSee('Kabar Gembira')
            ->assertSee('Kenangan terbaru')->assertSee('Seru banget')
            ->assertSee('Log Inner Circle')->assertSee('Pengaturan cepat');
    }

    public function test_kartu_log_hanya_memuat_area_inner_circle(): void
    {
        log_aktivitas::create(['pelaku' => 'admin', 'kategori' => 'inner-circle', 'aksi' => 'Acara ditambahkan', 'status' => 'berhasil', 'perangkat' => 'Komputer Windows · Edge', 'created_at' => now()]);
        log_aktivitas::create(['pelaku' => 'admin', 'kategori' => 'konten', 'aksi' => 'Album ditambahkan', 'status' => 'berhasil', 'created_at' => now()]);
        log_error::create(['fingerprint' => 'a', 'area' => 'inner-circle', 'kelas' => 'RuntimeException', 'pesan' => 'x', 'jumlah' => 1, 'selesai' => false, 'terakhir_pada' => now()]);
        log_error::create(['fingerprint' => 'b', 'area' => 'artis', 'kelas' => 'RuntimeException', 'pesan' => 'y', 'jumlah' => 1, 'selesai' => false, 'terakhir_pada' => now()]);

        $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()
            ->assertSee('1 masalah masih terbuka')
            ->assertSee('Acara ditambahkan')->assertSee('Komputer Windows · Edge')
            ->assertDontSee('Album ditambahkan');
    }

    public function test_ringkasan_kosong_tidak_error(): void
    {
        $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()
            ->assertSee('Belum ada anggota yang mendaftar.')
            ->assertSee('Belum ada pengumuman.')
            ->assertSee('Tidak ada masalah yang terbuka');
    }
}