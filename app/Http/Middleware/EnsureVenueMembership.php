<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVenueMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $venue = $request->attributes->get('venue');

        abort_unless($venue && $request->user()?->tenant_id === $venue->tenant_id, 403);

        return $next($request);
    }
}
