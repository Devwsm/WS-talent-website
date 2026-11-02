<?php

use App\Services\InnerCircle\IcDaftar;
use App\Services\InnerCircle\IcKonten;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Isi awal daftar konten Inner Circle (langkah "Cara kerjanya", FAQ, poin teaser): sama persis dengan teks yang
     * sebelumnya tertulis di view, supaya halaman Tentang dan teaser tampil identik sesudah deploy.
     *
     * Hanya daftar yang masih kosong yang diisi, jadi aman dijalankan ulang dan tidak menimpa suntingan admin.
     * Sumber isinya config/ic_konten.php (`daftar.*.bawaan`), yang juga dipakai tombol "Kembalikan daftar bawaan".
     */
    public function up(): void
    {
        app(IcDaftar::class)->tanamBawaanBilaKosong();

        try {
            app(IcKonten::class)->lupakan();
        } catch (Throwable) {
            // Cache belum siap (pemasangan pertama): tidak ada isi lama yang perlu dibuang.
        }
    }

    public function down(): void
    {
        // Sengaja kosong: isi daftar bisa sudah disunting admin, jadi tidak dihapus saat rollback.
    }
};