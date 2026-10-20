<?php

namespace App\Http\Middleware;

use Closure;

class AdminOnly
{
    public function handle($request, Closure $next)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $next($request);
    }
}
