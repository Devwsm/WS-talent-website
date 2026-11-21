<?php

namespace Tests\Feature\InnerCircle\Poin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Migrasi `stempel_jadi_poin`: data produksi (stempel) harus berubah jadi poin tanpa mengubah posisi anggota.
 * Tes memundurkan migrasinya, mengisi data bergaya lama, lalu menjalankannya lagi.
 */
class MigrasiStempelJadiPoinTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jalur berkas migrasi dicari lewat glob, bukan ditulis tetap, supaya tes tidak gagal bila nama berkasnya sedikit
     * berbeda di komputer tertentu (mis. akhiran " (1)" dari unduhan). Hasilnya relatif terhadap folder project.
     */
    private function jalur(): string
    {
        $berkas = glob(database_path('migrations/*_stempel_jadi_poin*.php')) ?: [];
        $this->assertCount(1, $berkas, 'Berkas migrasi stempel_jadi_poin harus ada tepat satu di database/migrations.');

        return 'database/migrations/' . basename($berkas[0]);
    }

    private function mundur(): void
    {
        Artisan::call('migrate:rollback', ['--path' => $this->jalur(), '--force' => true]);
    }

    private function maju(): void
    {
        Artisan::call('migrate', ['--path' => $this->jalur(), '--force' => true]);
    }

    public function test_mundur_mengembalikan_nama_lama_dan_maju_memakai_nama_baru(): void
    {
        $this->mundur();

        $this->assertTrue(Schema::hasTable('ic_stempel'));
        $this->assertFalse(Schema::hasTable('ic_poin'));
        $this->assertTrue(Schema::hasColumn('members', 'stempel_total'));
        $this->assertTrue(Schema::hasColumn('ic_rewards', 'target_stempel'));
        // Lima tahap lama (urutan 11-15 sejak Fase 8); hadiah baru Fase 8 ikut dibagi 1.000 saat mundur.
        $this->assertSame([10, 25, 50, 100, 250], DB::table('ic_rewards')->where('urutan', '>', 10)->orderBy('urutan')->pluck('target_stempel')->map(fn($n) => (int) $n)->all());

        $this->maju();

        $this->assertTrue(Schema::hasTable('ic_poin'));
        $this->assertFalse(Schema::hasTable('ic_stempel'));
        $this->assertTrue(Schema::hasColumn('ic_poin', 'id_poin'));
        $this->assertTrue(Schema::hasColumn('members', 'poin_total'));
        $this->assertTrue(Schema::hasColumn('ic_rewards', 'harga_poin'));
        $this->assertSame([10000, 25000, 50000, 100000, 250000], DB::table('ic_rewards')->where('urutan', '>', 10)->orderBy('urutan')->pluck('harga_poin')->map(fn($n) => (int) $n)->all());
        $this->assertSame([150000, 250000], DB::table('ic_rewards')->where('urutan', '<=', 10)->orderBy('urutan')->pluck('harga_poin')->map(fn($n) => (int) $n)->all());
    }

    public function test_saldo_buku_besar_dan_koreksi_dikali_seribu(): void
    {
        $this->mundur();

        $id = DB::table('members')->insertGetId([
            'nama' => 'Anggota Lama',
            'email' => 'lama@contoh.test',
            'password' => 'x',
            'stempel_total' => 7,
            'kode_member' => 'IC-00001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('ic_stempel')->insert([
            ['member_id' => $id, 'jumlah' => 9, 'sumber' => 'manual', 'judul' => 'Ikut kegiatan resmi', 'ikon' => 'sparkles', 'created_at' => now()],
            ['member_id' => $id, 'jumlah' => -2, 'sumber' => 'koreksi', 'judul' => 'Koreksi stempel oleh tim WSM', 'ikon' => 'sparkles', 'created_at' => now()],
        ]);

        $this->maju();

        $this->assertSame(7000, (int) DB::table('members')->where('id_member', $id)->value('poin_total'));
        $this->assertSame([-2000, 9000], DB::table('ic_poin')->where('member_id', $id)->orderBy('jumlah')->pluck('jumlah')->map(fn($n) => (int) $n)->all());
        $this->assertSame(1, DB::table('ic_poin')->where('judul', 'Koreksi poin oleh tim WSM')->count());
        $this->assertSame(0, DB::table('ic_poin')->where('judul', 'Koreksi stempel oleh tim WSM')->count());
        // Saldo cache tetap sama dengan jumlah buku besar.
        $this->assertSame(7000, (int) DB::table('ic_poin')->where('member_id', $id)->sum('jumlah'));
    }

    public function test_kunci_teks_berpindah_dan_teks_admin_tidak_hilang(): void
    {
        $this->mundur();

        DB::table('ic_pengaturan')->insert([
            ['kunci' => 'beranda.stempel.sisa', 'nilai' => 'langkah lagi menuju hadiah.'],
            ['kunci' => 'hadiah.deskripsi', 'nilai' => 'Kumpulkan stempel, buka kejutan.'],
        ]);

        $this->maju();

        $this->assertSame('langkah lagi menuju hadiah.', DB::table('ic_pengaturan')->where('kunci', 'beranda.poin.sisa')->value('nilai'));
        $this->assertSame(0, DB::table('ic_pengaturan')->where('kunci', 'beranda.stempel.sisa')->count());
        // Teks ubahan admin yang menyebut stempel tidak ditimpa (hanya dicatat di log).
        $this->assertSame('Kumpulkan stempel, buka kejutan.', DB::table('ic_pengaturan')->where('kunci', 'hadiah.deskripsi')->value('nilai'));
    }

    public function test_butir_daftar_bawaan_lama_diganti_dan_yang_diubah_admin_dibiarkan(): void
    {
        $this->mundur();

        DB::table('ic_item')->insert([
            ['grup' => 'langkah', 'urutan' => 1, 'judul' => 'Kumpulkan stempel', 'isi' => 'Setiap acara yang kamu hadiri memberi satu stempel. Stempel tidak pernah berkurang.', 'ikon' => 'ticket', 'aktif' => true],
            ['grup' => 'faq', 'urutan' => 1, 'judul' => 'Apa itu stempel?', 'isi' => 'Teks yang sudah ditulis ulang admin soal stempel.', 'ikon' => null, 'aktif' => true],
            ['grup' => 'teaser_poin', 'urutan' => 1, 'judul' => 'Hadiah di tiap tahap stempel', 'isi' => null, 'ikon' => 'gift', 'aktif' => true],
        ]);

        $this->maju();

        $langkah = DB::table('ic_item')->where('grup', 'langkah')->where('judul', 'Kumpulkan poin')->first();
        $this->assertNotNull($langkah);
        $this->assertSame('Setiap acara yang kamu hadiri memberi poin. Poin tidak berkurang saat kamu mengajukan hadiah.', $langkah->isi);
        $this->assertSame('Hadiah di tiap tahap poin', DB::table('ic_item')->where('grup', 'teaser_poin')->where('judul', 'Hadiah di tiap tahap poin')->value('judul'));
        // Diubah admin: judulnya sama dengan bawaan, isinya tidak, jadi tidak disentuh.
        $faq = DB::table('ic_item')->where('isi', 'Teks yang sudah ditulis ulang admin soal stempel.')->first();
        $this->assertNotNull($faq);
        $this->assertSame('Apa itu stempel?', $faq->judul);
    }

    public function test_seeder_hadiah_aman_dijalankan_ulang_dan_tidak_menggandakan(): void
    {
        (new \Database\Seeders\icRewardSeeder())->run();
        (new \Database\Seeders\icRewardSeeder())->run();

        $this->assertSame(7, DB::table('ic_rewards')->count()); // 5 tahap lama + Meet & Greet + Merchandise (Fase 8)
        $this->assertSame(10000, (int) DB::table('ic_rewards')->min('harga_poin'));
    }
}