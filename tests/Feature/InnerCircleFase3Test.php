<?php

namespace Tests\Feature;

use App\Models\ic_item;
use App\Models\member;
use App\Services\InnerCircle\IcKonten;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 3: halaman Tentang dan teaser beranda sepenuhnya dari CMS, plus pengelola daftar (langkah, FAQ, poin teaser). */
class InnerCircleFase3Test extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function rute(string $nama, string $daftar, ?int $id = null): string
    {
        return route('ic.admin.konten.item.' . $nama, array_filter([$daftar, $id], fn($v) => $v !== null));
    }

    private function id(string $grup, string $judul): int
    {
        return (int) ic_item::where('grup', $grup)->where('judul', $judul)->value('id_item');
    }

    private function judul(string $grup): array
    {
        return ic_item::where('grup', $grup)->orderBy('urutan')->pluck('judul')->all();
    }

    public function test_migrasi_menanam_daftar_bawaan_dan_tampilan_default_tidak_berubah(): void
    {
        $this->assertSame(4, ic_item::where('grup', 'langkah')->count());
        $this->assertSame(4, ic_item::where('grup', 'faq')->count());
        $this->assertSame(3, ic_item::where('grup', 'teaser_poin')->count());

        $this->get(route('ic.tentang'))->assertOk()
            ->assertSee('<title>WS Inner Circle, komunitas penggemar — Whisnu Santika</title>', false)
            ->assertSee('Komunitas penggemar resmi')
            ->assertSee('Cara kerjanya')
            ->assertSeeInOrder(['Daftar', 'Datang ke acara', 'Kumpulkan stempel', 'Buka hadiah'])
            ->assertSee('Pertanyaan umum')
            ->assertSee('Apa itu stempel?')
            ->assertSee('<a class="ic-tautan" href="' . route('privasi') . '">Kebijakan Privasi</a>, dan kamu', false)
            ->assertSee('Siap bergabung?');

        $this->get('/')->assertOk()
            ->assertSee('Check-in QR di acara')
            ->assertSee('bi-qr-code-scan', false)
            ->assertSee('Pelajari cara kerjanya');
    }

    public function test_teks_tentang_dan_teaser_bisa_diubah_dan_ter_escape(): void
    {
        $this->admin()->post(route('ic.admin.konten.simpan', 'tentang'), ['k' => [
            'tentang__hero__judul' => 'Rumah Kita & "Bersama"',
            'tentang__faq__judul' => 'Tanya Jawab',
        ]])->assertSessionHasNoErrors();
        $this->admin()->post(route('ic.admin.konten.simpan', 'teaser'), ['k' => ['teaser__tombol__gabung' => 'Ikut Yuk']])
            ->assertSessionHasNoErrors();

        $html = $this->get(route('ic.tentang'))->assertOk()->getContent();
        $this->assertStringContainsString('Rumah Kita &amp; &quot;Bersama&quot;', $html);
        $this->assertStringContainsString('Tanya Jawab', $html);
        $this->assertStringNotContainsString('Pertanyaan umum', $html);

        $this->get('/')->assertSee('Ikut Yuk')->assertDontSee('Gabung sekarang');
    }

    public function test_tambah_ubah_sembunyi_dan_hapus_butir_langsung_tampil_di_halaman(): void
    {
        $this->admin()->post($this->rute('tambah', 'langkah'), ['judul' => 'Bagikan kenangan', 'isi' => 'Unggah foto acara.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Bagikan kenangan', last($this->judul('langkah')));
        $this->get(route('ic.tentang'))->assertSee('Bagikan kenangan')->assertSee('Unggah foto acara.');

        $id = $this->id('langkah', 'Bagikan kenangan');
        $this->admin()->put($this->rute('ubah', 'langkah', $id), ['judul' => 'Bagikan momen', 'isi' => 'Baru.'])
            ->assertSessionHasNoErrors();
        $this->get(route('ic.tentang'))->assertSee('Bagikan momen')->assertDontSee('Bagikan kenangan');

        $this->admin()->patch($this->rute('aktif', 'langkah', $id), ['aktif' => 0]);
        $this->get(route('ic.tentang'))->assertDontSee('Bagikan momen');
        $this->assertDatabaseHas('ic_item', ['id_item' => $id, 'aktif' => false]);

        $this->admin()->patch($this->rute('aktif', 'langkah', $id), ['aktif' => 1]);
        $this->get(route('ic.tentang'))->assertSee('Bagikan momen');

        $this->admin()->delete($this->rute('hapus', 'langkah', $id));
        $this->get(route('ic.tentang'))->assertDontSee('Bagikan momen');
        $this->assertSame([1, 2, 3, 4], ic_item::where('grup', 'langkah')->orderBy('urutan')->pluck('urutan')->all());
    }

    public function test_geser_menukar_urutan_dan_di_ujung_tidak_berubah(): void
    {
        $awal = $this->judul('langkah');
        $kedua = $this->id('langkah', $awal[1]);

        $this->admin()->patch($this->rute('geser', 'langkah', $kedua), ['arah' => 'naik'])->assertSessionHasNoErrors();
        $this->assertSame([$awal[1], $awal[0], $awal[2], $awal[3]], $this->judul('langkah'));
        $this->get(route('ic.tentang'))->assertSeeInOrder([$awal[1], $awal[0]]);

        $this->admin()->patch($this->rute('geser', 'langkah', $kedua), ['arah' => 'naik']); // sudah paling atas
        $this->assertSame([$awal[1], $awal[0], $awal[2], $awal[3]], $this->judul('langkah'));

        $this->admin()->patch($this->rute('geser', 'langkah', $kedua), ['arah' => 'sembarang'])->assertSessionHasErrors('arah');
    }

    public function test_semua_butir_disembunyikan_menyembunyikan_bagiannya(): void
    {
        ic_item::where('grup', 'faq')->update(['aktif' => false]);
        app(IcKonten::class)->lupakan();

        $this->get(route('ic.tentang'))->assertOk()->assertDontSee('Pertanyaan umum')->assertDontSee('Apa itu stempel?');

        ic_item::where('grup', 'teaser_poin')->update(['aktif' => false]);
        app(IcKonten::class)->lupakan();
        $this->get('/')->assertOk()->assertDontSee('Check-in QR di acara')->assertSee('WS Inner Circle');
    }

    public function test_batas_jumlah_butir_dan_kembalikan_bawaan(): void
    {
        $maks = config('ic_konten.daftar.teaser_poin.maks_item');
        for ($i = ic_item::where('grup', 'teaser_poin')->count(); $i < $maks; $i++) {
            $this->admin()->post($this->rute('tambah', 'teaser_poin'), ['judul' => "Poin $i", 'ikon' => 'heart'])->assertSessionHasNoErrors();
        }
        $this->admin()->post($this->rute('tambah', 'teaser_poin'), ['judul' => 'Kelebihan', 'ikon' => 'heart'])
            ->assertSessionHas('error');
        $this->assertSame($maks, ic_item::where('grup', 'teaser_poin')->count());

        $this->admin()->post($this->rute('bawaan', 'teaser_poin'))->assertSessionHas('success');
        $this->assertSame(['Check-in QR di acara', 'Hadiah di tiap tahap stempel', 'Galeri kenangan penggemar'], $this->judul('teaser_poin'));
    }

    public function test_validasi_butir_galat_per_form_dan_tanda_berbahaya_ditolak(): void
    {
        $this->admin()->post($this->rute('tambah', 'faq'), ['judul' => 'Halo <b>', 'isi' => 'ok'])
            ->assertSessionHasErrorsIn('item_baru_faq', 'judul');
        $this->admin()->post($this->rute('tambah', 'faq'), ['judul' => 'Tanya', 'isi' => 'Lihat {asing}'])
            ->assertSessionHasErrorsIn('item_baru_faq', 'isi');
        $this->admin()->post($this->rute('tambah', 'langkah'), ['judul' => 'x', 'isi' => 'Tautan {privasi}'])
            ->assertSessionHasErrorsIn('item_baru_langkah', 'isi'); // langkah tidak boleh memuat tautan
        $this->admin()->post($this->rute('tambah', 'teaser_poin'), ['judul' => 'x', 'ikon' => 'tidak-ada'])
            ->assertSessionHasErrorsIn('item_baru_teaser_poin', 'ikon');
        $this->admin()->post($this->rute('tambah', 'faq'), ['judul' => str_repeat('a', 121), 'isi' => 'ok'])
            ->assertSessionHasErrorsIn('item_baru_faq', 'judul');

        $this->assertSame(4, ic_item::where('grup', 'faq')->count());
    }

    public function test_tautan_privasi_di_faq_dibuat_kode_dan_isi_lain_ter_escape(): void
    {
        $this->admin()->post($this->rute('tambah', 'faq'), ['judul' => 'Aman?', 'isi' => "Baca {privasi} & \"tenang\"\nBaris dua."])
            ->assertSessionHasNoErrors();

        $html = $this->get(route('ic.tentang'))->assertOk()->getContent();
        $this->assertStringContainsString('Baca <a class="ic-tautan" href="' . route('privasi') . '">Kebijakan Privasi</a> &amp; &quot;tenang&quot;<br>', $html);
    }

    public function test_hanya_admin_dan_daftar_tidak_dikenal_404(): void
    {
        $this->post($this->rute('tambah', 'faq'), ['judul' => 'x', 'isi' => 'y'])->assertRedirect(route('login'));
        $this->admin()->post($this->rute('tambah', 'tidak_ada'), ['judul' => 'x'])->assertNotFound();
        $this->admin()->delete($this->rute('hapus', 'faq', 999999))->assertSessionHas('error');
    }

    public function test_halaman_konten_menampilkan_pengelola_daftar_dan_label_log(): void
    {
        $this->admin()->get(route('ic.admin.konten.bagian', 'tentang'))->assertOk()
            ->assertSee('Langkah &quot;Cara kerjanya&quot;', false)
            ->assertSee('Tambah pertanyaan')
            ->assertSee('Kembalikan daftar');
        $this->admin()->get(route('ic.admin.konten.bagian', 'teaser'))->assertOk()
            ->assertSee('id="daftar-teaser_poin"', false);
        $this->admin()->get(route('ic.admin.konten.bagian', 'masuk'))->assertOk()
            ->assertSee('id="grup-masuk"', false);

        foreach (['tambah', 'ubah', 'geser', 'aktif', 'hapus', 'bawaan'] as $aksi) {
            $this->assertNotNull(config("log_admin.rute.ic.admin.konten.item.$aksi") ?? config('log_admin.rute')["ic.admin.konten.item.$aksi"] ?? null);
        }
    }

    public function test_migrasi_tidak_menimpa_suntingan_admin(): void
    {
        $this->admin()->post($this->rute('tambah', 'faq'), ['judul' => 'Tambahan', 'isi' => 'ok']);
        app(\App\Services\InnerCircle\IcDaftar::class)->tanamBawaanBilaKosong();

        $this->assertSame(5, ic_item::where('grup', 'faq')->count());
        $this->assertTrue(member::count() === 0);
    }
}