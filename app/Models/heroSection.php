<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class heroSection extends Model
{
    //
    protected $table = 'hero_section';
    protected $primaryKey = 'id_hero_section'; //primary key
    protected $fillable = [
        'logo', 'background_video'
    ];
}