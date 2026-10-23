<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 6): galeri kenangan buatan anggota.
     *
     * `foto` berisi path di disk `public` (WebP hasil olahan ImageProcessor) atau null: kartu tanpa foto
     * memakai sampul berwarna sesuai kategori. `status`: tayang (langsung terbit) atau disembunyikan (oleh admin).
     */
    public function up(): void
    {
        Schema::create('ic_memories', function (Blueprint $table) {
            $table->id('id_memory');
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->string('caption', 240);
            $table->string('tempat', 65)->nullable();
            $table->string('kategori', 10); // festival | club | personal
            $table->string('foto')->nullable();
            $table->string('status', 14)->default('tayang'); // tayang | disembunyikan
            $table->timestamps();

            $table->index(['status', 'kategori', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_memories');
    }
};