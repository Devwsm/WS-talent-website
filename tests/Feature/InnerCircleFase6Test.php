<?php

namespace Tests\Feature;

use App\Models\ic_pengaturan;
use App\Services\InnerCircle\IcKonten;
use App\Services\InnerCircle\IcTampilan;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Fase 6 (pisah CMS): tema dan merek (nama program, warna aksen/latar/kartu, logo) yang bisa diubah dari CMS. */
class InnerCircleFase6Test extends TestCase
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

    private function diskPublik(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk;
    }

    /** @param  \Closure|null  $perilaku  fn(): string, dilempar GambarGagal bila perlu; bawaan mengembalikan byte WebP palsu */
    private function pakaiPemrosesPalsu(?\Closure $perilaku = null): void
    {
        $this->app->instance(ImageProcessor::class, new class($perilaku) extends ImageProcessor {
            public function __construct(private readonly ?\Closure $perilaku) {}

            public function keWebp(string $sumber, int $sisiMaks, int $kualitas = 82): string
            {
                return $this->perilaku ? ($this->perilaku)() : 'RIFF0000WEBPfake-' . $sisiMaks;
            }
        });
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function logoPng(string $nama = 'logo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, base64_decode(self::PNG_1X1));
    }

    /** Kirim form Tampilan seperti yang dikirim browser: semua isian ada, kosong = bawaan. */
    private function kirim(array $k = [], array $lain = [])
    {
        $dasar = ['tampilan__program' => '', 'tampilan__aksen' => '', 'tampilan__latar' => '', 'tampilan__kartu' => ''];

        return $this->admin()->post(route('ic.admin.konten.tampilan.simpan'), ['k' => $k + $dasar] + $lain);
    }

    private function tampilan(): IcTampilan
    {
        return app(IcTampilan::class);
    }

    private function halamanMasuk(): string
    {
        return $this->get(route('ic.masuk'))->assertOk()->getContent();
    }

    // ---------- Bawaan identik ----------

    public function test_tabel_kosong_tidak_menambah_style_dan_memakai_logo_bawaan(): void
    {
        $this->assertSame('', $this->tampilan()->css());
        $this->assertFalse($this->tampilan()->warnaKustom());
        $this->assertNull($this->tampilan()->logoUrl());
        $this->assertSame('WS Inner Circle', ic_program());

        $html = $this->halamanMasuk();
        $this->assertStringNotContainsString('body.ic-app{', $html);
        $this->assertStringContainsString('aset/inner-circle/logo/wsic-wordmark.webp', $html);
        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('WS Inner Circle', $html);
    }

    public function test_warna_bawaan_di_config_sama_dengan_token_css_dan_lolos_kontras(): void
    {
        $css = (string) file_get_contents(resource_path('css/inner-circle.css'));
        $bawaan = $this->tampilan()->bawaan();

        foreach (config('ic_konten.tampilan.warna') as $def) {
            $this->assertMatchesRegularExpression('/' . preg_quote($def['css'], '/') . ':\s*' . preg_quote($def['bawaan'], '/') . ';/i', $css, $def['css'] . ' di CSS beda dari config');
        }

        foreach (config('ic_konten.tampilan.teks') as $nama => $def) {
            $this->assertMatchesRegularExpression('/--ic-' . $nama . ':\s*' . preg_quote($def['warna'], '/') . ';/i', $css, "--ic-$nama di CSS beda dari config");
        }

        $this->assertSame([], $this->tampilan()->periksaKontras($bawaan), 'warna bawaan sendiri harus lolos pemeriksaan kontras');
    }

    public function test_teks_bawaan_yang_memuat_nama_program_tampil_sama_seperti_sebelumnya(): void
    {
        $this->assertSame('WS Inner Circle', ic_teks('tentang.hero.judul'));
        $this->assertSame('WS Inner Circle', ic_teks('teaser.judul'));
        $this->assertSame('Gabung WS Inner Circle', ic_teks('daftar.judul'));
        $this->assertSame('WS Inner Circle, komunitas penggemar', ic_teks('tentang.meta.judul'));
        $this->assertSame('Terima kasih sudah menjadi bagian dari keluarga WS Inner Circle.', ic_teks('beranda.terima_kasih'));
    }

    // ---------- Warna ----------

    public function test_warna_tersimpan_tampil_di_halaman_dan_turunannya_ikut(): void
    {
        $this->kirim(['tampilan__aksen' => '#1A5FB4', 'tampilan__latar' => '#E8F0FF', 'tampilan__kartu' => '#F4FBFF'])
            ->assertSessionHasNoErrors()->assertRedirect(route('ic.admin.konten.bagian', 'tampilan'));

        $html = $this->halamanMasuk();
        $this->assertStringContainsString('body.ic-app{--ic-aksen:#1a5fb4;--ic-latar:#e8f0ff;--ic-shell:#f4fbff;--ic-lilac:#8dafda}', $html);
        $this->assertStringContainsString('<meta name="theme-color" content="#f4fbff">', $this->get(route('ic.tentang'))->getContent());
    }

    public function test_hanya_warna_yang_diubah_yang_ditimpa(): void
    {
        $this->kirim(['tampilan__latar' => '#e8f0ff'])->assertSessionHasNoErrors();

        $this->assertSame('body.ic-app{--ic-latar:#e8f0ff}', $this->tampilan()->css());
    }

    public function test_warna_sama_dengan_bawaan_atau_kosong_tidak_membuat_baris_dan_menghapusnya(): void
    {
        $this->kirim(['tampilan__aksen' => '#1a5fb4'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ic_pengaturan', ['kunci' => 'tampilan.aksen', 'nilai' => '#1a5fb4']);

        $this->kirim(['tampilan__aksen' => '#1B68A0'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'tampilan.aksen']);

        $this->kirim(['tampilan__aksen' => '#1a5fb4']);
        $this->kirim(['tampilan__aksen' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'tampilan.aksen']);
        $this->assertSame('', $this->tampilan()->css());
    }

    public function test_nilai_warna_yang_bukan_hex_ditolak_dan_tidak_menyimpan_apa_pun(): void
    {
        foreach (['merah', '#fff', '#12345g', 'rgb(1,2,3)', '#1a5fb4; } body{display:none', '</style><script>x</script>'] as $buruk) {
            $this->kirim(['tampilan__aksen' => $buruk, 'tampilan__program' => 'Nama Baru'])->assertSessionHasErrorsIn('tampilan', 'k.tampilan__aksen');
        }

        $this->assertSame(0, ic_pengaturan::query()->where('kunci', 'like', 'tampilan.%')->count());
    }

    public function test_nilai_rusak_di_database_diabaikan_dan_tidak_masuk_ke_css(): void
    {
        ic_pengaturan::create(['kunci' => 'tampilan.aksen', 'nilai' => 'red;}</style><script>alert(1)</script>']);
        ic_pengaturan::create(['kunci' => 'tampilan.kartu', 'nilai' => '#12']);
        app(IcKonten::class)->lupakan();

        $this->assertSame('', $this->tampilan()->css());
        $this->assertSame($this->tampilan()->bawaan(), $this->tampilan()->warna());
        $this->assertStringNotContainsString('<script>alert', $this->halamanMasuk());
    }

    public function test_kontras_rendah_ditolak_dengan_pesan_jelas(): void
    {
        // Kartu gelap: teks gelap tidak terbaca.
        $this->kirim(['tampilan__kartu' => '#222233'])->assertSessionHasErrorsIn('tampilan', 'warna');
        $pesan = session('errors')->getBag('tampilan')->first('warna');
        $this->assertStringContainsString('Warna kartu kurang kontras', $pesan);
        $this->assertStringContainsString('minimal 4,5:1', $pesan);

        // Latar gelap untuk teks utama.
        $this->kirim(['tampilan__latar' => '#101018'])->assertSessionHasErrorsIn('tampilan', 'warna');
        $this->assertStringContainsString('Warna latar terlalu gelap', session('errors')->getBag('tampilan')->first('warna'));

        // Aksen terlalu pudar di atas kartu.
        $this->kirim(['tampilan__aksen' => '#f0f0f8'])->assertSessionHasErrorsIn('tampilan', 'warna');
        $this->assertStringContainsString('Warna aksen kurang kontras', session('errors')->getBag('tampilan')->first('warna'));

        $this->assertSame(0, ic_pengaturan::query()->where('kunci', 'like', 'tampilan.%')->count());
    }

    public function test_kontras_dihitung_dari_gabungan_bukan_dari_isian_yang_diubah_saja(): void
    {
        // Aksen yang lolos di kartu bawaan bisa gagal bila kartunya diganti sekaligus.
        $this->kirim(['tampilan__aksen' => '#7a66dd'])->assertSessionHasNoErrors();
        $this->kirim(['tampilan__aksen' => '#7a66dd', 'tampilan__kartu' => '#6a5acd'])->assertSessionHasErrorsIn('tampilan');
        $this->assertSame('#7a66dd', $this->tampilan()->warna()['aksen']);
        $this->assertSame('#ffffff', $this->tampilan()->warna()['kartu']);
    }

    public function test_hitungan_kontras_wcag(): void
    {
        $this->assertEqualsWithDelta(21.0, IcTampilan::rasio('#000000', '#ffffff'), 0.01);
        $this->assertEqualsWithDelta(1.0, IcTampilan::rasio('#8d73ff', '#8d73ff'), 0.001);
        $this->assertSame('4,4:1', IcTampilan::tampilRasio(4.46), 'dibulatkan ke bawah supaya tidak tampak lolos');
        $this->assertSame('#808080', IcTampilan::campur('#000000', '#ffffff', 0.5));
    }

    // ---------- Nama program ----------

    public function test_nama_program_dipakai_di_judul_teks_bawaan_dan_logo(): void
    {
        $this->kirim(['tampilan__program' => 'Whisnu Crew'])->assertSessionHasNoErrors();

        $this->assertSame('Whisnu Crew', ic_program());
        $this->assertSame('Whisnu Crew', ic_teks('tentang.hero.judul'));
        $this->assertSame('Gabung Whisnu Crew', ic_teks('daftar.judul'));

        $html = $this->halamanMasuk();
        $this->assertStringContainsString('alt="Whisnu Crew"', $html);
        $this->assertStringContainsString('<meta property="og:site_name" content="Whisnu Crew">', $html);
        $this->assertStringContainsString('og:image:alt" content="Whisnu Crew, Official Fanbase', $html);

        // Halaman tanpa judul sendiri memakai nama program sebagai judul tab; Tentang memakai teks `tentang.meta.judul`.
        $this->assertStringContainsString('<title>Whisnu Crew, komunitas penggemar — Whisnu Santika</title>', $this->get(route('ic.tentang'))->getContent());
    }

    public function test_nama_program_ditolak_bila_berbahaya_atau_kepanjangan(): void
    {
        foreach (['<b>Crew</b>', 'Crew {nama}', str_repeat('x', 31)] as $buruk) {
            $this->kirim(['tampilan__program' => $buruk])->assertSessionHasErrorsIn('tampilan', 'k.tampilan__program');
        }
        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'tampilan.program']);
    }

    public function test_nama_program_ter_escape_dan_sama_dengan_bawaan_menghapus_baris(): void
    {
        $this->kirim(['tampilan__program' => 'Tom & "Jerry"'])->assertSessionHasNoErrors();
        $html = $this->halamanMasuk();
        $this->assertStringContainsString('Tom &amp; &quot;Jerry&quot;', $html);
        $this->assertStringNotContainsString('Tom & "Jerry"', $html);

        $this->kirim(['tampilan__program' => 'WS Inner Circle'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'tampilan.program']);
    }

    // ---------- Logo ----------

    public function test_logo_diolah_disimpan_dan_dipakai_di_halaman(): void
    {
        $this->kirim([], ['logo' => $this->logoPng()])->assertSessionHasNoErrors();

        $path = ic_pengaturan::query()->where('kunci', 'tampilan.logo')->value('nilai');
        $this->assertMatchesRegularExpression('#^inner-circle/logo-kustom/[0-9a-f-]{36}\.webp$#', $path);
        $this->diskPublik()->assertExists($path);
        $this->assertSame('RIFF0000WEBPfake-800', Storage::disk('public')->get($path), 'sisi terpanjang logo mengikuti config');

        $html = $this->halamanMasuk();
        $this->assertStringContainsString(Storage::url($path), $html);
        $this->assertStringNotContainsString('wsic-wordmark.webp', $html);
    }

    public function test_logo_baru_menggantikan_dan_menghapus_berkas_lama(): void
    {
        $this->kirim([], ['logo' => $this->logoPng()]);
        $lama = ic_pengaturan::query()->where('kunci', 'tampilan.logo')->value('nilai');

        $this->kirim([], ['logo' => $this->logoPng('baru.png')])->assertSessionHasNoErrors();
        $baru = ic_pengaturan::query()->where('kunci', 'tampilan.logo')->value('nilai');

        $this->assertNotSame($lama, $baru);
        $this->diskPublik()->assertMissing($lama);
        $this->diskPublik()->assertExists($baru);
    }

    public function test_simpan_tanpa_logo_baru_tidak_menghapus_logo_yang_ada(): void
    {
        $this->kirim([], ['logo' => $this->logoPng()]);
        $logo = ic_pengaturan::query()->where('kunci', 'tampilan.logo')->value('nilai');

        $this->kirim(['tampilan__program' => 'Crew'])->assertSessionHasNoErrors();

        $this->diskPublik()->assertExists($logo);
        $this->assertNotNull($this->tampilan()->logoUrl());
    }

    public function test_hapus_logo_kembali_ke_logo_bawaan(): void
    {
        $this->kirim([], ['logo' => $this->logoPng()]);
        $logo = ic_pengaturan::query()->where('kunci', 'tampilan.logo')->value('nilai');

        $this->kirim([], ['hapus_logo' => '1'])->assertSessionHasNoErrors();

        $this->diskPublik()->assertMissing($logo);
        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'tampilan.logo']);
        $this->assertStringContainsString('wsic-wordmark.webp', $this->halamanMasuk());
    }

    public function test_logo_gagal_diolah_tidak_mengubah_apa_pun(): void
    {
        $this->pakaiPemrosesPalsu(fn() => throw new GambarGagal('Berkas ini bukan gambar yang valid.'));

        $this->kirim(['tampilan__program' => 'Crew'], ['logo' => $this->logoPng()])
            ->assertSessionHasErrorsIn('tampilan', 'logo');

        $this->assertSame(0, ic_pengaturan::query()->where('kunci', 'like', 'tampilan.%')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_logo_bukan_gambar_atau_terlalu_besar_ditolak(): void
    {
        $this->kirim([], ['logo' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')])
            ->assertSessionHasErrorsIn('tampilan', 'logo');
        $this->kirim([], ['logo' => UploadedFile::fake()->create('besar.png', 5000, 'image/png')])
            ->assertSessionHasErrorsIn('tampilan', 'logo');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_jalur_logo_yang_diubah_manual_diabaikan_dan_tidak_pernah_dihapus(): void
    {
        Storage::disk('public')->put('inner-circle/avatar/milik-anggota.webp', 'x');
        ic_pengaturan::create(['kunci' => 'tampilan.logo', 'nilai' => 'inner-circle/avatar/milik-anggota.webp']);
        app(IcKonten::class)->lupakan();

        $this->assertNull($this->tampilan()->logoUrl());

        $this->kirim([], ['hapus_logo' => '1'])->assertSessionHasNoErrors();
        $this->diskPublik()->assertExists('inner-circle/avatar/milik-anggota.webp');
    }

    public function test_berkas_logo_yang_hilang_memakai_logo_bawaan(): void
    {
        $this->kirim([], ['logo' => $this->logoPng()]);
        $logo = ic_pengaturan::query()->where('kunci', 'tampilan.logo')->value('nilai');
        Storage::disk('public')->delete($logo);

        $this->assertNull($this->tampilan()->logoUrl());
        $this->assertStringContainsString('wsic-wordmark.webp', $this->halamanMasuk());
    }

    // ---------- Kembalikan bawaan ----------

    public function test_kembalikan_semua_ke_bawaan(): void
    {
        $this->kirim(['tampilan__program' => 'Crew', 'tampilan__aksen' => '#1a5fb4'], ['logo' => $this->logoPng()]);
        $logo = ic_pengaturan::query()->where('kunci', 'tampilan.logo')->value('nilai');
        ic_pengaturan::create(['kunci' => 'checkin.eyebrow', 'nilai' => 'Teks lain yang tidak boleh ikut hilang']);

        $this->admin()->post(route('ic.admin.konten.tampilan.bawaan'))
            ->assertRedirect(route('ic.admin.konten.bagian', 'tampilan'))->assertSessionHas('success');

        $this->assertSame(0, ic_pengaturan::query()->where('kunci', 'like', 'tampilan.%')->count());
        $this->assertDatabaseHas('ic_pengaturan', ['kunci' => 'checkin.eyebrow']);
        $this->diskPublik()->assertMissing($logo);
        $this->assertSame('', $this->tampilan()->css());
        $this->assertSame('WS Inner Circle', ic_program());
    }

    // ---------- Pratinjau dan CMS ----------

    public function test_pratinjau_memakai_isian_belum_disimpan_tanpa_menulis_database(): void
    {
        $res = $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'tampilan',
            'halaman' => 'masuk',
            'k' => ['tampilan__aksen' => '#1a5fb4', 'tampilan__program' => 'Whisnu Crew'],
        ])->assertOk();

        $this->assertStringContainsString('--ic-aksen:#1a5fb4', $res->getContent());
        $this->assertStringContainsString('Whisnu Crew', $res->getContent());
        $this->assertSame(0, ic_pengaturan::query()->count());

        // Permintaan berikutnya tidak terbawa draf.
        $this->assertSame('', $this->tampilan()->css());
        $this->assertSame('WS Inner Circle', ic_program());
    }

    public function test_pratinjau_membuang_warna_tidak_valid_dan_semua_halaman_tampilan_bisa_dirender(): void
    {
        foreach (array_keys(config('ic_konten.tampilan.pratinjau')) as $halaman) {
            $res = $this->admin()->post(route('ic.admin.konten.pratinjau'), [
                'grup' => 'tampilan',
                'halaman' => $halaman,
                'k' => ['tampilan__aksen' => 'bukan-warna</style>'],
            ])->assertOk();

            $this->assertStringNotContainsString('bukan-warna', $res->getContent());
            $this->assertStringNotContainsString('body.ic-app{', $res->getContent());
        }
    }

    public function test_halaman_konten_menampilkan_bagian_tampilan_dengan_nilai_tersimpan(): void
    {
        $this->admin()->get(route('ic.admin.konten.bagian', 'tampilan'))
            ->assertOk()
            ->assertSee('id="tampilan"', false)
            ->assertSee('Nama program')
            ->assertSee('Warna aksen')
            ->assertSee('memakai logo bawaan.');

        $this->kirim(['tampilan__program' => 'Whisnu Crew', 'tampilan__aksen' => '#1a5fb4'], ['logo' => $this->logoPng()]);

        $this->admin()->get(route('ic.admin.konten.bagian', 'tampilan'))
            ->assertOk()
            ->assertSee('value="Whisnu Crew"', false)
            ->assertSee('value="#1a5fb4"', false)
            ->assertSee('Logo kustom saat ini')
            ->assertDontSee('memakai logo bawaan.');
    }

    public function test_isian_form_dipulihkan_saat_ditolak(): void
    {
        // Browser mengikuti redirect dengan cookie sesi yang sama; di tes, followingRedirects meniru itu.
        $this->admin()->followingRedirects()
            ->post(route('ic.admin.konten.tampilan.simpan'), ['k' => ['tampilan__program' => 'Crew Baru', 'tampilan__aksen' => '', 'tampilan__latar' => '', 'tampilan__kartu' => '#222233']])
            ->assertOk()
            ->assertSee('value="Crew Baru"', false)
            ->assertSee('value="#222233"', false)
            ->assertSee('Warna kartu kurang kontras');
    }

    public function test_hanya_admin_yang_boleh_mengubah_tampilan(): void
    {
        $this->post(route('ic.admin.konten.tampilan.simpan'), ['k' => ['tampilan__program' => 'x']])->assertRedirect(route('login'));
        $this->post(route('ic.admin.konten.tampilan.bawaan'))->assertRedirect(route('login'));

        $this->assertSame(0, ic_pengaturan::query()->count());
    }

    public function test_tampilan_tidak_terdaftar_sebagai_grup_teks_biasa(): void
    {
        $this->assertArrayNotHasKey('tampilan', config('ic_konten.grup'));
        $this->admin()->post(route('ic.admin.konten.simpan', 'tampilan'), ['k' => ['tampilan__program' => 'x']])->assertNotFound();
        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'tidak_ada'])->assertNotFound();
    }
}