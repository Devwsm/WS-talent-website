<?php

namespace Tests\Feature;

use App\Models\ic_pengaturan;
use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 4: teks layar anggota (beranda, hadiah, komunitas, profil, masuk/daftar) bisa diubah dari tab Konten. */
class InnerCircleFase4Test extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function anggota(string $nama = 'Ancha Maulana'): member
    {
        $m = member::create(['nama' => $nama, 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function ubah(string $grup, array $isi): void
    {
        $this->admin()->post(route('ic.admin.konten.simpan', $grup), ['k' => $isi])->assertSessionHasNoErrors();
    }

    public function test_tampilan_default_semua_layar_tidak_berubah(): void
    {
        // Halaman tamu dibuka dulu: sesudah actingAs, halaman masuk/daftar mengalihkan anggota ke beranda.
        $this->get(route('ic.masuk'))->assertOk()->assertSee('Selamat datang kembali');
        $this->get(route('ic.daftar'))->assertOk()->assertSee('Gabung WS Inner Circle');
        $this->get(route('ic.sandi.lupa'))->assertOk()->assertSee('Lupa kata sandi?');
        $this->get(route('ic.sandi.atur', 'abc123'))->assertOk()->assertSee('Buat kata sandi baru');

        $m = $this->anggota();

        $this->actingAs($m, 'member')->get(route('ic.beranda'))->assertOk()
            ->assertSee('Musik seru. Teman baru.')->assertSee('Hai, Ancha!')
            ->assertSee('Terima kasih sudah menjadi bagian dari keluarga WS Inner Circle.')
            ->assertSee('Setiap momen berarti')->assertSee('Aktivitas terbaru ✦')
            ->assertSee('KARTU KEANGGOTAAN DIGITAL')->assertSee('Stempel terus terkumpul dan tidak hangus');

        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertOk()
            ->assertSee('Kumpulkan • Buka • Rasakan')->assertSee('Klaim hadiahmu ↗')->assertSee('✦ Cara kerja hadiah');

        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertOk()
            ->assertSee('Lebih banyak musik • Teman yang sama')
            ->assertSee('Lebih banyak musik.<br>Teman yang sama.', false)
            ->assertSee('✦ WS INNER CIRCLE ✦')->assertSee('Kabar terbaru ✳');

        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertOk()
            ->assertSee('Galeri kenangan ♡')->assertSee('<b>Simpan momen terbaikmu.</b> Bagikan', false)
            ->assertSee('Penggemar, foto &amp; cerita', false);

        $this->actingAs($m, 'member')->get(route('ic.profil'))->assertOk()
            ->assertSee('Perjalanan Inner Circle-mu')->assertSee('(maksimal 5 MB)', false)
            ->assertSee('Belum ada pengajuan. Buka <a class="ic-tautan" href="' . route('ic.hadiah') . '">Hadiah</a> untuk', false)
            ->assertSee('Datamu hanya dipakai untuk Inner Circle. <a class="ic-tautan" href="' . route('privasi') . '" target="_blank" rel="noopener">Baca Kebijakan Privasi</a>.', false);
    }

    public function test_perubahan_cms_tampil_di_layar_anggota(): void
    {
        $this->ubah('beranda', [
            'beranda__salam' => 'Halo {nama}, apa kabar?',
            'beranda__stiker' => 'Lihat kabar',
            'beranda__stempel__sisa' => 'langkah lagi menuju hadiah.',
        ]);
        $this->ubah('hadiah', ['hadiah__eyebrow' => 'Buka Hadiah']);
        $this->ubah('komunitas', ['komunitas__banner__judul' => "Satu musik.\nSatu keluarga."]);
        $this->ubah('profil', ['profil__klaim__kosong' => 'Kosong. Lihat {hadiah} ya.']);
        $this->ubah('masuk', ['masuk__judul' => 'Masuk Dulu', 'atur__intro' => 'Sandi baru ya.']);

        $this->get(route('ic.masuk'))->assertSee('Masuk Dulu');
        $this->get(route('ic.sandi.atur', 'abc123'))->assertSee('Sandi baru ya.');

        $m = $this->anggota();
        $this->actingAs($m, 'member')->get(route('ic.beranda'))
            ->assertSee('Halo Ancha, apa kabar?')->assertSee('Lihat kabar')->assertSee('langkah lagi menuju hadiah.')
            ->assertDontSee('Hai, Ancha!');
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertSee('Buka Hadiah');
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertSee('Satu musik.<br>Satu keluarga.', false);
        $this->actingAs($m, 'member')->get(route('ic.profil'))
            ->assertSee('Kosong. Lihat <a class="ic-tautan" href="' . route('ic.hadiah') . '">Hadiah</a> ya.', false);
    }

    public function test_nama_anggota_di_sapaan_ter_escape(): void
    {
        $m = $this->anggota('<i>Budi</i> Santoso');

        $html = $this->actingAs($m, 'member')->get(route('ic.beranda'))->assertOk()->getContent();

        $this->assertStringContainsString('Hai, &lt;i&gt;Budi&lt;/i&gt;!', $html);
        $this->assertStringNotContainsString('<i>Budi</i>', $html);
    }

    public function test_placeholder_hanya_di_kunci_yang_mengizinkannya(): void
    {
        $this->admin()->post(route('ic.admin.konten.simpan', 'beranda'), ['k' => ['beranda__eyebrow' => 'Hai {nama}']])
            ->assertSessionHasErrors('k.beranda__eyebrow');
        $this->admin()->post(route('ic.admin.konten.simpan', 'profil'), ['k' => ['profil__akun__deskripsi' => 'Lihat {hadiah}.']])
            ->assertSessionHasErrors('k.profil__akun__deskripsi'); // tautan hadiah tidak diizinkan di kunci ini
        $this->admin()->post(route('ic.admin.konten.simpan', 'komunitas'), ['k' => ['komunitas__banner__judul' => 'A <b>B</b>']])
            ->assertSessionHasErrors('k.komunitas__banner__judul');

        $this->assertSame(0, ic_pengaturan::count());
    }

    public function test_semua_grup_tampil_di_tab_konten_dengan_petunjuk_placeholder(): void
    {
        $this->admin()->get(route('ic.admin.konten'))->assertOk()
            ->assertSee('Beranda anggota')->assertSee('Halaman Hadiah')->assertSee('Halaman Komunitas')
            ->assertSee('Halaman Profil anggota')->assertSee('Halaman masuk dan daftar')
            ->assertSee('Bisa memakai:')->assertSee('{nama}', false);
    }
}