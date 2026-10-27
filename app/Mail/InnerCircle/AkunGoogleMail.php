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
        return 'Akun WS Inner Circle kamu masuk lewat Google';
    }

    protected function isi(): array
    {
        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => 'Akun ini masuk lewat Google, tidak memakai kata sandi.',
            'paragraf' => [
                'Kami menerima permintaan atur ulang kata sandi untuk akun ini. Akun ini dibuat dengan Google, jadi tidak ada kata sandi yang perlu diatur ulang.',
                $this->googleAktif
                    ? 'Cukup tekan tombol di bawah dan pilih akun Google dengan alamat email yang sama.'
                    : 'Silakan masuk dari halaman Masuk memakai akun Google dengan alamat email yang sama.',
            ],
            'tombol' => ['teks' => $this->googleAktif ? 'Masuk dengan Google' : 'Buka halaman Masuk', 'url' => $this->url],
            'catatan' => ['Kalau kamu tidak merasa meminta ini, abaikan saja email ini. Akunmu tetap aman.'],
        ];
    }
}