<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 8): login dengan Google.
     *
     * - google_id: nilai `sub` dari Google (pengenal tetap akun Google, bukan email yang bisa berganti).
     * - email_verified_at: terisi bila emailnya terbukti milik anggota (kini hanya lewat Google).
     * - password boleh kosong: akun yang hanya lewat Google tidak punya kata sandi.
     * - sesi_versi: dinaikkan saat akun dikaitkan ke Google, sehingga sesi lama (mis. milik orang yang lebih dulu
     *   mendaftarkan email itu dengan sandi) otomatis tidak berlaku lagi (lihat middleware IcSesiVersi).
     * Semua perubahan bersifat menambah/melonggarkan, data anggota yang ada tidak berubah.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('google_id', 64)->nullable()->unique()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('google_id');
            $table->unsignedInteger('sesi_versi')->default(0)->after('stempel_total');
            $table->string('password')->nullable()->change();
        });
    }

    /** Kolom password sengaja dibiarkan boleh kosong: mengembalikannya ke wajib akan gagal bila sudah ada akun Google. */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'email_verified_at', 'sesi_versi']);
        });
    }
};