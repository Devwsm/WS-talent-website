<?php

namespace Tests\Feature\InnerCircle\Komunitas;

use App\Models\ic_memory;
use App\Models\ic_memory_comment;
use App\Models\member;
use App\Support\ImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kenangan: bingkai foto 4:5 (potong atau utuh), halaman detail, komentar anggota, dan moderasi komentar oleh admin.
 */
class KomentarKenanganTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /** Pemroses palsu yang mencatat rasio dan mode bingkai yang diminta. */
    public static array $dipanggil = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        self::$dipanggil = [];
        $this->app->instance(ImageProcessor::class, new class extends ImageProcessor {
            public function keWebp(string $sumber, int $sisiMaks, int $kualitas = 82, ?float $rasio = null, bool $potong = true): string
            {
                KomentarKenanganTest::$dipanggil[] = ['sisi' => $sisiMaks, 'rasio' => $rasio, 'potong' => $potong];

                return 'RIFF0000WEBPfake';
            }
        });
    }

    private function anggota(string $nama = 'Budi Santoso', string $email = 'a@contoh.test', bool $verifikasi = true): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = $verifikasi ? now() : null;
        $m->save();

        return $m;
    }

    private function kenangan(member $m, array $o = []): ic_memory
    {
        return ic_memory::create($o + ['member_id' => $m->id_member, 'caption' => 'Seru banget', 'tempat' => 'Blackowl', 'kategori' => 'festival', 'status' => 'tayang']);
    }

    // ---------- bingkai 4:5 ----------

    public function test_foto_kenangan_diproses_ke_4_banding_5_potong_pas_secara_bawaan(): void
    {
        $m = $this->anggota();
        $foto = UploadedFile::fake()->createWithContent('f.png', base64_decode(self::PNG_1X1));

        $this->actingAs($m, 'member')->post(route('ic.kenangan.simpan'), ['caption' => 'Halo', 'kategori' => 'festival', 'foto' => $foto])
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(0.8, self::$dipanggil[0]['rasio'], 0.0001);
        $this->assertTrue(self::$dipanggil[0]['potong']);
    }

    public function test_pilihan_foto_utuh_dikirim_ke_pemroses_dan_nilai_asing_ditolak(): void
    {
        $m = $this->anggota();
        $foto = fn() => UploadedFile::fake()->createWithContent('f.png', base64_decode(self::PNG_1X1));

        $this->actingAs($m, 'member')->post(route('ic.kenangan.simpan'), ['caption' => 'Halo', 'kategori' => 'festival', 'bingkai' => 'utuh', 'foto' => $foto()])
            ->assertSessionHasNoErrors();
        $this->assertFalse(self::$dipanggil[0]['potong']);

        $this->actingAs($m, 'member')->post(route('ic.kenangan.simpan'), ['caption' => 'Halo', 'kategori' => 'festival', 'bingkai' => 'aneh', 'foto' => $foto()])
            ->assertSessionHasErrors('bingkai');
    }

    // ---------- detail ----------

    public function test_detail_menampilkan_kenangan_dan_komentar_dan_yang_disembunyikan_404(): void
    {
        $penulis = $this->anggota('Citra Dewi', 'c@contoh.test');
        $lihat = $this->anggota('Budi Santoso');
        $k = $this->kenangan($penulis, ['caption' => 'Malam yang panjang']);
        ic_memory_comment::create(['memory_id' => $k->id_memory, 'member_id' => $penulis->id_member, 'isi' => 'Terima kasih sudah datang']);

        $this->actingAs($lihat, 'member')->get(route('ic.kenangan.detail', $k->id_memory))
            ->assertOk()
            ->assertSee('Malam yang panjang')
            ->assertSee('Terima kasih sudah datang')
            ->assertSee('Citra D.')       // nama disingkat untuk sesama anggota
            ->assertDontSee('Citra Dewi');

        $k->update(['status' => 'disembunyikan']);
        $this->actingAs($lihat, 'member')->get(route('ic.kenangan.detail', $k->id_memory))->assertNotFound();
        $this->actingAs($lihat, 'member')->post(route('ic.kenangan.komentar', $k->id_memory), ['isi' => 'halo'])->assertNotFound();
    }

    public function test_kartu_galeri_punya_pintasan_komentar_dan_tautan_ke_detail(): void
    {
        $m = $this->anggota();
        $k = $this->kenangan($m);
        ic_memory_comment::create(['memory_id' => $k->id_memory, 'member_id' => $m->id_member, 'isi' => 'Satu']);

        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertOk()
            ->assertSee(route('ic.kenangan.detail', $k->id_memory) . '#komentar', false)
            ->assertSee('aria-label="Komentar (1)"', false);
    }

    // ---------- komentar ----------

    public function test_anggota_terverifikasi_bisa_berkomentar_dan_teksnya_dirapikan_dan_di_escape(): void
    {
        $m = $this->anggota();
        $k = $this->kenangan($this->anggota('Citra Dewi', 'c@contoh.test'));

        $this->actingAs($m, 'member')->post(route('ic.kenangan.komentar', $k->id_memory), ['isi' => "  Keren   banget <b>x</b>\n\n lho "])
            ->assertRedirect(route('ic.kenangan.detail', $k->id_memory) . '#komentar');

        $this->assertSame('Keren banget <b>x</b> lho', ic_memory_comment::first()->isi);
        $this->actingAs($m, 'member')->get(route('ic.kenangan.detail', $k->id_memory))
            ->assertSee('&lt;b&gt;x&lt;/b&gt;', false)->assertDontSee('<b>x</b>', false);
    }

    public function test_komentar_divalidasi_dan_butuh_email_terverifikasi(): void
    {
        $k = $this->kenangan($this->anggota('Citra Dewi', 'c@contoh.test'));
        $m = $this->anggota('Budi', 'b@contoh.test');

        $this->actingAs($m, 'member')->post(route('ic.kenangan.komentar', $k->id_memory), ['isi' => '   '])->assertSessionHasErrors('isi');
        $this->actingAs($m, 'member')->post(route('ic.kenangan.komentar', $k->id_memory), ['isi' => str_repeat('a', 301)])->assertSessionHasErrors('isi');

        $belum = $this->anggota('Dewa', 'd@contoh.test', false);
        $this->actingAs($belum, 'member')->post(route('ic.kenangan.komentar', $k->id_memory), ['isi' => 'halo']);
        $this->assertSame(0, ic_memory_comment::count());
    }

    public function test_komentar_dibatasi_per_menit(): void
    {
        config(['inner_circle.batas_komentar_per_menit' => 2]);
        $m = $this->anggota();
        $k = $this->kenangan($this->anggota('Citra Dewi', 'c@contoh.test'));

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($m, 'member')->post(route('ic.kenangan.komentar', $k->id_memory), ['isi' => "komentar $i"]);
        }

        $this->assertSame(2, ic_memory_comment::count());
    }

    public function test_hapus_komentar_oleh_penulis_atau_pemilik_kenangan_bukan_orang_lain(): void
    {
        $pemilik = $this->anggota('Citra Dewi', 'c@contoh.test');
        $penulis = $this->anggota('Budi', 'b@contoh.test');
        $asing = $this->anggota('Eko', 'e@contoh.test');
        $k = $this->kenangan($pemilik);
        $buat = fn() => ic_memory_comment::create(['memory_id' => $k->id_memory, 'member_id' => $penulis->id_member, 'isi' => 'halo']);

        $kom = $buat();
        $this->actingAs($asing, 'member')->delete(route('ic.kenangan.komentar.hapus', $kom->id_komentar))->assertForbidden();
        $this->assertSame(1, ic_memory_comment::count());

        $this->actingAs($penulis, 'member')->delete(route('ic.kenangan.komentar.hapus', $kom->id_komentar))->assertRedirect();
        $this->assertSame(0, ic_memory_comment::count());

        $kom = $buat();
        $this->actingAs($pemilik, 'member')->delete(route('ic.kenangan.komentar.hapus', $kom->id_komentar))->assertRedirect();
        $this->assertSame(0, ic_memory_comment::count());
    }

    public function test_komentar_ikut_terhapus_bersama_kenangan_dan_admin_bisa_menghapus(): void
    {
        $m = $this->anggota();
        $k = $this->kenangan($m);
        $kom = ic_memory_comment::create(['memory_id' => $k->id_memory, 'member_id' => $m->id_member, 'isi' => 'melanggar']);
        $admin = $this->withSession(['login' => true, 'user' => 'admin']);

        $admin->get(route('ic.admin.kenangan'))->assertOk()->assertSee('melanggar');
        $admin->delete(route('ic.admin.kenangan.komentar.hapus', $kom->id_komentar))->assertSessionHas('success');
        $this->assertSame(0, ic_memory_comment::count());

        ic_memory_comment::create(['memory_id' => $k->id_memory, 'member_id' => $m->id_member, 'isi' => 'lagi']);
        $k->delete();
        $this->assertSame(0, ic_memory_comment::count());
    }
}
