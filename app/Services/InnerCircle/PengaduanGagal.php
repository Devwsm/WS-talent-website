<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/** Pengaduan ditolak karena aturan bisnis. Pesannya aman dan berbahasa Indonesia, jadi boleh ditampilkan langsung. */
class PengaduanGagal extends RuntimeException {}