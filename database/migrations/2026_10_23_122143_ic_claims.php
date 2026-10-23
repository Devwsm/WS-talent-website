<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 5): pengajuan hadiah anggota.
     *
     * Kunci unik (member_id, reward_id) menjamin satu pengajuan per anggota per tahap, termasuk bila
     * dua klik datang bersamaan. `status`: diajukan -> ditinjau -> disetujui -> selesai, atau ditolak.
     * `catatan_admin` tampil di Profil anggota (mis. kode voucher atau alasan penolakan).
     */
    public function up(): void
    {
        Schema::create('ic_claims', function (Blueprint $table) {
            $table->id('id_claim');
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('ic_rewards', 'id_reward')->restrictOnDelete();
            $table->string('opsi_dipilih', 60)->nullable();
            $table->string('status', 12)->default('diajukan');
            $table->string('catatan_admin', 160)->nullable();
            $table->string('diproses_oleh', 60)->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'reward_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_claims');
    }
};