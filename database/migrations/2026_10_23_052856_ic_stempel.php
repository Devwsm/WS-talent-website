<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 4): buku besar stempel. Hanya ditambah, tidak pernah diubah.
     *
     * Check-in = baris dengan sumber `checkin` dan `event_id` terisi. Kunci unik
     * (member_id, event_id) menjamin 1 stempel per anggota per acara; baris stempel manual
     * (`event_id` kosong) tidak terkena karena NULL dianggap berbeda oleh MySQL dan SQLite.
     */
    public function up(): void
    {
        Schema::create('ic_stempel', function (Blueprint $table) {
            $table->id('id_stempel');
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->unsignedSmallInteger('jumlah')->default(1);
            $table->string('sumber', 10); // checkin | manual
            $table->foreignId('event_id')->nullable()->constrained('ic_events', 'id_event')->restrictOnDelete();
            $table->string('judul', 120);
            $table->string('ikon', 30)->default('sparkles');
            $table->string('catatan', 160)->nullable();
            $table->string('dicatat_oleh', 60)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['member_id', 'event_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_stempel');
    }
};