<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class media_coverage extends Model
{
    //
    protected $table = 'media_coverage';
    protected $primaryKey = 'id_media_coverage';
    protected $fillable = [
        'platform',
        'link',
    ];
}