<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu teks konten Inner Circle yang diubah dari CMS. Hanya ada bila berbeda dari teks bawaan (config/ic_konten.php). */
class ic_pengaturan extends Model
{
    protected $table = 'ic_pengaturan';
    protected $primaryKey = 'id_pengaturan';

    protected $fillable = ['kunci', 'nilai', 'diubah_oleh'];
}