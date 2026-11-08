<?php

namespace Tests\Feature\InnerCircle\Komunitas;

use App\Models\ic_memory;
use App\Models\member;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Foto profil anggota: unggah, ganti, hapus, dan tampil di kenangan.
 */
class FotoProfilTest extends TestCase
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
