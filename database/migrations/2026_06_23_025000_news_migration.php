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
        Schema::create('news', function (Blueprint $table) {
            $table->id('id_news');
            $table->string('news_title');
            $table->text('news_description');
            $table->string('news_source');
            $table->string('news_date');
            $table->string('news_cover');
            $table->string('news_link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('news');
    }
};