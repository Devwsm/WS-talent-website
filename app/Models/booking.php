<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class booking extends Model
{
    //
    protected $table = 'booking';
    protected $primaryKey = 'id_booking';
    protected $fillable = [
        'category',
        'contact',
    ];
}