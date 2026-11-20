<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\ic_poin;
use App\Models\member;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aturan check-in WS Inner Circle (semua dicek di server):
 * anggota login, acara bisa check-in (bukan privat, ada venue, ada titik lokasi), di dalam jendela waktu,
 * berada di dalam radius, dan belum pernah check-in di acara itu.
 *
 * Dua cara: `checkin` (pemindaian QR / kode, token harus cocok; untuk semua anggota, termasuk pemenang GL) dan
 * `checkinMandiri` (tombol, tanpa token; DITUTUP untuk pemenang GL supaya poin tidak dobel). Poin ditentukan status GL,
 * bukan cara check-in: pemenang GL mendapat poin GL, yang lain poin mandiri.
 *
 * Token menentukan acaranya. Kode acara dari halaman hanya petunjuk: kalau token cocok dengan
 * acara lain yang sedang dibuka, check-in dicatat ke acara milik token itu.
 */
class CheckinService
{
    public function __construct(private PoinService $poin, private LokasiService $lokasi) {}

    /**
     * Ambil token WS-XXXXXXXXXXXX dari teks bebas (kode ketikan, URL hasil pindai QR, atau hasil tempel).
     * Kode 12 karakter tanpa awalan "WS-" juga diterima. Mengembalikan null bila bentuknya tidak cocok.
     */
    public static function normalisasiToken(mixed $masukan): ?string
    {
        if (! is_string($masukan)) {
            return null;
        }

        if (preg_match('/WS-[A-Z0-9]{12}/i', $masukan, $cocok)) {
            return strtoupper($cocok[0]);
        }

        $polos = preg_replace('/[\s\-]+/', '', $masukan);
        if (preg_match('/^[A-Z0-9]{12}$/i', (string) $polos)) {
            return 'WS-' . strtoupper($polos);
        }

        return null;
    }

    /**
     * Semua acara yang bisa check-in dan jendelanya sedang terbuka. Kandidat dipersempit dulu lewat SQL
     * (acara bertanggal hari ini atau kemarin, plus acara dengan jendela khusus), lalu jendelanya dicek di PHP.
     *
     * @return Collection<int, ic_event>
     */
    public function eventTerbuka(?Carbon $sekarang = null): Collection
    {
        $sekarang ??= now();

        return ic_event::query()
            ->where(function ($q) use ($sekarang) {
                $q->where(fn($t) => $t
                    ->whereDate('tanggal', '>=', $sekarang->copy()->subDay()->toDateString())
                    ->whereDate('tanggal', '<=', $sekarang->copy()->toDateString()))
                    ->orWhereNotNull('jendela_mulai')
                    ->orWhereNotNull('jendela_selesai');
            })
            ->get()
            ->filter(fn(ic_event $e) => $e->bisa_checkin && $e->dalamJendela($sekarang))
            ->values();
    }

    /**
     * Acara yang jendela check-in-nya sedang terbuka dan tokennya cocok. Token terenkripsi sehingga
     * tidak bisa dicari lewat SQL: tokennya dibandingkan di PHP terhadap acara yang sedang terbuka.
     */
    public function cariEventDariToken(string $token, ?Carbon $sekarang = null): ?ic_event
    {
        return $this->eventTerbuka($sekarang)
            ->first(fn(ic_event $e) => hash_equals((string) $e->token, $token));
    }

    /**
     * Catat check-in. Melempar CheckinGagal (pesan siap tampil) bila ada aturan yang tidak terpenuhi.
     *
     * @param  string|null  $kodeAcara  kode acara yang dipilih anggota di halaman (WS26-1008), opsional
     * @param  array{lat?: mixed, lng?: mixed, akurasi?: mixed}|null  $lokasi  posisi HP anggota; wajib bila acara punya titik lokasi
     */
    public function checkin(member $anggota, string $masukanKode, ?string $kodeAcara = null, ?Carbon $sekarang = null, ?array $lokasi = null): ic_poin
    {
        $sekarang ??= now();

        $token = self::normalisasiToken($masukanKode);
        if ($token === null) {
            throw new CheckinGagal('Kode check-in tidak valid. Periksa lagi kode dari panitia atau pindai ulang QR.');
        }

        $event = $this->tentukanEvent($token, $kodeAcara, $sekarang);

        return $this->catat($anggota, $event, $lokasi);
    }

    /**
     * Check-in mandiri (tombol, tanpa QR). Ditutup untuk pemenang GL gratis di acara itu: kehadiran mereka dicatat lewat
     * pemindaian QR (poin GL), jadi tombol ini tidak boleh memberi poin kedua kali.
     *
     * @param  array{lat?: mixed, lng?: mixed, akurasi?: mixed}|null  $lokasi
     *
     * @throws CheckinGagal
     */
    public function checkinMandiri(member $anggota, string $kodeAcara, ?Carbon $sekarang = null, ?array $lokasi = null): ic_poin
    {
        $sekarang ??= now();

        $event = ic_event::query()->where('kode', $kodeAcara)->first();
        if (! $event) {
            throw new CheckinGagal('Acara tidak ditemukan.');
        }

        $this->pastikanBisaCheckin($event, $sekarang);

        if ($this->pemenangGl($anggota, $event)) {
            throw new CheckinGagal('Kamu terpilih GL gratis di acara ini, jadi check-in dilakukan dengan memindai QR di pintu. Tombol check-in mandiri ditutup supaya poinmu tidak dobel.');
        }

        return $this->catat($anggota, $event, $lokasi);
    }

    /** Anggota ini pemenang GL gratis di acara itu (status menang di antrean). */
    public function pemenangGl(member $anggota, ic_event $event): bool
    {
        return ic_gl_antrean::query()
            ->where('event_id', $event->getKey())
            ->where('member_id', $anggota->getKey())
            ->where('status', ic_gl_antrean::MENANG)
            ->exists();
    }

    /** Poin untuk satu kehadiran: nilai milik acara bila diisi, kalau tidak bawaan dari Aturan check-in. */
    public function poinUntuk(ic_event $event, bool $pemenangGl): int
    {
        $aturan = app(IcAturan::class);

        return $pemenangGl
            ? (int) ($event->poin_gl ?? $aturan->poinGl())
            : (int) ($event->poin_mandiri ?? $aturan->poinMandiri());
    }

    /** Periksa lokasi lalu tulis satu kehadiran (satu per anggota per acara, dijaga kunci baris dan kunci unik). */
    private function catat(member $anggota, ic_event $event, ?array $lokasi): ic_poin
    {
        // HP harus di dalam radius titik acara. Yang dicatat hanya jarak dan akurasi, bukan koordinat anggota.
        $ukuran = $this->lokasi->periksa($event, $lokasi);

        try {
            return DB::transaction(function () use ($anggota, $event, $ukuran) {
                // Kunci baris anggota supaya dua permintaan bersamaan diproses bergantian.
                $terkunci = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();

                $sudah = ic_poin::query()
                    ->where('member_id', $terkunci->getKey())
                    ->where('event_id', $event->getKey())
                    ->exists();
                if ($sudah) {
                    throw new CheckinGagal('Kamu sudah check-in di acara ini. Satu acara hanya memberi poin satu kali.');
                }

                $tipe = app(IcPilihan::class)->tipeEvent()[$event->tipe] ?? null;

                return $this->poin->tambah(
                    $terkunci,
                    $this->poinUntuk($event, $this->pemenangGl($terkunci, $event)),
                    'checkin',
                    'Check-in di ' . $event->venue,
                    $tipe['ikon'] ?? 'ticket',
                    $event,
                    jarakM: $ukuran['jarak'] ?? null,
                    akurasiM: $ukuran['akurasi'] ?? null,
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw new CheckinGagal('Kamu sudah check-in di acara ini. Satu acara hanya memberi poin satu kali.');
        } finally {
            // Instance anggota dari guard masih membawa angka lama.
            $anggota->refresh();
        }
    }

    private function tentukanEvent(string $token, ?string $kodeAcara, Carbon $sekarang): ic_event
    {
        $pilihan = filled($kodeAcara) ? ic_event::query()->where('kode', $kodeAcara)->first() : null;

        // Token cocok dengan acara yang dipilih: periksa aturan acara itu satu per satu.
        if ($pilihan && hash_equals((string) $pilihan->token, $token)) {
            $this->pastikanBisaCheckin($pilihan, $sekarang);

            return $pilihan;
        }

        // Token milik acara lain yang sedang dibuka (mis. anggota memindai QR tanpa memilih acara dulu).
        $dariToken = $this->cariEventDariToken($token, $sekarang);
        if ($dariToken) {
            return $dariToken;
        }

        if ($pilihan) {
            $this->pastikanBisaCheckin($pilihan, $sekarang);

            throw new CheckinGagal('Kode tidak cocok dengan acara ini. Pastikan memakai QR terbaru dari panitia di lokasi.');
        }

        throw new CheckinGagal('QR atau kode ini tidak cocok dengan acara yang sedang dibuka untuk check-in. Mungkin sudah diganti panitia atau acaranya belum dimulai.');
    }

    private function pastikanBisaCheckin(ic_event $event, Carbon $sekarang): void
    {
        if ($event->tipe === 'private') {
            throw new CheckinGagal('Acara privat tidak bisa dipakai untuk check-in.');
        }

        if (blank($event->venue)) {
            throw new CheckinGagal('Venue acara ini belum diumumkan, jadi check-in belum dibuka.');
        }

        if ($sekarang->lessThan($event->jendela_buka)) {
            throw new CheckinGagal(
                'Check-in belum dibuka. Dibuka ' . $event->jendela_buka->locale('id')->translatedFormat('D, j M Y \p\u\k\u\l H.i') . ' WIB.'
            );
        }

        if ($sekarang->greaterThan($event->jendela_tutup)) {
            throw new CheckinGagal('Check-in untuk acara ini sudah ditutup.');
        }
    }
}