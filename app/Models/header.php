<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class header extends Model
{
    //
    protected $table = 'headers';
    protected $primaryKey = 'id_header';
    protected $fillable = [
        'header_color',
        'header_title',
        'header_img',
        'header_name',
        'header_description',
        'link_header',
        'header_background',
    ];
}