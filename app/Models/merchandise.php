<?php

namespace App\Models;

use App\Models\Concerns\Berurutan;
use Illuminate\Database\Eloquent\Model;

class merchandise extends Model
{
    use Berurutan;

    protected $table = 'merchandise';
    protected $primaryKey = 'id_merchandise';
    protected $fillable = [
        'merchandise_name',
        'link_merchandise',
        'merchandise_cover',
        'urutan',
    ];
}