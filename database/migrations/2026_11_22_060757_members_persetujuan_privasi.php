<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bukti persetujuan Kebijakan Privasi saat mendaftar: kapan anggota mencentangnya dan versi (tanggal pembaruan)
     * kebijakan yang berlaku saat itu. Anggota lama (daftar sebelum kebijakan 21 Nov 2026) dibiarkan kosong.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('privasi_disetujui_pada')->nullable()->after('daftar_via');
            $table->string('privasi_versi', 10)->nullable()->after('privasi_disetujui_pada');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['privasi_disetujui_pada', 'privasi_versi']);
        });
    }
};