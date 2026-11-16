<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CMS Artis: urutan tampil dan jadwal tayang.
     *
     * - `urutan` (album, banner, header, merchandise): posisi di halaman publik, dari 1. Diisi dari urutan lama
     *   (kunci utama naik), yaitu urutan yang selama ini dipakai halaman publik, jadi tampilan situs TIDAK berubah
     *   sampai admin menggeser sesuatu.
     * - `terbit_pada` (berita dan album): kapan mulai tampil di halaman publik. Kosong = langsung tampil, jadi semua
     *   data lama tetap tampil seperti biasa.
     */
    private const TABEL_URUTAN = [
        'albums' => 'id_albums',
        'banners' => 'id_banner',
        'headers' => 'id_header',
        'merchandise' => 'id_merchandise',
    ];

    public function up(): void
    {
        foreach (self::TABEL_URUTAN as $tabel => $kunci) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->unsignedInteger('urutan')->default(0)->after('created_at');
            });

            $nomor = 0;
            foreach (DB::table($tabel)->orderBy($kunci)->pluck($kunci) as $id) {
                DB::table($tabel)->where($kunci, $id)->update(['urutan' => ++$nomor]);
            }
        }

        foreach (['news', 'albums'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dateTime('terbit_pada')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['news', 'albums'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn('terbit_pada');
            });
        }

        foreach (array_keys(self::TABEL_URUTAN) as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn('urutan');
            });
        }
    }
};