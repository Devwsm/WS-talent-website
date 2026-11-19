<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle, aturan baru (Fase 2): fondasi acara untuk antrean GL gratis.
     *
     * Hanya MENAMBAH kolom dan tabel. Semua kolom baru boleh kosong, jadi acara yang sudah ada dan check-in yang
     * sedang berjalan tidak berubah. Data lama tidak disentuh, sehingga tidak perlu cadangan khusus di fase ini
     * (cadangan rutin sebelum push tetap dianjurkan).
     *
     * ic_events:
     * - username_acara / sandi_acara: akun masuk acara yang dibagikan ke pemenang GL. Sandi disimpan TERENKRIPSI
     *   (cast TokenAcara), jadi kolomnya `text`.
     * - kuota_gl: jumlah pemenang GL. Kosong = acara ini tidak punya antrean GL.
     * - undian_pada: waktu undian per acara. Kosong = bawaan (hari H pukul 18.00 WIB, lihat config inner_circle.gl).
     * - status_undian: belum | ditunda | tertunda | selesai. Undian hanya dijalankan admin lewat tombol (tanpa cron).
     * - poin_gl / poin_mandiri: nilai poin per acara bila beda dari bawaan (kosong = bawaan di Aturan check-in).
     * - poster, deskripsi, petunjuk_masuk: tampilan halaman detail acara anggota.
     *
     * ic_gl_antrean: satu baris = satu pendaftaran GL (unik per acara per anggota). Setelah undian, baris yang
     * menang berubah status dan menjadi dasar kartu GL; barisnya tidak dihapus supaya hasil undian bisa diaudit.
     */
    public function up(): void
    {
        Schema::table('ic_events', function (Blueprint $table) {
            $table->string('username_acara', 60)->nullable()->after('lokasi_diatur_pada');
            $table->text('sandi_acara')->nullable()->after('username_acara');
            $table->unsignedSmallInteger('kuota_gl')->nullable()->after('sandi_acara');
            $table->timestamp('undian_pada')->nullable()->after('kuota_gl');
            $table->string('status_undian', 12)->default('belum')->after('undian_pada');
            $table->timestamp('undian_selesai_pada')->nullable()->after('status_undian');
            $table->unsignedInteger('poin_gl')->nullable()->after('undian_selesai_pada');
            $table->unsignedInteger('poin_mandiri')->nullable()->after('poin_gl');
            $table->string('poster', 255)->nullable()->after('poin_mandiri');
            $table->string('deskripsi', 500)->nullable()->after('poster');
            $table->string('petunjuk_masuk', 300)->nullable()->after('deskripsi');
        });

        Schema::create('ic_gl_antrean', function (Blueprint $table) {
            $table->id('id_antrean');
            $table->foreignId('event_id')->constrained('ic_events', 'id_event')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->string('status', 16)->default('antri'); // antri | menang | tidak_terpilih
            $table->unsignedSmallInteger('urutan_undi')->nullable();
            $table->timestamp('kartu_dikirim_pada')->nullable();
            $table->timestamp('diterima_show_pada')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'member_id']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ic_gl_antrean');

        Schema::table('ic_events', function (Blueprint $table) {
            $table->dropColumn([
                'username_acara',
                'sandi_acara',
                'kuota_gl',
                'undian_pada',
                'status_undian',
                'undian_selesai_pada',
                'poin_gl',
                'poin_mandiri',
                'poster',
                'deskripsi',
                'petunjuk_masuk',
            ]);
        });
    }
};