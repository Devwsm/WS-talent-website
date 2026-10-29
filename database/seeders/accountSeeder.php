<?php

namespace Database\Seeders;

use App\Models\account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Membuat akun admin CMS pertama.
 *
 * Kredensial TIDAK lagi ditulis di kode (sebelumnya username dan sandi ada di repo, jadi siapa pun yang
 * pernah melihat repo bisa masuk). Isi ADMIN_USERNAME dan ADMIN_PASSWORD di .env sebelum `db:seed`
 * (dibaca lewat config('site.admin_username') dan config('site.admin_password')).
 * - Produksi: wajib diisi, kalau kosong seeder berhenti dengan pesan galat.
 * - Lokal/testing: kalau kosong, dibuat akun `admin` dengan sandi acak yang dicetak sekali di terminal.
 * ADMIN_EMAIL (opsional) = tujuan kode OTP login. Idempoten: bila username sudah ada, akun dibiarkan (sandi lama tidak ditimpa;
 * email hanya diisi bila masih kosong).
 */
class accountSeeder extends Seeder
{
    public function run(): void
    {
        $username = trim((string) config('site.admin_username'));
        $password = (string) config('site.admin_password');

        if ($username === '' || $password === '') {
            if (app()->environment('production')) {
                $this->command?->error('ADMIN_USERNAME dan ADMIN_PASSWORD wajib diisi di .env sebelum db:seed di produksi.');

                return;
            }

            $username = $username !== '' ? $username : 'admin';
            $password = Str::password(16, symbols: false);
            $this->command?->warn("Akun admin lokal: {$username} / {$password} (dibuat acak, catat sekarang).");
        } elseif (strlen($password) < 12) {
            $this->command?->error('ADMIN_PASSWORD minimal 12 karakter.');

            return;
        }

        $email = trim((string) config('site.admin_email'));

        $akun = account::firstOrCreate(
            ['username' => $username],
            ['password' => Hash::make($password), 'email' => $email !== '' ? $email : null]
        );

        // Akun yang sudah ada tetap utuh, kecuali emailnya masih kosong: diisi dari ADMIN_EMAIL (untuk kode OTP login).
        if (! $akun->wasRecentlyCreated && $email !== '' && blank($akun->email)) {
            $akun->update(['email' => $email]);
            $this->command?->info("Email admin '{$username}' diisi dari ADMIN_EMAIL.");
        }

        $this->command?->info($akun->wasRecentlyCreated
            ? "Akun admin '{$username}' dibuat."
            : "Akun admin '{$username}' sudah ada, tidak diubah.");
    }
}