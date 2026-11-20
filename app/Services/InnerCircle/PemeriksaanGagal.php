<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/** Pengajuan atau peninjauan pemeriksaan kehadiran ditolak oleh aturan. Pesannya aman ditampilkan langsung. */
class PemeriksaanGagal extends RuntimeException {}