<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 5): jejak perubahan status klaim (siapa, kapan, catatan).
     * Hanya ditambah, tidak pernah diubah. `oleh` kosong untuk pengajuan oleh anggota sendiri.
     */
    public function up(): void
    {
        Schema::create('ic_claim_logs', function (Blueprint $table) {
            $table->id('id_log');
            $table->foreignId('claim_id')->constrained('ic_claims', 'id_claim')->cascadeOnDelete();
            $table->string('status', 12);
            $table->string('oleh', 60)->nullable();
            $table->string('catatan', 160)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('claim_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_claim_logs');
    }
};