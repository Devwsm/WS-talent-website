<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 9): jadwal acara bisa diambil dari Bandsintown.
     *
     * - sumber: 'manual' (dibuat/diubah admin) atau 'bandsintown' (dibuat otomatis oleh sinkron). Hanya acara
     *   bersumber 'bandsintown' yang tanggal, kota, dan venue-nya diperbarui otomatis oleh sinkron berikutnya.
     * - bit_id: ID acara di Bandsintown, supaya sinkron berulang tidak membuat acara dobel.
     * - bit_url: tautan halaman acara di Bandsintown (tanpa app_id).
     * - disinkron_pada: terakhir kali acara ini dicocokkan dengan Bandsintown.
     * Semua kolom baru boleh kosong/punya bawaan: acara yang sudah ada tidak berubah.
     */
    public function up(): void
    {
        Schema::table('ic_events', function (Blueprint $table) {
            $table->string('sumber', 20)->default('manual')->after('tipe');
            $table->string('bit_id', 40)->nullable()->unique()->after('sumber');
            $table->string('bit_url', 255)->nullable()->after('bit_id');
            $table->timestamp('disinkron_pada')->nullable()->after('bit_url');
        });
    }

    public function down(): void
    {
        Schema::table('ic_events', function (Blueprint $table) {
            $table->dropUnique(['bit_id']);
            $table->dropColumn(['sumber', 'bit_id', 'bit_url', 'disinkron_pada']);
        });
    }
};