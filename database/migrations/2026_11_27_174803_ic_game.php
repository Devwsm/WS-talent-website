<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle: mini game Mambo Jambo Pineapple Catcher. Satu baris = satu ronde.
     *
     * - `kode` acak 32 hex dipakai browser untuk menyelesaikan ronde (bukan id berurutan, tidak bisa ditebak).
     * - `seed` dibuat server saat ronde dimulai; server menjalankan ulang ronde dari seed + catatan input dan menghitung
     *   skor sendiri (`skor`). `skor_klaim` hanya jejak audit angka yang dikirim browser.
     * - `tanggal` (zona waktu aplikasi) dasar jatah ronde harian; ronde yang ditinggalkan atau ditolak tetap terhitung.
     * - `status`: berjalan, selesai, ditolak (alasan di kolom `alasan`), ditinggalkan (ronde baru dimulai sebelum selesai).
     * - Poin masuk lewat PoinService (sumber `game` di buku besar `ic_poin`); `poin_id` menunjuk barisnya.
     */
    public function up(): void
    {
        Schema::create('ic_game', function (Blueprint $table) {
            $table->id('id_game');
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->char('kode', 32)->unique();
            $table->unsignedInteger('seed');
            $table->date('tanggal');
            $table->string('status', 12)->default('berjalan');
            $table->string('alasan', 40)->nullable();
            $table->integer('skor')->nullable();
            $table->integer('skor_klaim')->nullable();
            $table->unsignedSmallInteger('level')->nullable();
            $table->unsignedInteger('langkah')->nullable();
            $table->unsignedSmallInteger('nanas')->nullable();
            $table->unsignedSmallInteger('bir')->nullable();
            $table->unsignedSmallInteger('lewat')->nullable();
            $table->unsignedSmallInteger('poin')->default(0);
            $table->unsignedBigInteger('poin_id')->nullable();
            $table->timestamp('mulai_pada')->useCurrent();
            $table->timestamp('selesai_pada')->nullable();

            $table->index(['member_id', 'tanggal']);
            $table->index(['member_id', 'status', 'skor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_game');
    }
};