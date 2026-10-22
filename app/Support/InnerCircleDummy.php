<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Data contoh WS Inner Circle untuk Fase 1-3 (UI dulu, belum ada database).
 *
 * Bentuknya sengaja sama dengan kolom tabel yang akan dibuat di Fase 4+
 * (objek dengan properti bernama sama), jadi view cukup memakai $x->properti
 * dan tidak perlu diubah saat controller nanti pindah ke Eloquent.
 *
 * Seluruh isi di sini hanya contoh, bukan data anggota atau jadwal asli.
 */
class InnerCircleDummy
{
    public static function member(): object
    {
        return (object) [
            'nama' => 'Ancha MK',
            'kode_member' => 'IC-00248',
            'email' => 'ancha@contoh.test',
            'avatar' => null,
            'avatar_emoji' => '😎',
            'stempel_total' => 68,
        ];
    }

    /** Tahapan hadiah + status untuk anggota contoh (10 sudah diajukan). */
    public static function rewards(): array
    {
        $stempel = self::member()->stempel_total;
        $klaim = [10 => 'diajukan'];

        $data = [
            [10, 'Hadiah Pertama', 'Diskon merchandise resmi + stiker & gantungan kunci', 'gift', 'koral', null],
            [25, 'Akses Eksklusif', 'Akses awal merchandise + edisi khusus', 'cap', 'kuning', null],
            [50, 'Interaksi Personal', 'Panggilan video ATAU ucapan video khusus', 'love-letter', 'biru', ['Panggilan video', 'Ucapan video khusus']],
            [100, 'Di Balik Panggung', 'Akses belakang panggung festival + cek suara + foto Instax bertanda tangan', 'camera', 'lilac', null],
            [250, 'Legenda Inner Circle', 'Tiket pertunjukan khusus + belakang panggung + koleksi eksklusif', 'ticket', 'pink', null],
        ];

        return array_map(fn($r) => (object) [
            'target_stempel' => $r[0],
            'judul' => $r[1],
            'deskripsi' => $r[2],
            'ikon' => $r[3],
            'warna' => $r[4],
            'opsi' => $r[5],
            'status_member' => $klaim[$r[0]] ?? ($stempel >= $r[0] ? 'terbuka' : 'terkunci'),
        ], $data);
    }

    /** Tahapan berikutnya yang belum tercapai (setelah tahap terakhir, tetap 250). */
    public static function berikutnya(): object
    {
        $stempel = self::member()->stempel_total;
        $target = collect(self::rewards())
            ->pluck('target_stempel')
            ->first(fn($n) => $n > $stempel);
        $target ??= 250;

        return (object) [
            'target_stempel' => $target,
            'sisa' => max(0, $target - $stempel),
            'persen' => min(100, (int) round($stempel / $target * 100)),
        ];
    }

    public static function aktivitas(): array
    {
        return array_map(fn($r) => (object) [
            'judul' => $r[0],
            'ikon' => $r[1],
            'jumlah' => 1,
            'tanggal' => Carbon::parse($r[2]),
        ], [
            ['Check-in di acara resmi', 'headphone', '2026-10-08'],
            ['Ikut kegiatan resmi', 'sparkles', '2026-09-22'],
            ['Check-in di acara resmi', 'ticket', '2026-09-15'],
        ]);
    }

    /** Riwayat stempel lengkap untuk halaman Profil (Fase 4: dari tabel ic_stempel). */
    public static function riwayat(): array
    {
        return self::aktivitas();
    }

    /** Pengajuan hadiah milik anggota contoh (hadiah 10 sudah diajukan). */
    public static function klaim(): array
    {
        return [
            (object) [
                'target_stempel' => 10,
                'judul' => 'Hadiah Pertama',
                'opsi' => null,
                'diajukan_pada' => Carbon::parse('2026-10-21'),
                'status' => 'diajukan',
            ],
        ];
    }

    /** Tiga angka ringkasan di halaman Profil. */
    public static function metrik(): object
    {
        $terbuka = collect(self::rewards())
            ->filter(fn($r) => $r->status_member !== 'terkunci')
            ->count();

        return (object) [
            'total_stempel' => self::member()->stempel_total,
            'tahap_terbuka' => $terbuka,
            'jumlah_klaim' => count(self::klaim()),
        ];
    }

    /**
     * Jadwal contoh dari database/data/ic_events_contoh.csv (50 baris, tentatif).
     *
     * @param  array{bulan?: int|string|null, tipe?: string|null, q?: string|null}  $filter
     */
    public static function events(array $filter = []): array
    {
        $baris = self::bacaCsv(database_path('data/ic_events_contoh.csv'));
        $sudahCheckin = ['WS26-1008', 'WS26-1009'];
        $hariIni = Carbon::today();

        $bulan = $filter['bulan'] ?? null;
        $tipe = $filter['tipe'] ?? null;
        $q = mb_strtolower(trim((string) ($filter['q'] ?? '')));

        $hasil = [];
        foreach ($baris as $b) {
            $tanggal = Carbon::parse($b['tanggal'])->startOfDay();
            $venue = $b['venue'] !== '' ? $b['venue'] : null;
            $kota = $b['kota'] !== '' ? $b['kota'] : null;

            if ($bulan && $bulan !== 'all' && (int) $bulan !== $tanggal->month) {
                continue;
            }
            if ($tipe && $tipe !== 'all' && $tipe !== $b['tipe']) {
                continue;
            }
            if ($q !== '' && ! str_contains(mb_strtolower(implode(' ', [$kota, $venue, $b['tipe']])), $q)) {
                continue;
            }

            $hasil[] = (object) [
                'kode' => $b['kode'],
                'tanggal' => $tanggal,
                'kota' => $kota,
                'venue' => $venue,
                'tipe' => $b['tipe'],
                'status_waktu' => $tanggal->isSameDay($hariIni) ? 'hari_ini' : ($tanggal->lt($hariIni) ? 'lewat' : 'akan_datang'),
                'bisa_checkin' => $b['tipe'] !== 'private' && $venue !== null,
                'sudah_checkin' => in_array($b['kode'], $sudahCheckin, true),
            ];
        }

        return $hasil;
    }

    public static function totalEvents(): int
    {
        return count(self::bacaCsv(database_path('data/ic_events_contoh.csv')));
    }

    /** Satu pengumuman yang ditandai unggulan ("Kabar pilihan"). */
    public static function pengumumanUnggulan(): object
    {
        return (object) [
            'judul' => 'Sampai jumpa di acara berikutnya.',
            'isi' => 'Kuartal IV dipenuhi musik seru dan wajah-wajah yang sudah akrab. Lihat jadwal terbaru di Check-in dan tetap pantau pengumuman resmi.',
            'cta_label' => 'Lihat jadwal acara',
            'cta_tujuan' => 'ic.checkin',
        ];
    }

    public static function pengumuman(): array
    {
        return array_map(fn($r) => (object) [
            'tag' => $r[0],
            'terbit_pada' => Carbon::parse($r[1]),
            'judul' => $r[2],
            'isi' => $r[3],
            'cta_label' => $r[4],
            'cta_tujuan' => $r[5],
            'ikon' => $r[6],
            'warna' => $r[7],
        ], [
            ['ACARA • KUARTAL IV 2026', '2026-10-13', 'Sampai jumpa di pertunjukan berikutnya', 'Cek jadwal Oktober hingga Desember di halaman Check-in. Detail akses dan stempel akan diumumkan secara resmi.', 'Lihat jadwal pertunjukan', 'ic.checkin', 'ticket', 'merah'],
            ['MUSIK • PENGUMUMAN', '2026-10-12', 'Ada sesuatu yang manis segera hadir', 'Sugar Rush masuk ke rangkaian Package Album Vol. 3. Save the date: 19 Oktober 2026.', 'Lihat hadiah', 'ic.hadiah', 'cd', 'kuning'],
            ['INNER CIRCLE • HADIAH', '2026-10-10', 'Kesetiaanmu, ceritamu', 'Semakin banyak stempel, semakin dekat dengan benefit Inner Circle. Cek progres dan tahapan yang sudah terbuka.', 'Lihat tahapan hadiah', 'ic.hadiah', 'gift', 'hijau'],
        ]);
    }

    /** Kenangan contoh; $kategori = null untuk semua. */
    public static function kenangan(?string $kategori = null): array
    {
        $data = [
            [1, 'Rani P.', '🌻', 'WS Showcase · Jakarta', 'festival', '2026-10-08', 'Masih kebayang drop terakhirnya! Ketemu teman-teman baru di depan stage. 🪩', 'fireworks', 'kuning', 128],
            [2, 'Dio R.', '😎', 'Blackowl · PIK', 'club', '2026-10-09', 'Set malam itu bikin lupa waktu. More music, same people! 🎧', 'knobs', 'biru', 93],
            [3, 'Maya S.', '🍒', 'Inner Circle moment', 'personal', '2026-10-10', 'Simpan wristband dan tiket ini buat kenangan. See you at the next show! 🎟️', 'ticket', 'pink', 74],
            [4, 'Fikri A.', '⚡', 'Depo Jaya Bangunan Festival · Lombok', 'festival', '2026-10-01', 'Dari Lombok, untuk musik yang bikin kita kumpul bareng. 🤘', 'camera', 'kuning', 162],
        ];

        $hasil = [];
        foreach ($data as $r) {
            if ($kategori && $kategori !== 'all' && $kategori !== $r[4]) {
                continue;
            }
            $hasil[] = (object) [
                'id_memory' => $r[0],
                'nama_member' => $r[1],
                'avatar_emoji' => $r[2],
                'tempat' => $r[3],
                'kategori' => $r[4],
                'tanggal' => Carbon::parse($r[5]),
                'caption' => $r[6],
                'ikon' => $r[7],
                'warna' => $r[8],
                'foto' => null,
                'jumlah_like' => $r[9],
                'disukai_saya' => false,
                'milik_saya' => false,
            ];
        }

        return $hasil;
    }

    // ------------------------------------------------------------------
    // Fase 3: data contoh untuk dashboard CMS (admin). Bentuk objeknya mengikuti kolom tabel
    // Fase 4-6 (members, ic_events, ic_claims, ic_broadcasts, ic_memories), sama seperti di atas.
    // ------------------------------------------------------------------

    /**
     * Daftar anggota contoh untuk tab Anggota, terbaru di atas. Anggota ke-5 (IC-00248) sama
     * dengan anggota contoh di area anggota.
     *
     * @return array<int, object>
     */
    public static function anggota(?string $q = null): array
    {
        $data = [
            [252, 'Bagas Saputra', 'bagas@contoh.test', '🎸', 27, '2026-06-03', '2026-10-09'],
            [251, 'Fikri Alamsyah', 'fikri@contoh.test', '⚡', 31, '2026-06-18', '2026-10-01'],
            [250, 'Maya Sari', 'maya@contoh.test', '🍒', 14, '2026-08-02', '2026-10-10'],
            [249, 'Rizky Hakim', 'rizky@contoh.test', '🎧', 0, '2026-10-20', null],
            [248, 'Ancha MK', 'ancha@contoh.test', '😎', 68, '2026-08-14', '2026-10-08'],
            [247, 'Rani Pratiwi', 'rani@contoh.test', '🌻', 112, '2026-07-02', '2026-10-08'],
            [246, 'Dio Ramadhan', 'dio@contoh.test', '🎤', 134, '2026-04-11', '2026-10-09'],
            [245, 'Sinta Dewi', 'sinta@contoh.test', '🦋', 12, '2026-05-27', '2026-09-22'],
            [244, 'Hendra Wijaya', 'hendra@contoh.test', '🚀', 8, '2026-09-05', '2026-09-15'],
            [243, 'Putri Anindya', 'putri@contoh.test', '🌙', 261, '2026-01-19', '2026-10-09'],
            [242, 'Kevin Tanoto', 'kevin@contoh.test', '🔥', 45, '2026-03-08', '2026-10-02'],
            [241, 'Salsa Maharani', 'salsa@contoh.test', '🍀', 52, '2026-02-14', '2026-10-08'],
        ];

        $kata = mb_strtolower(trim((string) $q));
        $hasil = [];
        foreach ($data as $r) {
            $m = (object) [
                'id_member' => $r[0],
                'kode_member' => 'IC-' . str_pad((string) $r[0], 5, '0', STR_PAD_LEFT),
                'nama' => $r[1],
                'email' => $r[2],
                'avatar' => null,
                'avatar_emoji' => $r[3],
                'stempel_total' => $r[4],
                'bergabung_pada' => Carbon::parse($r[5]),
                'terakhir_checkin' => $r[6] ? Carbon::parse($r[6]) : null,
            ];

            if ($kata !== '' && ! str_contains(mb_strtolower($m->nama . ' ' . $m->email . ' ' . $m->kode_member), $kata)) {
                continue;
            }
            $hasil[] = $m;
        }

        return $hasil;
    }

    /**
     * Pengajuan hadiah dari semua anggota contoh untuk tab Klaim. Yang masih menunggu tindakan
     * ada di atas. Ikon dan warna diambil dari tahapan hadiahnya supaya sama dengan area anggota.
     *
     * @return array<int, object>
     */
    public static function klaimAdmin(bool $hanyaMenunggu = false): array
    {
        $hadiah = collect(self::rewards())->keyBy('target_stempel');
        $data = [
            [1, 248, 10, null, '2026-10-21', 'diajukan', null],
            [2, 252, 25, null, '2026-10-22', 'diajukan', null],
            [3, 247, 50, 'Panggilan video', '2026-10-20', 'ditinjau', null],
            [4, 251, 25, null, '2026-10-19', 'disetujui', 'Kode akses awal akan dikirim lewat email anggota.'],
            [5, 250, 10, null, '2026-10-14', 'selesai', 'Voucher diskon sudah dikirim ke email anggota.'],
            [6, 246, 100, null, '2026-10-12', 'selesai', 'Akses belakang panggung dijadwalkan bersama tim WSM.'],
            [7, 245, 10, null, '2026-10-11', 'ditolak', 'Data anggota belum cocok. Silakan hubungi tim WSM.'],
        ];

        $anggota = collect(self::anggota())->keyBy('id_member');
        $hasil = [];
        foreach ($data as $r) {
            $a = $anggota[$r[1]];
            $h = $hadiah[$r[2]];
            if ($hanyaMenunggu && ! in_array($r[5], ['diajukan', 'ditinjau', 'disetujui'], true)) {
                continue;
            }
            $hasil[] = (object) [
                'id_claim' => $r[0],
                'nama_member' => $a->nama,
                'kode_member' => $a->kode_member,
                'target_stempel' => $r[2],
                'judul' => $h->judul,
                'ikon' => $h->ikon,
                'warna' => $h->warna,
                'opsi_dipilih' => $r[3],
                'diajukan_pada' => Carbon::parse($r[4]),
                'status' => $r[5],
                'catatan_admin' => $r[6],
            ];
        }

        return $hasil;
    }

    /** Angka di tab Ringkasan, dihitung dari data contoh di atas supaya cocok dengan tab lain. */
    public static function ringkasan(): object
    {
        $events = self::events();

        return (object) [
            'anggota_total' => count(self::anggota()),
            'kehadiran_terverifikasi' => array_sum(array_map(fn($m) => $m->stempel_total, self::anggota())),
            'klaim_menunggu' => count(self::klaimAdmin(true)),
            'acara_aktif' => count(array_filter($events, fn($e) => $e->status_waktu === 'hari_ini' && $e->bisa_checkin)),
            'acara_berikutnya' => collect($events)->first(fn($e) => $e->status_waktu !== 'lewat' && $e->bisa_checkin),
        ];
    }

    /**
     * Jadwal untuk tab Acara & QR: sama dengan events() ditambah token QR, jendela check-in
     * (tanggal show pukul 12.00 sampai H+1 pukul 06.00 WIB), dan jumlah check-in contoh.
     *
     * @param  array{bulan?: int|string|null, tipe?: string|null, q?: string|null}  $filter
     * @return array<int, object>
     */
    public static function eventsAdmin(array $filter = []): array
    {
        return array_map(function ($e) {
            $e->token = self::tokenAcara($e->kode);
            $e->jendela_buka = $e->tanggal->copy()->setTime(12, 0);
            $e->jendela_tutup = $e->tanggal->copy()->addDay()->setTime(6, 0);
            $e->jumlah_checkin = ($e->bisa_checkin && $e->status_waktu !== 'akan_datang')
                ? 20 + (crc32($e->kode) % 90)
                : 0;

            return $e;
        }, self::events($filter));
    }

    /** Token QR contoh per acara (Fase 4: dibuat acak di server dan bisa diganti admin). */
    public static function tokenAcara(string $kode): string
    {
        return 'WS-' . strtoupper(substr(md5('ws-ic-' . $kode), 0, 12));
    }

    /**
     * Pengumuman untuk tab Pengumuman: satu unggulan di urutan pertama, lalu pengumuman biasa.
     * Teksnya memakai ulang data area anggota supaya preview CMS sama dengan tampilan anggota.
     *
     * @return array<int, object>
     */
    public static function pengumumanAdmin(): array
    {
        $unggulan = (object) array_merge((array) self::pengumumanUnggulan(), [
            'id_broadcast' => 1,
            'tag' => 'KABAR PILIHAN',
            'ikon' => 'sparkles',
            'warna' => 'kuning',
            'terbit_pada' => Carbon::parse('2026-10-14'),
            'unggulan' => true,
        ]);

        $daftar = [];
        foreach (self::pengumuman() as $i => $p) {
            $p->id_broadcast = $i + 2;
            $p->unggulan = false;
            $daftar[] = $p;
        }

        return array_merge([$unggulan], $daftar);
    }

    /**
     * Kenangan untuk tab moderasi: kenangan() ditambah status tayang dan jumlah laporan.
     *
     * @param  string|null  $status  'tayang', 'disembunyikan', atau null untuk semua
     * @return array<int, object>
     */
    public static function kenanganAdmin(?string $kategori = null, ?string $status = null): array
    {
        $disembunyikan = [3];
        $laporan = [2 => 1, 3 => 3];

        $hasil = [];
        foreach (self::kenangan($kategori) as $k) {
            $k->disembunyikan = in_array($k->id_memory, $disembunyikan, true);
            $k->jumlah_laporan = $laporan[$k->id_memory] ?? 0;

            if ($status === 'tayang' && $k->disembunyikan) {
                continue;
            }
            if ($status === 'disembunyikan' && ! $k->disembunyikan) {
                continue;
            }
            $hasil[] = $k;
        }

        return $hasil;
    }

    /** @return array<int, array<string, string>> */
    private static function bacaCsv(string $path): array
    {
        $baris = [];
        if (! is_file($path) || ($h = fopen($path, 'r')) === false) {
            return $baris;
        }

        $kolom = fgetcsv($h, null, ',', '"', '');
        while ($kolom && ($r = fgetcsv($h, null, ',', '"', '')) !== false) {
            if (count($r) === count($kolom)) {
                $baris[] = array_combine($kolom, $r);
            }
        }
        fclose($h);

        return $baris;
    }
}