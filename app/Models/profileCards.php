<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class profileCards extends Model
{
    //
    protected $table = 'profile_card';
    protected $primaryKey = 'id_profile_card';
    protected $fillable = [
        'role',
        'name',
        'description',
    ];
}