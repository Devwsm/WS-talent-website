<?php

namespace Tests\Feature\Cms;

use App\Models\header;
use App\Models\news;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Validasi tanggal berita (harus tanggal sungguhan, bukan teks bebas) dan batas ukuran Cover Header di tingkat
 * validasi (sebelum berkas diproses).
 */
class ValidasiBeritaHeaderTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    /** Isian berita yang sengaja dibiarkan kurang (tanpa judul) supaya hanya validasi yang diuji, tanpa memproses gambar. */
    private function berita(string $tanggal): array
    {
        return [
            'news_description' => '<p>Isi</p>',
            'news_source' => 'Sumber',
            'news_date' => $tanggal,
            'news_link' => 'https://contoh.test/berita',
        ];
    }

    public function test_tanggal_berita_teks_bebas_atau_tidak_nyata_ditolak(): void
    {
        foreach (['1 Januari 2026', '31/12/2026', '2026-02-31', 'kemarin', '2026-13-01'] as $nilai) {
            $this->admin()->post(route('news.tambah'), $this->berita($nilai))
                ->assertSessionHasErrors(['news_date' => 'Tanggal Berita tidak valid. Pilih tanggal lewat pemilih tanggal.']);
        }
    }

    public function test_tanggal_berita_iso_lolos_validasi_tanggal(): void
    {
        $this->admin()->post(route('news.tambah'), $this->berita('2026-11-12'))
            ->assertSessionHasErrors(['news_title'])
            ->assertSessionDoesntHaveErrors(['news_date']);
    }

    public function test_tanggal_kosong_tetap_meminta_diisi_dan_update_memakai_aturan_sama(): void
    {
        $this->admin()->post(route('news.tambah'), $this->berita(''))
            ->assertSessionHasErrors(['news_date' => 'Tanggal Berita harus diisi.']);

        $berita = news::create([
            'news_title' => 'Lama',
            'news_description' => '<p>x</p>',
            'news_source' => 'S',
            'news_date' => '1 Januari 2026',
            'news_cover' => 'x.webp',
            'news_link' => 'https://contoh.test',
        ]);

        $this->admin()->put(route('news.update', $berita->id_news), $this->berita('bukan tanggal') + ['news_title' => 'Baru'])
            ->assertSessionHasErrors(['news_date']);
    }

    public function test_cover_header_lebih_dari_50mb_ditolak_di_validasi_tambah_dan_update(): void
    {
        $video = UploadedFile::fake()->create('besar.mp4', 51201, 'video/mp4');

        $this->admin()->post(route('headers.tambah'), ['header_background' => $video])
            ->assertSessionHasErrors(['header_background' => 'Cover Header tidak boleh lebih dari 50MB (gambar maksimal 1MB).']);

        $ada = header::create([
            'header_color' => '#112233',
            'header_title' => 'T',
            'header_img' => 'a.webp',
            'header_name' => 'N',
            'header_description' => 'D',
            'link_header' => 'https://contoh.test',
            'header_background' => 'b.webp',
        ]);

        $this->admin()->put(route('headers.update', $ada->id_header), ['header_background' => UploadedFile::fake()->create('besar.mp4', 51201, 'video/mp4')])
            ->assertSessionHasErrors(['header_background' => 'Cover Header tidak boleh lebih dari 50MB (gambar maksimal 1MB).']);
    }

    public function test_cover_header_bukan_gambar_atau_video_ditolak(): void
    {
        $this->admin()->post(route('headers.tambah'), ['header_background' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors(['header_background' => 'File harus berupa gambar (jpg, jpeg, png) atau video (mp4, mov, avi, mkv, webm).']);
    }
}