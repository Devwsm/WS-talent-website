<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu komentar anggota pada kenangan. Teks apa adanya (di-escape saat tampil). */
class ic_memory_comment extends Model
{
    protected $table = 'ic_memory_comments';
    protected $primaryKey = 'id_komentar';

    protected $fillable = ['memory_id', 'member_id', 'isi'];

    public function kenangan(): BelongsTo
    {
        return $this->belongsTo(ic_memory::class, 'memory_id', 'id_memory');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }

    /** Nama untuk sesama anggota: nama lengkap dari profil (sama seperti kartu kenangan). */
    protected function namaMember(): Attribute
    {
        return Attribute::get(function () {
            $nama = trim((string) preg_replace('/\s+/u', ' ', (string) ($this->member->nama ?? '')));

            return $nama !== '' ? $nama : 'Anggota';
        });
    }
}