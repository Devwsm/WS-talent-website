<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class log_error extends Model
{
    protected $table = 'log_errors';
    protected $primaryKey = 'id_log_error';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'selesai' => 'boolean',
            'jumlah' => 'integer',
            'pertama_pada' => 'datetime',
            'terakhir_pada' => 'datetime',
        ];
    }
}