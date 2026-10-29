<?php

namespace App\Mail\Admin;

use App\Mail\InnerCircle\PesanIc;

/** Kode OTP untuk masuk ke dashboard CMS (2FA lewat email). */
class KodeLoginAdminMail extends PesanIc
{
    public function __construct(
        private string $username,
        private string $kode,
        private int $menit,
    ) {}

    protected function subjek(): string
    {
        return "Kode masuk dashboard: {$this->kode}";
    }

    protected function isi(): array
    {
        return [
            'merek' => 'WS Talent Website',
            'sapaan' => "Halo {$this->username},",
            'pratinjau' => "Kode masuk dashboard kamu: {$this->kode}",
            'kodeBesar' => $this->kode,
            'paragraf' => [
                'Masukkan kode di atas untuk masuk ke dashboard.',
                "Kode berlaku {$this->menit} menit dan hanya bisa dipakai sekali.",
            ],
            'tombol' => null,
            'catatan' => ['Bukan kamu yang mencoba masuk? Abaikan email ini dan segera ganti kata sandi admin.'],
        ];
    }
}