<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 6): tanda suka. Kunci utama pasangan (anggota, kenangan) menjamin satu suka
     * per anggota per kenangan, bahkan bila dua klik datang bersamaan. Tidak punya model: dipakai lewat
     * relasi `penyuka()` di model ic_memory.
     */
    public function up(): void
    {
        Schema::create('ic_memory_likes', function (Blueprint $table) {
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->foreignId('memory_id')->constrained('ic_memories', 'id_memory')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['member_id', 'memory_id']);
            $table->index('memory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_memory_likes');
    }
};