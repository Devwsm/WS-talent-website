<?php

namespace Tests\Feature;

use App\Models\ic_broadcast;
use App\Models\ic_memory;
use App\Models\member;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * WS Inner Circle Fase 6: Komunitas (pengumuman, kenangan, suka, moderasi) dan foto profil.
 *
 * ImageProcessor diganti stub di sini supaya alur unggah teruji tanpa GD; pengolahan gambarnya sendiri
 * dites di tests/Unit/ImageProcessorTest.php.
 */
class InnerCircleKomunitasTest extends TestCase
{
    use RefreshDatabase;

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

    private function siar(array $o = []): ic_broadcast
    {
        return ic_broadcast::create($o + [
            'judul' => 'Pengumuman uji',
            'isi' => 'Isi pengumuman uji.',
            'tag' => 'UJI',
            'ikon' => 'sparkles',
            'warna' => 'kuning',
            'cta_label' => 'Lihat',
            'cta_tujuan' => 'ic.checkin',
            'unggulan' => false,
            'terbit_pada' => today()->subDay()->toDateString(),
            'aktif' => true,
        ]);
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

    private function dataPengumuman(array $o = []): array
    {
        return $o + [
            'tag' => 'MUSIK',
            'terbit_pada' => today()->toDateString(),
            'judul' => 'Judul uji',
            'isi' => 'Isi uji.',
            'ikon' => 'cd',
            'warna' => 'biru',
            'cta_label' => 'Lihat hadiah',
            'cta_tujuan' => 'ic.hadiah',
        ];
    }

    // ---------- skema ----------

    public function test_tiga_tabel_komunitas_ada(): void
    {
        foreach (['ic_broadcasts', 'ic_memories', 'ic_memory_likes'] as $tabel) {
            $this->assertTrue(Schema::hasTable($tabel), $tabel);
        }
    }

    // ---------- pengumuman: sisi anggota ----------

    public function test_anggota_hanya_melihat_pengumuman_aktif_yang_sudah_terbit(): void
    {
        $this->siar(['judul' => 'Kabar Pilihan Uji', 'unggulan' => true]);
        $this->siar(['judul' => 'Pengumuman Biasa Uji']);
        $this->siar(['judul' => 'Pengumuman Besok', 'terbit_pada' => today()->addDay()->toDateString()]);
        $this->siar(['judul' => 'Pengumuman Nonaktif', 'aktif' => false]);

        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas'))
            ->assertOk()
            ->assertSee('Kabar Pilihan Uji')
            ->assertSee('Pengumuman Biasa Uji')
            ->assertDontSee('Pengumuman Besok')
            ->assertDontSee('Pengumuman Nonaktif');
    }

    public function test_pengumuman_terjadwal_tampil_setelah_tanggalnya_tiba(): void
    {
        $this->siar(['judul' => 'Pengumuman Terjadwal', 'terbit_pada' => today()->addDays(2)->toDateString()]);
        $m = $this->anggota();

        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertDontSee('Pengumuman Terjadwal');

        $this->travel(2)->days();
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertSee('Pengumuman Terjadwal');
    }

    public function test_komunitas_kosong_tidak_error(): void
    {
        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas'))
            ->assertOk()
            ->assertSee('Belum ada pengumuman');

        $this->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertOk()
            ->assertSee('Belum ada kenangan');
    }

    public function test_teks_pengumuman_di_escape(): void
    {
        $this->siar(['judul' => 'Uji <script>alert(1)</script>']);

        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_tombol_ke_route_yang_sudah_tidak_ada_tidak_membuat_halaman_error(): void
    {
        $this->siar(['judul' => 'Tujuan Hilang', 'cta_tujuan' => 'ic.sudah-dihapus']);

        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas'))
            ->assertOk()
            ->assertSee('Tujuan Hilang')
            ->assertSee(route('ic.komunitas'), false);
    }

    // ---------- pengumuman: admin ----------

    public function test_tab_admin_komunitas_butuh_login_admin(): void
    {
        $this->get(route('ic.admin.pengumuman'))->assertRedirect(route('login'));
        $this->get(route('ic.admin.kenangan'))->assertRedirect(route('login'));
        $this->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman())->assertRedirect(route('login'));
        $this->assertSame(0, ic_broadcast::count());
    }

    public function test_admin_menerbitkan_mengubah_dan_menghapus_pengumuman(): void
    {
        $this->admin()->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman(['judul' => 'Judul Awal']))
            ->assertRedirect(route('ic.admin.pengumuman'))
            ->assertSessionHas('success');

        $p = ic_broadcast::firstOrFail();
        $this->assertSame('Judul Awal', $p->judul);
        $this->assertTrue($p->aktif);
        $this->assertFalse($p->unggulan);

        $this->admin()->put(route('ic.admin.pengumuman.ubah', $p->id_broadcast), $this->dataPengumuman(['judul' => 'Judul Baru', 'tag' => '']))
            ->assertRedirect(route('ic.admin.pengumuman'));
        $this->assertSame('Judul Baru', $p->fresh()->judul);
        $this->assertNull($p->fresh()->tag, 'kategori kosong disimpan sebagai null');

        $this->admin()->delete(route('ic.admin.pengumuman.hapus', $p->id_broadcast))->assertRedirect(route('ic.admin.pengumuman'));
        $this->assertSame(0, ic_broadcast::count());
    }

    public function test_hanya_satu_kabar_pilihan_dan_yang_lama_menjadi_biasa(): void
    {
        $lama = $this->siar(['judul' => 'Unggulan Lama', 'unggulan' => true]);

        $this->admin()->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman(['judul' => 'Unggulan Baru', 'unggulan' => '1']))
            ->assertSessionHasNoErrors();

        $baru = ic_broadcast::where('judul', 'Unggulan Baru')->firstOrFail();
        $this->assertTrue($baru->unggulan);
        $this->assertFalse($lama->fresh()->unggulan);
        $this->assertSame(1, ic_broadcast::where('unggulan', true)->count());

        // Mengubah yang lama menjadi unggulan membalik keadaan, tetap satu.
        $this->admin()->put(route('ic.admin.pengumuman.ubah', $lama->id_broadcast), $this->dataPengumuman(['judul' => 'Unggulan Lama', 'unggulan' => '1']));
        $this->assertTrue($lama->fresh()->unggulan);
        $this->assertFalse($baru->fresh()->unggulan);
        $this->assertSame(1, ic_broadcast::where('unggulan', true)->count());

        // Menyimpan ulang pengumuman unggulan tanpa mengubah apa pun tidak menurunkan dirinya sendiri.
        $this->admin()->put(route('ic.admin.pengumuman.ubah', $lama->id_broadcast), $this->dataPengumuman(['judul' => 'Unggulan Lama', 'unggulan' => '1']));
        $this->assertTrue($lama->fresh()->unggulan);

        // Tidak mencentang = bukan unggulan.
        $this->admin()->put(route('ic.admin.pengumuman.ubah', $lama->id_broadcast), $this->dataPengumuman(['judul' => 'Unggulan Lama']));
        $this->assertFalse($lama->fresh()->unggulan);
    }

    public function test_pengumuman_tidak_valid_ditolak_dan_kembali_ke_mode_yang_sama(): void
    {
        $p = $this->siar();

        $this->admin()->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman([
            'judul' => str_repeat('a', 81),
            'isi' => str_repeat('b', 221),
            'cta_tujuan' => 'ic.admin.ringkasan',
            'ikon' => 'tidak-ada',
            'warna' => 'ungu',
            'terbit_pada' => '23-10-2026',
            'cta_label' => '',
        ]))
            ->assertSessionHasErrors(['judul', 'isi', 'cta_tujuan', 'ikon', 'warna', 'terbit_pada', 'cta_label']);
        $this->assertSame(1, ic_broadcast::count());

        $respons = $this->admin()->put(route('ic.admin.pengumuman.ubah', $p->id_broadcast), $this->dataPengumuman(['judul' => '']));
        $respons->assertSessionHasErrors('judul');
        $this->assertStringContainsString('ubah=' . $p->id_broadcast, $respons->headers->get('Location'));
        $this->assertSame('Pengumuman uji', $p->fresh()->judul);

        // Halaman mode ubah memuat form PUT dengan isi pengumumannya.
        $this->admin()->get(route('ic.admin.pengumuman', ['ubah' => $p->id_broadcast]))
            ->assertOk()
            ->assertSee('Ubah pengumuman')
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('Isi pengumuman uji.');
    }

    public function test_admin_menandai_pengumuman_terjadwal(): void
    {
        $this->siar(['judul' => 'Nanti Saja', 'terbit_pada' => today()->addWeek()->toDateString()]);

        $this->admin()->get(route('ic.admin.pengumuman'))->assertOk()->assertSee('Nanti Saja')->assertSee('Terjadwal');

        $this->admin()->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman(['terbit_pada' => today()->addDays(3)->toDateString()]))
            ->assertSessionHas('success', fn($s) => str_contains($s, 'dijadwalkan'));
    }

    // ---------- kenangan: unggah ----------

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

    // ---------- kenangan: tampilan galeri ----------

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

    // ---------- suka ----------

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

    // ---------- hapus oleh pemilik ----------

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

    // ---------- moderasi admin ----------

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

    // ---------- foto profil ----------

    public function test_foto_profil_diunggah_diganti_dan_dihapus(): void
    {
        $m = $this->anggota();
        $this->actingAs($m, 'member');

        $this->post(route('ic.profil.foto'), ['foto' => $this->fotoPng()])
            ->assertRedirect(route('ic.profil'))->assertSessionHas('ic_toast');
        $pertama = $m->fresh()->avatar;
        $this->assertStringStartsWith('inner-circle/avatar/', $pertama);
        $this->diskPublik()->assertExists($pertama);
        $this->assertSame('RIFF0000WEBPfake-512', Storage::disk('public')->get($pertama), 'sisi maksimum avatar 512 px');

        $this->get(route('ic.profil'))->assertOk()->assertSee($pertama, false);

        // Ganti: yang baru tersimpan, yang lama terhapus.
        $this->post(route('ic.profil.foto'), ['foto' => $this->fotoPng('lain.png')])->assertSessionHasNoErrors();
        $kedua = $m->fresh()->avatar;
        $this->assertNotSame($pertama, $kedua);
        $this->diskPublik()->assertExists($kedua);
        $this->diskPublik()->assertMissing($pertama);

        // Hapus: kembali ke avatar bawaan.
        $this->delete(route('ic.profil.foto.hapus'))->assertRedirect(route('ic.profil'))->assertSessionHas('ic_toast');
        $this->assertNull($m->fresh()->avatar);
        $this->diskPublik()->assertMissing($kedua);
        $this->assertSame([], Storage::disk('public')->allFiles());

        // Hapus lagi saat memang tidak ada foto: tidak error.
        $this->delete(route('ic.profil.foto.hapus'))->assertRedirect(route('ic.profil'));
    }

    public function test_foto_profil_tidak_valid_ditolak_dan_foto_lama_aman(): void
    {
        $m = $this->anggota();
        $this->actingAs($m, 'member')->post(route('ic.profil.foto'), ['foto' => $this->fotoPng()]);
        $lama = $m->fresh()->avatar;

        $this->post(route('ic.profil.foto'), [])->assertSessionHasErrors('foto');
        $this->post(route('ic.profil.foto'), ['foto' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])->assertSessionHasErrors('foto');
        $this->post(route('ic.profil.foto'), ['foto' => UploadedFile::fake()->create('besar.png', 5121, 'image/png')])->assertSessionHasErrors('foto');

        $this->pakaiPemrosesPalsu(fn() => throw new GambarGagal('Foto tidak bisa dibaca.'));
        $this->post(route('ic.profil.foto'), ['foto' => $this->fotoPng()])->assertSessionHasErrors(['foto' => 'Foto tidak bisa dibaca.']);

        $this->assertSame($lama, $m->fresh()->avatar);
        $this->diskPublik()->assertExists($lama);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_foto_profil_butuh_login_anggota(): void
    {
        $this->post(route('ic.profil.foto'), ['foto' => $this->fotoPng()])->assertRedirect(route('ic.masuk'));
        $this->delete(route('ic.profil.foto.hapus'))->assertRedirect(route('ic.masuk'));
        $this->post(route('ic.kenangan.simpan'), ['caption' => 'x', 'kategori' => 'festival'])->assertRedirect(route('ic.masuk'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_foto_profil_tampil_di_kenangan_milik_anggota(): void
    {
        $m = $this->anggota();
        $this->actingAs($m, 'member')->post(route('ic.profil.foto'), ['foto' => $this->fotoPng()]);
        $this->kenangan($m);

        $this->get(route('ic.komunitas', ['tab' => 'galeri']))->assertSee($m->fresh()->avatar, false);
    }
}