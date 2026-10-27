<?php

namespace App\Mail\InnerCircle;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Dasar semua email WS Inner Circle: satu tampilan bersama (resources/views/emails/inner-circle/pesan), jadi format
 * semua notifikasi seragam. Tiap jenis email cukup mengisi `subjek()` dan `isi()`.
 *
 * Sengaja TIDAK ShouldQueue: hosting bersama tanpa terminal tidak punya queue worker, jadi email antre tidak akan
 * pernah terkirim. Pengiriman dilakukan langsung (lihat EmailService::tunda untuk jalur setelah respons).
 */
abstract class PesanIc extends Mailable
{
    /** Bila true: subjek diberi awalan [TES] dan ada catatan "email percobaan" di badan email. */
    public bool $tes = false;

    abstract protected function subjek(): string;

    /**
     * @return array{sapaan: string, pratinjau: string, paragraf: array<int, string>, tombol: array{teks: string, url: string}|null, catatan: array<int, string>}
     */
    abstract protected function isi(): array;

    public function sebagaiTes(): static
    {
        $this->tes = true;

        return $this;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->tes ? '[TES] ' : '') . $this->subjek());
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inner-circle.pesan',
            text: 'emails.inner-circle.pesan-teks',
            with: $this->isi() + ['judul' => $this->subjek()],
        );
    }
}