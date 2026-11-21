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

    /** Nama untuk sesama anggota: kata pertama + inisial kata kedua (sama seperti kartu kenangan). */
    protected function namaMember(): Attribute
    {
        return Attribute::get(function () {
            $kata = preg_split('/\s+/u', trim((string) ($this->member->nama ?? '')), -1, PREG_SPLIT_NO_EMPTY);
            if (count($kata) === 0) {
                return 'Anggota';
            }

            return count($kata) === 1 ? $kata[0] : $kata[0] . ' ' . mb_strtoupper(mb_substr($kata[1], 0, 1)) . '.';
        });
    }
}