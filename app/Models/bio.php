<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class bio extends Model
{
    //
    protected $table = 'bio';
    protected $primaryKey = 'id_bio';
    protected $fillable = [
        'description',
    ];
}