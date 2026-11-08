<?php

namespace Tests\Feature\InnerCircle\KontenCms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tiap modul Konten memuat panel pratinjau dan form-nya sendiri.
 */
class PanelPratinjauTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Pratinjau replika ----------
    // ---------- Lencana belum dibaca ----------
    // ---------- Logo, favicon, Open Graph ----------

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_tiap_modul_konten_memuat_panel_pratinjau_dan_formnya_sendiri(): void
    {
        // Tiap modul (formulir kecil) punya satu panel pratinjau; tampilan/merek punya satu panel di bagiannya sendiri (Fase 6).
        foreach (array_keys(config('ic_konten.grup')) as $kode) {
            $modul = \App\Services\InnerCircle\IcBagian::modul($kode);
            $html = $this->admin()->get(route('ic.admin.konten.bagian', $kode))->assertOk()->getContent();
            $this->assertSame(count($modul), preg_match_all('/\sdata-replika(?=[\s>])/', $html), $kode);
            $this->assertStringNotContainsString('<iframe', $html, $kode);
            foreach ($modul as $slug => $m) {
                if ($m['kunci'] !== []) {
                    $this->assertStringContainsString('id="form-' . $kode . '-' . $slug . '"', $html, "$kode/$slug");
                }
            }
        }

        $tampilan = $this->admin()->get(route('ic.admin.konten.bagian', 'tampilan'))->assertOk()->getContent();
        $this->assertSame(1, preg_match_all('/\sdata-replika-tampilan(?=[\s>])/', $tampilan));
        $this->assertStringNotContainsString('<iframe', $tampilan);
        $this->assertStringContainsString('id="form-tampilan"', $tampilan);
    }
}
