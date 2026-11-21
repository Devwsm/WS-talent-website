<?php

use App\Services\InnerCircle\IcKonten;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 8 aturan baru Inner Circle: hadiah dan penukaran dengan poin.
     *
     * Struktur
     * - `ic_rewards`: kolom `stok` (kosong = tak terbatas) dan harga poin tidak lagi unik. Kuncinya di URL sekarang `id_reward`,
     *   jadi dua hadiah boleh berharga sama (Merchandise 250.000 sama dengan tahap lama 250.000).
     * - `ic_claims`: kolom `poin_ditahan` (poin yang ditahan untuk penukaran ini; 0 = klaim lama sebelum Fase 8, tidak memotong
     *   saldo). Kunci unik (anggota, hadiah) dilepas supaya hadiah boleh ditukar berulang.
     * - `ic_poin`: kolom `claim_id` dan kunci unik (claim_id, sumber): satu baris `tukar` (tahan) dan paling banyak satu baris
     *   `kembali` per penukaran, sehingga poin tidak mungkin terpotong atau dikembalikan dua kali. NULL dianggap berbeda, jadi
     *   baris check-in dan manual tidak terkena.
     * - `ic_reward_tunggu`: daftar tunggu anggota untuk hadiah yang stoknya habis.
     *
     * Data
     * - Lima tahap lama (10.000 sampai 250.000 poin) DINONAKTIFKAN, bukan dihapus, supaya riwayat klaim lama tetap utuh.
     * - Katalog baru dari alur "Penukaran hadiah / gunakan poin": Meet & Greet 150.000 poin dan Merchandise 250.000 poin.
     * - Teks bawaan yang menyebut "poin tidak berkurang" diganti HANYA bila masih sama persis dengan bawaan lama; teks yang
     *   pernah diubah admin tidak disentuh.
     *
     * Cadangkan database sebelum deploy: migrasi ini mengubah data hadiah.
     */
    public function up(): void
    {
        Schema::table('ic_rewards', function (Blueprint $table) {
            $table->unsignedInteger('stok')->nullable()->after('opsi');
        });
        $this->lepasUnik('ic_rewards', ['harga_poin']);

        Schema::table('ic_claims', function (Blueprint $table) {
            $table->unsignedInteger('poin_ditahan')->default(0)->after('opsi_dipilih');
        });
        // MySQL: indeks gabungan (member_id, reward_id) ikut melayani kunci asing member_id. Beri indeks sendiri dulu
        // sebelum kunci unik dilepas, kalau tidak MySQL menolak (galat 1553).
        if (! $this->adaIndeks('ic_claims', ['member_id'])) {
            Schema::table('ic_claims', fn(Blueprint $table) => $table->index('member_id', 'ic_claims_member_id_index'));
        }
        $this->lepasUnik('ic_claims', ['member_id', 'reward_id']);

        Schema::table('ic_poin', function (Blueprint $table) {
            $table->unsignedBigInteger('claim_id')->nullable()->after('event_id');
            $table->unique(['claim_id', 'sumber'], 'ic_poin_claim_sumber_unique');
        });

        Schema::create('ic_reward_tunggu', function (Blueprint $table) {
            $table->id('id_tunggu');
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('ic_rewards', 'id_reward')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['member_id', 'reward_id']);
        });

        $this->pensiunkanTahapLama();
        $this->isiKatalogBaru();
        $this->perbaruiTeks();
    }

    /**
     * Mengembalikan struktur. Hadiah baru, klaim yang sudah memakai penukaran (poin_ditahan > 0), dan daftar tunggu ikut
     * hilang dari struktur; baris buku besar `tukar`/`kembali` tetap ada (poin anggota tidak berubah). Kunci unik lama
     * dipasang kembali bila datanya masih memungkinkan. Teks yang diganti tidak dikembalikan.
     */
    public function down(): void
    {
        Schema::dropIfExists('ic_reward_tunggu');

        Schema::table('ic_poin', function (Blueprint $table) {
            $table->dropUnique('ic_poin_claim_sumber_unique');
            $table->dropColumn('claim_id');
        });

        Schema::table('ic_claims', function (Blueprint $table) {
            $table->dropColumn('poin_ditahan');
        });
        try {
            Schema::table('ic_claims', fn(Blueprint $table) => $table->unique(['member_id', 'reward_id']));
        } catch (Throwable) {
            // Sudah ada penukaran berulang pada hadiah yang sama: kunci unik lama tidak bisa dipasang lagi.
        }

        Schema::table('ic_rewards', function (Blueprint $table) {
            $table->dropColumn('stok');
        });
        try {
            Schema::table('ic_rewards', fn(Blueprint $table) => $table->unique('harga_poin'));
        } catch (Throwable) {
            // Dua hadiah berharga sama (mis. 250.000): kunci unik lama tidak bisa dipasang lagi.
        }
    }

    /** @param list<string> $kolom */
    private function adaIndeks(string $tabel, array $kolom): bool
    {
        foreach (Schema::getIndexes($tabel) as $indeks) {
            if (($indeks['columns'] ?? []) === $kolom) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lepas kunci unik pada kolom tertentu lewat NAMA yang sebenarnya ada di database (nama lama tetap `target_stempel`
     * walau kolomnya sudah diganti nama, jadi nama bawaan Laravel tidak bisa ditebak).
     *
     * @param list<string> $kolom
     */
    private function lepasUnik(string $tabel, array $kolom): void
    {
        foreach (Schema::getIndexes($tabel) as $indeks) {
            if (($indeks['unique'] ?? false) && ! ($indeks['primary'] ?? false) && ($indeks['columns'] ?? []) === $kolom) {
                Schema::table($tabel, fn(Blueprint $table) => $table->dropUnique($indeks['name']));
            }
        }
    }

    private function pensiunkanTahapLama(): void
    {
        DB::table('ic_rewards')
            ->whereNotIn('judul', ['Meet & Greet', 'Merchandise'])
            ->update(['aktif' => false, 'urutan' => DB::raw('urutan + 10')]);
    }

    private function isiKatalogBaru(): void
    {
        $katalog = [
            ['Meet & Greet', 150000, 'Kesempatan bertemu langsung dengan Whisnu Santika. Jadwal dan caranya dikabarkan tim WSM setelah penukaran diperiksa.', 'camera', 'biru', 1],
            ['Merchandise', 250000, 'Tukar dengan merchandise resmi Whisnu Santika. Pilihan barangnya dikonfirmasi tim WSM setelah penukaran diperiksa.', 'cap', 'kuning', 2],
        ];

        foreach ($katalog as [$judul, $harga, $deskripsi, $ikon, $warna, $urutan]) {
            if (DB::table('ic_rewards')->where('judul', $judul)->exists()) {
                continue;
            }

            DB::table('ic_rewards')->insert([
                'harga_poin' => $harga,
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'ikon' => $ikon,
                'warna' => $warna,
                'opsi' => null,
                'stok' => null,
                'urutan' => $urutan,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** Teks yang sebelumnya benar ("poin tidak berkurang") kini salah. Hanya yang persis sama dengan bawaan lama diganti. */
    private function perbaruiTeks(): void
    {
        if (Schema::hasTable('ic_item')) {
            $peta = [
                [
                    ['Kumpulkan poin', 'Setiap acara yang kamu hadiri memberi poin. Poin tidak berkurang saat kamu mengajukan hadiah.'],
                    ['Kumpulkan poin', 'Setiap acara yang kamu hadiri memberi poin. Poin yang terkumpul bisa kamu tukar dengan hadiah.'],
                ],
                [
                    ['Buka hadiah', 'Di setiap tahap poin, ajukan hadiahmu dan pantau statusnya sampai selesai.'],
                    ['Buka hadiah', 'Pilih hadiah di katalog, ajukan penukarannya, lalu pantau statusnya sampai hadiah diterima.'],
                ],
                [
                    ['Apa itu poin?', 'Poin adalah bukti kehadiranmu di sebuah acara. Setiap acara memberi poin, dan jumlahnya tidak berkurang saat kamu mengajukan hadiah.'],
                    ['Apa itu poin?', 'Poin adalah bukti kehadiranmu di sebuah acara. Poin bisa ditukar dengan hadiah: poinnya ditahan saat kamu mengajukan, lalu dikembalikan bila penukaran dibatalkan atau ditolak.'],
                ],
                [
                    ['Bagaimana cara mengajukan hadiah?', 'Saat poinmu mencapai target sebuah tahap, buka halaman Hadiah lalu ajukan. Tim WSM akan meninjau dan memberi kabar lewat halaman Hadiah dan email.'],
                    ['Bagaimana cara mengajukan hadiah?', 'Saat poinmu cukup untuk sebuah hadiah, buka halaman Hadiah lalu ajukan. Tim WSM akan memeriksa dan memberi kabar lewat halaman Hadiah dan email.'],
                ],
            ];

            foreach ($peta as [[$judulLama, $isiLama], [$judulBaru, $isiBaru]]) {
                DB::table('ic_item')->where('judul', $judulLama)->where('isi', $isiLama)->update(['judul' => $judulBaru, 'isi' => $isiBaru]);
            }
        }

        if (Schema::hasTable('ic_pengaturan')) {
            // Baris yang nilainya sama dengan bawaan lama dihapus: halaman otomatis memakai bawaan baru dari config.
            $lama = [
                'hadiah.cara.deskripsi' => 'Poin bersifat kumulatif dan tidak berkurang setelah klaim. Setiap tahapan hanya dapat diklaim satu kali. Benefit pengalaman bergantung pada kapasitas, jadwal artis, dan izin promotor atau venue.',
                'beranda.poin.selesai' => 'Semua tahapan hadiah sudah terbuka.',
                'profil.klaim.kosong' => 'Belum ada pengajuan. Buka {hadiah} untuk mengajukan tahapan yang sudah terbuka.',
            ];
            foreach ($lama as $kunci => $nilai) {
                DB::table('ic_pengaturan')->where('kunci', $kunci)->where('nilai', $nilai)->delete();
            }
        }

        try {
            app(IcKonten::class)->lupakan();
        } catch (Throwable) {
            // Cache belum siap (pemasangan pertama): tidak ada isi lama yang perlu dibuang.
        }
    }
};