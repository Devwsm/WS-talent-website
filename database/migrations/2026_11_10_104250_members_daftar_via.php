<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cara anggota PERTAMA KALI mendaftar: `manual` (formulir daftar dengan sandi) atau `google`.
     *
     * Kolom ini perlu karena cara daftar tidak bisa ditebak dari kolom lain: akun manual yang kemudian dikaitkan ke
     * Google ikut mendapat google_id (dan sandinya dihapus bila belum terverifikasi), jadi tampak seperti akun Google.
     *
     * Data lama diisi dengan tebakan terbaik:
     * - tanpa google_id -> manual (pasti benar).
     * - dengan google_id -> google bila email terverifikasi di detik yang sama dengan akun dibuat (akun Google baru
     *   langsung terverifikasi saat dibuat); selain itu manual (daftar dulu, Google dikaitkan belakangan).
     * Akun manual yang dikaitkan ke Google dalam hitungan detik sesudah daftar bisa salah tercatat sebagai google;
     * jarang terjadi, dan tidak memengaruhi apa pun selain label di tab Anggota.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('daftar_via', 10)->default('manual')->after('email_verified_at');
        });

        DB::table('members')->whereNotNull('google_id')->orderBy('id_member')
            ->select(['id_member', 'created_at', 'email_verified_at'])
            ->chunk(200, function ($baris) {
                foreach ($baris as $b) {
                    if ($b->created_at === null || $b->email_verified_at === null) {
                        continue;
                    }

                    $selisih = abs(strtotime((string) $b->email_verified_at) - strtotime((string) $b->created_at));
                    if ($selisih <= 5) {
                        DB::table('members')->where('id_member', $b->id_member)->update(['daftar_via' => 'google']);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('daftar_via');
        });
    }
};