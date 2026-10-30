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
        return 'Verifikasi email WS Inner Circle';
    }

    protected function isi(): array
    {
        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => 'Satu langkah lagi: pastikan email ini milikmu.',
            'paragraf' => [
                'Terima kasih sudah bergabung di WS Inner Circle. Tekan tombol di bawah untuk memastikan email ini milikmu. Email yang sudah terverifikasi dibutuhkan untuk mengajukan hadiah.',
            ],
            'tombol' => ['teks' => 'Verifikasi email', 'url' => $this->url],
            'catatan' => [
                "Tautan ini berlaku {$this->jam} jam. Buka tautan saat kamu sudah masuk ke akun WS Inner Circle; bila belum, kamu akan diminta masuk dulu.",
                'Kalau kamu tidak mendaftar di WS Inner Circle, abaikan saja email ini.',
            ],
        ];
    }
}