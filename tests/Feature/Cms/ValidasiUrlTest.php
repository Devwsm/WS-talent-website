<?php

namespace Tests\Feature\Cms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tautan di CMS hanya boleh http/https (javascript: ditolak).
 */
class ValidasiUrlTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Token rusak ("The MAC is invalid") ----------
    // ---------- Bandsintown ----------
    // ---------- Lupa kata sandi ----------
    // ---------- Tab Email ----------
    // ---------- Validasi link CMS ----------

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_link_banner_javascript_ditolak(): void
    {
        $this->admin()->post(route('banner.tambah'), ['banner_name' => 'x', 'link_banner' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('link_banner');
    }
}
