<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\banner;
use App\Models\bio;
use App\Models\booking;
use App\Models\collab;
use App\Models\genre;
use App\Models\header;
use App\Models\media_coverage;
use App\Models\media_sosial;
use App\Models\merchandise;
use App\Models\news;
use App\Models\profile_hero;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * robots.txt — dulunya file statis di public/, sekarang dinamis biar bisa
     * nunjuk ke sitemap.xml pakai domain aktif (APP_URL), bukan hardcode.
     */
    public function robots(): Response
    {
        $content = "User-agent: *\n" .
            "Disallow: /dashboard\n" .
            "Disallow: /login\n" .
            "Allow: /\n\n" .
            'Sitemap: ' . url('/sitemap.xml') . "\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * sitemap.xml — cuma 2 halaman publik (home & profile), jadi digenerate
     * langsung tanpa perlu package tambahan. lastmod diambil dari data yang
     * paling baru diubah di masing-masing halaman, biar search engine tau
     * halaman mana yang paling sering update.
     */
    public function sitemap(): Response
    {
        $homeLastmod = collect([
            banner::max('updated_at'),
            header::max('updated_at'),
            albums::max('updated_at'),
            merchandise::max('updated_at'),
            news::max('updated_at'),
            profile_hero::max('updated_at'),
        ])->filter()->max();

        $profileLastmod = collect([
            profile_hero::max('updated_at'),
            bio::max('updated_at'),
            genre::max('updated_at'),
            collab::max('updated_at'),
            media_coverage::max('updated_at'),
            booking::max('updated_at'),
            media_sosial::max('updated_at'),
        ])->filter()->max();

        $urls = [
            [
                'loc' => url('/'),
                'lastmod' => $homeLastmod ? \Illuminate\Support\Carbon::parse($homeLastmod)->toAtomString() : now()->toAtomString(),
                'priority' => '1.0',
            ],
            [
                'loc' => url('/profile'),
                'lastmod' => $profileLastmod ? \Illuminate\Support\Carbon::parse($profileLastmod)->toAtomString() : now()->toAtomString(),
                'priority' => '0.8',
            ],
        ];

        $xml = view('sitemap', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}