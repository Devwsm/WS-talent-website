<?php

namespace App\Mail\InnerCircle;

/** Tautan verifikasi email untuk anggota yang mendaftar dengan kata sandi (hadiah baru bisa diajukan setelah terverifikasi). */
class VerifikasiEmailMail extends PesanIc
{
    public function __construct(
        private string $nama,
        private string $url,
        private int $jam = 24,
    ) {}

    protected function subjek(): string
    {
        return ic_email('verifikasi', 'subjek', ['nama' => $this->nama]);
    }

    protected function isi(): array
    {
        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => 'Satu langkah lagi: pastikan email ini milikmu.',
            'paragraf' => [
                ic_email('verifikasi', 'pembuka', ['nama' => $this->nama]),
            ],
            'tombol' => ['teks' => 'Verifikasi email', 'url' => $this->url],
            'catatan' => [
                "Tautan ini berlaku {$this->jam} jam. Buka tautan saat kamu sudah masuk ke akun " . ic_program() . '; bila belum, kamu akan diminta masuk dulu.',
                'Kalau kamu tidak mendaftar di ' . ic_program() . ', abaikan saja email ini.',
            ],
        ];
    }
}