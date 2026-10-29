<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class log_aktivitas extends Model
{
    protected $table = 'log_aktivitas';
    protected $primaryKey = 'id_log_aktivitas';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}