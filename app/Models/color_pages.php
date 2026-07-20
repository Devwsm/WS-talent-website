<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class color_pages extends Model
{
    //
    protected $table = 'color_pages';
    protected $primaryKey = 'id_color_pages';
    protected $fillable = [
        'color',
    ];
}