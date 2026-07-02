<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class statistik extends Model
{
    //
    protected $table = 'statistik';
    protected $primaryKey = 'id_statistik';
    protected $fillable = [
        'total',
        'platform',
    ];
}