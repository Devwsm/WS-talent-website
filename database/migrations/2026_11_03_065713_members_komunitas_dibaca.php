<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lencana "belum dibaca" di tab Komunitas.
     *
     * Dua kolom menyimpan kapan anggota terakhir membuka tab Pengumuman dan tab Galeri. Isi yang terbit/tayang sesudah
     * waktu itu (dan bukan kenangan milik anggota sendiri) dihitung belum dibaca. Kolom kosong = anggota belum pernah
     * membuka tab itu: pembanding yang dipakai adalah waktu ia bergabung, jadi isi lama sebelum ia bergabung tidak
     * menjadi lencana. Hanya menambah kolom, data yang ada tidak berubah.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('pengumuman_dibaca_pada')->nullable()->after('sesi_versi');
            $table->timestamp('galeri_dibaca_pada')->nullable()->after('pengumuman_dibaca_pada');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['pengumuman_dibaca_pada', 'galeri_dibaca_pada']);
        });
    }
};