<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class highlight extends Model
{
    //
    protected $table = 'highlight';
    protected $primaryKey = 'id_highlight';
    protected $fillable = [
        'name',
        'place',
        'year',
    ];
}