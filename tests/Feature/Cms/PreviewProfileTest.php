<?php

namespace Tests\Feature\Cms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Preview CMS Profile (form kiri, preview kanan): kesembilan section memakai satu bingkai yang sama supaya kartu
 * preview selalu setinggi form (tidak ada ruang kosong di desktop) dan tampil sebagai layar skala mobile.
 */
class PreviewProfileTest extends TestCase
{
    use RefreshDatabase;

    private const SECTION = ['profile-card', 'genre', 'bio', 'statistik', 'highlight', 'collab', 'media-coverage', 'booking', 'media-sosial'];

    private function halaman(): string
    {
        return $this->withSession(['login' => true, 'user' => 'admin'])
            ->get('/dashboard/profile')
            ->assertOk()
            ->getContent();
    }

    public function test_sembilan_section_memakai_bingkai_preview_yang_sama(): void
    {
        $html = $this->halaman();

        $this->assertSame(9, substr_count($html, 'Skala mobile'), 'Tiap section harus punya satu bingkai preview.');
        $this->assertSame(9, substr_count($html, 'items-stretch'), 'Kartu preview harus meregang setinggi kartu form.');
        $this->assertStringContainsString('overflow-clip', $html, 'overflow-hidden mematikan sticky; pakai overflow-clip.');
        $this->assertStringNotContainsString('lg:sticky lg:top-8 rounded-3xl border border-white/10 bg-black overflow-hidden', $html, 'Pembungkus preview lama tidak boleh tersisa.');
    }

    public function test_tiap_komponen_section_memakai_komponen_bingkai(): void
    {
        foreach (self::SECTION as $nama) {
            $isi = file_get_contents(resource_path("views/components/profile/dashboard/{$nama}.blade.php"));

            $this->assertStringContainsString('<x-dashboard.preview-frame>', $isi, $nama);
            $this->assertStringContainsString('items-stretch', $isi, $nama);
            $this->assertStringNotContainsString('items-start', $isi, $nama);
        }
    }

    public function test_preview_draft_tidak_memakai_innerhtml_dengan_isian_form(): void
    {
        foreach (['statistik', 'collab', 'highlight', 'booking'] as $nama) {
            $isi = file_get_contents(resource_path("views/components/profile/dashboard/{$nama}.blade.php"));

            $this->assertStringNotContainsString('draft.innerHTML', $isi, "Preview {$nama}: isian form harus lewat textContent.");
            $this->assertStringContainsString('replaceChildren', $isi, $nama);
        }
    }

    public function test_preview_hero_tetap_menampilkan_data_tersimpan(): void
    {
        $this->seed(\Database\Seeders\profileSeeder::class);

        $html = $this->halaman();

        $this->assertStringContainsString('Tampilan di halaman Profile', $html);
        $this->assertStringContainsString('id="heroPreviewNama"', $html);
    }
}
