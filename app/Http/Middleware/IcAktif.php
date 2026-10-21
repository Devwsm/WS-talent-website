<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang flag WS Inner Circle: kalau INNER_CIRCLE_AKTIF=false, fitur ini
 * seolah tidak ada (404), jadi UI dummy tidak ikut tayang di produksi.
 */
class IcAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('inner_circle.aktif'), 404);

        return $next($request);
    }
}