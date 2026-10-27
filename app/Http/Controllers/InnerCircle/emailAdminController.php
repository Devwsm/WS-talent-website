<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\kirimEmailTesRequest;
use App\Models\ic_claim;
use App\Services\InnerCircle\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Tab Email di dashboard Inner Circle: lihat format tiap email, cek pengaturan pengiriman, dan kirim email tes.
 */
class emailAdminController extends Controller
{
    public function index(Request $request)
    {
        $jenis = $request->query('jenis');
        $jenis = is_string($jenis) && array_key_exists($jenis, EmailService::JENIS) ? $jenis : array_key_first(EmailService::JENIS);

        $mailer = (string) config('mail.default');

        return view('pages.dashboard-pages.inner-circle.email', [
            'daftar' => EmailService::JENIS,
            'jenis' => $jenis,
            'mailer' => $mailer,
            // `log` dan `array` tidak mengirim email sungguhan: isinya hanya ditulis ke storage/logs.
            'emailNyata' => ! in_array($mailer, ['log', 'array'], true),
            'dariAlamat' => (string) config('mail.from.address'),
            'dariNama' => (string) config('mail.from.name'),
            'jumlahKlaimMenunggu' => ic_claim::query()->whereIn('status', ic_claim::statusMenunggu())->count(),
        ]);
    }

    /** HTML satu email contoh, dimuat di iframe (sandbox) pada tab Email. */
    public function pratinjau(string $jenis)
    {
        abort_unless(array_key_exists($jenis, EmailService::JENIS), 404);

        return response(EmailService::contoh($jenis)->render())
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store')
            ->header('Content-Security-Policy', "sandbox; default-src 'none'; style-src 'unsafe-inline'");
    }

    public function tes(kirimEmailTesRequest $request, EmailService $email)
    {
        $jenis = $request->validated('jenis');
        $tujuan = $request->validated('tujuan');

        try {
            $email->kirimTes($jenis, $tujuan);
        } catch (Throwable $e) {
            Log::warning('Email tes gagal dikirim', ['pesan' => $e->getMessage()]);

            return redirect()->route('ic.admin.email', ['jenis' => $jenis])
                ->withInput()
                ->with('error', 'Email tes gagal dikirim: ' . Str::limit($e->getMessage(), 200) . ' Periksa pengaturan MAIL_ di .env (lihat README, bagian Email).');
        }

        $label = EmailService::JENIS[$jenis]['label'];

        return redirect()->route('ic.admin.email', ['jenis' => $jenis])->with('success', in_array(config('mail.default'), ['log', 'array'], true)
            ? "Email tes \"{$label}\" ditulis ke log (MAIL_MAILER=" . config('mail.default') . "), belum dikirim ke kotak masuk. Ganti ke smtp di .env untuk kirim sungguhan."
            : "Email tes \"{$label}\" dikirim ke {$tujuan}. Cek kotak masuk (dan folder spam).");
    }
}