<?php

namespace App\Mail\InnerCircle;

/** Sambutan untuk anggota baru yang mendaftar lewat Google (emailnya sudah terverifikasi Google). */
class SelamatDatangMail extends PesanIc
{
    public function __construct(
        private string $nama,
        private string $kodeMember,
        private string $url,
    ) {}

    protected function subjek(): string
    {
        return 'Selamat datang di WS Inner Circle';
    }

    protected function isi(): array
    {
        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => 'Akun WS Inner Circle kamu sudah aktif.',
            'paragraf' => [
                "Akun WS Inner Circle kamu sudah aktif. Kode anggotamu: {$this->kodeMember}.",
                'Datang ke show, pindai QR check-in di venue, kumpulkan stempel, lalu tukarkan dengan hadiah.',
            ],
            'tombol' => ['teks' => 'Buka WS Inner Circle', 'url' => $this->url],
            'catatan' => [],
        ];
    }
}