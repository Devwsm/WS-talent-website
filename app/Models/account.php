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
        'email',
        'password'
    ];

    /**
     * Sidik sandi untuk sesi admin: berubah begitu sandi diganti, jadi sesi lama bisa dikenali dan dikeluarkan.
     * Yang disimpan di sesi hanya sidik ini, bukan sandi maupun hash-nya.
     */
    public function sidikSesi(): string
    {
        return hash_hmac('sha256', (string) $this->password, (string) config('app.key'));
    }
}