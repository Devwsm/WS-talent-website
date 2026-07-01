<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class banner extends Model
{
    //
    protected $table = 'banners';
    protected $primaryKey = 'id_banner';
    protected $fillable = [
        'banner_name',
        'link_banner',
        'banner_cover',
    ];
}