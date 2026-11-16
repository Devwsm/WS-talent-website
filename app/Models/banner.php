<?php

namespace App\Models;

use App\Models\Concerns\Berurutan;
use Illuminate\Database\Eloquent\Model;

class banner extends Model
{
    use Berurutan;

    protected $table = 'banners';
    protected $primaryKey = 'id_banner';
    protected $fillable = [
        'banner_name',
        'link_banner',
        'banner_cover',
        'urutan',
    ];
}