<?php

namespace App\Http\Middleware;

use App\Models\Venue;
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
        $base = strtolower(config('app.base_domain') ?: 'cardapiodigitalbh.com.br');
        $slug = str_ends_with($host, '.'.$base) ? substr($host, 0, -strlen('.'.$base)) : null;
        if (! $slug && app()->environment('testing')) {
            $slug = explode('.', $host)[0];
        }
        if ($slug) {
            $request->attributes->set('venue', Venue::where('subdomain', $slug)->firstOrFail());
        } elseif ($request->user()?->tenant_id) {
            $request->attributes->set('venue', $request->user()->tenant?->venues()->first());
        }

        return $next($request);
    }
}
