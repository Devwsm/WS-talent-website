<?php

namespace App\Services\InnerCircle;

use App\Models\ic_broadcast;
use Illuminate\Support\Facades\DB;

/**
 * Pengumuman WS Inner Circle. Aturan utamanya: hanya satu yang boleh jadi "Kabar pilihan".
 * Menandai satu sebagai unggulan otomatis menurunkan yang lama menjadi pengumuman biasa, dalam transaksi
 * yang sama dengan penyimpanannya (MySQL tidak punya kunci unik parsial untuk ini).
 */
class PengumumanService
{
    /**
     * Buat pengumuman baru, atau ubah yang sudah ada bila $pengumuman diberikan.
     *
     * @param  array{judul: string, isi: string, tag?: ?string, ikon: string, warna: string, cta_label: string, cta_tujuan: string, terbit_pada: string, unggulan?: bool}  $data
     */
    public function simpan(array $data, ?ic_broadcast $pengumuman = null): ic_broadcast
    {
        return DB::transaction(function () use ($data, $pengumuman) {
            $unggulan = (bool) ($data['unggulan'] ?? false);

            if ($unggulan) {
                ic_broadcast::query()
                    ->where('unggulan', true)
                    ->when($pengumuman, fn($q) => $q->where('id_broadcast', '!=', $pengumuman->getKey()))
                    ->update(['unggulan' => false]);
            }

            $atribut = [
                'judul' => $data['judul'],
                'isi' => $data['isi'],
                'tag' => ($data['tag'] ?? null) ?: null,
                'ikon' => $data['ikon'],
                'warna' => $data['warna'],
                'cta_label' => $data['cta_label'],
                'cta_tujuan' => $data['cta_tujuan'],
                'terbit_pada' => $data['terbit_pada'],
                'unggulan' => $unggulan,
            ];

            if ($pengumuman) {
                $pengumuman->update($atribut);

                return $pengumuman;
            }

            return ic_broadcast::query()->create($atribut + ['aktif' => true]);
        });
    }

    public function hapus(ic_broadcast $pengumuman): void
    {
        $pengumuman->delete();
    }
}