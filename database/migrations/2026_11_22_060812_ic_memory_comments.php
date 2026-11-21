<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle: komentar anggota pada kenangan. Ikut terhapus bersama kenangannya (atau akun penulisnya).
     * `status` dipakai moderasi admin; komentar yang dihapus admin benar-benar dihapus, jadi hanya `tayang` yang ada.
     */
    public function up(): void
    {
        Schema::create('ic_memory_comments', function (Blueprint $table) {
            $table->id('id_komentar');
            $table->foreignId('memory_id')->constrained('ic_memories', 'id_memory')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->string('isi', 300);
            $table->timestamps();

            $table->index(['memory_id', 'id_komentar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_memory_comments');
    }
};