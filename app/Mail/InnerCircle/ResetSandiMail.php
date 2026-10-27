<?php

namespace App\Mail\InnerCircle;

/** Tautan atur ulang kata sandi (akun yang punya kata sandi). */
class ResetSandiMail extends PesanIc
{
    public function __construct(
        private string $nama,
        private string $url,
        private int $menit = 60,
    ) {}

    protected function subjek(): string
    {
        return 'Atur ulang kata sandi WS Inner Circle';
    }

    protected function isi(): array
    {
        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => 'Buat kata sandi baru untuk akun WS Inner Circle kamu.',
            'paragraf' => [
                'Kami menerima permintaan untuk mengatur ulang kata sandi akun WS Inner Circle kamu. Tekan tombol di bawah untuk membuat kata sandi baru.',
            ],
            'tombol' => ['teks' => 'Buat kata sandi baru', 'url' => $this->url],
            'catatan' => [
                "Tautan ini berlaku {$this->menit} menit dan hanya bisa dipakai sekali.",
                'Kalau kamu tidak meminta ini, abaikan saja email ini. Kata sandimu tidak berubah.',
            ],
        ];
    }
}