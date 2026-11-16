<?php

namespace App\Models;

use App\Models\Concerns\Terjadwal;
use Illuminate\Database\Eloquent\Model;

class news extends Model
{
    use Terjadwal;

    protected $table = 'news';
    protected $primaryKey = 'id_news';
    protected $fillable = [
        'news_title',
        'news_description',
        'news_source',
        'news_date',
        'news_cover',
        'news_link',
        'terbit_pada',
    ];
}