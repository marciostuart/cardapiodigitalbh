<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $request->session()->has('impersonator_id') && ! $user->hasAcceptedCurrentLegalDocuments()) {
            return redirect()->route('legal.acceptance.show');
        }

        return $next($request);
    }
}
