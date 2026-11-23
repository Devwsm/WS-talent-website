<?php

namespace App\Services\InnerCircle;

use App\Mail\InnerCircle\AkunGoogleMail;
use App\Mail\InnerCircle\KartuGlMail;
use App\Mail\InnerCircle\PesanIc;
use App\Mail\InnerCircle\ResetSandiMail;
use App\Mail\InnerCircle\SelamatDatangMail;
use App\Mail\InnerCircle\StatusKlaimMail;
use App\Mail\InnerCircle\StatusPengaduanMail;
use App\Mail\InnerCircle\VerifikasiEmailMail;
use App\Models\ic_claim;
use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\ic_pengaduan;
use App\Models\member;
use App\Services\AnggaranEmail;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Semua email WS Inner Circle lewat sini: kapan dikirim, ke siapa, dan contoh isinya untuk pratinjau/tes di dashboard.
 *
 * Kegagalan kirim email TIDAK PERNAH menggagalkan aksi pengguna (daftar, minta tautan, proses klaim): hanya dicatat
 * di log. Satu-satunya yang melempar galat adalah kirimTes(), supaya admin melihat alasannya.
 */
class EmailService
{
    /** Daftar jenis email: kunci dipakai di URL dashboard (?jenis=) dan formulir tes. */
    public const JENIS = [
        'reset' => [
            'label' => 'Atur ulang kata sandi',
            'ikon' => 'bi-key-fill',
            'kapan' => 'Dikirim saat anggota meminta tautan di halaman Lupa kata sandi (akun yang punya kata sandi). Tautan berlaku 60 menit.',
        ],
        'google' => [
            'label' => 'Akun masuk lewat Google',
            'ikon' => 'bi-google',
            'kapan' => 'Dikirim sebagai ganti tautan atur ulang bila akun itu dibuat lewat Google dan tidak punya kata sandi. Isinya mengarahkan anggota masuk dengan Google.',
        ],
        'selamat-datang' => [
            'label' => 'Selamat datang',
            'ikon' => 'bi-stars',
            'kapan' => 'Dikirim sekali saat anggota baru mendaftar lewat Google (emailnya sudah terverifikasi Google). Pendaftaran dengan kata sandi tidak mengirim email ini.',
        ],
        'verifikasi' => [
            'label' => 'Verifikasi email',
            'ikon' => 'bi-patch-check-fill',
            'kapan' => 'Dikirim saat anggota mendaftar dengan kata sandi, dan saat ia menekan "Kirim ulang email verifikasi" di halaman Hadiah. Tautan berlaku 24 jam dan harus dibuka saat sudah masuk. Hadiah baru bisa diajukan setelah email terverifikasi (akun Google sudah otomatis terverifikasi).',
        ],
        'klaim' => [
            'label' => 'Status hadiah berubah',
            'ikon' => 'bi-gift-fill',
            'kapan' => 'Dikirim ke anggota saat admin melanjutkan atau menolak penukaran hadiahnya di tab Penukaran. Saat ditolak, email menyebut poin yang dikembalikan. Mengubah catatan saja tidak mengirim email; anggota yang membatalkan sendiri juga tidak dikirimi email.',
        ],
        'pengaduan' => [
            'label' => 'Status pengaduan berubah',
            'ikon' => 'bi-chat-left-text-fill',
            'kapan' => 'Dikirim ke anggota saat admin mengubah status pengaduannya (Diproses atau Selesai) dari detail anggota. Catatan admin ikut dikirim. Mengubah catatan saja tidak mengirim email.',
        ],
    ];

    /** Contoh email untuk pratinjau dan tes (data palsu, tidak menyentuh database). */
    public static function contoh(string $jenis): PesanIc
    {
        return match ($jenis) {
            'reset' => new ResetSandiMail('Contoh Anggota', url('/innercircle/atur-ulang/contoh-token'), self::menitBerlakuReset()),
            'google' => new AkunGoogleMail('Contoh Anggota', route('ic.masuk'), GoogleOAuth::tersedia()),
            'selamat-datang' => new SelamatDatangMail('Contoh Anggota', 'IC-00001', route('ic.beranda')),
            'verifikasi' => new VerifikasiEmailMail('Contoh Anggota', url('/innercircle/verifikasi/1/contoh'), self::jamBerlakuVerifikasi()),
            'klaim' => new StatusKlaimMail('Contoh Anggota', 'Merch Eksklusif', 'ditolak', 'Ditolak', 'Stok tahap ini sudah habis, hubungi tim WSM untuk alternatif.', route('ic.hadiah'), 250000),
            'pengaduan' => new StatusPengaduanMail('Contoh Anggota', 'Hadiah dan penukaran', 'selesai', 'Selesai', 'Sudah kami periksa dan poinmu sudah kembali.', route('ic.pengaduan')),
        };
    }

    /** Kirim email contoh ke alamat tujuan. Galat sengaja dilempar ke pemanggil. */
    public function kirimTes(string $jenis, string $tujuan): void
    {
        Mail::to($tujuan)->send(self::contoh($jenis)->sebagaiTes());
    }

    /**
     * Permintaan "lupa kata sandi" untuk $alamat. Tidak melaporkan apa pun ke pemanggil (anti-penebakan email):
     * email yang tidak terdaftar hanya tidak menghasilkan apa-apa. Dijalankan lewat tunda() oleh controller.
     */
    public function kirimLupaSandi(string $alamat): void
    {
        $anggota = member::query()->where('email', $alamat)->first();

        if (! $anggota) {
            return;
        }

        // Akun yang hanya punya Google tidak punya sandi untuk diatur ulang: arahkan masuk dengan Google.
        if ($anggota->password === null && $anggota->google_id !== null) {
            $this->kirimAkunGoogle($anggota);

            return;
        }

        Password::broker('members')->sendResetLink(['email' => $alamat]);
    }

    /** Dipanggil member::sendPasswordResetNotification() oleh broker kata sandi. */
    public function kirimResetSandi(member $anggota, string $token): void
    {
        $url = route('ic.sandi.atur', ['token' => $token, 'email' => $anggota->email]);

        Mail::to($anggota->email)->send(new ResetSandiMail($anggota->nama, $url, self::menitBerlakuReset()));
    }

    public function kirimAkunGoogle(member $anggota): void
    {
        $google = GoogleOAuth::tersedia();

        Mail::to($anggota->email)->send(new AkunGoogleMail(
            $anggota->nama,
            $google ? route('ic.google.masuk') : route('ic.masuk'),
            $google,
        ));
    }

    /** Sambutan hanya saat akun Google dibuat (satu kali per anggota). Berprioritas rendah: ditahan bila anggaran email menipis. */
    public function kirimSelamatDatang(member $anggota): void
    {
        if (! AnggaranEmail::izinkan(AnggaranEmail::RENDAH, 'selamat-datang')) {
            return;
        }

        $this->tunda(fn() => Mail::to($anggota->email)->send(new SelamatDatangMail(
            $anggota->nama,
            (string) $anggota->kode_member,
            route('ic.beranda'),
        )));
    }

    /**
     * Kirim tautan verifikasi email ke anggota yang mendaftar dengan kata sandi (juga saat ia meminta kirim ulang).
     *
     * Mengembalikan false bila batas harian seluruh situs sudah habis: pendaftaran massal tidak boleh menghabiskan
     * kuota email hosting atau membanjiri kotak masuk orang lain. Gagal kirim setelahnya hanya dicatat di log.
     */
    public function kirimVerifikasi(member $anggota): bool
    {
        $kunci = 'ic-verifikasi-global';

        if (RateLimiter::tooManyAttempts($kunci, (int) config('inner_circle.batas_verifikasi_global_per_hari', 200))) {
            return false;
        }
        RateLimiter::hit($kunci, 86400);

        // Alamat dibuat sekarang (bukan di dalam tunda) supaya memakai alamat situs dari permintaan yang sedang berjalan.
        $url = self::urlVerifikasi($anggota);

        $this->tunda(fn() => Mail::to($anggota->email)->send(new VerifikasiEmailMail(
            $anggota->nama,
            $url,
            self::jamBerlakuVerifikasi(),
        )));

        return true;
    }

    /**
     * Tautan verifikasi: bertanda tangan dan kedaluwarsa. `hash` mengikatnya ke alamat email saat ini.
     * Tautan hanya bisa diselesaikan oleh anggota yang SEDANG MASUK ke akun itu (lihat authController::verifikasiEmail),
     * jadi orang yang mendaftarkan email orang lain tidak bisa mengesahkannya lewat email korban.
     */
    public static function urlVerifikasi(member $anggota): string
    {
        return URL::temporarySignedRoute(
            'ic.verifikasi',
            now()->addHours(self::jamBerlakuVerifikasi()),
            ['id' => $anggota->getKey(), 'hash' => sha1(mb_strtolower((string) $anggota->email))],
        );
    }

    public function kirimStatusKlaim(ic_claim $klaim): void
    {
        $klaim->loadMissing(['member', 'reward']);

        if (! $klaim->member || ! $klaim->reward) {
            return;
        }

        if (! AnggaranEmail::izinkan(AnggaranEmail::BIASA, 'status-klaim')) {
            return;
        }

        $this->tunda(fn() => Mail::to($klaim->member->email)->send(new StatusKlaimMail(
            $klaim->member->nama,
            $klaim->reward->judul,
            $klaim->status,
            (string) config('inner_circle.status_klaim.' . $klaim->status . '.label', $klaim->status),
            $klaim->catatan_admin,
            route('ic.hadiah'),
            $klaim->status === 'ditolak' ? (int) $klaim->poin_ditahan : 0,
        )));
    }

    /** Kabari anggota saat status pengaduannya berubah. Gagal kirim hanya dicatat di log (tunda). */
    public function kirimStatusPengaduan(ic_pengaduan $pengaduan): void
    {
        $pengaduan->loadMissing('member');

        if (! $pengaduan->member) {
            return;
        }

        if (! AnggaranEmail::izinkan(AnggaranEmail::BIASA, 'status-pengaduan')) {
            return;
        }

        $this->tunda(fn() => Mail::to($pengaduan->member->email)->send(new StatusPengaduanMail(
            $pengaduan->member->nama,
            $pengaduan->labelKategori(),
            $pengaduan->status,
            $pengaduan->labelStatus(),
            $pengaduan->catatan_admin,
            route('ic.pengaduan'),
        )));
    }

    /**
     * Kabari pemenang GL bahwa kartunya siap. Hanya sekali per pemenang (kartu_dikirim_pada), jadi mengulang
     * undian susulan atau memanggil dua kali tidak menggandakan email. Sandi acara tidak dikirim (lihat KartuGlMail).
     */
    public function kirimKartuGl(ic_gl_antrean $baris, ic_event $event): void
    {
        $baris->loadMissing('anggota');

        if ($baris->kartu_dikirim_pada !== null || ! $baris->anggota) {
            return;
        }

        // Ditahan saat anggaran email menipis; kirimSisaKartuGl() mengirimnya otomatis setelah anggaran longgar lagi.
        if (! AnggaranEmail::izinkan(AnggaranEmail::BIASA, 'kartu-gl')) {
            return;
        }

        $this->tunda(function () use ($baris, $event) {
            Mail::to($baris->anggota->email)->send(new KartuGlMail(
                $baris->anggota->nama,
                GlService::namaAcara($event),
                $event->tanggal->locale('id')->translatedFormat('l, j F Y'),
                route('ic.beranda'),
            ));

            $baris->forceFill(['kartu_dikirim_pada' => now()])->save();
        });
    }

    /**
     * Kirim Kartu GL yang belum terkirim, paling banyak $maks per panggilan (bawaan kartu_per_menit): sisa pemenang di
     * luar batch pertama, kartu yang ditahan anggaran email, dan yang gagal kirim. Dipanggil jadwal ic:kartu-gl-sisa tiap
     * menit, jadi undian besar mengalir bertahap dan tidak menghabiskan jatah email sehari sekaligus.
     *
     * Hanya pemenang dari undian dalam kartu_jendela_hari terakhir. Satu kartu dicoba paling banyak kartu_coba_maks
     * kali; setelah itu dilaporkan ke halaman Log (sekali) dan dilepas, supaya alamat yang rusak tidak diulang selamanya.
     *
     * @return int jumlah kartu yang dicoba kirim pada panggilan ini
     */
    public function kirimSisaKartuGl(?int $maks = null): int
    {
        $maks ??= (int) config('inner_circle.gl.kartu_per_menit', 30);
        $cobaMaks = (int) config('inner_circle.gl.kartu_coba_maks', 5);
        $sejak = now()->subDays((int) config('inner_circle.gl.kartu_jendela_hari', 3));

        $daftar = ic_gl_antrean::query()
            ->with(['anggota', 'event'])
            ->where('status', ic_gl_antrean::MENANG)
            ->whereNull('kartu_dikirim_pada')
            ->whereHas('event', fn($q) => $q->where('undian_selesai_pada', '>=', $sejak))
            ->orderBy('id_antrean')
            ->limit(200)
            ->get();

        $dicoba = 0;

        foreach ($daftar as $baris) {
            if ($dicoba >= $maks) {
                break;
            }
            if (! $baris->event || ! $baris->anggota) {
                continue;
            }

            $kunci = 'kartu-gl-coba:' . $baris->getKey();
            $sudah = (int) Cache::get($kunci, 0);

            if ($sudah >= $cobaMaks) {
                if (Cache::add('kartu-gl-lapor:' . $baris->getKey(), 1, now()->addDays(3))) {
                    report(new JadwalGagal("Kartu GL gagal terkirim setelah {$cobaMaks} percobaan (antrean #{$baris->getKey()}, acara {$baris->event->kode}). Cek alamat email anggota di CMS."));
                }

                continue;
            }

            if (! AnggaranEmail::izinkan(AnggaranEmail::BIASA, 'kartu-gl')) {
                break;
            }

            Cache::put($kunci, $sudah + 1, now()->addDays(3));
            $this->kirimKartuGl($baris, $baris->event);
            $dicoba++;
        }

        return $dicoba;
    }

    /**
     * Jalankan pengiriman SETELAH respons terkirim ke browser (di web), supaya halaman tidak menunggu server email
     * dan waktu respons tidak membedakan email terdaftar dari yang tidak. Di konsol (artisan, tes) langsung jalan.
     * Galat apa pun hanya dicatat di log.
     */
    public function tunda(Closure $kerja): void
    {
        $jalankan = function () use ($kerja) {
            try {
                $kerja();
            } catch (Throwable $e) {
                Log::warning('Email Inner Circle gagal dikirim', ['pesan' => $e->getMessage()]);
            }
        };

        app()->runningInConsole() ? $jalankan() : app()->terminating($jalankan);
    }

    public static function jamBerlakuVerifikasi(): int
    {
        return max(1, (int) config('inner_circle.verifikasi_jam', 24));
    }

    private static function menitBerlakuReset(): int
    {
        return (int) config('auth.passwords.members.expire', 60);
    }
}