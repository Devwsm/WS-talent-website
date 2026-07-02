<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class media_sosial extends Model
{
    //
    protected $table = 'media_sosial';
    protected $primaryKey = 'id_media_sosial';
    protected $fillable = [
        'platform',
        'link',
    ];
}