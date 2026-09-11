<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class profile_hero extends Model
{
    //
    protected $table = 'profile_hero';
    protected $primaryKey = 'id_profile_hero';
    protected $fillable = [
        'foto',
        'judul_singkat',
        'nama',
        'tagline',
    ];
}