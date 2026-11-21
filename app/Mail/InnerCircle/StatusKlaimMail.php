<?php

namespace App\Mail\InnerCircle;

/** Kabar untuk anggota saat status penukaran hadiahnya berubah (diproses admin di tab Penukaran). */
class StatusKlaimMail extends PesanIc
{
    public function __construct(
        private string $nama,
        private string $hadiah,
        private string $status,
        private string $labelStatus,
        private ?string $catatan,
        private string $url,
        private int $poinKembali = 0,
    ) {}

    protected function subjek(): string
    {
        return ic_email('klaim', 'subjek', ['nama' => $this->nama, 'hadiah' => $this->hadiah, 'status' => $this->labelStatus]);
    }

    protected function isi(): array
    {
        $kalimat = match ($this->status) {
            'diajukan' => "Pengajuan hadiah \"{$this->hadiah}\" kami buka kembali dan akan ditinjau lagi oleh tim WSM.",
            'ditolak' => "Pengajuan hadiah \"{$this->hadiah}\" belum bisa kami proses." . ($this->poinKembali > 0 ? ' ' . ic_angka($this->poinKembali) . ' poin yang ditahan sudah dikembalikan ke saldomu.' : ''),
            'selesai' => "Pengajuan hadiah \"{$this->hadiah}\" sudah selesai. Terima kasih sudah jadi bagian dari Inner Circle!",
            default => "Status pengajuan hadiah \"{$this->hadiah}\" sekarang: {$this->labelStatus}.",
        };

        // Pembuka dari CMS (bila diisi) menggantikan kalimat bawaan menurut status untuk semua status.
        $pembuka = ic_email('klaim', 'pembuka', ['nama' => $this->nama, 'hadiah' => $this->hadiah, 'status' => $this->labelStatus]);
        $kalimat = $pembuka !== '' ? $pembuka : $kalimat;

        return [
            'sapaan' => "Halo {$this->nama},",
            'pratinjau' => "Status hadiah \"{$this->hadiah}\": {$this->labelStatus}.",
            'paragraf' => array_values(array_filter([
                $kalimat,
                filled($this->catatan) ? ($this->status === 'ditolak' ? 'Alasan dari tim WSM: ' : 'Catatan dari tim WSM: ') . $this->catatan : null,
            ])),
            'tombol' => ['teks' => 'Lihat hadiahku', 'url' => $this->url],
            'catatan' => [],
        ];
    }
}