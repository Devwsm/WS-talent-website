<?php

namespace Tests\Feature\Admin;

use App\Models\account;
use App\Models\albums;
use App\Models\ic_reward;
use App\Models\log_aktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman Log > Aktivitas: perubahan admin tercatat lengkap (apa, siapa, kapan, perangkat) tanpa isi form dan tanpa IP,
 * sedangkan login hanya dicatat bila gagal (satu-satunya yang menyimpan IP).
 */
class LogAktivitasTest extends TestCase
{
    use RefreshDatabase;

    private const SANDI = 'SandiPanjangUji123';

    private const UA_HP = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36';

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function akun(?string $email = 'admin@contoh.test'): account
    {
        return account::create(['username' => 'admin', 'email' => $email, 'password' => Hash::make(self::SANDI)]);
    }

    /** Id hadiah uji: tahap lama 10.000 poin (hadiah mana pun cukup untuk menguji tampil/sembunyikan). */
    private function idHadiah(): int
    {
        return (int) ic_reward::where('harga_poin', 10000)->value('id_reward');
    }

    private function login(array $o = [])
    {
        return $this->post(route('login.proses'), $o + ['username' => 'admin', 'password' => self::SANDI]);
    }

    public function test_aksi_tulis_admin_tercatat_dengan_teks_jelas_dan_isi_form_tidak_disimpan(): void
    {
        $this->admin()->patch(route('ic.admin.hadiah.aktif', $this->idHadiah()), ['aktif' => 0, 'rahasia' => 'JANGAN-DISIMPAN'])
            ->assertRedirect();

        $baris = log_aktivitas::first();
        $reward = ic_reward::find($this->idHadiah());
        $this->assertSame('Tampilan hadiah diubah', $baris->aksi);
        $this->assertSame('Hadiah “' . $reward->judul . '” disembunyikan.', $baris->keterangan);
        $this->assertSame('admin', $baris->pelaku);
        $this->assertSame('inner-circle', $baris->kategori);
        $this->assertSame('berhasil', $baris->status);
        $this->assertStringNotContainsString('JANGAN-DISIMPAN', json_encode($baris->toArray()));
    }

    public function test_perubahan_tercatat_dengan_nama_butir_perangkat_dan_tanpa_ip(): void
    {
        Storage::fake('public');
        $this->admin()->withHeader('User-Agent', self::UA_HP)->post(route('albums.tambah'), [
            'albums_name' => 'Judul Album Baru',
            'link_spotify' => 'https://open.spotify.com/album/abc',
            'albums_cover' => UploadedFile::fake()->image('c.jpg', 600, 600),
        ])->assertRedirect();

        $baris = log_aktivitas::first();
        $this->assertSame('Album ditambahkan', $baris->aksi);
        $this->assertSame('“Judul Album Baru” ditambahkan.', $baris->keterangan);
        $this->assertSame('konten', $baris->kategori);
        $this->assertSame('HP Android · Chrome', $baris->perangkat);
        $this->assertNull($baris->ip);
    }

    public function test_hapus_dan_ubah_memakai_nama_butir_yang_benar(): void
    {
        $album = albums::create(['albums_name' => 'Lama', 'link_spotify' => 'https://x.test', 'albums_cover' => 'x.webp']);

        $this->admin()->put(route('albums.update', $album->id_albums), ['albums_name' => 'Nama Baru', 'link_spotify' => 'https://open.spotify.com/a'])->assertRedirect();
        $this->assertSame('Data “Nama Baru” diperbarui.', log_aktivitas::where('aksi', 'Album diubah')->value('keterangan'));

        // Nama dibaca sebelum dihapus, karena sesudahnya barisnya sudah tidak ada.
        $this->admin()->delete(route('albums.hapus', $album->id_albums))->assertRedirect();
        $this->assertSame('“Nama Baru” dihapus dari website.', log_aktivitas::where('aksi', 'Album dihapus')->value('keterangan'));
    }

    public function test_gagal_validasi_tidak_dicatat_tapi_penolakan_aturan_dicatat_sebagai_gagal(): void
    {
        $this->admin()->patch(route('ic.admin.hadiah.aktif', $this->idHadiah()), ['aktif' => 'sembarang'])->assertSessionHasErrors('aktif');
        $this->assertSame(0, log_aktivitas::count());

        // Menyembunyikan tahap terakhir yang aktif ditolak aturan bisnis -> dicatat sebagai gagal, dengan pesan dari sistem.
        ic_reward::query()->where('id_reward', '!=', $this->idHadiah())->update(['aktif' => false]);
        $this->admin()->patch(route('ic.admin.hadiah.aktif', $this->idHadiah()), ['aktif' => 0]);

        $baris = log_aktivitas::first();
        $this->assertSame('gagal', $baris->status);
        $this->assertSame('Tampilan hadiah gagal diubah', $baris->aksi);
        $this->assertSame('Minimal satu hadiah harus tetap aktif.', $baris->keterangan);
    }

    public function test_klik_ganda_pada_aksi_yang_sama_dicatat_sekali(): void
    {
        $this->admin()->patch(route('ic.admin.hadiah.aktif', $this->idHadiah()), ['aktif' => 0]);
        $this->admin()->patch(route('ic.admin.hadiah.aktif', $this->idHadiah()), ['aktif' => 0]);
        $this->assertSame(1, log_aktivitas::count());

        // Aksi berbeda (hadiah lain, atau nilai lain) tetap tercatat.
        $this->admin()->patch(route('ic.admin.hadiah.aktif', $this->idHadiah()), ['aktif' => 1]);
        $this->assertSame(2, log_aktivitas::count());
    }

    public function test_aksi_mengelola_log_dan_pencarian_alamat_tidak_dicatat(): void
    {
        $this->admin()->delete(route('log.error.bersihkan'))->assertRedirect();
        $this->admin()->delete(route('ic.admin.log.error.bersihkan'))->assertRedirect();

        $this->assertSame(0, log_aktivitas::count());
    }

    public function test_membuka_halaman_tidak_dicatat_dan_halaman_aktivitas_bisa_disaring(): void
    {
        $this->admin()->get(route('ic.admin.hadiah'))->assertOk();
        $this->assertSame(0, log_aktivitas::count());

        $this->admin()->patch(route('ic.admin.hadiah.aktif', $this->idHadiah()), ['aktif' => 0]);
        $this->admin()->post(route('login.proses'), ['username' => 'x', 'password' => 'salahsalah']);

        $this->admin()->get(route('log.index', ['tab' => 'aktivitas']))
            ->assertOk()->assertSee('Gagal masuk ke dashboard')->assertDontSee('Tampilan hadiah diubah');
        $this->admin()->get(route('ic.admin.log.index', ['tab' => 'aktivitas']))
            ->assertOk()->assertSee('Tampilan hadiah diubah')->assertDontSee('Gagal masuk ke dashboard');
    }

    // ---------- login ----------

    public function test_hanya_login_gagal_yang_dicatat_dengan_ip_dan_tanpa_username_yang_diketik(): void
    {
        $this->akun();

        $this->login(['username' => 'SalahKetikSandiDiSini'])->assertSessionHasErrors('login');
        $gagal = log_aktivitas::first();
        $this->assertSame('Gagal masuk ke dashboard', $gagal->aksi);
        $this->assertSame('akses', $gagal->kategori);
        $this->assertSame('gagal', $gagal->status);
        $this->assertNotNull($gagal->ip); // kondisi berisiko: IP disimpan
        $this->assertStringNotContainsString('SalahKetikSandiDiSini', json_encode($gagal->toArray()));

        $this->login()->assertRedirect(route('dashboard'));
        $this->post(route('logout'));
        $this->assertSame(1, log_aktivitas::count()); // login berhasil dan logout tidak dicatat
    }

    public function test_login_gagal_berulang_dari_ip_sama_digabung_jadi_satu_baris(): void
    {
        $this->akun();

        foreach (range(1, 3) as $_) {
            $this->login(['password' => 'SalahSalahSalah'])->assertSessionHasErrors('login');
        }

        $this->assertSame(1, log_aktivitas::count());
        $this->assertSame(3, log_aktivitas::first()->jumlah);

        $this->admin()->get(route('log.index', ['tab' => 'aktivitas']))->assertOk()->assertSee('3x')->assertSee('IP 127.0.0.1');
    }

    public function test_ip_tidak_tampil_untuk_perubahan_konten(): void
    {
        log_aktivitas::create(['pelaku' => 'admin', 'kategori' => 'konten', 'aksi' => 'Album ditambahkan', 'ip' => '203.0.113.9', 'status' => 'berhasil', 'created_at' => now()]);

        $this->admin()->get(route('log.index', ['tab' => 'aktivitas']))->assertOk()->assertSee('Album ditambahkan')->assertDontSee('203.0.113.9');
    }
}