<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aturan check-in WS Inner Circle (semua dicek di server):
 * anggota login, acara bisa check-in (bukan privat, ada venue), di dalam jendela waktu,
 * token cocok, dan belum pernah check-in di acara itu.
 *
 * Token menentukan acaranya. Kode acara dari halaman hanya petunjuk: kalau token cocok dengan
 * acara lain yang sedang dibuka, check-in dicatat ke acara milik token itu.
 */
class CheckinService
{
    public function __construct(private StempelService $stempel) {}

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
     */
    public function checkin(member $anggota, string $masukanKode, ?string $kodeAcara = null, ?Carbon $sekarang = null): ic_stempel
    {
        $sekarang ??= now();

        $token = self::normalisasiToken($masukanKode);
        if ($token === null) {
            throw new CheckinGagal('Kode check-in tidak valid. Periksa lagi kode dari panitia atau pindai ulang QR.');
        }

        $event = $this->tentukanEvent($token, $kodeAcara, $sekarang);

        try {
            return DB::transaction(function () use ($anggota, $event) {
                // Kunci baris anggota supaya dua permintaan bersamaan diproses bergantian.
                $terkunci = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();

                $sudah = ic_stempel::query()
                    ->where('member_id', $terkunci->getKey())
                    ->where('event_id', $event->getKey())
                    ->exists();
                if ($sudah) {
                    throw new CheckinGagal('Kamu sudah check-in di acara ini. Satu acara hanya memberi satu stempel.');
                }

                $tipe = config('inner_circle.tipe_event.' . $event->tipe);

                return $this->stempel->tambah(
                    $terkunci,
                    1,
                    'checkin',
                    'Check-in di ' . $event->venue,
                    $tipe['ikon'] ?? 'ticket',
                    $event,
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw new CheckinGagal('Kamu sudah check-in di acara ini. Satu acara hanya memberi satu stempel.');
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
