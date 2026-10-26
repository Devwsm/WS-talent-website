<?php

namespace App\Services\InnerCircle;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Masuk dengan Google (OAuth 2.0 authorization code + PKCE, OpenID Connect).
 *
 * Tanpa pustaka tambahan: hanya HTTP client bawaan Laravel. Perlindungan yang dipasang:
 * - `state` acak di sesi (cegah CSRF login), `nonce` di id_token (cegah replay), PKCE S256 (cegah pencurian kode).
 * - id_token diterima LANGSUNG dari endpoint token Google lewat HTTPS dengan client secret kita, jadi menurut
 *   dokumentasi Google tanda tangannya tidak perlu diverifikasi ulang; yang tetap diperiksa: iss, aud, exp, nonce.
 * - Token Google tidak disimpan; hanya identitas (sub, email, nama) yang dipakai.
 */
class GoogleOAuth
{
    private const URL_OTORISASI = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const URL_TOKEN = 'https://oauth2.googleapis.com/token';
    private const BATAS_DETIK = 600;

    /** Tombol dan route Google hanya hidup bila flag menyala DAN kredensial terisi. */
    public static function tersedia(): bool
    {
        return (bool) config('inner_circle.google.aktif')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    public function alamatBalik(): string
    {
        return config('services.google.redirect') ?: route('ic.google.callback');
    }

    /** Menyiapkan state, nonce, dan PKCE di sesi, lalu mengembalikan alamat halaman izin Google. */
    public function mulai(Request $request): string
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);

        $request->session()->put('ic_google', [
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'dibuat' => time(),
        ]);

        return self::URL_OTORISASI . '?' . http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->alamatBalik(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Memproses kembalian dari Google dan mengembalikan klaim identitas yang sudah diperiksa.
     *
     * @return array{sub:string,email:string,name:string,email_verified:bool}
     *
     * @throws GoogleGagal
     */
    public function selesai(Request $request): array
    {
        // Data sesi sekali pakai: diambil dan dihapus apa pun hasilnya.
        $sesi = $request->session()->pull('ic_google');

        if ($request->filled('error')) {
            throw new GoogleGagal($request->query('error') === 'access_denied'
                ? 'Masuk dengan Google dibatalkan.'
                : 'Google menolak permintaan masuk. Silakan coba lagi.');
        }

        if (! is_array($sesi) || (time() - (int) ($sesi['dibuat'] ?? 0)) > self::BATAS_DETIK) {
            throw new GoogleGagal('Sesi masuk sudah kedaluwarsa. Silakan coba lagi.');
        }

        if (! hash_equals((string) $sesi['state'], (string) $request->query('state'))) {
            throw new GoogleGagal('Permintaan masuk tidak valid. Silakan coba lagi.');
        }

        $kode = (string) $request->query('code');
        if ($kode === '') {
            throw new GoogleGagal('Google tidak mengirim kode masuk. Silakan coba lagi.');
        }

        try {
            $respons = Http::asForm()->acceptJson()->timeout(10)->post(self::URL_TOKEN, [
                'code' => $kode,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->alamatBalik(),
                'grant_type' => 'authorization_code',
                'code_verifier' => $sesi['verifier'],
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Masuk Google: gagal menghubungi Google', ['pesan' => $e->getMessage()]);
            throw new GoogleGagal('Tidak bisa menghubungi Google saat ini. Silakan coba lagi sebentar lagi.');
        }

        if ($respons->failed()) {
            // Tanpa client secret dan tanpa isi respons lengkap di log.
            Log::warning('Masuk Google: endpoint token menolak', [
                'status' => $respons->status(),
                'error' => $respons->json('error'),
            ]);
            throw new GoogleGagal('Masuk dengan Google gagal. Silakan coba lagi.');
        }

        return $this->periksaIdToken((string) $respons->json('id_token'), (string) $sesi['nonce']);
    }

    /** @return array{sub:string,email:string,name:string,email_verified:bool} */
    private function periksaIdToken(string $jwt, string $nonce): array
    {
        $bagian = explode('.', $jwt);
        $klaim = count($bagian) === 3
            ? json_decode((string) base64_decode(strtr($bagian[1], '-_', '+/')), true)
            : null;

        if (! is_array($klaim)) {
            throw new GoogleGagal('Data dari Google tidak bisa dibaca. Silakan coba lagi.');
        }

        $aud = $klaim['aud'] ?? null;
        $audCocok = is_array($aud)
            ? in_array(config('services.google.client_id'), $aud, true)
            : $aud === config('services.google.client_id');

        $valid = in_array($klaim['iss'] ?? null, ['https://accounts.google.com', 'accounts.google.com'], true)
            && $audCocok
            && (int) ($klaim['exp'] ?? 0) >= time()
            && hash_equals($nonce, (string) ($klaim['nonce'] ?? ''))
            && filled($klaim['sub'] ?? null);

        if (! $valid) {
            Log::warning('Masuk Google: id_token gagal diperiksa (iss/aud/exp/nonce)');
            throw new GoogleGagal('Data dari Google tidak valid. Silakan coba lagi.');
        }

        $diverifikasi = filter_var($klaim['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! filled($klaim['email'] ?? null) || ! $diverifikasi) {
            throw new GoogleGagal('Email akun Google ini belum terverifikasi, jadi tidak bisa dipakai untuk masuk.');
        }

        return [
            'sub' => (string) $klaim['sub'],
            'email' => mb_strtolower(trim((string) $klaim['email'])),
            'name' => trim((string) ($klaim['name'] ?? '')),
            'email_verified' => true,
        ];
    }
}