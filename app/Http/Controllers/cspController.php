<?php

namespace App\Http\Controllers;

use App\Services\CspLaporanService;
use App\Support\KebijakanCsp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Penampung laporan pelanggaran Content-Security-Policy dari browser (report-uri). Publik, tanpa sesi dan tanpa CSRF
 * (browser mengirimnya sendiri), dibatasi per IP, dan SELALU membalas 204 tanpa isi supaya tidak membocorkan apa pun.
 */
class cspController extends Controller
{
    /** Laporan asli hanya beberapa ratus byte; yang lebih besar dari ini bukan dari browser. */
    private const BATAS_BYTE = 10000;

    public function laporan(Request $request, CspLaporanService $layanan): Response
    {
        $mode = (string) config('keamanan.csp.mode');
        if (! in_array($mode, [KebijakanCsp::MODE_LAPOR, KebijakanCsp::MODE_PAKSA], true)) {
            return response()->noContent();
        }

        $isi = (string) $request->getContent();
        if ($isi === '' || strlen($isi) > self::BATAS_BYTE) {
            return response()->noContent();
        }

        $data = json_decode($isi, true);
        if (! is_array($data)) {
            return response()->noContent();
        }

        foreach ($this->daftarLaporan($data) as $laporan) {
            $layanan->catat($laporan, $request->ip());
        }

        return response()->noContent();
    }

    /**
     * Dua bentuk laporan: report-uri lama {"csp-report": {...}} dan Reporting API [{"type":"csp-violation","body":{...}}].
     *
     * @return array<int, array<string, mixed>>
     */
    private function daftarLaporan(array $data): array
    {
        if (isset($data['csp-report']) && is_array($data['csp-report'])) {
            return [$data['csp-report']];
        }

        $hasil = [];
        foreach (array_slice($data, 0, 10) as $butir) {
            if (! is_array($butir) || ($butir['type'] ?? '') !== 'csp-violation' || ! is_array($butir['body'] ?? null)) {
                continue;
            }

            $b = $butir['body'];
            $hasil[] = [
                'document-uri' => $b['documentURL'] ?? '',
                'effective-directive' => $b['effectiveDirective'] ?? '',
                'violated-directive' => $b['violatedDirective'] ?? '',
                'blocked-uri' => $b['blockedURL'] ?? '',
                'source-file' => $b['sourceFile'] ?? '',
                'line-number' => $b['lineNumber'] ?? 0,
                'disposition' => $b['disposition'] ?? 'report',
            ];
        }

        return $hasil;
    }
}