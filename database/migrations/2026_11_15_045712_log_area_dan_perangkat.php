<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const IC = 'inner-circle';

    /**
     * Log dipisah per CMS dan lebih ramah dibaca:
     * - log_errors.area: catatan error milik CMS Artis atau CMS Inner Circle (dari alamat halamannya).
     * - log_aktivitas.perangkat: ringkasan perangkat admin ("HP Android · Chrome"); log_aktivitas.jumlah: penghitung
     *   login gagal yang digabung.
     * Catatan lama dirapikan: login berhasil/logout/kode dikirim dihapus (kini tidak dicatat), dan IP dihapus dari
     * catatan yang tidak berisiko (kini IP hanya disimpan untuk login gagal).
     */
    public function up(): void
    {
        Schema::table('log_errors', function (Blueprint $table) {
            $table->string('area', 12)->default('artis')->after('fingerprint')->index();
        });

        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->string('perangkat', 120)->nullable()->after('rute');
            $table->unsignedInteger('jumlah')->default(1)->after('status');
        });

        // Error lama milik Inner Circle dipindahkan ke area-nya, dengan sidik jari baru (rumus sama dengan AreaLog::sidikJari).
        DB::table('log_errors')->chunkById(200, function ($baris) {
            foreach ($baris as $b) {
                if (! $this->milikInnerCircle((string) $b->url, (string) $b->lokasi)) {
                    continue;
                }

                if (str_starts_with((string) $b->kelas, 'CSP:')) {
                    $dasar = 'csp|' . substr((string) $b->kelas, 4) . '|' . (strtok((string) $b->lokasi, ' ') ?: 'tidak-diketahui');
                } else {
                    $dasar = $b->kelas . '|' . $b->lokasi;
                }

                DB::table('log_errors')->where('id_log_error', $b->id_log_error)->update([
                    'area' => self::IC,
                    'fingerprint' => sha1($dasar . '|' . self::IC),
                ]);
            }
        }, 'id_log_error');

        DB::table('log_aktivitas')->where('kategori', 'akses')->where('status', 'berhasil')->delete();
        DB::table('log_aktivitas')->where('kategori', '!=', 'akses')->whereNotNull('ip')->update(['ip' => null]);
    }

    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropColumn(['perangkat', 'jumlah']);
        });

        Schema::table('log_errors', function (Blueprint $table) {
            $table->dropIndex(['area']);
            $table->dropColumn('area');
        });
    }

    /** Sama dengan AreaLog::dariUrl (disalin agar migrasi tetap berjalan walau kode aplikasi berubah kelak). */
    private function milikInnerCircle(string $url, string $lokasi): bool
    {
        if ($url === 'artisan') {
            return str_contains($lokasi, 'InnerCircle') || str_contains($lokasi, 'Bandsintown');
        }

        $jalur = '/' . trim(strtolower((string) parse_url($url, PHP_URL_PATH)), '/');

        foreach (['/innercircle', '/inner-circle', '/dashboard/inner-circle'] as $awalan) {
            if ($jalur === $awalan || str_starts_with($jalur, $awalan . '/')) {
                return true;
            }
        }

        return false;
    }
};