<?php

namespace App\Services\InnerCircle;

use App\Http\Controllers\InnerCircle\memberController;
use App\Http\Controllers\InnerCircle\tentangController;
use App\Models\member;
use App\Support\IcReplika;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

/**
 * Pratinjau potongan halaman ASLI untuk tab Konten (CMS Inner Circle).
 *
 * Masalah replika tulisan tangan: susunannya bisa menyimpang dari halaman yang dilihat anggota, sehingga admin bingung
 * "ini tampil di mana?". Kelas ini menghilangkan penyimpangan itu dengan memakai view yang SAMA dengan halaman anggota:
 *
 *  1. Halaman asli dirender SEKALI di server (bukan iframe, tanpa permintaan per ketukan) dengan anggota contoh yang
 *     tidak pernah ditulis ke database. Tiap teks konten diberi penanda tak terlihat (lihat IcKonten::pakaiPenanda).
 *  2. Penanda diubah menjadi <span data-replika-teks="kolom" data-bawaan="...">, yaitu kait yang sama dengan yang dipakai
 *     skrip pratinjau di halaman Konten, sehingga teks tetap berubah langsung saat admin mengetik.
 *  3. Hanya bagian halaman yang memuat isian modul yang dipertahankan, lengkap dengan pembungkus aslinya (supaya CSS
 *     tetap kena); saudara-saudaranya dibuang.
 *
 * Isian yang tidak tampil di halaman contoh (mis. teks "belum ada acara" saat acara ada) dilaporkan lewat `hilang`,
 * supaya tampilan bisa menampilkannya sebagai replika kecil dan tidak ada isian yang diam-diam tidak berpengaruh.
 */
class IcPotongan
{
    public const AWAL = "\u{E000}";

    public const AKHIR = "\u{E001}";

    public const TUTUP = "\u{E002}";

    /** Elemen yang layak jadi "satu potongan": cukup besar untuk dimengerti, cukup kecil untuk tidak memuat seluruh halaman. */
    private const BLOK = ['section', 'header', 'footer', 'article', 'aside', 'form', 'li', 'dialog'];

    /** Teks contoh untuk {nama} (nama depan anggota contoh). */
    public const NAMA_CONTOH = 'Anggota';

    /** @var array<string, string> halaman => HTML bagian <body> yang sudah berisi span kait */
    private array $halamanMemo = [];

    public function __construct(private readonly IcKonten $konten) {}

    /**
     * Potongan tiap modul sebuah grup teks.
     *
     * @param  array<string, array<string, mixed>>  $modul  keluaran IcBagian::modul()
     * @return array<string, array{html: string, halaman: string, hilang: list<string>}> kunci = slug modul; modul tanpa potongan tidak ada
     */
    public function untukGrup(string $kodeGrup, array $modul): array
    {
        $halamanGrup = (array) config("ic_konten.grup.$kodeGrup.pratinjau", []);
        // Teaser tampil di beranda situs artis (tema, font, dan CSS lain), jadi tetap memakai replika.
        if ($halamanGrup === [] || $kodeGrup === 'teaser') {
            return [];
        }

        $hasil = [];
        foreach ($modul as $slug => $m) {
            $kunci = (array) ($m['kunci'] ?? []);
            if ($kunci === []) {
                continue;
            }

            // Halaman yang namanya sama dengan modul dicoba dulu (mis. modul "daftar" di grup masuk).
            $urutan = array_keys($halamanGrup);
            usort($urutan, fn($a, $b) => ($b === $slug) <=> ($a === $slug));

            foreach ($urutan as $halaman) {
                $potong = $this->potong($this->halaman($halaman), $kunci);
                if ($potong !== null) {
                    $hasil[$slug] = $potong + ['halaman' => (string) $halamanGrup[$halaman]];
                    break;
                }
            }
        }

        return $hasil;
    }

    /** Kait span untuk satu halaman: dirender sekali per objek (satu permintaan CMS). */
    private function halaman(string $halaman): string
    {
        if (isset($this->halamanMemo[$halaman])) {
            return $this->halamanMemo[$halaman];
        }

        // Pesan galat form CMS yang sedang dibuka tidak boleh bocor ke formulir di halaman contoh.
        $galatLama = View::shared('errors');
        View::share('errors', new ViewErrorBag);
        $this->konten->pakaiPenanda(true);
        try {
            $html = $this->render($halaman);
        } catch (\Throwable $e) {
            report($e);
            $html = '';
        } finally {
            $this->konten->pakaiPenanda(false);
            View::share('errors', $galatLama ?? new ViewErrorBag);
        }

        return $this->halamanMemo[$halaman] = $this->jadikanKait($html);
    }

    /**
     * Ambil isi <body>, buang penanda di dalam atribut, lalu ubah pasangan penanda menjadi span kait.
     * Penanda yang masih tersisa (mis. di luar <body>) dibuang.
     */
    public function jadikanKait(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*)<\/body>/si', $html, $cocok) === 1) {
            $html = $cocok[1];
        }

        // Penanda di dalam tag (atribut seperti aria-label atau value) tidak boleh menjadi markup.
        $html = preg_replace_callback('/<[^>]*>/u', fn($m) => self::bersihkan($m[0]), $html) ?? $html;

        $program = function_exists('ic_program') ? ic_program() : '';
        $peta = $this->peta($program);

        $html = preg_replace_callback(
            '/\x{E000}([a-z0-9_.]+)\x{E001}(.*?)\x{E002}/su',
            function ($m) use ($peta) {
                $def = $this->konten->definisiKunci($m[1]);
                $bawaan = $def !== null ? IcReplika::ganti((string) $def['bawaan'], $peta) : '';

                return '<span data-replika-teks="' . e(str_replace('.', '__', $m[1])) . '" data-bawaan="' . e($bawaan) . '">' . $m[2] . '</span>';
            },
            $html,
        ) ?? $html;

        return self::bersihkan($html);
    }

    /**
     * Peta placeholder => teks pratinjau, sama dengan yang dikirim ke skrip pratinjau (data-ganti).
     *
     * @return array<string, string>
     */
    public function peta(string $program): array
    {
        return IcReplika::petaGanti($program, (array) config('ic_konten.tautan', [])) + ['{nama}' => self::NAMA_CONTOH];
    }

    /** Buang semua penanda dari teks. */
    public static function bersihkan(string $html): string
    {
        return preg_replace('/\x{E000}[a-z0-9_.]*\x{E001}|\x{E002}/u', '', $html) ?? $html;
    }

    /**
     * Potong $html (isi body dengan span kait) menjadi bagian yang memuat kunci-kunci modul.
     *
     * @param  list<string>  $kunci
     * @return array{html: string, hilang: list<string>}|null null bila tak satu pun kunci tampil di halaman ini
     */
    public function potong(string $html, array $kunci): ?array
    {
        if ($html === '' || ! str_contains($html, 'data-replika-teks')) {
            return null;
        }

        $dok = $this->muat($html);
        $akar = $dok->getElementById('ic-akar-potongan');
        if (! $akar instanceof DOMElement) {
            return null;
        }
        $xpath = new DOMXPath($dok);

        // Skrip, token CSRF, dan atribut yang bisa bentrok dengan halaman CMS (id ganda, autofocus) dibuang.
        foreach (iterator_to_array($xpath->query('//script | //noscript | //input[@type="hidden"]') ?: []) as $buang) {
            $buang->parentNode?->removeChild($buang);
        }
        foreach (iterator_to_array($xpath->query('//*[@autofocus or @aria-labelledby or @aria-controls or @for or (@id and @id != "ic-akar-potongan")]') ?: []) as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }
            foreach (['autofocus', 'aria-labelledby', 'aria-controls', 'for', 'id'] as $atribut) {
                $el->removeAttribute($atribut);
            }
        }

        $simpan = [];
        $hilang = [];
        foreach ($kunci as $k) {
            $kolom = str_replace('.', '__', $k);
            $simpul = $xpath->query('//*[@data-replika-teks="' . $kolom . '"]');
            $pertama = $simpul !== false ? $simpul->item(0) : null;
            if ($pertama === null) {
                $hilang[] = $k;

                continue;
            }
            foreach ($this->cariBlok($pertama, $akar) as $blok) {
                $simpan[spl_object_id($blok)] = $blok;
            }
        }

        if ($simpan === []) {
            return null;
        }

        foreach ($simpan as $blok) {
            $blok->setAttribute('data-ic-simpan', '1');
            for ($induk = $blok->parentNode; $induk instanceof DOMElement && $induk !== $akar; $induk = $induk->parentNode) {
                if (! $induk->hasAttribute('data-ic-simpan')) {
                    $induk->setAttribute('data-ic-leluhur', '1');
                }
            }
        }
        $this->pangkas($akar);

        $keluar = '';
        foreach ($akar->childNodes as $anak) {
            $keluar .= $dok->saveHTML($anak);
        }
        $keluar = preg_replace('/ data-ic-(?:simpan|leluhur)="1"/', '', $keluar) ?? $keluar;

        return ['html' => self::bersihkan($keluar), 'hilang' => $hilang];
    }

    private function muat(string $html): DOMDocument
    {
        $dok = new DOMDocument('1.0', 'UTF-8');
        $dulu = libxml_use_internal_errors(true);
        $dok->loadHTML('<?xml encoding="UTF-8"><div id="ic-akar-potongan">' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($dulu);

        return $dok;
    }

    /**
     * Blok yang dipertahankan untuk sebuah simpul: pembungkus kenal terdekat (section, header, form, li, ...). Judul bagian
     * (div.ic-bagian-kepala) dibawa bersama elemen tepat sesudahnya (daftar langkah, ubin hadiah, FAQ, ...), supaya admin
     * melihat judul itu berdiri di atas apa.
     *
     * @return list<DOMElement>
     */
    private function cariBlok(DOMNode $dari, DOMElement $akar): array
    {
        $atas = null;
        for ($node = $dari; $node instanceof DOMElement && $node !== $akar; $node = $node->parentNode) {
            if (in_array(strtolower($node->nodeName), self::BLOK, true)) {
                return [$node];
            }
            if (in_array('ic-bagian-kepala', preg_split('/\s+/', $node->getAttribute('class')) ?: [], true)) {
                $hasil = [$node];
                for ($s = $node->nextSibling; $s !== null; $s = $s->nextSibling) {
                    if (! $s instanceof DOMElement) {
                        continue;
                    }
                    if (! in_array('ic-bagian-kepala', preg_split('/\s+/', $s->getAttribute('class')) ?: [], true)) {
                        $hasil[] = $s;
                    }
                    break;
                }

                return $hasil;
            }
            // Tidak ada pembungkus yang dikenal: anak langsung <main> yang memuat teks ini dipakai (yang terdalam menang).
            $induk = $node->parentNode;
            if ($induk instanceof DOMElement && strtolower($induk->nodeName) === 'main') {
                return $this->denganJudulHalaman($node);
            }
            if ($induk === $akar && $atas === null) {
                $atas = $node;
            }
        }

        return $atas instanceof DOMElement ? [$atas] : [];
    }

    /**
     * Teks yang berdiri sendiri di <main> (eyebrow atau penjelasan) dibawa bersama judul halaman (<h1>) di sebelahnya,
     * supaya admin tahu itu penjelasan halaman mana.
     *
     * @return list<DOMElement>
     */
    private function denganJudulHalaman(DOMElement $node): array
    {
        $hasil = [$node];
        foreach (['previousSibling', 'nextSibling'] as $arah) {
            for ($s = $node->{$arah}; $s !== null; $s = $s->{$arah}) {
                if (! $s instanceof DOMElement) {
                    continue;
                }
                if (strtolower($s->nodeName) === 'h1') {
                    $hasil[] = $s;
                }
                break;
            }
        }

        return $hasil;
    }

    /** Hanya mempertahankan blok bertanda dan rantai pembungkusnya. */
    private function pangkas(DOMElement $induk): void
    {
        foreach (iterator_to_array($induk->childNodes) as $anak) {
            if ($anak instanceof DOMElement) {
                if ($anak->hasAttribute('data-ic-simpan')) {
                    continue;
                }
                if ($anak->hasAttribute('data-ic-leluhur')) {
                    $this->pangkas($anak);

                    continue;
                }
            }
            if ($anak instanceof DOMText && trim($anak->textContent) === '' && $induk->hasAttribute('data-ic-simpan')) {
                continue;
            }
            $induk->removeChild($anak);
        }
    }

    /** Render halaman asli (view dan controller yang sama dengan yang dilihat anggota) dengan anggota contoh. */
    private function render(string $halaman): string
    {
        $tamu = match ($halaman) {
            'tentang' => fn() => app(tentangController::class)->index(),
            'masuk' => fn() => view('pages.inner-circle.masuk'),
            'daftar' => fn() => view('pages.inner-circle.daftar'),
            'lupa' => fn() => view('pages.inner-circle.lupa-sandi'),
            'atur' => fn() => view('pages.inner-circle.atur-ulang', ['token' => 'contoh', 'email' => '']),
            default => null,
        };

        if ($tamu !== null) {
            return $tamu()->render();
        }

        $rute = [
            'beranda' => 'ic.beranda',
            'hadiah' => 'ic.hadiah',
            'checkin' => 'ic.checkin',
            'komunitas' => 'ic.komunitas',
            'galeri' => 'ic.komunitas',
            'profil' => 'ic.profil',
        ][$halaman] ?? null;

        if ($rute === null) {
            return '';
        }

        // Anggota contoh (id 0, tidak ada di database) dan route aslinya dibuat "aktif" (sorotan menu dock).
        /** @var \Illuminate\Auth\SessionGuard $guard */
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
                default => throw new \InvalidArgumentException("Halaman pratinjau tidak dikenal: $halaman"),
            };

            return $tampilan->render();
        } finally {
            $guard->forgetUser();
            request()->setRouteResolver($asli);
        }
    }

    /** Anggota contoh: tidak ada di database, email terverifikasi, bergabung setahun lalu, 10 stempel. */
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
}