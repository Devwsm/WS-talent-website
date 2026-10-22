<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 4): akun anggota.
     *
     * `kode_member` (IC-00001) diisi tepat setelah baris dibuat, di dalam transaksi yang sama
     * (lihat authController@prosesDaftar), jadi kolomnya boleh kosong sesaat.
     * `stempel_total` hanya cache: sumber kebenarannya tabel `ic_stempel`.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id('id_member');
            $table->string('kode_member', 12)->nullable()->unique();
            $table->string('nama', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->unsignedInteger('stempel_total')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};