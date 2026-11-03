<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu pilihan dalam daftar yang dikelola dari CMS: tipe acara (`tipe_event`) atau kategori kenangan
 * (`kategori_kenangan`). `kunci` tetap seumur hidup baris karena dipakai data lama.
 */
class ic_pilihan extends Model
{
    public const TIPE_EVENT = 'tipe_event';
    public const KATEGORI_KENANGAN = 'kategori_kenangan';

    protected $table = 'ic_pilihan';
    protected $primaryKey = 'id_pilihan';

    protected $fillable = ['jenis', 'kunci', 'label', 'ikon', 'sampul', 'warna', 'urutan', 'aktif'];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }
}