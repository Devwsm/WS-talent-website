<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/**
 * Ronde game ditolak karena aturan bisnis (jatah habis, ronde tidak dikenal, hasil tidak bisa dihitung).
 * Pesannya aman dan berbahasa Indonesia, jadi controller boleh menampilkannya langsung; `status` = kode HTTP JSON.
 */
class GameGagal extends RuntimeException
{
    public function __construct(string $pesan, public readonly int $status = 422)
    {
        parent::__construct($pesan);
    }
}