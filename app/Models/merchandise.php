<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class merchandise extends Model
{
    //
    protected $table = 'merchandise';
    protected $primaryKey = 'id_merchandise';
    protected $fillable = [
        'merchandise_name', 
        'link_merchandise', 
        'merchandise_cover'
    ];
}