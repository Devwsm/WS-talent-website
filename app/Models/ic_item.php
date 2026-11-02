<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu butir dalam daftar berurutan (langkah, FAQ, poin teaser, ...) yang dikelola dari CMS. */
class ic_item extends Model
{
    protected $table = 'ic_item';
    protected $primaryKey = 'id_item';

    protected $fillable = ['grup', 'urutan', 'judul', 'isi', 'ikon', 'aktif'];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }
}