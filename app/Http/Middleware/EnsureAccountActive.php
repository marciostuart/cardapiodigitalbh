<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role === 'platform_owner' || $request->session()->has('impersonator_id')) {
            return $next($request);
        }

        if ($user->active === false || ($user->tenant && $user->tenant->status !== 'active')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Esta conta está inativa. Entre em contato com o suporte.',
            ]);
        }

        return $next($request);
    }
}
