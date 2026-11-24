<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acara bisa disembunyikan dari daftar anggota. Kolom `tampil_anggota`:
 *  - NULL  = otomatis: acara di bulan sebelum bulan berjalan tersembunyi dengan sendirinya (bawaan semua acara lama dan baru)
 *  - true  = admin memaksa acara tampil (mis. acara bulan lalu yang masih mau dipajang)
 *  - false = admin menyembunyikan acara itu
 * Hanya memengaruhi daftar acara anggota dan teaser; QR, check-in, kartu GL, dan tautan langsung tetap berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ic_events', function (Blueprint $table) {
            $table->boolean('tampil_anggota')->nullable()->after('petunjuk_masuk');
        });
    }

    public function down(): void
    {
        Schema::table('ic_events', function (Blueprint $table) {
            $table->dropColumn('tampil_anggota');
        });
    }
};