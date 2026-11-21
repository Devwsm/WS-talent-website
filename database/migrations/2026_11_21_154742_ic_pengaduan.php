<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 9 aturan baru Inner Circle: pengaduan anggota. Hanya menambah tabel, tidak mengubah data lama.
     *
     * Anggota mengirim (kategori, acara terkait opsional, isi), admin menindaklanjuti dari detail anggota (status baru ->
     * diproses -> selesai plus catatan untuk anggota). Kunci asing ke anggota bersifat cascade: pengaduan ikut terhapus saat
     * akun dihapus. Acara terkait memakai `nullOnDelete` supaya pengaduan tetap ada bila acaranya dihapus.
     */
    public function up(): void
    {
        Schema::create('ic_pengaduan', function (Blueprint $table) {
            $table->id('id_pengaduan');
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->string('kategori', 12);
            $table->foreignId('event_id')->nullable()->constrained('ic_events', 'id_event')->nullOnDelete();
            $table->text('isi');
            $table->string('status', 10)->default('baru'); // baru | diproses | selesai
            $table->string('catatan_admin', 300)->nullable();
            $table->string('diproses_oleh', 60)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['member_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_pengaduan');
    }
};