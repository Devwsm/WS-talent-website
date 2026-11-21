<?php

namespace App\Mail\InnerCircle;

/** Kabar untuk anggota saat status pengaduannya berubah (diproses admin di detail anggota). */
class StatusPengaduanMail extends PesanIc
{
    public function __construct(
        private string $nama,
        private string $kategori,
        private string $status,
        private string $labelStatus,
        private ?string $catatan,
        private string $url,
    ) {}

    protected function subjek(): string
    {
        return ic_email('pengaduan', 'subjek', ['nama' => $this->nama, 'status' => $this->labelStatus]);
    }

    protected function isi(): array
    {
        $kalimat = match ($this->status) {
            'diproses' => "Pengaduanmu ({$this->kategori}) sedang kami tindak lanjuti.",
            'selesai' => "Pengaduanmu ({$this->kategori}) sudah kami tangani. Terima kasih sudah memberi tahu kami.",
            default => "Status pengaduanmu ({$this->kategori}) sekarang: {$this->labelStatus}.",
        };

        // Pembuka dari CMS (bila diisi) menggantikan kalimat bawaan menurut status untuk semua status.
        $pembuka = ic_email('pengaduan', 'pembuka', ['nama' => $this->nama, 'status' => $this->labelStatus]);
        $kalimat = $pembuka !== '' ? $pembuka : $kalimat;

        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => "Status pengaduanmu: {$this->labelStatus}.",
            'paragraf' => array_values(array_filter([
                $kalimat,
                filled($this->catatan) ? 'Catatan dari tim WSM: ' . $this->catatan : null,
            ])),
            'tombol' => ['teks' => 'Lihat pengaduanku', 'url' => $this->url],
            'catatan' => [],
        ];
    }
}