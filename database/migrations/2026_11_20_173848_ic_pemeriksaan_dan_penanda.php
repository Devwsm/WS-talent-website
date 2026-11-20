<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WS Inner Circle, Fase 5B: pemeriksaan kehadiran dan penanda mencurigakan.
     *
     * Hanya MENAMBAH tabel dan satu kolom kosong, jadi check-in yang sudah tercatat tidak berubah.
     *
     * ic_pemeriksaan: anggota yang GPS-nya gagal (izin ditolak, sinyal lemah, di luar radius) mengajukan pemeriksaan.
     * Admin menyetujui (poin masuk lewat buku besar seperti check-in biasa) atau menolak dengan alasan.
     * Satu pengajuan per anggota per acara. Koordinat anggota TIDAK disimpan: hanya jarak dan akurasi bila server
     * sempat mengukurnya.
     *
     * ic_penanda: tanda yang dipasang sistem pada check-in yang tampak janggal (perangkat sama dipakai beberapa akun,
     * lokasi terlalu sempurna, perjalanan mustahil, banyak percobaan gagal). Check-in TIDAK ditahan; penanda hanya
     * memberi tahu admin untuk ditinjau.
     *
     * ic_poin.sidik_perangkat: hash HMAC dari token acak di cookie browser (bukan IP, bukan sidik jari browser).
     */
    public function up(): void
    {
        Schema::create('ic_pemeriksaan', function (Blueprint $table) {
            $table->id('id_pemeriksaan');
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('ic_events', 'id_event')->cascadeOnDelete();
            $table->string('alasan', 20); // izin_ditolak | tidak_tersedia | sinyal_lemah | akurasi_kasar | di_luar_radius
            $table->unsignedInteger('jarak_m')->nullable();
            $table->unsignedSmallInteger('akurasi_m')->nullable();
            $table->string('catatan_anggota', 300)->nullable();
            $table->string('status', 12)->default('menunggu'); // menunggu | disetujui | ditolak
            $table->string('catatan_admin', 300)->nullable();
            $table->string('ditinjau_oleh', 60)->nullable();
            $table->timestamp('ditinjau_pada')->nullable();
            $table->unsignedBigInteger('poin_id')->nullable();
            $table->string('sidik_perangkat', 64)->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'event_id']);
            $table->index(['status', 'created_at']);
            $table->index('event_id');
        });

        Schema::create('ic_penanda', function (Blueprint $table) {
            $table->id('id_penanda');
            $table->foreignId('member_id')->constrained('members', 'id_member')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('ic_events', 'id_event')->cascadeOnDelete();
            $table->unsignedBigInteger('poin_id')->nullable();
            $table->string('jenis', 24); // perangkat_sama | lokasi_janggal | perjalanan_mustahil | banyak_gagal
            $table->string('detail', 255);
            $table->string('status', 10)->default('baru'); // baru | aman | ditindak
            $table->string('catatan_admin', 200)->nullable();
            $table->string('ditinjau_oleh', 60)->nullable();
            $table->timestamp('ditinjau_pada')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'event_id', 'jenis']);
            $table->index(['status', 'created_at']);
            $table->index('event_id');
        });

        Schema::table('ic_poin', function (Blueprint $table) {
            $table->string('sidik_perangkat', 64)->nullable()->after('akurasi_m');
            $table->index(['event_id', 'sidik_perangkat']);
        });
    }

    public function down(): void
    {
        Schema::table('ic_poin', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'sidik_perangkat']);
            $table->dropColumn('sidik_perangkat');
        });

        Schema::dropIfExists('ic_penanda');
        Schema::dropIfExists('ic_pemeriksaan');
    }
};