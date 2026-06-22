<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class albums extends Model
{
    //
        protected $table = 'albums';
    protected $primaryKey = 'id_albums'; // primary key
    protected $fillable = [        
        'albums_name',
        'link_spotify',
        'albums_cover',
    ]; 

}