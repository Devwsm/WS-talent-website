<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Email admin: tujuan kode OTP login (ADMIN_2FA_AKTIF). Boleh kosong sampai 2FA dinyalakan. */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};