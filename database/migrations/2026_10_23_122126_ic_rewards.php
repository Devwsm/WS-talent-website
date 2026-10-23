<?php

use Database\Seeders\icRewardSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle (Fase 5): tahapan hadiah. Teks dan tampilannya diedit di CMS (tab Hadiah);
     * `target_stempel` tetap dan unik karena dipakai sebagai kunci di URL dan aturan klaim.
     *
     * Lima tahapan dari prototype ikut terisi di migration ini (seeder `icRewardSeeder`, tidak menimpa
     * baris yang sudah ada), jadi setelah deploy cukup `php artisan migrate`.
     */
    public function up(): void
    {
        Schema::create('ic_rewards', function (Blueprint $table) {
            $table->id('id_reward');
            $table->unsignedSmallInteger('target_stempel')->unique();
            $table->string('judul', 40);
            $table->string('deskripsi', 140);
            $table->string('ikon', 30)->default('gift');
            $table->string('warna', 10)->default('koral');
            $table->json('opsi')->nullable(); // null, atau tepat 2 pilihan (mis. tahap 50)
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        (new icRewardSeeder())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_rewards');
    }
};