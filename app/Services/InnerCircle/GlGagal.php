<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/**
 * Antrean atau undian GL ditolak karena aturan bisnis. Pesannya aman dan sudah berbahasa Indonesia,
 * jadi controller boleh menampilkannya langsung ke anggota atau admin.
 */
class GlGagal extends RuntimeException {}