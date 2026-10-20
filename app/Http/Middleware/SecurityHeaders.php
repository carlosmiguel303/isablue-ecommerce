<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $r = $next($request);
        $r->headers->set('X-Content-Type-Options', 'nosniff');
        $r->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $r->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        if ($request->is('admin*', 'carrito*', 'checkout*', 'pedido*', 'mi-cuenta*')) {
            $r->headers->set('Cache-Control', 'no-store, private');
        }

        return $r;
    }
}
