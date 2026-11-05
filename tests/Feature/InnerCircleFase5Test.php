<?php

namespace Tests\Feature;

use App\Models\ic_event;
use App\Models\ic_memory;
use App\Models\ic_pengaturan;
use App\Models\ic_pilihan;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\IcAturan;
use App\Services\InnerCircle\IcPilihan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 5 (pisah CMS): tipe acara, kategori kenangan, dan aturan bawaan check-in yang bisa diubah dari CMS. */
class InnerCircleFase5Test extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function anggota(): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function acara(string $tanggal = '2027-03-10', array $lain = []): ic_event
    {
        return app(AcaraService::class)->buat(['tanggal' => $tanggal, 'tipe' => 'club', 'venue' => 'Venue Uji', 'kota' => 'Bali'] + $lain);
    }

    private function dataKategori(array $ubah = []): array
    {
        return $ubah + ['label' => 'Konser Amal', 'ikon' => 'sparkles', 'sampul' => 'cd', 'warna' => 'hijau'];
    }

    // ---------- Bawaan identik ----------

    public function test_migrasi_mengisi_pilihan_sama_persis_dengan_config(): void
    {
        $this->assertSame(config('inner_circle.tipe_event'), collect(ic_tipe_event())->map(fn($t) => array_intersect_key($t, array_flip(['label', 'ikon', 'warna'])))->all());
        $this->assertSame(['festival', 'club', 'personal'], array_keys(ic_kategori_kenangan()));
        $this->assertSame('Acara klub', ic_kategori_kenangan()['club']['label']);
    }

    public function test_tabel_pilihan_kosong_memakai_config(): void
    {
        ic_pilihan::query()->delete();
        app(IcPilihan::class)->lupakan();

        $this->assertSame('Festival', ic_tipe_event()['festival']['label']);
        $this->assertCount(3, ic_kategori_kenangan());
    }

    // ---------- Tipe acara ----------

    public function test_label_tipe_baru_tampil_di_kartu_dan_filter_dan_kunci_tidak_berubah(): void
    {
        $a = $this->anggota();
        $this->acara('2027-03-10');

        $this->admin()->put(route('ic.admin.konten.pilihan.tipe.ubah', 'club'), ['label' => 'Pesta Klub', 'ikon' => 'knobs', 'warna' => 'biru'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('club', ic_event::first()->tipe);
        $html = $this->actingAs($a, 'member')->get(route('ic.checkin'))->assertOk()->getContent();
        $this->assertStringContainsString('Pesta Klub', $html);
        $this->assertStringContainsString('ic-warna-biru', $html);
    }

    public function test_tipe_tidak_bisa_ditambah_dan_isian_tidak_valid_ditolak(): void
    {
        $this->admin()->post('/dashboard/inner-circle/konten-pilihan/tipe', [])->assertNotFound();

        $this->admin()->put(route('ic.admin.konten.pilihan.tipe.ubah', 'club'), ['label' => '<b>x</b>', 'ikon' => 'bukan-ikon', 'warna' => 'ungu'])
            ->assertSessionHasErrorsIn('pilihan_tipe_club', ['label', 'ikon', 'warna']);
        $this->assertSame('Klub', ic_pilihan::where('kunci', 'club')->value('label'));

        $this->admin()->put(route('ic.admin.konten.pilihan.tipe.ubah', 'tidak_ada'), ['label' => 'X', 'ikon' => 'gift', 'warna' => 'biru'])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_tipe_privat_boleh_memakai_ikon_gembok_dan_bawaan_bisa_dikembalikan(): void
    {
        $this->admin()->put(route('ic.admin.konten.pilihan.tipe.ubah', 'private'), ['label' => 'Tertutup', 'ikon' => 'lock', 'warna' => 'pink'])->assertSessionHasNoErrors();
        $this->assertSame('Tertutup', ic_tipe_event()['private']['label']);

        $this->admin()->post(route('ic.admin.konten.pilihan.tipe.bawaan', 'private'))->assertSessionHas('success');
        $this->assertSame('Acara Privat', ic_tipe_event()['private']['label']);
    }

    // ---------- Kategori kenangan ----------

    public function test_tambah_kategori_membuat_kunci_dan_bisa_dipilih_anggota(): void
    {
        $a = $this->anggota();
        $this->admin()->post(route('ic.admin.konten.pilihan.kategori.tambah'), $this->dataKategori())->assertSessionHasNoErrors();

        $this->assertArrayHasKey('konser_amal', ic_kategori_kenangan());
        $this->actingAs($a, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertSee('Konser Amal');
        $this->actingAs($a, 'member')->post(route('ic.kenangan.simpan'), ['caption' => 'Seru', 'kategori' => 'konser_amal'])->assertSessionHasNoErrors();
        $this->assertSame('konser_amal', ic_memory::first()->kategori);
    }

    public function test_kunci_kategori_tidak_kembar_dan_tidak_memakai_all(): void
    {
        $this->admin()->post(route('ic.admin.konten.pilihan.kategori.tambah'), $this->dataKategori(['label' => 'All']));
        $this->admin()->post(route('ic.admin.konten.pilihan.kategori.tambah'), $this->dataKategori(['label' => 'Festival']));

        $kunci = ic_pilihan::where('jenis', 'kategori_kenangan')->pluck('kunci')->all();
        $this->assertContains('kategori', $kunci);
        $this->assertContains('festival_2', $kunci);
        $this->assertNotContains('all', $kunci);
    }

    public function test_batas_jumlah_kategori(): void
    {
        config(['inner_circle.kategori_kenangan_maks' => 3]);
        $this->admin()->post(route('ic.admin.konten.pilihan.kategori.tambah'), $this->dataKategori())->assertSessionHas('error');
        $this->assertSame(3, ic_pilihan::where('jenis', 'kategori_kenangan')->count());
    }

    public function test_kategori_terpakai_tidak_bisa_dihapus_tapi_bisa_disembunyikan_dan_kenangan_lama_tetap_tampil(): void
    {
        $a = $this->anggota();
        ic_memory::create(['member_id' => $a->id_member, 'caption' => 'Kenangan lama', 'kategori' => 'club', 'status' => 'tayang']);

        $this->admin()->delete(route('ic.admin.konten.pilihan.kategori.hapus', 'club'))->assertSessionHas('error');
        $this->assertTrue(ic_pilihan::where('kunci', 'club')->exists());

        $this->admin()->patch(route('ic.admin.konten.pilihan.kategori.aktif', 'club'), ['aktif' => 0])->assertSessionHas('success');
        $this->assertArrayNotHasKey('club', ic_kategori_kenangan());

        $html = $this->actingAs($a, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertOk()->getContent();
        $this->assertStringContainsString('Kenangan lama', $html);
        $this->assertStringContainsString('Acara klub', $html);

        // Kategori tersembunyi tidak bisa dipilih untuk kenangan baru.
        $this->actingAs($a, 'member')->post(route('ic.kenangan.simpan'), ['caption' => 'Baru', 'kategori' => 'club'])->assertSessionHasErrors('kategori');
    }

    public function test_kategori_belum_terpakai_bisa_dihapus_dan_urutan_dirapatkan(): void
    {
        $this->admin()->delete(route('ic.admin.konten.pilihan.kategori.hapus', 'club'))->assertSessionHas('success');

        $this->assertSame(['festival', 'personal'], array_keys(ic_kategori_kenangan()));
        $this->assertSame([1, 2], ic_pilihan::where('jenis', 'kategori_kenangan')->orderBy('urutan')->pluck('urutan')->all());
    }

    public function test_minimal_satu_kategori_tampil(): void
    {
        foreach (['festival', 'club'] as $k) {
            $this->admin()->patch(route('ic.admin.konten.pilihan.kategori.aktif', $k), ['aktif' => 0])->assertSessionHas('success');
        }
        $this->admin()->patch(route('ic.admin.konten.pilihan.kategori.aktif', 'personal'), ['aktif' => 0])->assertSessionHas('error');
        $this->admin()->delete(route('ic.admin.konten.pilihan.kategori.hapus', 'personal'))->assertSessionHas('error');
        $this->assertArrayHasKey('personal', ic_kategori_kenangan());
    }

    public function test_geser_kategori_dan_kembalikan_bawaan(): void
    {
        $this->admin()->patch(route('ic.admin.konten.pilihan.kategori.geser', 'club'), ['arah' => 'naik']);
        $this->assertSame(['club', 'festival', 'personal'], array_keys(ic_kategori_kenangan()));

        $this->admin()->put(route('ic.admin.konten.pilihan.kategori.ubah', 'club'), $this->dataKategori(['label' => 'Klub Malam']))->assertSessionHasNoErrors();
        $this->assertSame('Klub Malam', ic_kategori_kenangan()['club']['label']);
        $this->admin()->post(route('ic.admin.konten.pilihan.kategori.bawaan', 'club'));
        $this->assertSame('Acara klub', ic_kategori_kenangan()['club']['label']);
    }

    // ---------- Aturan check-in ----------

    private function aturan(array $ubah = []): array
    {
        return $ubah + ['jendela_buka' => '12:00', 'jendela_tutup' => '06:00', 'jendela_tutup_hari' => 1, 'radius_bawaan_m' => 150, 'akurasi_maks_m' => 150];
    }

    public function test_tanpa_perubahan_aturan_sama_persis_dan_tidak_ada_baris(): void
    {
        $e = $this->acara('2027-03-10');
        $this->assertSame('2027-03-10 12:00', $e->jendela_buka->format('Y-m-d H:i'));
        $this->assertSame('2027-03-11 06:00', $e->jendela_tutup->format('Y-m-d H:i'));
        $this->assertSame(150, $e->radius_efektif);

        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan())->assertSessionHas('success', 'Tidak ada aturan yang berubah.');
        $this->assertSame(0, ic_pengaturan::count());
    }

    public function test_jendela_bawaan_baru_hanya_berlaku_untuk_acara_baru(): void
    {
        $lama = $this->acara('2027-03-10');
        $sendiri = $this->acara('2027-03-12', ['jendela_mulai' => '2027-03-12 08:00', 'jendela_selesai' => '2027-03-12 23:00']);

        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['jendela_buka' => '15:30', 'jendela_tutup' => '03:00']))
            ->assertSessionHasNoErrors();

        $baru = $this->acara('2027-04-01');
        $this->assertSame('2027-04-01 15:30', $baru->jendela_buka->format('Y-m-d H:i'));
        $this->assertSame('2027-04-02 03:00', $baru->jendela_tutup->format('Y-m-d H:i'));

        $lama->refresh();
        $this->assertSame('2027-03-10 12:00', $lama->jendela_buka->format('Y-m-d H:i'));
        $this->assertSame('2027-03-11 06:00', $lama->jendela_tutup->format('Y-m-d H:i'));
        $this->assertSame('2027-03-12 08:00', $sendiri->refresh()->jendela_buka->format('Y-m-d H:i'));
    }

    public function test_check_in_mengikuti_jendela_bawaan_yang_baru(): void
    {
        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['jendela_buka' => '18:00']));
        $e = $this->acara('2027-05-05');

        $this->assertSame('belum', $e->statusCheckin(Carbon::parse('2027-05-05 17:00')));
        $this->assertSame('siap', $e->statusCheckin(Carbon::parse('2027-05-05 18:30')));
    }

    public function test_radius_bawaan_baru_tidak_mengubah_acara_yang_sudah_punya_titik(): void
    {
        $e = $this->acara('2027-03-10');
        $e->forceFill(['lokasi_lat' => -8.65, 'lokasi_lng' => 115.2])->save();

        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['radius_bawaan_m' => 400]))->assertSessionHasNoErrors();

        $this->assertSame(150, $e->refresh()->radius_efektif);
        $baru = $this->acara('2027-06-01');
        $baru->forceFill(['lokasi_lat' => -8.65, 'lokasi_lng' => 115.2])->save();
        $this->assertSame(400, $baru->refresh()->radius_efektif);
    }

    public function test_batas_akurasi_baru_berlaku_untuk_check_in(): void
    {
        $this->assertSame(150, app(IcAturan::class)->akurasiMaks());
        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['akurasi_maks_m' => 80]));
        $this->assertSame(80, app(IcAturan::class)->akurasiMaks());
    }

    public function test_validasi_aturan(): void
    {
        $tolak = fn(array $u, string $kolom) => $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan($u))->assertSessionHasErrorsIn('aturan', $kolom);

        $tolak(['jendela_buka' => '25:00'], 'jendela_buka');
        $tolak(['jendela_tutup' => '6'], 'jendela_tutup');
        $tolak(['jendela_tutup_hari' => 0, 'jendela_buka' => '12:00', 'jendela_tutup' => '06:00'], 'jendela_tutup');
        $tolak(['radius_bawaan_m' => 5], 'radius_bawaan_m');
        $tolak(['radius_bawaan_m' => 99999], 'radius_bawaan_m');
        $tolak(['akurasi_maks_m' => 10], 'akurasi_maks_m');
        $this->assertSame(0, ic_pengaturan::count());
    }

    public function test_kembalikan_bawaan_aturan(): void
    {
        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['akurasi_maks_m' => 80, 'radius_bawaan_m' => 300]));
        $this->assertGreaterThan(0, ic_pengaturan::count());

        $this->admin()->post(route('ic.admin.konten.aturan.bawaan'))->assertSessionHas('success');
        $this->assertSame(0, ic_pengaturan::count());
        $this->assertSame(150, app(IcAturan::class)->akurasiMaks());
    }

    public function test_nilai_aturan_rusak_di_database_diabaikan(): void
    {
        ic_pengaturan::create(['kunci' => 'aturan.jendela_buka', 'nilai' => 'abc']);
        ic_pengaturan::create(['kunci' => 'aturan.radius_bawaan_m', 'nilai' => '999999']);
        app(\App\Services\InnerCircle\IcKonten::class)->lupakan();

        $this->assertSame('12:00', app(IcAturan::class)->efektif()['jendela_buka']);
        $this->assertSame(150, app(IcAturan::class)->radiusBawaan());
    }

    // ---------- Halaman dan akses ----------

    public function test_tab_konten_memuat_bagian_pilihan_dan_aturan_dan_teks_acara_dinamis(): void
    {
        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['jendela_buka' => '14:00']));
        $halaman = [
            'tipe-acara' => ['id="pilihan-tipe"'],
            'kategori-kenangan' => ['id="pilihan-kategori"', 'Kategori kenangan'],
            'aturan-checkin' => ['id="aturan-checkin"', 'Aturan check-in'],
        ];
        foreach ($halaman as $slug => $harus) {
            $html = $this->admin()->get(route('ic.admin.konten.bagian', $slug))->assertOk()->getContent();
            foreach ($harus as $s) {
                $this->assertStringContainsString($s, $html, $slug);
            }
        }

        $acara = $this->admin()->get(route('ic.admin.acara'))->assertOk()->getContent();
        $this->assertStringContainsString('pukul 14.00', $acara);
    }

    public function test_hanya_admin_yang_bisa_mengubah(): void
    {
        $this->put(route('ic.admin.konten.pilihan.tipe.ubah', 'club'), ['label' => 'X', 'ikon' => 'gift', 'warna' => 'biru'])->assertRedirect();
        $this->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['akurasi_maks_m' => 80]))->assertRedirect();
        $this->assertSame('Klub', ic_pilihan::where('kunci', 'club')->value('label'));
        $this->assertSame(150, app(IcAturan::class)->akurasiMaks());
    }
}