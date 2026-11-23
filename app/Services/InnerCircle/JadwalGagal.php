<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/**
 * Pekerjaan terjadwal (sinkron Bandsintown, undian GL otomatis) gagal. Dilaporkan lewat report() supaya muncul di
 * halaman Log CMS Inner Circle dan ikut email peringatan, bukan hanya hilang di keluaran cron.
 */
class JadwalGagal extends RuntimeException {}