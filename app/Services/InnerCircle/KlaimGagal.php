<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/**
 * Pengajuan atau perubahan status klaim ditolak karena aturan bisnis. Pesannya aman dan sudah
 * berbahasa Indonesia, jadi controller boleh menampilkannya langsung.
 */
class KlaimGagal extends RuntimeException {}