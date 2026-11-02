<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/** Aksi pengelolaan konten ditolak; pesannya aman ditampilkan ke admin. */
class KontenGagal extends RuntimeException {}