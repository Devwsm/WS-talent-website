<?php

use App\Services\InnerCircle\IcPilihan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar pilihan Inner Circle yang bisa diubah dari CMS (tab Konten): tipe acara dan kategori kenangan.
     *
     * Sebelumnya keduanya tertulis di config/inner_circle.php. Sekarang sumbernya tabel ini, diisi awal dari config
     * (label, ikon, dan warna SAMA PERSIS), jadi tampilan setelah deploy identik. `kunci` tidak pernah berubah karena
     * dipakai data lama (`ic_events.tipe`, `ic_memories.kategori`) dan logika (acara `private` tidak bisa check-in).
     */
    public function up(): void
    {
        Schema::create('ic_pilihan', function (Blueprint $table) {
            $table->id('id_pilihan');
            $table->string('jenis', 20);            // tipe_event | kategori_kenangan
            $table->string('kunci', 30);
            $table->string('label', 40);
            $table->string('ikon', 30);
            $table->string('sampul', 30)->nullable(); // ikon sampul kartu tanpa foto (kategori kenangan saja)
            $table->string('warna', 20);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['jenis', 'kunci']);
        });

        app(IcPilihan::class)->tanamBawaanBilaKosong();
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_pilihan');
    }
};