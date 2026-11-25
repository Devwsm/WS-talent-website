<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian perubahan pada Log > Aktivitas admin: satu baris teks per perubahan (mis. "Kuota GL: kosong → 25").
 * Kolom `keterangan` (255 karakter) tetap berisi ringkasan satu kalimat; rincian untuk yang ingin tahu detailnya.
 * Nullable, jadi catatan lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->text('rincian')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropColumn('rincian');
        });
    }
};