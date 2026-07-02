<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class collab extends Model
{
    //
    protected $table = 'collab';
    protected $primaryKey = 'id_collab';
    protected $fillable = [
        'name',
        'description',
    ];
}