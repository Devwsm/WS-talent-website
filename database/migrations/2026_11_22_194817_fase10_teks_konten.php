<?php

use App\Services\InnerCircle\IcKonten;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 10 (penutup): teks Konten yang masih menyebut aturan lama (check-in lewat QR untuk semua, "Riwayat poin")
     * disesuaikan dengan aturan sekarang. HANYA teks yang persis sama dengan bawaan lama yang diganti; teks yang
     * pernah diubah admin dari CMS tidak disentuh. Tidak mengubah struktur tabel maupun data anggota.
     */
    public function up(): void
    {
        if (Schema::hasTable('ic_item')) {
            // [judul lama, isi lama] => [judul baru, isi baru]
            $peta = [
                [
                    ['Datang ke acara', 'Pindai QR check-in di venue saat Whisnu Santika tampil.'],
                    ['Datang ke acara', 'Antri GL gratis dari halaman acara, lalu check-in di venue. Lokasi HP-mu dicek; pemenang GL menunjukkan Kartu GL ke staf.'],
                ],
                [
                    ['Bagaimana cara check-in di acara?', 'Di venue, pindai QR resmi acara dengan kamera HP, lalu masuk ke akunmu. Check-in dibuka pada hari acara dan hanya bisa dilakukan satu kali per acara.'],
                    ['Bagaimana cara check-in di acara?', 'Buka halaman acara lalu tekan Check-in saat check-in dibuka, dan izinkan akses lokasi. Tidak perlu QR. Pemenang GL gratis menunjukkan Kartu GL ke staf, lalu memindai QR acara dari staf untuk mendapat poin. Satu kali per acara.'],
                ],
            ];
            foreach ($peta as [[$judulLama, $isiLama], [$judulBaru, $isiBaru]]) {
                DB::table('ic_item')->where('judul', $judulLama)->where('isi', $isiLama)->update(['judul' => $judulBaru, 'isi' => $isiBaru]);
            }

            DB::table('ic_item')
                ->where('judul', 'Check-in QR di acara')
                ->where('ikon', 'qr-code-scan')
                ->update(['judul' => 'Check-in di acara', 'ikon' => 'geo-alt']);
        }

        if (Schema::hasTable('ic_pengaturan')) {
            // Baris yang nilainya sama dengan bawaan lama dihapus: halaman otomatis memakai bawaan baru dari config.
            $lama = [
                'tentang.meta.deskripsi' => 'Komunitas penggemar resmi DJ Whisnu Santika. Datang ke acara, kumpulkan poin lewat check-in QR, dan buka hadiah eksklusif di setiap tahap.',
                'checkin.lokasi.deskripsi' => 'Pilih acara di atas, atau langsung pindai QR dari panitia. Acaranya terpilih otomatis.',
                'beranda.stiker' => 'Scan dan check-in sekarang',
                'profil.riwayat.judul' => 'Riwayat poin',
                'profil.riwayat.kosong' => 'Belum ada poin. Datang ke acara dan check-in untuk mulai mengumpulkan.',
            ];
            foreach ($lama as $kunci => $nilai) {
                DB::table('ic_pengaturan')->where('kunci', $kunci)->where('nilai', $nilai)->delete();
            }
        }

        try {
            app(IcKonten::class)->lupakan();
        } catch (Throwable) {
            // Cache belum siap (pemasangan pertama): tidak ada isi lama yang perlu dibuang.
        }
    }

    public function down(): void
    {
        // Sengaja kosong: teks bisa sudah disunting admin, jadi tidak dikembalikan ke aturan lama.
    }
};