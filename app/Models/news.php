<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class news extends Model
{
    //
    protected $table = 'news';
    protected $primaryKey = 'id_news';
    protected $fillable = [
        'news_title',
        'news_description',
        'news_source',
        'news_date',
        'news_cover',
        'news_link'
    ];
}