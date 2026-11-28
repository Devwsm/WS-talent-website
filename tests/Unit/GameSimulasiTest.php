<?php

namespace Tests\Unit;

use App\Services\InnerCircle\GameSimulasi;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Mesin game server harus menghasilkan angka yang SAMA PERSIS dengan mesin browser (resources/js/game-mambo-sim.js).
 * Angka emas di bawah dibuat dari berkas JS itu (`ulang(seed, log, langkah)` di Node). Bila tes ini gagal setelah
 * mengubah salah satu mesin, ubah KEDUANYA lalu buat ulang angkanya.
 */
class GameSimulasiTest extends TestCase
{
    /** @return array<string, array{int, list<int>, int, array<string,mixed>}> */
    public static function angkaEmas(): array
    {
        return [
            'diam saja, 5 nanas lolos' => [777, [], 5000, ['skor' => 10, 'level' => 1, 'nyawa' => 0, 'langkah' => 938, 'nanas' => 1, 'bir' => 0, 'lewat' => 5, 'selesai' => true]],
            'geser lalu panah' => [12345, [0, 1002, 30, 1000], 3000, ['skor' => 0, 'level' => 1, 'nyawa' => 0, 'langkah' => 978, 'nanas' => 0, 'bir' => 0, 'lewat' => 5, 'selesai' => true]],
            'seed besar campuran input' => [4000000000, [10, 160, 50, 1001, 5, 1002, 40, 1001, 20, 100], 2500, ['skor' => 0, 'level' => 1, 'nyawa' => 0, 'langkah' => 815, 'nanas' => 0, 'bir' => 0, 'lewat' => 5, 'selesai' => true]],
        ];
    }

    #[DataProvider('angkaEmas')]
    public function test_hasil_sama_dengan_mesin_browser(int $seed, array $log, int $langkah, array $harapan): void
    {
        $this->assertSame($harapan, GameSimulasi::ulang($seed, $log, $langkah));
    }

    public function test_ronde_sama_selalu_menghasilkan_hasil_sama(): void
    {
        $log = [5, 100, 20, 250, 20, 1002, 30, 1001];
        $this->assertSame(GameSimulasi::ulang(99, $log, 4000), GameSimulasi::ulang(99, $log, 4000));
    }

    public function test_langkah_dihentikan_sesuai_batas_yang_diminta(): void
    {
        $hasil = GameSimulasi::ulang(777, [], 100);

        $this->assertFalse($hasil['selesai']);
        $this->assertSame(100, $hasil['langkah']);
        $this->assertSame(5, $hasil['nyawa']);
    }
}
