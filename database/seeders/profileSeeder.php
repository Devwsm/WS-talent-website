<?php

namespace Database\Seeders;

use App\Models\bio;
use App\Models\booking;
use App\Models\collab;
use App\Models\genre;
use App\Models\highlight;
use App\Models\media_coverage;
use App\Models\media_sosial;
use App\Models\profile_hero;
use App\Models\statistik;
use Illuminate\Database\Seeder;

class profileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statistik = [
            ['total' => '593K+', 'platform' => 'YouTube Subscribers'],
            ['total' => '3.3M+', 'platform' => 'Spotify Monthly Listeners'],
            ['total' => '443K+', 'platform' => 'Instagram Followers'],
        ];
        foreach ($statistik as $item) {
            statistik::create($item);
        }

        // Catatan: field 'place' menyimpan nama event, 'description' menyimpan lokasi
        // (mengikuti urutan tampil di profile-full.blade.php)
        $highlights = [
            ['place' => 'Tomorrowland 2026', 'description' => 'Boom, Belgium', 'year' => '2026'],
            ['place' => 'Djakarta Warehouse Project', 'description' => 'Bali, Indonesia', 'year' => '2025'],
            ['place' => 'Sahara 1st Anniversary Tour', 'description' => 'Multishow, Indonesia', 'year' => '2023'],
            ['place' => 'Borderland Music Festival', 'description' => 'Kuching, Sarawak', 'year' => '2025'],
        ];
        foreach ($highlights as $item) {
            highlight::create($item);
        }

        // Catatan: seed di bawah ini diambil persis dari konten yang saat ini
        // hardcode di resources/views/components/profile/profile-full.blade.php,
        // supaya pas UI Fase 6 langkah 2 jadi, datanya nggak kosong / nggak beda.

        // hero (singleton)
        profile_hero::create([
            'foto' => null, // belum ada file webp existing, staff upload lewat form Fase 6 langkah 2
            'judul_singkat' => 'DJ & Producer',
            'nama' => 'Whisnu Santika',
            'tagline' => 'Pionir Indonesian Bounce — ',
        ]);

        // genre
        $genres = ['Indonesian Bounce', 'EDM', 'Dancehall', 'Afrobeat', 'Moombahton', 'Bass Music'];
        foreach ($genres as $nama) {
            genre::create(['nama_genre' => $nama]);
        }

        // bio (singleton, disimpan sebagai HTML rich text — 3 paragraf jadi 3x <p>)
        bio::create([
            'konten' => '<p>Whisnu Santika adalah DJ dan produser rekaman asal Jakarta yang dikenal sebagai pionir '
                . '<strong>Indonesian Bounce</strong> — sebuah pendekatan genre yang ia ciptakan sendiri dengan '
                . 'memadukan EDM, dancehall, moombahton, hip hop, bass, dan afrobeat.</p>'
                . '<p>Memulai karier pada 2012, ia konsisten membangun identitas suara yang khas dan relevan '
                . 'di skena musik elektronik Indonesia maupun internasional. Hits seperti "Tequila", "Cartel", '
                . '"Yalla Habibi", dan "Sahara" meraih jutaan stream dan dimainkan di panggung festival dunia.</p>'
                . '<p>Pada 2024, ia tampil di <strong>Tomorrowland Belgium</strong> — festival EDM terbesar '
                . 'di dunia — menjadi salah satu DJ Indonesia dengan jangkauan panggung global.</p>',
        ]);

        // collab
        $collabs = [
            ['nama' => 'Dipha Barus', 'role' => 'DJ / Producer'],
            ['nama' => 'Cinta Laura Kiehl', 'role' => 'Penyanyi'],
            ['nama' => 'Souljah', 'role' => 'Band'],
            ['nama' => 'Amy B', 'role' => 'Singer · Australia'],
        ];
        foreach ($collabs as $item) {
            collab::create($item);
        }

        // media coverage
        $medias = ['Suara.com', 'iNews.ID', 'Spotify Editorial', 'Wikipedia ID'];
        foreach ($medias as $nama) {
            media_coverage::create(['nama_media' => $nama]);
        }

        // booking & kontak
        $bookings = [
            ['label' => 'Show & Touring', 'email' => 'jeannita.kirana@gmail.com'],
            ['label' => 'Brand & Partnership', 'email' => 'partnership@whisnusantika.com'],
            ['label' => 'Media & Press', 'email' => 'management@whisnusantika.com'],
        ];
        foreach ($bookings as $item) {
            booking::create($item);
        }

        // media sosial
        $socials = [
            ['platform' => 'Instagram', 'url' => 'https://www.instagram.com/whisnusantika'],
            ['platform' => 'TikTok', 'url' => 'https://www.tiktok.com/@whisnusantika'],
            ['platform' => 'YouTube', 'url' => 'https://youtube.com/@whisnusantika'],
            ['platform' => 'Spotify', 'url' => 'https://open.spotify.com/artist/6gvsmDZKW5wRvjKCPnbHDh'],
        ];
        foreach ($socials as $item) {
            media_sosial::create($item);
        }
    }
}