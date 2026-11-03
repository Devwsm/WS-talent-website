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
        return ic_email('selamat-datang', 'subjek', ['nama' => $this->nama, 'kode' => $this->kodeMember]);
    }

    protected function isi(): array
    {
        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => 'Akun ' . ic_program() . ' kamu sudah aktif.',
            'paragraf' => [
                ic_email('selamat-datang', 'pembuka', ['nama' => $this->nama, 'kode' => $this->kodeMember]),
                'Datang ke show, pindai QR check-in di venue, kumpulkan stempel, lalu tukarkan dengan hadiah.',
            ],
            'tombol' => ['teks' => 'Buka ' . ic_program(), 'url' => $this->url],
            'catatan' => [],
        ];
    }
}