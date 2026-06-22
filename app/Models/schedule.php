<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class schedule extends Model
{
    //
    protected $table = 'schedule';
    protected $primaryKey = 'id_schedule'; // primary key
    protected $fillable = [        
        'tanggal',
        'nama_tempat',
        'daerah'
    ]; 
}