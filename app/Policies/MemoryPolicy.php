<?php

namespace App\Policies;

use App\Models\ic_memory;
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

    /** Hanya pemilik yang bisa menghapus kenangannya (admin menyembunyikan, tidak menghapus). */
    public function hapus(member $anggota, ic_memory $kenangan): bool
    {
        return (int) $kenangan->member_id === (int) $anggota->getKey();
    }
}