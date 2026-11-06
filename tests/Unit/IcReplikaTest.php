<?php

namespace Tests\Unit;

use App\Support\IcReplika;
use PHPUnit\Framework\TestCase;

/** Bentuk replika pratinjau tab Konten: murni PHP, tanpa database dan tanpa Laravel. */
class IcReplikaTest extends TestCase
{
    public function test_tipe_tiap_kunci_dikenali(): void
    {
        $this->assertSame('meta-judul', IcReplika::tipe('tentang.meta.judul'));
        $this->assertSame('meta-deskripsi', IcReplika::tipe('tentang.meta.deskripsi'));
        $this->assertSame('judul', IcReplika::tipe('tentang.hero.judul'));
        $this->assertSame('judul', IcReplika::tipe('beranda.salam'));
        $this->assertSame('judul-bagian', IcReplika::tipe('tentang.faq.judul'));
        $this->assertSame('eyebrow', IcReplika::tipe('tentang.hero.eyebrow'));
        $this->assertSame('eyebrow', IcReplika::tipe('komunitas.unggulan.label'));
        $this->assertSame('tombol', IcReplika::tipe('tentang.tombol.daftar'));
        $this->assertSame('tombol', IcReplika::tipe('komunitas.galeri.tombol'));
        $this->assertSame('tombol-garis', IcReplika::tipe('tentang.tombol.masuk'));
        $this->assertSame('tombol-garis', IcReplika::tipe('tentang.cta.masuk'));
        $this->assertSame('tautan', IcReplika::tipe('teaser.tautan'));
        $this->assertSame('deskripsi', IcReplika::tipe('tentang.hero.deskripsi'));
        $this->assertSame('deskripsi', IcReplika::tipe('beranda.aktivitas.kosong'));
    }

    public function test_placeholder_program_dan_tautan_diganti_lainnya_dibiarkan(): void
    {
        $peta = IcReplika::petaGanti('Whisnu Crew', ['privasi' => ['label' => 'Kebijakan Privasi']]);

        $this->assertSame(
            'Selamat datang di Whisnu Crew, {nama}. Baca Kebijakan Privasi.',
            IcReplika::ganti('Selamat datang di {program}, {nama}. Baca {privasi}.', $peta),
        );
        $this->assertSame('tanpa placeholder', IcReplika::ganti('tanpa placeholder', []));
    }
}