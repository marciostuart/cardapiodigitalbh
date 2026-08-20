<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $base = strtolower(config('app.base_domain', 'cardapiodigitalbh.com.br'));
        if (str_ends_with($host, '.'.$base)) {
            $slug = substr($host, 0, -strlen('.'.$base));
            $request->attributes->set('venue', \App\Models\Venue::where('subdomain', $slug)->firstOrFail());
        }
        return $next($request);
    }
}
