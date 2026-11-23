<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR check-in dirender di server sebagai SVG (tajam di layar maupun cetak, tanpa ekstensi GD/Imagick).
 * Isi QR adalah URL /innercircle/check-in?kode=WS-XXXXXXXXXXXX, jadi kamera bawaan HP pun
 * membuka halaman Check-in yang benar dengan kode sudah terisi.
 */
class QrService
{
    public function tautan(ic_event $event): string
    {
        return route('ic.checkin', ['kode' => $event->token]);
    }

    /** SVG lengkap (dengan deklarasi XML), untuk diunduh sebagai berkas .svg. */
    public function svgBerkas(ic_event $event, int $ukuran = 600): string
    {
        $renderer = new ImageRenderer(new RendererStyle($ukuran, 2), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($this->tautan($event), 'UTF-8', ErrorCorrectionLevel::M());
    }

    /**
     * Matriks QR (tanpa zona tenang) sebagai daftar baris berisi "0" dan "1", untuk digambar sebagai kotak vektor di
     * PDF yang dibuat di browser (kartu GL untuk vendor).
     *
     * @return list<string>
     */
    public function matriks(ic_event $event): array
    {
        $matriks = Encoder::encode($this->tautan($event), ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
        $baris = [];
        for ($y = 0; $y < $matriks->getHeight(); $y++) {
            $teks = '';
            for ($x = 0; $x < $matriks->getWidth(); $x++) {
                $teks .= $matriks->get($x, $y) === 1 ? '1' : '0';
            }
            $baris[] = $teks;
        }

        return $baris;
    }

    /** SVG tanpa deklarasi XML, siap disisipkan langsung di dalam HTML. */
    public function svgInline(ic_event $event, int $ukuran = 600): string
    {
        $svg = $this->svgBerkas($event, $ukuran);

        return trim((string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
}
}