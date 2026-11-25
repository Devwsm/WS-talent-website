<?php

namespace App\Support;

use Illuminate\Http\Request;
use Throwable;

/**
 * Menerjemahkan catatan error teknis menjadi judul dan keterangan berbahasa sehari-hari untuk halaman Log (dan email
 * peringatan). Data teknis aslinya (kelas, pesan, lokasi kode, jejak) tetap disimpan dan tampil di "Detail teknis".
 *
 * Tidak menyentuh database dan tidak melempar galat: bila sesuatu tidak dikenali, dipakai teks umum yang tetap jelas.
 */
class PenjelasLog
{
    /** Aturan untuk error biasa; yang pertama cocok dipakai. `kelas` dan `pesan` adalah pola regex (salah satu cukup). */
    private const ATURAN = [
        [
            'kelas' => '/QueryException|PDOException|DatabaseException/i',
            'pesan' => '/SQLSTATE|MySQL server has gone away/i',
            'judul' => 'Database bermasalah',
            'isi' => 'Data tidak bisa dibaca atau disimpan. Biasanya karena database sedang gangguan, atau ada data yang tidak cocok dengan aturan tabelnya.',
        ],
        [
            'kelas' => '/Transport|Mailer|Swift_/i',
            'pesan' => '/Connection could not be established|Failed to authenticate|Expected response code/i',
            'judul' => 'Email gagal terkirim',
            'isi' => 'Server email tidak bisa dihubungi atau menolak pesan. Periksa pengaturan email di server.',
        ],
        [
            'kelas' => '/ConnectionException/i',
            'pesan' => '/cURL error|timed out|Could not resolve/i',
            'judul' => 'Layanan luar tidak bisa dihubungi',
            'isi' => 'Website gagal menghubungi layanan lain (misalnya Bandsintown, Google, atau peta). Biasanya sementara, coba lagi nanti.',
        ],
        [
            'kelas' => '/UnableToWriteFile|UnableToReadFile|UnableToCreateDirectory|FilesystemException|FileNotFoundException/i',
            'pesan' => '/Failed to open stream|Permission denied|No space left/i',
            'judul' => 'File gagal disimpan atau dibaca',
            'isi' => 'Server tidak bisa menulis atau membaca sebuah file. Periksa izin folder dan sisa ruang penyimpanan hosting.',
        ],
        [
            'kelas' => '/ViewException|CompilerException|ParseError/i',
            'pesan' => '/(?!)/',
            'judul' => 'Tampilan halaman bermasalah',
            'isi' => 'Ada bagian halaman yang gagal ditampilkan karena kesalahan di kode tampilan. Perlu dicek tim teknis.',
        ],
    ];

    private const UMUM = [
        'judul' => 'Terjadi kesalahan di sistem',
        'isi' => 'Ada masalah teknis yang tidak terduga. Teruskan ke tim teknis bila terus berulang; rinciannya ada di "Detail teknis".',
    ];

    /** Jenis konten yang dilaporkan CSP (awalan nama aturan) dalam bahasa awam. */
    private const JENIS_CSP = [
        'script' => 'Skrip',
        'style' => 'Gaya tampilan',
        'img' => 'Gambar',
        'font' => 'Font',
        'connect' => 'Koneksi data',
        'frame' => 'Konten tersemat',
        'child' => 'Konten tersemat',
        'media' => 'Audio atau video',
        'worker' => 'Skrip latar belakang',
        'manifest' => 'File manifest',
        'form' => 'Pengiriman formulir',
        'object' => 'Objek tersemat',
    ];

    private const SUMBER_CSP = [
        'inline' => 'yang tertanam di halaman',
        'eval' => 'dinamis (eval)',
        'data' => 'tertanam (data:)',
        'blob' => 'sementara (blob:)',
        'tidak-diketahui' => 'dari sumber yang tidak diketahui',
    ];

    /** Nama halaman untuk route yang tidak punya pola umum. */
    private const HALAMAN = [
        'home' => 'Beranda website',
        'profile' => 'Halaman Profil',
        'privasi' => 'Kebijakan Privasi',
        'login' => 'Login admin',
        'login.proses' => 'Login admin',
        'login.otp' => 'Kode verifikasi admin',
        'login.otp.proses' => 'Kode verifikasi admin',
        'login.otp.ulang' => 'Kode verifikasi admin',
        'dashboard' => 'CMS Artis › Dashboard',
        'dashboard.profile' => 'CMS Artis › Profile',
        'color_pages.index' => 'CMS Artis › Warna Web',
        'color_pages.update' => 'CMS Artis › Warna Web',
        'robots' => 'robots.txt',
        'sitemap' => 'sitemap.xml',
        'ic.tentang' => 'Tentang Inner Circle',
        'ic.masuk' => 'Masuk Inner Circle',
        'ic.masuk.proses' => 'Masuk Inner Circle',
        'ic.daftar' => 'Daftar Inner Circle',
        'ic.daftar.proses' => 'Daftar Inner Circle',
        'ic.sandi.lupa' => 'Lupa kata sandi Inner Circle',
        'ic.sandi.kirim' => 'Lupa kata sandi Inner Circle',
        'ic.sandi.atur' => 'Atur ulang kata sandi Inner Circle',
        'ic.sandi.atur.proses' => 'Atur ulang kata sandi Inner Circle',
        'ic.beranda' => 'Beranda anggota Inner Circle',
        'ic.hadiah' => 'Hadiah anggota Inner Circle',
        'ic.checkin' => 'Check-in Inner Circle',
        'ic.checkin.proses' => 'Check-in Inner Circle',
        'ic.checkin.mandiri' => 'Check-in mandiri Inner Circle',
        'ic.komunitas' => 'Komunitas Inner Circle',
        'ic.profil' => 'Profil anggota Inner Circle',
    ];

    /**
     * Judul dan keterangan untuk satu catatan error.
     *
     * @return array{judul: string, deskripsi: string, halaman: ?string}
     */
    public static function untuk(string $kelas, string $pesan, ?string $lokasi, ?string $url, ?string $metode): array
    {
        try {
            $halaman = self::halaman($url, $metode);

            if (str_starts_with($kelas, 'CSP:')) {
                return self::csp($kelas, $pesan, (string) $lokasi, $halaman) + ['halaman' => $halaman];
            }

            $aturan = self::UMUM;
            foreach (self::ATURAN as $a) {
                if (preg_match($a['kelas'], $kelas) === 1 || preg_match($a['pesan'], $pesan) === 1) {
                    $aturan = $a;
                    break;
                }
            }

            return [
                'judul' => $aturan['judul'] . ($halaman ? ' di ' . $halaman : ''),
                'deskripsi' => $aturan['isi'] . (self::menulis($metode) && $halaman ? ' Terjadi saat menyimpan perubahan.' : ''),
                'halaman' => $halaman,
            ];
        } catch (Throwable) {
            return ['judul' => self::UMUM['judul'], 'deskripsi' => self::UMUM['isi'], 'halaman' => null];
        }
    }

    /**
     * Nama halaman yang mudah dibaca untuk sebuah alamat, mis. "/innercircle/daftar" menjadi "Daftar Inner Circle".
     * Null bila alamatnya tidak dikenali atau berasal dari perintah artisan.
     */
    public static function halaman(?string $url, ?string $metode = 'GET'): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || $url === 'artisan') {
            return null;
        }

        $jalur = (string) parse_url($url, PHP_URL_PATH);
        $jalur = $jalur === '' ? '/' : $jalur;
        $metode = in_array($metode, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true) ? $metode : 'GET';

        try {
            $nama = app('router')->getRoutes()->match(Request::create($jalur, $metode))->getName();
        } catch (Throwable) {
            return null;
        }

        return is_string($nama) && $nama !== '' ? self::namaHalaman($nama) : null;
    }

    // ---------- internal ----------

    private static function csp(string $kelas, string $pesan, string $lokasi, ?string $halaman): array
    {
        $direktif = substr($kelas, 4);
        $host = strtok($lokasi, ' ') ?: 'tidak-diketahui';
        $diblokir = str_contains($pesan, '(diblokir)');

        $awalan = strtok($direktif, '-') ?: '';
        $jenis = self::JENIS_CSP[$awalan] ?? 'Konten';
        $dari = isset(self::SUMBER_CSP[$host]) ? self::SUMBER_CSP[$host] : 'dari ' . $host;

        $kalimat = ['Halaman ' . ($halaman ? '“' . $halaman . '”' : 'ini') . ' memuat ' . strtolower($jenis) . ' ' . $dari . ' yang belum ada di daftar izin pengaman situs.'];
        $kalimat[] = $diblokir
            ? 'Bagian halaman yang bergantung padanya mungkin tidak tampil.'
            : 'Saat ini hanya dicatat dan tidak diblokir, jadi pengunjung tidak terganggu.';
        $kalimat[] = preg_match('/(facebook|instagram|fbcdn)\./i', $host)
            ? 'Sering muncul saat halaman dibuka lewat aplikasi Instagram atau Facebook.'
            : 'Bisa juga berasal dari ekstensi browser pengunjung; abaikan bila tidak ada keluhan.';

        return [
            'judul' => $jenis . ' ' . $dari . ($diblokir ? ' diblokir' : ' terdeteksi'),
            'deskripsi' => implode(' ', $kalimat),
        ];
    }

    /** Nama halaman CMS dari nama route (mis. "ic.admin.acara.tampil" → "CMS Inner Circle › Acara"). Null bila tidak dikenal. */
    public static function halamanDariRoute(string $nama): ?string
    {
        return self::namaHalaman($nama);
    }

    private static function namaHalaman(string $nama): ?string
    {
        if (isset(self::HALAMAN[$nama])) {
            return self::HALAMAN[$nama];
        }

        // CMS Inner Circle: ic.admin.<tab>... -> "CMS Inner Circle › <nama tab>"
        if (str_starts_with($nama, 'ic.admin.')) {
            $tab = explode('.', $nama)[2] ?? '';
            $label = config('inner_circle.tab_admin.' . $tab . '.label') ?? ($tab === 'log' ? 'Log' : ucfirst($tab));

            return 'CMS Inner Circle › ' . $label;
        }

        // Area anggota Inner Circle yang tidak terdaftar di atas.
        if (str_starts_with($nama, 'ic.')) {
            return 'Area anggota Inner Circle';
        }

        // CMS Artis: awalan route = jenis data ("albums.tambah" -> Album) atau Log.
        $awal = explode('.', $nama)[0];
        if ($awal === 'log') {
            return 'CMS Artis › Log';
        }
        $objek = config('log_admin.modul.' . $awal . '.objek');

        return is_string($objek) ? 'CMS Artis › ' . $objek : null;
    }

    private static function menulis(?string $metode): bool
    {
        return in_array($metode, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}