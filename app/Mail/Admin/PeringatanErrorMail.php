<?php

namespace App\Mail\Admin;

use App\Mail\InnerCircle\PesanIc;

/** Peringatan error aplikasi untuk pengelola (tujuan: LOG_ALERT_EMAIL). Detail lengkap ada di halaman Log. */
class PeringatanErrorMail extends PesanIc
{
    public function __construct(
        private string $kelas,
        private string $pesan,
        private string $lokasi,
        private ?string $url,
        private string $waktu,
        private int $jumlah,
        private string $urlLog,
    ) {}

    protected function subjek(): string
    {
        return '[Error] ' . class_basename($this->kelas) . ' di website Whisnu Santika';
    }

    protected function isi(): array
    {
        return [
            'merek' => 'WS Talent Website',
            'sapaan' => 'Halo,',
            'pratinjau' => 'Ada error di website yang perlu dicek.',
            'paragraf' => array_values(array_filter([
                'Ada error yang tidak tertangani di website.',
                'Jenis: ' . $this->kelas,
                'Pesan: ' . mb_substr($this->pesan, 0, 300),
                'Lokasi kode: ' . $this->lokasi,
                $this->url ? 'Alamat: ' . $this->url : null,
                'Terakhir terjadi: ' . $this->waktu . ' (' . $this->jumlah . 'x sejauh ini)',
            ])),
            'tombol' => ['teks' => 'Buka halaman Log', 'url' => $this->urlLog],
            'catatan' => ['Email serupa untuk error yang sama tidak dikirim lagi selama satu jam.'],
        ];
    }
}