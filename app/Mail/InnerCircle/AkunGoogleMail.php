<?php

namespace App\Mail\InnerCircle;

/** Balasan "lupa kata sandi" untuk akun yang dibuat lewat Google (tidak punya kata sandi untuk diatur ulang). */
class AkunGoogleMail extends PesanIc
{
    public function __construct(
        private string $nama,
        private string $url,
        private bool $googleAktif = true,
    ) {}

    protected function subjek(): string
    {
        return ic_email('google', 'subjek', ['nama' => $this->nama]);
    }

    protected function isi(): array
    {
        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => 'Akun ini masuk lewat Google, tidak memakai kata sandi.',
            'paragraf' => [
                ic_email('google', 'pembuka', ['nama' => $this->nama]),
                $this->googleAktif
                    ? 'Cukup tekan tombol di bawah dan pilih akun Google dengan alamat email yang sama.'
                    : 'Silakan masuk dari halaman Masuk memakai akun Google dengan alamat email yang sama.',
            ],
            'tombol' => ['teks' => $this->googleAktif ? 'Masuk dengan Google' : 'Buka halaman Masuk', 'url' => $this->url],
            'catatan' => ['Kalau kamu tidak merasa meminta ini, abaikan saja email ini. Akunmu tetap aman.'],
        ];
    }
}