<?php

namespace Tests\Feature\InnerCircle\Komunitas;

use App\Models\ic_memory;
use App\Models\member;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Galeri kenangan: unggah, galeri/filter, suka, hak hapus (policy), dan moderasi admin.
 */
class KenanganTest extends TestCase
{
    use RefreshDatabase;
    // ---------- skema ----------
    // ---------- pengumuman: sisi anggota ----------
    // ---------- pengumuman: admin ----------
    // ---------- kenangan: unggah ----------
    // ---------- kenangan: tampilan galeri ----------
    // ---------- suka ----------
    // ---------- hapus oleh pemilik ----------
    // ---------- moderasi admin ----------
    // ---------- foto profil ----------

    /** PNG 1x1 yang valid, supaya validasi `mimes` (berdasarkan isi berkas) lolos tanpa GD. */
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->pakaiPemrosesPalsu();
    }

    /** Disk fake bertipe FilesystemAdapter, supaya assertExists/assertMissing dikenali analisis statis (Intelephense). */
    private function diskPublik(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk;
    }

    /** @param  \Closure|null  $perilaku  fn(string $sumber, int $sisiMaks): string; bawaan mengembalikan byte WebP palsu */
    private function pakaiPemrosesPalsu(?\Closure $perilaku = null): void
    {
        $this->app->instance(ImageProcessor::class, new class($perilaku) extends ImageProcessor {
            public function __construct(private readonly ?\Closure $perilaku) {}

            public function keWebp(string $sumber, int $sisiMaks, int $kualitas = 82): string
            {
                return $this->perilaku ? ($this->perilaku)($sumber, $sisiMaks) : 'RIFF0000WEBPfake-' . $sisiMaks;
            }
        });
    }

    private function anggota(string $nama = 'Anggota Uji', string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now(); // check-in, kenangan, dan suka butuh email terverifikasi
        $m->save();

        return $m->fresh();
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function kenangan(member $m, array $o = []): ic_memory
    {
        $waktu = $o['created_at'] ?? null;
        unset($o['created_at']); // bukan kolom yang boleh diisi massal

        $k = ic_memory::create($o + [
            'member_id' => $m->id_member,
            'caption' => 'Caption uji ' . uniqid(),
            'tempat' => 'Tempat uji',
            'kategori' => 'festival',
            'status' => 'tayang',
        ]);

        if ($waktu) {
            $k->created_at = $waktu;
            $k->save();
        }

        return $k;
    }

    private function fotoPng(string $nama = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, base64_decode(self::PNG_1X1));
    }

    public function test_anggota_mengunggah_kenangan_teks_saja_dan_langsung_tayang(): void
    {
        $m = $this->anggota('Rani Pratiwi', 'rani@contoh.test');

        $this->actingAs($m, 'member')->post(route('ic.kenangan.simpan'), [
            'caption' => 'Malam yang tidak terlupakan',
            'tempat' => 'WS Showcase',
            'kategori' => 'club',
        ])->assertRedirect(route('ic.komunitas', ['tab' => 'galeri']))->assertSessionHas('ic_toast');

        $k = ic_memory::firstOrFail();
        $this->assertSame($m->id_member, (int) $k->member_id);
        $this->assertSame('tayang', $k->status);
        $this->assertNull($k->foto);

        $this->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertOk()
            ->assertSee('Malam yang tidak terlupakan')
            ->assertSee('WS Showcase');
    }

    public function test_kenangan_dengan_foto_disimpan_sebagai_webp_olahan(): void
    {
        $m = $this->anggota();

        $this->actingAs($m, 'member')->post(route('ic.kenangan.simpan'), [
            'caption' => 'Dengan foto',
            'kategori' => 'festival',
            'tempat' => '',
            'foto' => $this->fotoPng(),
        ])->assertSessionHasNoErrors();

        $k = ic_memory::firstOrFail();
        $this->assertNull($k->tempat, 'tempat kosong disimpan sebagai null');
        $this->assertStringStartsWith('inner-circle/kenangan/', $k->foto);
        $this->assertStringEndsWith('.webp', $k->foto);
        $this->diskPublik()->assertExists($k->foto);
        $this->assertSame('RIFF0000WEBPfake-1600', Storage::disk('public')->get($k->foto), 'sisi maksimum kenangan 1600 px');
    }

    public function test_kenangan_tidak_valid_ditolak_tanpa_menyimpan_apa_pun(): void
    {
        $m = $this->anggota();
        $dasar = ['caption' => 'Cerita', 'kategori' => 'festival'];
        // Banyak POST beruntun di satu tes: batas per menit dilonggarkan supaya yang diuji validasinya, bukan limiter.
        config(['inner_circle.batas_kenangan_per_menit' => 1000]);

        $this->actingAs($m, 'member');
        $this->post(route('ic.kenangan.simpan'), ['caption' => '   ', 'kategori' => 'festival'])->assertSessionHasErrors('caption');
        $this->post(route('ic.kenangan.simpan'), ['caption' => str_repeat('a', 241), 'kategori' => 'festival'])->assertSessionHasErrors('caption');
        $this->post(route('ic.kenangan.simpan'), $dasar + ['tempat' => str_repeat('t', 66)])->assertSessionHasErrors('tempat');
        $this->post(route('ic.kenangan.simpan'), ['caption' => 'Cerita', 'kategori' => 'rahasia'])->assertSessionHasErrors('kategori');
        $this->post(route('ic.kenangan.simpan'), $dasar + ['foto' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf')])->assertSessionHasErrors('foto');
        // Berkas yang namanya .jpg tetapi isinya bukan gambar ditolak oleh ImageProcessor (tests/Unit/ImageProcessorTest);
        // berkas palsu buatan Laravel menentukan tipenya dari nama, jadi penyamaran isi tidak bisa ditiru di sini.
        // Di atas batas 4 MB.
        $this->post(route('ic.kenangan.simpan'), $dasar + ['foto' => UploadedFile::fake()->create('besar.png', 4097, 'image/png')])->assertSessionHasErrors('foto');

        $this->assertSame(0, ic_memory::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_foto_yang_gagal_diolah_tidak_meninggalkan_kenangan_atau_berkas(): void
    {
        $this->pakaiPemrosesPalsu(fn() => throw new GambarGagal('Resolusi foto terlalu besar untuk diproses server.'));

        $this->actingAs($this->anggota(), 'member')->post(route('ic.kenangan.simpan'), [
            'caption' => 'Gagal',
            'kategori' => 'festival',
            'foto' => $this->fotoPng(),
        ])->assertSessionHasErrors(['foto' => 'Resolusi foto terlalu besar untuk diproses server.']);

        $this->assertSame(0, ic_memory::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_form_unggah_terbuka_lagi_dengan_isian_dan_galat(): void
    {
        $this->actingAs($this->anggota(), 'member')
            ->from(route('ic.komunitas'))
            ->followingRedirects()
            ->post(route('ic.kenangan.simpan'), ['caption' => 'Ceritaku', 'kategori' => 'club', 'tempat' => 'x' . str_repeat('y', 70)])
            ->assertOk()
            ->assertSee('data-ic-buka-awal', false)
            ->assertSee('Nama acara atau tempat terlalu panjang')
            ->assertSee('Ceritaku');

        // Tanpa galat modal tertutup.
        $this->get(route('ic.komunitas'))->assertDontSee('data-ic-buka-awal', false);
    }

    public function test_unggah_kenangan_dibatasi_per_menit_per_anggota(): void
    {
        $m = $this->anggota();
        $lain = $this->anggota('Lain', 'lain@contoh.test');
        $kirim = fn(member $a) => $this->actingAs($a, 'member')->post(route('ic.kenangan.simpan'), ['caption' => 'Cerita', 'kategori' => 'festival']);

        for ($i = 0; $i < 4; $i++) {
            $kirim($m)->assertSessionMissing('ic_toast_galat');
        }
        $kirim($m)->assertSessionHas('ic_toast_galat');
        $this->assertSame(4, ic_memory::where('member_id', $m->id_member)->count());

        // Batas per anggota, bukan per IP: anggota lain tidak ikut terblokir.
        $kirim($lain)->assertSessionMissing('ic_toast_galat');
    }

    public function test_galeri_hanya_menampilkan_yang_tayang_dengan_filter_kategori_dan_paginasi(): void
    {
        $m = $this->anggota();
        $this->kenangan($m, ['caption' => 'Tayang Festival', 'kategori' => 'festival']);
        $this->kenangan($m, ['caption' => 'Tayang Klub', 'kategori' => 'club']);
        $this->kenangan($m, ['caption' => 'Disembunyikan Uji', 'status' => 'disembunyikan']);

        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertSee('Tayang Festival')->assertSee('Tayang Klub')->assertDontSee('Disembunyikan Uji');

        $this->get(route('ic.komunitas', ['tab' => 'galeri', 'kategori' => 'club']))
            ->assertSee('Tayang Klub')->assertDontSee('Tayang Festival');

        // Kategori ngawur diabaikan, bukan error.
        $this->get(route('ic.komunitas', ['tab' => 'galeri', 'kategori' => 'ngawur']))->assertOk()->assertSee('Tayang Festival');

        foreach (range(1, 12) as $i) {
            $this->kenangan($m, ['caption' => "Massal {$i}", 'created_at' => now()->subDays(30)]);
        }
        $halaman1 = $this->get(route('ic.komunitas', ['tab' => 'galeri']));
        $halaman1->assertSee('Halaman 1 dari 2')->assertDontSee('Massal 1</p>', false);
        $this->get(route('ic.komunitas', ['tab' => 'galeri', 'page' => 2]))->assertOk()->assertSee('Halaman 2 dari 2');
    }

    public function test_nama_di_galeri_disingkat_dan_nama_lengkap_tidak_bocor(): void
    {
        $this->kenangan($this->anggota('Ancha Mahendra Kusuma', 'ancha@contoh.test'));
        $this->kenangan($this->anggota('Dio', 'dio@contoh.test'));

        $this->actingAs($this->anggota('Penonton', 'p@contoh.test'), 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertSee('Ancha M.')
            ->assertSee('Dio')
            ->assertDontSee('Mahendra')
            ->assertDontSee('ancha@contoh.test');
    }

    public function test_kenangan_berfoto_dan_tanpa_foto_dirender(): void
    {
        $m = $this->anggota();
        $k = $this->kenangan($m, ['foto' => 'inner-circle/kenangan/contoh.webp']);
        $tanpa = $this->kenangan($m, ['kategori' => 'personal']);

        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertSee('inner-circle/kenangan/contoh.webp', false)
            ->assertSee('ic-warna-pink', false);

        $this->assertSame('pink', $tanpa->warna);
        $this->assertSame('ticket', $tanpa->ikon);
        $this->assertSame('kuning', $k->warna);
    }

    public function test_suka_bersifat_toggle_per_anggota_dan_angkanya_dari_server(): void
    {
        $pemilik = $this->anggota();
        $a = $this->anggota('A', 'a2@contoh.test');
        $b = $this->anggota('B', 'b2@contoh.test');
        $k = $this->kenangan($pemilik);

        $this->actingAs($a, 'member')->postJson(route('ic.kenangan.suka', $k->id_memory))
            ->assertOk()->assertExactJson(['disukai' => true, 'jumlah' => 1]);
        $this->actingAs($b, 'member')->postJson(route('ic.kenangan.suka', $k->id_memory))
            ->assertOk()->assertExactJson(['disukai' => true, 'jumlah' => 2]);
        $this->actingAs($a, 'member')->postJson(route('ic.kenangan.suka', $k->id_memory))
            ->assertOk()->assertExactJson(['disukai' => false, 'jumlah' => 1]);

        $this->assertSame(1, DB::table('ic_memory_likes')->count());

        // Galeri menampilkan angka dan status suka milik anggota yang sedang melihat.
        $this->actingAs($b, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertSee('aria-pressed="true"', false);
        $this->actingAs($a, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertSee('aria-pressed="false"', false);
    }

    public function test_suka_kenangan_disembunyikan_atau_tidak_ada_adalah_404_dan_tamu_ditolak(): void
    {
        $m = $this->anggota();
        $tersembunyi = $this->kenangan($m, ['status' => 'disembunyikan']);

        $this->actingAs($m, 'member')->postJson(route('ic.kenangan.suka', $tersembunyi->id_memory))->assertNotFound();
        $this->postJson(route('ic.kenangan.suka', 9999))->assertNotFound();
        $this->assertSame(0, DB::table('ic_memory_likes')->count());

        $this->app['auth']->forgetGuards();
        $this->postJson(route('ic.kenangan.suka', $tersembunyi->id_memory))->assertUnauthorized();
    }

    public function test_pasangan_anggota_dan_kenangan_unik_di_database(): void
    {
        $m = $this->anggota();
        $k = $this->kenangan($m);
        DB::table('ic_memory_likes')->insert(['member_id' => $m->id_member, 'memory_id' => $k->id_memory]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('ic_memory_likes')->insert(['member_id' => $m->id_member, 'memory_id' => $k->id_memory]);
    }

    public function test_hanya_pemilik_yang_bisa_menghapus_kenangan(): void
    {
        $pemilik = $this->anggota();
        $lain = $this->anggota('Lain', 'lain@contoh.test');
        $path = 'inner-circle/kenangan/hapus-aku.webp';
        Storage::disk('public')->put($path, 'x');
        $k = $this->kenangan($pemilik, ['foto' => $path]);
        DB::table('ic_memory_likes')->insert(['member_id' => $lain->id_member, 'memory_id' => $k->id_memory]);

        $this->actingAs($lain, 'member')->delete(route('ic.kenangan.hapus', $k->id_memory))->assertForbidden();
        $this->assertNotNull($k->fresh());
        $this->diskPublik()->assertExists($path);

        // Tombol Hapus hanya tampil untuk pemilik.
        // (action="..." lengkap, karena URL suka /kenangan/{id}/suka berawalan sama dengan URL hapus.)
        $formHapus = 'action="' . route('ic.kenangan.hapus', $k->id_memory) . '"';
        $this->get(route('ic.komunitas', ['tab' => 'galeri']))->assertDontSee($formHapus, false);
        $this->actingAs($pemilik, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertSee($formHapus, false);

        $this->delete(route('ic.kenangan.hapus', $k->id_memory))->assertRedirect()->assertSessionHas('ic_toast');
        $this->assertNull(ic_memory::find($k->id_memory));
        $this->diskPublik()->assertMissing($path);
        $this->assertSame(0, DB::table('ic_memory_likes')->count(), 'tanda suka ikut terhapus');
    }

    public function test_policy_kenangan(): void
    {
        $pemilik = $this->anggota();
        $lain = $this->anggota('Lain', 'lain@contoh.test');
        $k = $this->kenangan($pemilik);

        $this->assertTrue(Gate::forUser($pemilik)->allows('hapus', $k));
        $this->assertFalse(Gate::forUser($lain)->allows('hapus', $k));
        $this->assertTrue(Gate::forUser($lain)->allows('suka', $k));

        $k->update(['status' => 'disembunyikan']);
        $this->assertFalse(Gate::forUser($lain)->allows('suka', $k->fresh()));
        $this->assertTrue(Gate::forUser($pemilik)->allows('hapus', $k->fresh()), 'pemilik tetap boleh menghapus yang disembunyikan');
    }

    public function test_admin_menyembunyikan_dan_menayangkan_kembali_kenangan(): void
    {
        $m = $this->anggota('Maya Sari Dewi', 'maya@contoh.test');
        $k = $this->kenangan($m, ['caption' => 'Kenangan Dimoderasi']);

        $this->admin()->patch(route('ic.admin.kenangan.moderasi', $k->id_memory), ['aksi' => 'sembunyikan'])
            ->assertRedirect()->assertSessionHas('success', fn($s) => str_contains($s, 'Maya Sari Dewi'));
        $this->assertSame('disembunyikan', $k->fresh()->status);

        $penonton = $this->anggota('Penonton', 'p@contoh.test');
        $this->actingAs($penonton, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertDontSee('Kenangan Dimoderasi');
        $this->postJson(route('ic.kenangan.suka', $k->id_memory))->assertNotFound();
        $this->app['auth']->forgetGuards();

        // Admin tetap melihatnya, lengkap dengan nama lengkap pengunggah dan filter status.
        $this->admin()->get(route('ic.admin.kenangan'))
            ->assertOk()->assertSee('Kenangan Dimoderasi')->assertSee('Disembunyikan')->assertSee('Maya Sari Dewi');
        $this->admin()->get(route('ic.admin.kenangan', ['status' => 'tayang']))->assertDontSee('Kenangan Dimoderasi');
        $this->admin()->get(route('ic.admin.kenangan', ['status' => 'disembunyikan']))->assertSee('Kenangan Dimoderasi');

        $this->admin()->patch(route('ic.admin.kenangan.moderasi', $k->id_memory), ['aksi' => 'tayangkan'])->assertRedirect();
        $this->assertSame('tayang', $k->fresh()->status);
        $this->actingAs($penonton, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertSee('Kenangan Dimoderasi');
    }

    public function test_moderasi_menolak_aksi_ngawur_dan_kenangan_yang_tidak_ada(): void
    {
        $k = $this->kenangan($this->anggota());

        $this->admin()->patch(route('ic.admin.kenangan.moderasi', $k->id_memory), ['aksi' => 'hapus'])->assertSessionHasErrors('aksi');
        $this->admin()->patch(route('ic.admin.kenangan.moderasi', 9999), ['aksi' => 'sembunyikan'])->assertNotFound();
        $this->assertSame('tayang', $k->fresh()->status);

        // Tanpa sesi admin (withSession di atas menempel di tes ini, jadi dibersihkan dulu).
        $this->flushSession();
        $this->patch(route('ic.admin.kenangan.moderasi', $k->id_memory), ['aksi' => 'sembunyikan'])->assertRedirect(route('login'));
        $this->assertSame('tayang', $k->fresh()->status);
    }

    public function test_tab_admin_kenangan_filter_dan_paginasi(): void
    {
        $m = $this->anggota();
        $this->kenangan($m, ['caption' => 'Festival Admin', 'kategori' => 'festival']);
        $this->kenangan($m, ['caption' => 'Klub Admin', 'kategori' => 'club']);

        $this->admin()->get(route('ic.admin.kenangan', ['kategori' => 'club']))->assertSee('Klub Admin')->assertDontSee('Festival Admin');

        foreach (range(1, 12) as $i) {
            $this->kenangan($m, ['caption' => "Admin massal {$i}", 'created_at' => now()->subDays(20)]);
        }
        $this->admin()->get(route('ic.admin.kenangan'))->assertOk()->assertSee('Halaman 1 dari 2');
        $this->admin()->get(route('ic.admin.kenangan', ['page' => 2]))->assertOk()->assertSee('Halaman 2 dari 2');
    }
}
