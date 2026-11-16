<?php

namespace App\Models;

use App\Models\Concerns\Berurutan;
use App\Models\Concerns\Terjadwal;
use Illuminate\Database\Eloquent\Model;

class albums extends Model
{
    use Berurutan, Terjadwal;

    protected $table = 'albums';
    protected $primaryKey = 'id_albums'; // primary key
    protected $fillable = [
        'albums_name',
        'link_spotify',
        'albums_cover',
        'urutan',
        'terbit_pada',
    ];
}