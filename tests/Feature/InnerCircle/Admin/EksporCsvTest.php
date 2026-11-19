<?php

namespace Tests\Feature\InnerCircle\Admin;

use App\Models\ic_event;
use App\Models\log_aktivitas;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\PoinService;
use App\Support\CsvEkspor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ekspor CSV: daftar anggota (mengikuti filter) dan kehadiran per acara. */
class EksporCsvTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    private function anggota(string $email, string $nama): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function acara(string $venue = 'Superhouse', ?string $tanggal = null): ic_event
    {
        return app(AcaraService::class)->buat(['tanggal' => $tanggal ?? now()->toDateString(), 'tipe' => 'club', 'venue' => $venue, 'kota' => 'Jakarta']);
    }

    /** @return string[][] baris CSV (tanpa BOM) sebagai larik kolom */
    private function baris(string $isi): array
    {
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);

        return array_map(fn($b) => str_getcsv($b, ';', '"', '\\'), array_values(array_filter(explode("\r\n", $isi), fn($b) => $b !== '')));
    }

    public function test_ekspor_anggota_berisi_kolom_laporan_dengan_bom_dan_titik_koma(): void
    {
        $a = $this->anggota('a@contoh.test', 'Ani');
        $this->anggota('b@contoh.test', 'Budi');
        $e = $this->acara();
        app(PoinService::class)->tambah($a, 1, 'checkin', 'Check-in', 'pin', $e);
        app(PoinService::class)->tambahManual($a, 2, 'Uji', 'admin-uji');

        $r = $this->admin()->get(route('ic.admin.anggota.ekspor'));
        $r->assertOk();
        $this->assertStringContainsString('text/csv', $r->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $r->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));

        $isi = $r->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $isi);

        $baris = $this->baris($isi);
        $this->assertSame(['Kode', 'Nama', 'Email', 'Email terverifikasi', 'Daftar via', 'Google terhubung', 'Bergabung', 'Total poin', 'Jumlah check-in', 'Check-in terakhir', 'Status akun'], $baris[0]);
        $this->assertCount(3, $baris); // judul + 2 anggota, lama ke baru
        $this->assertSame(['IC-00001', 'Ani', 'a@contoh.test', 'Ya', 'Manual (sandi)', 'Tidak'], array_slice($baris[1], 0, 6));
        $this->assertSame(['3', '1', 'Aktif'], [$baris[1][7], $baris[1][8], $baris[1][10]]);
        $this->assertSame(['0', '0', ''], [$baris[2][7], $baris[2][8], $baris[2][9]]);
    }

    public function test_ekspor_anggota_mengikuti_pencarian_dan_filter_dan_menandai_akun_nonaktif(): void
    {
        $this->anggota('a@contoh.test', 'Ani');
        $b = $this->anggota('b@contoh.test', 'Budi');
        $this->admin()->patch(route('ic.admin.anggota.nonaktifkan', $b->id_member), ['alasan' => 'Uji']);

        $semua = $this->baris($this->admin()->get(route('ic.admin.anggota.ekspor'))->streamedContent());
        $this->assertCount(3, $semua);

        $cari = $this->baris($this->admin()->get(route('ic.admin.anggota.ekspor', ['q' => 'budi']))->streamedContent());
        $this->assertCount(2, $cari);
        $this->assertSame('Dinonaktifkan', $cari[1][10]);

        $aktif = $this->baris($this->admin()->get(route('ic.admin.anggota.ekspor', ['akun' => 'aktif']))->streamedContent());
        $this->assertSame(['Ani'], array_column(array_slice($aktif, 1), 1));
    }

    public function test_sel_berawalan_rumus_dinetralkan_tapi_angka_dan_kosong_tidak(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://jahat\")", CsvEkspor::aman('=HYPERLINK("http://jahat")'));
        $this->assertSame("'+62812", CsvEkspor::aman('+62812'));
        $this->assertSame("'-1+1", CsvEkspor::aman('-1+1'));
        $this->assertSame("'@SUM(1)", CsvEkspor::aman('@SUM(1)'));
        $this->assertSame('Ani', CsvEkspor::aman('Ani'));
        $this->assertSame(-2, CsvEkspor::aman(-2));
        $this->assertNull(CsvEkspor::aman(null));
        $this->assertSame('', CsvEkspor::aman(''));
    }

    public function test_nama_anggota_berbahaya_di_ekspor_tidak_jadi_rumus(): void
    {
        $this->anggota('x@contoh.test', '=cmd|calc');

        $baris = $this->baris($this->admin()->get(route('ic.admin.anggota.ekspor'))->streamedContent());

        $this->assertSame("'=cmd|calc", $baris[1][1]);
    }

    public function test_ekspor_kehadiran_hanya_yang_check_in_di_acara_itu(): void
    {
        $a = $this->anggota('a@contoh.test', 'Ani');
        $b = $this->anggota('b@contoh.test', 'Budi');
        $acaraIni = $this->acara('Superhouse', '2026-11-10');
        $acaraLain = $this->acara('Hall Lain', '2026-11-11');
        $svc = app(PoinService::class);
        $svc->tambah($a, 1, 'checkin', 'Check-in', 'pin', $acaraIni, jarakM: 35, akurasiM: 12);
        $svc->tambah($b, 1, 'checkin', 'Check-in', 'pin', $acaraLain);
        $svc->tambahManual($b, 1, 'Manual bukan check-in', 'admin-uji');

        $r = $this->admin()->get(route('ic.admin.acara.kehadiran', $acaraIni->kode));
        $r->assertOk();
        $this->assertStringContainsString('kehadiran-' . $acaraIni->kode . '-2026-11-10.csv', $r->headers->get('Content-Disposition'));

        $baris = $this->baris($r->streamedContent());
        $this->assertSame(['No', 'Kode anggota', 'Nama', 'Email', 'Waktu check-in', 'Jarak dari titik acara (m)', 'Akurasi lokasi (m)', 'Poin'], $baris[0]);
        $this->assertCount(2, $baris);
        $this->assertSame(['1', 'IC-00001', 'Ani', 'a@contoh.test'], array_slice($baris[1], 0, 4));
        $this->assertSame(['35', '12', '1'], array_slice($baris[1], 5, 3));
    }

    public function test_acara_tanpa_check_in_menghasilkan_file_berisi_judul_saja_dan_kode_asing_404(): void
    {
        $e = $this->acara();

        $baris = $this->baris($this->admin()->get(route('ic.admin.acara.kehadiran', $e->kode))->streamedContent());
        $this->assertCount(1, $baris);

        $this->admin()->get(route('ic.admin.acara.kehadiran', 'KODE-TIDAK-ADA'))->assertNotFound();
    }

    public function test_ekspor_butuh_login_admin_dan_tercatat_di_log_aktivitas(): void
    {
        $e = $this->acara();
        $this->anggota('a@contoh.test', 'Ani');

        $this->get(route('ic.admin.anggota.ekspor'))->assertRedirect(route('login'));
        $this->get(route('ic.admin.acara.kehadiran', $e->kode))->assertRedirect(route('login'));
        $this->assertSame(0, log_aktivitas::query()->where('kategori', 'inner-circle')->count());

        $this->admin()->get(route('ic.admin.anggota.ekspor'))->streamedContent();
        $this->admin()->get(route('ic.admin.acara.kehadiran', $e->kode))->streamedContent();

        $this->assertSame(
            ['Daftar anggota diunduh', 'Kehadiran acara diunduh'],
            log_aktivitas::query()->where('kategori', 'inner-circle')->orderBy('id_log_aktivitas')->pluck('aksi')->all(),
        );
        $this->assertStringContainsString('1 anggota', (string) log_aktivitas::query()->where('rute', 'ic.admin.anggota.ekspor')->value('keterangan'));
    }

    public function test_tombol_unduh_ada_di_halaman_anggota_dan_acara(): void
    {
        $e = $this->acara();
        $this->anggota('a@contoh.test', 'Ani');

        $this->admin()->get(route('ic.admin.anggota'))->assertOk()->assertSee('Unduh semua (CSV)')
            ->assertSee(route('ic.admin.anggota.ekspor'), false);
        $this->admin()->get(route('ic.admin.anggota', ['q' => 'ani']))->assertSee('Unduh hasil (CSV)')
            ->assertSee(route('ic.admin.anggota.ekspor', ['q' => 'ani']), false);
        $this->admin()->get(route('ic.admin.acara.harih', ['acara' => $e->kode]))->assertOk()
            ->assertSee(route('ic.admin.acara.kehadiran', $e->kode), false);
    }
}