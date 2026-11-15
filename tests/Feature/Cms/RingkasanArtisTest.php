<?php

namespace Tests\Feature\Cms;

use App\Models\bio;
use App\Models\genre;
use App\Models\log_aktivitas;
use App\Models\log_error;
use App\Models\profile_hero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard CMS Artis: ringkasan lengkap (angka, profil, log) dan tanpa bagian WS Inner Circle.
 */
class RingkasanArtisTest extends TestCase
{
    use RefreshDatabase;

    private function dashboard()
    {
        return $this->withSession(['login' => true, 'user' => 'staff@ws'])->get(route('dashboard'))->assertOk();
    }

    public function test_bagian_ws_inner_circle_tidak_ada_lagi_di_dashboard_artis(): void
    {
        $this->dashboard()->assertDontSee('WS Inner Circle');
    }

    public function test_menampilkan_angka_ringkas_profil_dan_log(): void
    {
        profile_hero::create(['foto' => 'x.webp', 'judul_singkat' => 'DJ & Produser', 'nama' => 'Whisnu Santika', 'tagline' => 'Tagline contoh']);
        bio::create(['konten' => '<p>Bio <b>singkat</b> artis.</p>']);
        genre::create(['nama_genre' => 'House']);

        $this->dashboard()
            ->assertSee('Isi profil')
            ->assertSee('Whisnu Santika')->assertSee('Tagline contoh')
            ->assertSee('Bio singkat artis.')
            ->assertSee('Media coverage')->assertSee('Booking')
            ->assertSee('Tidak ada masalah yang terbuka');
    }

    public function test_profil_kosong_mengajak_mengisi(): void
    {
        $this->dashboard()->assertSee('Hero profil belum diisi.')->assertSee('Bio belum diisi.');
    }

    public function test_kartu_log_menghitung_error_login_gagal_dan_perubahan_terbaru(): void
    {
        log_error::create(['fingerprint' => 'a', 'area' => 'artis', 'kelas' => 'RuntimeException', 'pesan' => 'x', 'jumlah' => 1, 'selesai' => false, 'terakhir_pada' => now()]);
        log_error::create(['fingerprint' => 'b', 'area' => 'inner-circle', 'kelas' => 'RuntimeException', 'pesan' => 'y', 'jumlah' => 1, 'selesai' => false, 'terakhir_pada' => now()]);
        log_aktivitas::create(['kategori' => 'akses', 'aksi' => 'Gagal masuk ke dashboard', 'status' => 'gagal', 'jumlah' => 3, 'ip' => '203.0.113.9', 'created_at' => now()]);
        log_aktivitas::create(['kategori' => 'akses', 'aksi' => 'Gagal masuk ke dashboard', 'status' => 'gagal', 'jumlah' => 5, 'created_at' => now()->subDays(2)]);
        log_aktivitas::create(['pelaku' => 'admin', 'kategori' => 'konten', 'aksi' => 'Album ditambahkan', 'status' => 'berhasil', 'perangkat' => 'HP Android · Chrome', 'created_at' => now()]);
        log_aktivitas::create(['pelaku' => 'admin', 'kategori' => 'inner-circle', 'aksi' => 'Acara ditambahkan', 'status' => 'berhasil', 'created_at' => now()]);

        $this->dashboard()
            ->assertSee('1 masalah masih terbuka')          // error Inner Circle tidak ikut
            ->assertSee('3x login gagal dalam 24 jam')      // yang lebih lama dari 24 jam tidak ikut
            ->assertSee('Album ditambahkan')->assertSee('HP Android · Chrome')
            ->assertDontSee('Acara ditambahkan')            // perubahan CMS Inner Circle bukan milik log ini
            ->assertDontSee('203.0.113.9');                 // IP tidak tampil di ringkasan
    }

    public function test_bila_inner_circle_mati_kartu_log_menghitung_semua_error(): void
    {
        log_error::create(['fingerprint' => 'b', 'area' => 'inner-circle', 'kelas' => 'RuntimeException', 'pesan' => 'y', 'jumlah' => 1, 'selesai' => false, 'terakhir_pada' => now()]);
        config(['inner_circle.aktif' => false]);

        $this->dashboard()->assertSee('1 masalah masih terbuka');
    }
}