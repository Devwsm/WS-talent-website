<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak aktivitas admin di dashboard: siapa, kapan, melakukan apa. Isi form (teks, sandi, file) tidak disimpan.
     */
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id('id_log_aktivitas');
            $table->string('pelaku', 100)->nullable();
            $table->string('kategori', 20)->index(); // akses | konten | inner-circle
            $table->string('aksi', 80);
            $table->string('keterangan', 255)->nullable();
            $table->string('rute', 120)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('status', 10)->default('berhasil'); // berhasil | gagal
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};