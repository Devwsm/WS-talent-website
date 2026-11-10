<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Cache data halaman publik (Home, Profile, footer, warna tema, sitemap).
 *
 * Kenapa ada: tiap kunjungan ke Home dan Profile dulu menjalankan belasan query ke database, padahal isinya hanya
 * berubah saat staf menyimpan sesuatu di CMS. Sekarang hasilnya disimpan sementara, dan otomatis dibuang begitu ada
 * data publik yang disimpan atau dihapus lewat model (lihat daftar model di AppServiceProvider).
 *
 * Yang disimpan hanya data polos (teks dan angka), BUKAN objek model: config/cache.php melarang objek PHP dipulihkan
 * dari cache (serializable_classes = false), jadi model yang disimpan mentah kembali sebagai objek rusak. Pakai
 * polos() sebelum menyimpan dan daftar()/satu() sesudah membaca; hasilnya model biasa tanpa query tambahan.
 *
 * Aman bila cache bermasalah: kalau cache tidak bisa dibaca atau ditulis (mis. tabel cache hilang), data diambil
 * langsung dari database seperti sebelumnya, jadi halaman publik tidak pernah mati karena cache.
 * Batas umur (TTL) 1 jam menjadi jaring pengaman bila data diubah langsung di database (mis. lewat phpMyAdmin).
 */
class CachePublik
{
    private const AWALAN = 'publik:';

    /** Batas umur cache dalam detik. */
    private const DETIK = 3600;

    /** Semua kunci yang dibuang saat data publik berubah. */
    public const KUNCI = ['home', 'profile', 'footer', 'tema', 'sitemap'];

    /**
     * Ambil dari cache; bila belum ada, jalankan $isi lalu simpan. $isi tidak boleh mengembalikan null.
     *
     * @template T
     *
     * @param  Closure(): T  $isi
     * @return T
     */
    public static function ingat(string $kunci, Closure $isi): mixed
    {
        try {
            $ada = Cache::get(self::AWALAN . $kunci);
        } catch (Throwable) {
            return $isi();
        }

        if ($ada !== null) {
            return $ada;
        }

        $nilai = $isi();

        try {
            Cache::put(self::AWALAN . $kunci, $nilai, self::DETIK);
        } catch (Throwable) {
            // Gagal menyimpan tidak boleh mengganggu halaman.
        }

        return $nilai;
    }

    /** Hasil query (daftar model, satu model, atau null) menjadi data polos yang aman disimpan di cache. */
    public static function polos(Collection|Model|null $hasil): ?array
    {
        if ($hasil === null) {
            return null;
        }

        return $hasil instanceof Model
            ? $hasil->getAttributes()
            : $hasil->map(fn(Model $m) => $m->getAttributes())->all();
    }

    /**
     * Bentuk ulang daftar model dari data polos (tanpa query ke database).
     *
     * @param  class-string<Model>  $model
     */
    public static function daftar(string $model, array $polos): Collection
    {
        return $model::hydrate($polos);
    }

    /**
     * Bentuk ulang satu model dari data polos, atau null bila memang tidak ada.
     *
     * @param  class-string<Model>  $model
     */
    public static function satu(string $model, ?array $polos): ?Model
    {
        return $polos === null ? null : $model::hydrate([$polos])->first();
    }

    /** Buang semua cache halaman publik. Tidak pernah melempar galat. */
    public static function lupakan(): void
    {
        foreach (self::KUNCI as $kunci) {
            try {
                Cache::forget(self::AWALAN . $kunci);
            } catch (Throwable) {
                // Cache bermasalah: TTL yang akan menyelesaikannya.
            }
        }
    }
}