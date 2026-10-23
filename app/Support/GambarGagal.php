<?php

namespace App\Support;

use RuntimeException;

/** Foto tidak bisa diproses. Pesannya berbahasa Indonesia dan aman ditampilkan langsung ke anggota. */
class GambarGagal extends RuntimeException {}