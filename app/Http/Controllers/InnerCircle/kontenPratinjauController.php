<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Controllers\homeController;
use App\Models\member;
use App\Services\InnerCircle\IcKonten;
use App\Services\InnerCircle\PratinjauPotongan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

/**
 * Pratinjau langsung di samping form tab Konten (CMS Inner Circle).
 *
 * Merender halaman ASLI (controller dan view yang sama dengan yang dilihat anggota) dengan dua perbedaan:
 * - teks yang tampil adalah isian form yang belum disimpan (IcKonten::pakaiDraf), bukan isi database;
 * - anggotanya contoh (tidak tersimpan, tidak pernah ditulis ke database), jadi layar anggota bisa ditampilkan
 *   tanpa login anggota dan tanpa efek samping (mis. menandai Komunitas dibaca).
 * Hasilnya HTML yang dimuat panel iframe di CMS. Tautan dan kirim form di dalamnya dimatikan.
 *
 * Dua mode:
 * - tanpa `fokus`: halaman utuh (perilaku lama);
 * - dengan `fokus` (kunci teks yang sedang diedit, titik diganti `__`): PRATINJAU POTONGAN, hanya bagian halaman yang
 *   memuat teks itu (lihat PratinjauPotongan). Bila halaman belum dipilih, semua halaman grup dicoba berurutan sampai
 *   teksnya ditemukan. Isian kosong tampil sebagai teks bawaan, jadi potongan selalu punya isi contoh.
 */
class kontenPratinjauController extends Controller
{
    public function tampil(Request $request, IcKonten $konten): Response
    {
        $data = Validator::make($request->all(), [
            'grup' => ['required', 'string', 'regex:/^[a-z_]+$/'],
            'halaman' => ['nullable', 'string', 'regex:/^[a-z_]+$/'],
            'fokus' => ['nullable', 'string', 'regex:/^[a-z0-9_]+$/'],
            'k' => ['nullable', 'array'],
        ])->validate();

        // Bagian Tampilan bukan grup teks biasa: halaman pratinjaunya ada di ic_konten.tampilan.
        $halamanGrup = $data['grup'] === 'tampilan'
            ? config('ic_konten.tampilan.pratinjau')
            : config('ic_konten.grup.' . $data['grup'] . '.pratinjau');
        abort_unless(is_array($halamanGrup) && $halamanGrup !== [], 404);

        $fokus = $data['fokus'] ?? null;
        $kunciFokus = $fokus !== null ? str_replace('__', '.', $fokus) : null;
        abort_unless($kunciFokus === null || $konten->definisiKunci($kunciFokus) !== null || str_starts_with($kunciFokus, 'tampilan.'), 404);

        $halaman = $data['halaman'] ?? array_key_first($halamanGrup);
        abort_unless(array_key_exists($halaman, $halamanGrup), 404);

        $jangkar = (string) config('ic_konten.grup.' . $data['grup'] . '.jangkar', '');
        $konten->pakaiDraf($data['k'] ?? []);

        try {
            if ($kunciFokus === null) {
                $html = $this->matikanInteraksi($this->render($halaman), $jangkar);
                $catatan = '';
            } else {
                // Teks tampilan/merek (nama program, warna) tidak punya penanda: tampilkan bagian atas halaman contoh.
                $tanpaPenanda = str_starts_with($kunciFokus, 'tampilan.');
                $konten->pakaiPenanda(! $tanpaPenanda);

                // Halaman yang dicoba: yang dipilih dulu; bila tidak dipilih dari form (kosong), semua halaman grup berurutan.
                $urutan = isset($data['halaman']) && $data['halaman'] !== ''
                    ? [$halaman]
                    : array_keys($halamanGrup);

                $potong = ['html' => '', 'catatan' => PratinjauPotongan::CATATAN_ATAS];
                foreach ($urutan as $kandidat) {
                    $hasil = $tanpaPenanda
                        ? ['html' => PratinjauPotongan::potongAtas($this->render($kandidat)), 'catatan' => '']
                        : PratinjauPotongan::potong($this->render($kandidat), $kunciFokus);

                    if ($potong['html'] === '' || $hasil['catatan'] === '') {
                        $potong = $hasil;
                    }
                    if ($hasil['catatan'] === '') {
                        break;
                    }
                }

                $html = $this->tambahGayaPotongan($potong['html']);
                $catatan = $potong['catatan'];
            }
        } finally {
            $konten->lupakanDraf();
        }

        return response($html, 200, array_filter([
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'X-Pratinjau-Catatan' => rawurlencode($catatan),
        ], fn($v) => $v !== ''));
    }

    private function render(string $halaman): string
    {
        // Halaman tanpa login anggota.
        $halamanTamu = match ($halaman) {
            'tentang' => fn() => app(tentangController::class)->index(),
            'teaser' => fn() => app(homeController::class)->index(),
            'masuk' => fn() => view('pages.inner-circle.masuk'),
            'daftar' => fn() => view('pages.inner-circle.daftar'),
            'lupa' => fn() => view('pages.inner-circle.lupa-sandi'),
            'atur' => fn() => view('pages.inner-circle.atur-ulang', ['token' => 'contoh', 'email' => '']),
            default => null,
        };

        if ($halamanTamu !== null) {
            return $halamanTamu()->render();
        }

        // Layar anggota: pakai anggota contoh dan jadikan route aslinya "aktif" (untuk sorotan menu dock).
        $rute = [
            'beranda' => 'ic.beranda',
            'hadiah' => 'ic.hadiah',
            'checkin' => 'ic.checkin',
            'komunitas' => 'ic.komunitas',
            'galeri' => 'ic.komunitas',
            'profil' => 'ic.profil',
        ][$halaman];

        $guard = Auth::guard('member');
        $guard->setUser($this->anggotaContoh());
        $asli = request()->getRouteResolver();
        request()->setRouteResolver(fn() => Route::getRoutes()->getByName($rute));

        try {
            $kontrol = app(memberController::class);
            $tampilan = match ($halaman) {
                'beranda' => app()->call([$kontrol, 'beranda']),
                'hadiah' => app()->call([$kontrol, 'hadiah']),
                'checkin' => app()->call([$kontrol, 'checkin'], ['request' => Request::create('/', 'GET')]),
                'komunitas' => app()->call([$kontrol, 'komunitas'], ['request' => Request::create('/', 'GET', ['tab' => 'pengumuman'])]),
                'galeri' => app()->call([$kontrol, 'komunitas'], ['request' => Request::create('/', 'GET', ['tab' => 'galeri'])]),
                'profil' => app()->call([$kontrol, 'profil']),
            };

            return $tampilan->render();
        } finally {
            $guard->forgetUser();
            request()->setRouteResolver($asli);
        }
    }

    /** Anggota contoh: tidak ada di database (id 0), email terverifikasi, bergabung setahun lalu, 10 stempel. */
    private function anggotaContoh(): member
    {
        $anggota = new member(['nama' => 'Anggota Contoh', 'email' => 'anggota@contoh.id']);
        $anggota->forceFill([
            'id_member' => 0,
            'kode_member' => 'IC-00000',
            'stempel_total' => 10,
            'email_verified_at' => now(),
            'created_at' => now()->subYear(),
        ]);

        return $anggota;
    }

    /** Pratinjau hanya untuk dilihat: tautan tidak berpindah halaman, form tidak terkirim, dan (bila ada) gulir ke bagian yang diedit. */
    private function matikanInteraksi(string $html, string $jangkar): string
    {
        $skrip = '<script>(function(){'
            . 'document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest("a");if(a){e.preventDefault();}},true);'
            . 'document.addEventListener("submit",function(e){e.preventDefault();},true);'
            . ($jangkar !== '' ? 'window.addEventListener("load",function(){var el=document.getElementById(' . json_encode($jangkar) . ');if(el){el.scrollIntoView();}});' : '')
            . '})();</script>';

        $posisi = strripos($html, '</body>');

        return $posisi === false ? $html . $skrip : substr($html, 0, $posisi) . $skrip . substr($html, $posisi);
    }

    /**
     * Gaya dan skrip untuk potongan: tinggi mengikuti isi (bukan tinggi layar), bagian yang diedit diberi garis putus-putus,
     * tautan dan form dimatikan, dan tinggi akhir dikirim ke halaman CMS supaya iframe-nya pas.
     */
    private function tambahGayaPotongan(string $html): string
    {
        $gaya = '<style>html,body{min-height:0!important;height:auto!important;overflow:hidden!important}'
            . 'body{margin:0!important}[data-ic-sorot]{outline:2px dashed #8d73ff;outline-offset:4px;border-radius:6px}</style>';
        $skrip = '<script>(function(){'
            . 'document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest("a");if(a){e.preventDefault();}},true);'
            . 'document.addEventListener("submit",function(e){e.preventDefault();},true);'
            . 'function kirim(){parent.postMessage({icPratinjauTinggi:document.documentElement.scrollHeight},"*");}'
            . 'window.addEventListener("load",function(){kirim();setTimeout(kirim,300);setTimeout(kirim,1200);});'
            . 'if(window.ResizeObserver){new ResizeObserver(kirim).observe(document.body);}'
            . '})();</script>';

        $kepala = strripos($html, '</head>');
        if ($kepala !== false) {
            $html = substr($html, 0, $kepala) . $gaya . substr($html, $kepala);
        } else {
            $html = $gaya . $html;
        }

        $badan = strripos($html, '</body>');

        return $badan === false ? $html . $skrip : substr($html, 0, $badan) . $skrip . substr($html, $badan);
    }
}