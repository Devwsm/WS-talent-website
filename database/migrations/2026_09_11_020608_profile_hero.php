<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::create('profile_hero', function (Blueprint $table) {
            $table->id('id_profile_hero');
            $table->string('foto')->nullable();
            $table->string('judul_singkat');
            $table->string('nama');
            $table->text('tagline');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('profile_hero');
    }
};