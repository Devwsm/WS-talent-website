<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 4): jadwal acara + token QR check-in.
     *
     * `token` disimpan terenkripsi (cast `encrypted` di model), jadi tidak bisa dicari lewat SQL.
     * `jendela_mulai` / `jendela_selesai` boleh kosong: bila kosong, model memakai bawaan
     * tanggal show pukul 12.00 sampai H+1 pukul 06.00 WIB.
     */
    public function up(): void
    {
        Schema::create('ic_events', function (Blueprint $table) {
            $table->id('id_event');
            $table->string('kode', 20)->unique();
            $table->date('tanggal');
            $table->string('kota', 60)->nullable();
            $table->string('venue', 80)->nullable();
            $table->string('tipe', 10); // festival | club | private
            $table->text('token');
            $table->timestamp('token_dibuat_pada')->nullable();
            $table->dateTime('jendela_mulai')->nullable();
            $table->dateTime('jendela_selesai')->nullable();
            $table->timestamps();

            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_events');
    }
};
