<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konten Inner Circle yang bisa diubah dari CMS (tab Konten).
     *
     * ic_pengaturan: satu baris = satu teks. `kunci` seperti `checkin.jadwal.judul` (daftar resminya ada di
     * config/ic_konten.php). Baris hanya ada bila teksnya DIUBAH; tanpa baris, halaman memakai teks bawaan dari config.
     *
     * ic_item: daftar berurutan (langkah, FAQ, poin teaser, dst.) yang dipakai fase berikutnya.
     */
    public function up(): void
    {
        Schema::create('ic_pengaturan', function (Blueprint $table) {
            $table->id('id_pengaturan');
            $table->string('kunci', 80)->unique();
            $table->text('nilai')->nullable();
            $table->string('diubah_oleh', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('ic_item', function (Blueprint $table) {
            $table->id('id_item');
            $table->string('grup', 30);
            $table->unsignedInteger('urutan')->default(0);
            $table->string('judul', 160);
            $table->text('isi')->nullable();
            $table->string('ikon', 40)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['grup', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_item');
        Schema::dropIfExists('ic_pengaturan');
    }
};