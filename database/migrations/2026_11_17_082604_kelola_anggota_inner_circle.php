<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CMS Inner Circle: kelola anggota.
     *
     * - `members.diblokir_pada` (+ alasan dan nama admin): akun dinonaktifkan oleh admin. Kosong = akun aktif, jadi semua
     *   anggota yang ada tetap aktif. Alasan hanya catatan untuk admin, tidak ditampilkan ke anggota.
     * - `ic_stempel.jumlah` jadi bertanda: koreksi admin ditulis sebagai baris baru bernilai negatif (sumber `koreksi`),
     *   bukan dengan mengubah atau menghapus baris lama, supaya buku besar tetap hanya-ditambah dan bisa diaudit.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('diblokir_pada')->nullable()->after('sesi_versi');
            $table->string('alasan_blokir', 160)->nullable()->after('diblokir_pada');
            $table->string('diblokir_oleh', 60)->nullable()->after('alasan_blokir');
        });

        Schema::table('ic_stempel', function (Blueprint $table) {
            $table->smallInteger('jumlah')->default(1)->change();
        });
    }

    /** Baris koreksi (negatif) dihapus dulu: kolom tak bertanda tidak bisa menyimpannya. Total di `members` dikembalikan. */
    public function down(): void
    {
        $negatif = \Illuminate\Support\Facades\DB::table('ic_stempel')->where('jumlah', '<', 0);
        foreach ((clone $negatif)->get(['member_id', 'jumlah']) as $baris) {
            \Illuminate\Support\Facades\DB::table('members')->where('id_member', $baris->member_id)->increment('stempel_total', -$baris->jumlah);
        }
        $negatif->delete();

        Schema::table('ic_stempel', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah')->default(1)->change();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['diblokir_pada', 'alasan_blokir', 'diblokir_oleh']);
        });
    }
};