<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/** Kegagalan masuk lewat Google yang pesannya aman ditampilkan ke anggota (Bahasa Indonesia, tanpa detail teknis). */
class GoogleGagal extends RuntimeException {}