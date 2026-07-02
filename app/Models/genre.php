<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class genre extends Model
{
    //
    protected $table = 'genre';
    protected $primaryKey = 'id_genre';
    protected $fillable = [
        'genre',
    ]; 
}