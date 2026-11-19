<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 1 stempel lama = 1.000 poin. */
    private const PENGALI = 1000;

    /**
     * Fase 1 aturan baru Inner Circle: stempel jadi poin.
     *
     * - Ganti nama: `ic_stempel` jadi `ic_poin` (kunci `id_stempel` jadi `id_poin`), `members.stempel_total` jadi `poin_total`,
     *   `ic_rewards.target_stempel` jadi `harga_poin`.
     * - Lebarkan kolom ke integer (smallint maks 32.767 / 65.535 tidak muat 150.000 dan 250.000), lalu kalikan semua angka
     *   lama dengan 1.000: buku besar, saldo anggota, dan harga tahap hadiah. Hasilnya tiap anggota tetap berada di
     *   tahap yang sama dan klaim lama tetap valid.
     * - Teks yang sudah tersimpan: kunci teks `beranda.stempel.*` dipindah ke `beranda.poin.*` (supaya perubahan admin tidak
     *   hilang), dan butir daftar (langkah, FAQ, teaser) yang MASIH SAMA dengan teks bawaan lama diganti ke teks baru.
     *   Teks yang pernah diubah admin tidak ditimpa; daftarnya ditulis ke storage/logs/laravel.log supaya bisa dicek.
     *
     * Cadangkan database sebelum deploy: migrasi ini mengubah angka di tiga tabel.
     */
    public function up(): void
    {
        Schema::rename('ic_stempel', 'ic_poin');
        Schema::table('ic_poin', fn(Blueprint $t) => $t->renameColumn('id_stempel', 'id_poin'));
        Schema::table('members', fn(Blueprint $t) => $t->renameColumn('stempel_total', 'poin_total'));
        Schema::table('ic_rewards', fn(Blueprint $t) => $t->renameColumn('target_stempel', 'harga_poin'));

        // Lebarkan dulu, baru kalikan: angka hasil kali tidak boleh meluap di kolom lama.
        Schema::table('ic_poin', fn(Blueprint $t) => $t->integer('jumlah')->default(1000)->change());
        Schema::table('ic_rewards', fn(Blueprint $t) => $t->unsignedInteger('harga_poin')->change());

        DB::table('ic_poin')->update(['jumlah' => DB::raw('jumlah * ' . self::PENGALI)]);
        DB::table('members')->update(['poin_total' => DB::raw('poin_total * ' . self::PENGALI)]);
        DB::table('ic_rewards')->update(['harga_poin' => DB::raw('harga_poin * ' . self::PENGALI)]);

        DB::table('ic_poin')
            ->where('judul', 'Koreksi stempel oleh tim WSM')
            ->update(['judul' => 'Koreksi poin oleh tim WSM']);

        $this->pindahkanTeks();
    }

    /**
     * Mengembalikan struktur dan membagi angka dengan 1.000 (dibulatkan). Poin yang bukan kelipatan 1.000 (mis. dari
     * penambahan manual) kehilangan sisanya. Teks di ic_pengaturan dan ic_item TIDAK dikembalikan.
     */
    public function down(): void
    {
        DB::table('ic_poin')->update(['jumlah' => DB::raw('ROUND(jumlah / ' . self::PENGALI . ')')]);
        DB::table('members')->update(['poin_total' => DB::raw('ROUND(poin_total / ' . self::PENGALI . ')')]);
        DB::table('ic_rewards')->update(['harga_poin' => DB::raw('ROUND(harga_poin / ' . self::PENGALI . ')')]);

        Schema::table('ic_poin', fn(Blueprint $t) => $t->smallInteger('jumlah')->default(1)->change());
        Schema::table('ic_rewards', fn(Blueprint $t) => $t->unsignedSmallInteger('harga_poin')->change());

        Schema::table('ic_rewards', fn(Blueprint $t) => $t->renameColumn('harga_poin', 'target_stempel'));
        Schema::table('members', fn(Blueprint $t) => $t->renameColumn('poin_total', 'stempel_total'));
        Schema::table('ic_poin', fn(Blueprint $t) => $t->renameColumn('id_poin', 'id_stempel'));
        Schema::rename('ic_poin', 'ic_stempel');

        DB::table('ic_stempel')
            ->where('judul', 'Koreksi poin oleh tim WSM')
            ->update(['judul' => 'Koreksi stempel oleh tim WSM']);

        if (Schema::hasTable('ic_pengaturan')) {
            foreach (['sisa', 'selesai', 'catatan'] as $akhir) {
                DB::table('ic_pengaturan')->where('kunci', "beranda.poin.$akhir")->update(['kunci' => "beranda.stempel.$akhir"]);
            }
        }
    }

    private function pindahkanTeks(): void
    {
        if (Schema::hasTable('ic_pengaturan')) {
            foreach (['sisa', 'selesai', 'catatan'] as $akhir) {
                $lama = "beranda.stempel.$akhir";
                $baru = "beranda.poin.$akhir";
                if (DB::table('ic_pengaturan')->where('kunci', $baru)->exists()) {
                    DB::table('ic_pengaturan')->where('kunci', $lama)->delete();
                } else {
                    DB::table('ic_pengaturan')->where('kunci', $lama)->update(['kunci' => $baru]);
                }
            }

            $diubah = DB::table('ic_pengaturan')->where('nilai', 'like', '%tempel%')->pluck('kunci')->all();
            if ($diubah !== []) {
                Log::warning('Migrasi stempel_jadi_poin: teks hasil ubahan admin masih menyebut "stempel", periksa di CMS (tab Konten): ' . implode(', ', $diubah));
            }
        }

        if (! Schema::hasTable('ic_item')) {
            return;
        }

        // [judul lama, isi lama (null = tanpa isi)] => [judul baru, isi baru]. Hanya baris yang persis sama yang diganti.
        $peta = [
            [
                ['Kumpulkan stempel', 'Setiap acara yang kamu hadiri memberi satu stempel. Stempel tidak pernah berkurang.'],
                ['Kumpulkan poin', 'Setiap acara yang kamu hadiri memberi poin. Poin tidak berkurang saat kamu mengajukan hadiah.']
            ],
            [
                ['Buka hadiah', 'Di setiap tahap stempel, ajukan hadiahmu dan pantau statusnya sampai selesai.'],
                ['Buka hadiah', 'Di setiap tahap poin, ajukan hadiahmu dan pantau statusnya sampai selesai.']
            ],
            [
                ['Apa itu stempel?', 'Stempel adalah bukti kehadiranmu di sebuah acara. Setiap acara memberi satu stempel, dan jumlahnya tidak berkurang saat kamu mengajukan hadiah.'],
                ['Apa itu poin?', 'Poin adalah bukti kehadiranmu di sebuah acara. Setiap acara memberi poin, dan jumlahnya tidak berkurang saat kamu mengajukan hadiah.']
            ],
            [
                ['Bagaimana cara mengajukan hadiah?', 'Saat stempelmu mencapai target sebuah tahap, buka halaman Hadiah lalu ajukan. Tim WSM akan meninjau dan memberi kabar lewat halaman Hadiah dan email.'],
                ['Bagaimana cara mengajukan hadiah?', 'Saat poinmu mencapai target sebuah tahap, buka halaman Hadiah lalu ajukan. Tim WSM akan meninjau dan memberi kabar lewat halaman Hadiah dan email.']
            ],
            [
                ['Hadiah di tiap tahap stempel', null],
                ['Hadiah di tiap tahap poin', null]
            ],
        ];

        foreach ($peta as [[$judulLama, $isiLama], [$judulBaru, $isiBaru]]) {
            $kueri = DB::table('ic_item')->where('judul', $judulLama);
            $isiLama === null ? $kueri->whereNull('isi') : $kueri->where('isi', $isiLama);
            $kueri->update(['judul' => $judulBaru, 'isi' => $isiBaru]);
        }

        $sisa = DB::table('ic_item')
            ->where(fn($q) => $q->where('judul', 'like', '%tempel%')->orWhere('isi', 'like', '%tempel%'))
            ->pluck('id_item')
            ->all();
        if ($sisa !== []) {
            Log::warning('Migrasi stempel_jadi_poin: butir daftar hasil ubahan admin masih menyebut "stempel" (id_item: ' . implode(', ', $sisa) . '), periksa di CMS (tab Konten).');
        }
    }
};