<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle: check-in dengan lokasi.
     *
     * ic_events: titik pusat + radius (meter) milik ACARA, bukan milik akun CMS atau petugas yang mengaturnya.
     * Semua kolom boleh kosong: acara tanpa titik lokasi tetap bisa check-in seperti sebelumnya.
     * - lokasi_sumber: petugas (GPS HP petugas di venue) | alamat (hasil cari alamat) | manual (koordinat diketik).
     * - lokasi_diatur_oleh: nama petugas yang diisi bebas (akun CMS dipakai bersama, jadi nama akun saja tidak cukup).
     *
     * ic_stempel: hanya JARAK ke titik acara dan akurasi GPS saat check-in. Koordinat anggota sendiri TIDAK disimpan.
     */
    public function up(): void
    {
        Schema::table('ic_events', function (Blueprint $table) {
            $table->decimal('lokasi_lat', 10, 7)->nullable()->after('jendela_selesai');
            $table->decimal('lokasi_lng', 10, 7)->nullable()->after('lokasi_lat');
            $table->unsignedSmallInteger('lokasi_radius')->nullable()->after('lokasi_lng');
            $table->string('lokasi_sumber', 12)->nullable()->after('lokasi_radius');
            $table->string('lokasi_diatur_oleh', 60)->nullable()->after('lokasi_sumber');
            $table->timestamp('lokasi_diatur_pada')->nullable()->after('lokasi_diatur_oleh');
        });

        Schema::table('ic_stempel', function (Blueprint $table) {
            $table->unsignedInteger('jarak_m')->nullable()->after('catatan');
            $table->unsignedSmallInteger('akurasi_m')->nullable()->after('jarak_m');
        });
    }

    public function down(): void
    {
        Schema::table('ic_stempel', function (Blueprint $table) {
            $table->dropColumn(['jarak_m', 'akurasi_m']);
        });

        Schema::table('ic_events', function (Blueprint $table) {
            $table->dropColumn([
                'lokasi_lat',
                'lokasi_lng',
                'lokasi_radius',
                'lokasi_sumber',
                'lokasi_diatur_oleh',
                'lokasi_diatur_pada',
            ]);
        });
    }
};