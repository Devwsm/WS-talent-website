<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/**
 * Check-in ditolak karena aturan bisnis. Pesannya aman dan sudah berbahasa Indonesia,
 * jadi controller boleh menampilkannya langsung ke anggota.
 */
class CheckinGagal extends RuntimeException {}
