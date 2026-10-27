<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/** Penghapusan akun yang ditolak dengan pesan yang aman ditampilkan ke anggota. */
class AkunGagal extends RuntimeException {}