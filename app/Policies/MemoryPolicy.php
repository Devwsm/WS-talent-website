<?php

namespace App\Policies;

use App\Models\ic_memory;
use App\Models\ic_memory_comment;
use App\Models\member;

/**
 * Hak anggota atas kenangan. Didaftarkan manual di AppServiceProvider (Gate::policy) karena nama model
 * huruf kecil tidak ditemukan penemuan otomatis. Dipanggil lewat Gate::forUser($anggota), sebab guard
 * bawaan aplikasi bukan guard `member`.
 */
class MemoryPolicy
{
    /** Hanya kenangan yang tayang yang bisa disukai; yang disembunyikan seolah tidak ada. */
    public function suka(member $anggota, ic_memory $kenangan): bool
    {
        return $kenangan->status === ic_memory::TAYANG;
    }

    /** Kenangan yang tayang bisa dilihat detailnya dan dikomentari; yang disembunyikan seolah tidak ada. */
    public function komentar(member $anggota, ic_memory $kenangan): bool
    {
        return $kenangan->status === ic_memory::TAYANG;
    }

    /** Komentar bisa dihapus penulisnya atau pemilik kenangan (admin menghapus lewat CMS). */
    public function hapusKomentar(member $anggota, ic_memory_comment $komentar): bool
    {
        return (int) $komentar->member_id === (int) $anggota->getKey()
            || (int) ($komentar->kenangan->member_id ?? 0) === (int) $anggota->getKey();
    }

    /** Hanya pemilik yang bisa menghapus kenangannya (admin menyembunyikan, tidak menghapus). */
    public function hapus(member $anggota, ic_memory $kenangan): bool
    {
        return (int) $kenangan->member_id === (int) $anggota->getKey();
    }
}