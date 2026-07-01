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
        Schema::create('headers', function (Blueprint $table) {
            $table->id("id_header");
            $table->string('header_color');
            $table->string('header_title');
            $table->string('header_img');
            $table->string('header_name');
            $table->string('header_description');
            $table->string('link_header');
            $table->string('header_background');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};