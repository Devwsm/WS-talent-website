<?php

namespace App\Models;

use App\Models\Concerns\Berurutan;
use Illuminate\Database\Eloquent\Model;

class header extends Model
{
    use Berurutan;

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
        'urutan',
    ];
}