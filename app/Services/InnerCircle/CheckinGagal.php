<?php

namespace App\Services\InnerCircle;

use RuntimeException;

/**
 * Check-in ditolak karena aturan bisnis. Pesannya aman dan sudah berbahasa Indonesia,
 * jadi controller boleh menampilkannya langsung ke anggota.
 *
 * Bila penyebabnya lokasi HP (bukan aturan lain), `alasan` berisi salah satu kunci `ic_pemeriksaan::ALASAN`
 * dan anggota boleh mengajukan pemeriksaan ke tim WSM (Fase 5B). `jarak` dan `akurasi` (meter) terisi bila server
 * sempat mengukurnya; `kodeAcara` adalah acara yang sedang diperiksa.
 */
class CheckinGagal extends RuntimeException
{
    public function __construct(
        string $pesan = '',
        public readonly ?string $alasan = null,
        public readonly ?int $jarak = null,
        public readonly ?int $akurasi = null,
        public readonly ?string $kodeAcara = null,
    ) {
        parent::__construct($pesan);
    }
}