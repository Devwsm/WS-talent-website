<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan error aplikasi untuk halaman Log di dashboard. Error yang sama (kelas + lokasi kode) digabung jadi
     * satu baris dan hanya menaikkan `jumlah`, supaya satu bug yang terjadi ribuan kali tidak memenuhi tabel.
     */
    public function up(): void
    {
        Schema::create('log_errors', function (Blueprint $table) {
            $table->id('id_log_error');
            $table->string('fingerprint', 40)->unique(); // sha1(kelas|berkas:baris)
            $table->string('kelas', 191);
            $table->text('pesan');
            $table->string('lokasi', 255)->nullable(); // berkas:baris, relatif terhadap folder proyek
            $table->text('jejak')->nullable();
            $table->string('url', 500)->nullable(); // tanpa query string (bisa memuat token)
            $table->string('metode', 10)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('pengguna', 100)->nullable();
            $table->unsignedInteger('jumlah')->default(1);
            $table->boolean('selesai')->default(false);
            $table->timestamp('pertama_pada')->nullable();
            $table->timestamp('terakhir_pada')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_errors');
    }
};