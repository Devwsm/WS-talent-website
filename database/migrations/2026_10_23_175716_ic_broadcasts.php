<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 6): pengumuman resmi WSM untuk anggota.
     *
     * `unggulan` ("Kabar pilihan") hanya boleh satu yang menyala; aturannya dijaga PengumumanService di dalam
     * transaksi (MySQL tidak punya kunci unik parsial). `terbit_pada` juga jadwal tayang: tanggal di masa depan
     * belum tampil ke anggota. `aktif` belum punya tombol di CMS (pengumuman dihapus, bukan dimatikan).
     */
    public function up(): void
    {
        Schema::create('ic_broadcasts', function (Blueprint $table) {
            $table->id('id_broadcast');
            $table->string('judul', 80);
            $table->string('isi', 220);
            $table->string('tag', 40)->nullable();
            $table->string('ikon', 30)->default('sparkles');
            $table->string('warna', 10)->default('kuning');
            $table->string('cta_label', 30);
            $table->string('cta_tujuan', 30)->default('ic.checkin'); // nama route anggota, lihat config cta_pengumuman
            $table->boolean('unggulan')->default(false);
            $table->date('terbit_pada');
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['aktif', 'terbit_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_broadcasts');
    }
};
