<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class account extends Model
{
    //
    protected $table = 'accounts';
    protected $primaryKey = 'id_account';
    protected $fillable = [
        'username',
        'password'
    ];

}