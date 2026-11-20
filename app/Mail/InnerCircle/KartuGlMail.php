<?php

namespace App\Mail\InnerCircle;

/**
 * Kabar untuk pemenang GL gratis. Sandi acara SENGAJA tidak ikut di email: kartu (username dan sandi) hanya
 * dibuka di area anggota setelah masuk, supaya sandi tidak tersimpan di kotak masuk atau terteruskan.
 */
class KartuGlMail extends PesanIc
{
    public function __construct(
        private string $nama,
        private string $acara,
        private string $tanggal,
        private string $url,
    ) {}

    protected function subjek(): string
    {
        return "Kamu terpilih GL gratis: {$this->acara}";
    }

    protected function isi(): array
    {
        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => "Selamat, kamu terpilih GL gratis untuk {$this->acara}.",
            'paragraf' => [
                "Selamat! Kamu terpilih sebagai penerima GL gratis untuk {$this->acara} ({$this->tanggal}).",
                'Kartu GL-mu berisi nama, nama acara, serta username dan sandi acara. Buka kartunya di area anggota setelah masuk, lalu tunjukkan di pintu masuk.',
            ],
            'tombol' => ['teks' => 'Buka kartu GL', 'url' => $this->url],
            'catatan' => ['Demi keamanan, sandi acara tidak dikirim lewat email dan jangan dibagikan ke orang lain.'],
        ];
    }
}